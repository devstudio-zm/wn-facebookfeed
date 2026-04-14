<?php namespace ImpulseTechnologies\FacebookFeed\Classes;

use ImpulseTechnologies\FacebookFeed\Models\Feed;
use Config;
use Http;
use Exception;
use Log;

class GraphApiService
{
    protected Feed   $feed;
    protected string $token;
    protected string $version;
    protected string $baseUrl;
    protected string $fields;
    protected int    $timeout;

    public function __construct(Feed $feed)
    {
        $this->feed    = $feed;
        $this->token   = trim((string) $feed->access_token);

        $this->version = Config::get('impulsetechnologies.facebookfeed::graph_api_version', 'v25.0');
        $this->baseUrl = rtrim(Config::get('impulsetechnologies.facebookfeed::graph_base_url', 'https://graph.facebook.com'), '/');
        $this->fields  = Config::get('impulsetechnologies.facebookfeed::posts_fields', 'message,attachments,full_picture,created_time');
        $this->timeout = (int) Config::get('impulsetechnologies.facebookfeed::request_timeout', 30);
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
            'feed_code' => $this->feed->code,
            'url'       => $url,
            'http_code' => $result->code,
        ]);

        if ($result->code !== 200) {
            $errorBody = json_decode($result->body, true) ?? [];
            $message   = $errorBody['error']['message'] ?? $result->body;

            Log::error('FacebookFeed: Graph API error', [
                'feed_code' => $this->feed->code,
                'page_id'   => $this->feed->page_id,
                'http_code' => $result->code,
                'message'   => $message,
            ]);

            throw new Exception("Facebook Graph API error (HTTP {$result->code}): {$message}");
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
     * Fetch one page of posts from the Graph API.
     *
     * @throws Exception
     */
    public function fetchPosts(?string $after = null): array
    {
        $url   = "{$this->baseUrl}/{$this->version}/{$this->feed->page_id}/posts";
        $query = [
            'fields'       => $this->fields,
            'access_token' => $this->token,
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