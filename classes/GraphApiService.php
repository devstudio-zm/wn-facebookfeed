<?php namespace ImpulseTechnologies\FacebookFeed\Classes;

use ImpulseTechnologies\FacebookFeed\Models\Feed;
use Config;
use Http;
use Exception;
use Log;

class GraphApiService
{
    protected Feed   $feed;
    protected string $resolvedToken;
    protected bool   $tokenResolved = false;

    // Graph API config, loaded once
    protected string $version;
    protected string $baseUrl;
    protected string $fields;
    protected int    $timeout;

    public function __construct(Feed $feed)
    {
        $this->feed          = $feed;
        // Strip all whitespace and non-printable/non-ASCII characters that can
        // silently corrupt the token when copy-pasted from browsers or editors.
        $this->resolvedToken = preg_replace('/[^\x20-\x7E]/', '', (string) $feed->access_token);
        $this->resolvedToken = trim($this->resolvedToken);

        $this->version = Config::get('impulsetechnologies.facebookfeed::graph_api_version', 'v25.0');
        $this->baseUrl  = rtrim(Config::get('impulsetechnologies.facebookfeed::graph_base_url', 'https://graph.facebook.com'), '/');
        $this->fields   = Config::get('impulsetechnologies.facebookfeed::posts_fields', 'message,attachments,full_picture,created_time');
        $this->timeout  = (int) Config::get('impulsetechnologies.facebookfeed::request_timeout', 30);
    }

    /**
     * Perform a GET request and return the decoded body array.
     *
     * @throws Exception
     */
    protected function get(string $url, array $query): array
    {
        $timeout = $this->timeout;

        $result = Http::get($url, function ($http) use ($query, $timeout) {
            $http->data($query)->timeout($timeout);
        });

        Log::debug('FacebookFeed: Graph API request', [
            'feed_code'    => $this->feed->code,
            'url'          => $url,
            'token_length' => strlen($this->resolvedToken),
            'token_prefix' => substr($this->resolvedToken, 0, 20) . '…',
            'http_code'    => $result->code,
        ]);

        if ($result->code !== 200) {
            $errorBody = json_decode($result->body, true) ?? [];
            $message   = $errorBody['error']['message'] ?? $result->body;
            $subcode   = $errorBody['error']['error_subcode'] ?? null;

            Log::error('FacebookFeed: Graph API error', [
                'feed_code' => $this->feed->code,
                'page_id'   => $this->feed->page_id,
                'http_code' => $result->code,
                'subcode'   => $subcode,
                'message'   => $message,
            ]);

            throw new Exception("Facebook Graph API error (HTTP {$result->code}): {$message}", (int) $subcode);
        }

        $body = json_decode($result->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse Facebook Graph API response.');
        }

        if (isset($body['error'])) {
            throw new Exception('Facebook Graph API error: ' . ($body['error']['message'] ?? 'Unknown error'));
        }

        return $body;
    }

    /**
     * Ensure we have a page access token.
     *
     * Strategy:
     * 1. Try /me/accounts (works when the stored token is a user access token).
     * 2. If that fails (e.g. the stored token is already a page token, or the
     *    user token has been rotated), fall back to using the stored token directly
     *    as a page token. The subsequent fetchPosts() call will surface any real
     *    auth error with a clear message.
     *
     * @throws Exception
     */
    protected function resolvePageToken(): void
    {
        if ($this->tokenResolved) {
            return;
        }

        $this->tokenResolved = true; // set before the request to prevent retry loops

        $url = "{$this->baseUrl}/{$this->version}/me/accounts";

        try {
            $body = $this->get($url, ['access_token' => $this->resolvedToken]);
        } catch (Exception $e) {
            // /me/accounts requires a user token. If the stored token is already
            // a page token (saved from a previous sync), this call will fail.
            // Treat the stored token as a page token and proceed.
            Log::info('FacebookFeed: /me/accounts failed; treating stored token as page token.', [
                'feed_code' => $this->feed->code,
                'reason'    => $e->getMessage(),
            ]);
            return;
        }

        if (!isset($body['data']) || !is_array($body['data'])) {
            // Unexpected response structure — proceed with the stored token as-is.
            Log::warning('FacebookFeed: /me/accounts returned unexpected structure, using stored token.', [
                'feed_code' => $this->feed->code,
            ]);
            return;
        }

        $pageId = (string) $this->feed->page_id;

        foreach ($body['data'] as $account) {
            if ((string) ($account['id'] ?? '') !== $pageId) {
                continue;
            }

            $pageToken = $account['access_token'] ?? null;

            if (!$pageToken) {
                throw new Exception("Page ID {$pageId} found in /me/accounts but has no access_token. Ensure pages_show_list permission is granted.");
            }

            Log::info('FacebookFeed: Resolved page access token via /me/accounts.', [
                'feed_code' => $this->feed->code,
                'page_id'   => $pageId,
            ]);

            // Only write to DB if the token has actually changed
            if ($pageToken !== $this->resolvedToken) {
                $this->resolvedToken      = $pageToken;
                $this->feed->access_token = $pageToken;
                $this->feed->save();
            } else {
                $this->resolvedToken = $pageToken;
            }

            return;
        }

        // Page not found in /me/accounts — the stored token may already be a
        // long-lived page token. Proceed and let fetchPosts() surface any real error.
        Log::warning('FacebookFeed: Page ID not found in /me/accounts; using stored token as page token.', [
            'feed_code' => $this->feed->code,
            'page_id'   => $pageId,
        ]);
    }

    /**
     * Fetch one page of posts from the Graph API.
     *
     * @throws Exception
     */
    public function fetchPosts(?string $after = null): array
    {
        // Always ensure we're using a page token, not a user token
        $this->resolvePageToken();

        $url   = "{$this->baseUrl}/{$this->version}/{$this->feed->page_id}/posts";
        $query = [
            'fields'       => $this->fields,
            'access_token' => $this->resolvedToken,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        return $this->get($url, $query);
    }

    /**
     * Fetch all pages of posts, following pagination cursors.
     *
     * @throws Exception
     */
    public function fetchAllPosts(): array
    {
        $all   = [];
        $after = null;

        do {
            $response = $this->fetchPosts($after);
            array_push($all, ...($response['data'] ?? []));
            $after   = $response['paging']['cursors']['after'] ?? null;
            $hasNext = isset($response['paging']['next']);
        } while ($hasNext && $after);

        return $all;
    }
}