<?php namespace ImpulseTechnologies\FacebookFeed\Classes;

use ImpulseTechnologies\FacebookFeed\Models\Feed;
use Config;
use Http;
use Exception;
use Log;

class GraphApiService
{
    protected Feed $feed;
    protected string $resolvedToken;

    public function __construct(Feed $feed)
    {
        $this->feed           = $feed;
        $this->resolvedToken  = $feed->access_token;
    }

    /**
     * Exchange a user access token for the Page access token by querying /me/accounts.
     * Updates the feed record with the page token so future syncs use it directly.
     *
     * @throws Exception
     */
    protected function resolvePageToken(): void
    {
        $version = Config::get('impulsetechnologies.facebookfeed::graph_api_version', 'v25.0');
        $baseUrl  = rtrim(Config::get('impulsetechnologies.facebookfeed::graph_base_url', 'https://graph.facebook.com'), '/');
        $timeout  = (int) Config::get('impulsetechnologies.facebookfeed::request_timeout', 30);

        $url   = "{$baseUrl}/{$version}/me/accounts";
        $query = ['access_token' => $this->resolvedToken];
        $feedTimeout = $timeout;

        $result = Http::get($url, function ($http) use ($query, $feedTimeout) {
            $http->data($query)->timeout($feedTimeout);
        });

        if ($result->code !== 200) {
            throw new Exception("Could not fetch page accounts (HTTP {$result->code}): {$result->body}");
        }

        $body = json_decode($result->body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($body['data'])) {
            throw new Exception('Failed to parse /me/accounts response.');
        }

        $pageId = (string) $this->feed->page_id;

        foreach ($body['data'] as $account) {
            if ((string) ($account['id'] ?? '') === $pageId) {
                $pageToken = $account['access_token'] ?? null;

                if (!$pageToken) {
                    throw new Exception("Page ID {$pageId} found in /me/accounts but has no access_token field. Ensure pages_show_list permission is granted.");
                }

                Log::info('FacebookFeed: Resolved page access token from /me/accounts', [
                    'feed_code' => $this->feed->code,
                    'page_id'   => $pageId,
                ]);

                $this->resolvedToken       = $pageToken;
                $this->feed->access_token  = $pageToken;
                $this->feed->save();

                return;
            }
        }

        throw new Exception("Page ID {$pageId} not found in /me/accounts. Ensure the token owner administers this page and pages_show_list permission is granted.");
    }

    /**
     * Fetch one page of posts from the Facebook Graph API.
     *
     * @param  string|null  $after  Pagination cursor for the next page.
     * @return array{data: array, paging: array}
     * @throws Exception
     */
    public function fetchPosts(?string $after = null): array
    {
        $version = Config::get('impulsetechnologies.facebookfeed::graph_api_version', 'v25.0');
        $baseUrl  = rtrim(Config::get('impulsetechnologies.facebookfeed::graph_base_url', 'https://graph.facebook.com'), '/');
        $fields   = Config::get('impulsetechnologies.facebookfeed::posts_fields', 'message,attachments,full_picture,created_time');
        $timeout  = (int) Config::get('impulsetechnologies.facebookfeed::request_timeout', 30);

        $url = "{$baseUrl}/{$version}/{$this->feed->page_id}/posts";

        $query = [
            'fields'       => $fields,
            'access_token' => $this->resolvedToken,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        $feedQuery   = $query;
        $feedTimeout = $timeout;

        $result = Http::get($url, function ($http) use ($feedQuery, $feedTimeout) {
            $http->data($feedQuery)->timeout($feedTimeout);
        });

        Log::debug('FacebookFeed: Graph API response', [
            'feed_code'    => $this->feed->code,
            'url'          => $url,
            'token_prefix' => substr($this->resolvedToken, 0, 20) . '…',
            'http_code'    => $result->code,
        ]);

        // If we got a 400 with OAuthException subcode 2069032 (user token not supported
        // for new Pages experience), automatically exchange for the Page access token.
        if ($result->code === 400) {
            $errorBody = json_decode($result->body, true);
            $subcode   = $errorBody['error']['error_subcode'] ?? null;

            if ($subcode === 2069032) {
                Log::info('FacebookFeed: User token detected, exchanging for Page token via /me/accounts', [
                    'feed_code' => $this->feed->code,
                    'page_id'   => $this->feed->page_id,
                ]);

                $this->resolvePageToken();

                // Retry with the resolved page token.
                return $this->fetchPosts($after);
            }

            Log::error('FacebookFeed: Graph API non-200 response', [
                'feed_code' => $this->feed->code,
                'page_id'   => $this->feed->page_id,
                'http_code' => $result->code,
                'response'  => $result->body,
            ]);

            throw new Exception("Facebook Graph API returned HTTP {$result->code}.");
        }

        if ($result->code !== 200) {
            Log::error('FacebookFeed: Graph API non-200 response', [
                'feed_code' => $this->feed->code,
                'page_id'   => $this->feed->page_id,
                'http_code' => $result->code,
                'response'  => $result->body,
            ]);

            throw new Exception("Facebook Graph API returned HTTP {$result->code}.");
        }

        $body = json_decode($result->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse Facebook Graph API response.');
        }

        if (isset($body['error'])) {
            throw new Exception(
                'Facebook Graph API error: ' . ($body['error']['message'] ?? 'Unknown error')
            );
        }

        return $body;
    }

    /**
     * Fetch all pages of posts from the Graph API, following pagination cursors.
     *
     * @return array  Flat array of post data arrays.
     * @throws Exception
     */
    public function fetchAllPosts(): array
    {
        $all   = [];
        $after = null;

        do {
            $response = $this->fetchPosts($after);
            $all      = array_merge($all, $response['data'] ?? []);
            $after    = $response['paging']['cursors']['after'] ?? null;
            $hasNext  = isset($response['paging']['next']);
        } while ($hasNext && $after);

        return $all;
    }
}

