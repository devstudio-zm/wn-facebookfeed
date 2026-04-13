<?php namespace ImpulseTechnologies\FacebookFeed\Console;

use Illuminate\Console\Command;
use ImpulseTechnologies\FacebookFeed\Models\Feed;
use ImpulseTechnologies\FacebookFeed\Models\Post;
use ImpulseTechnologies\FacebookFeed\Classes\GraphApiService;
use Carbon\Carbon;
use Exception;

class SyncFacebookFeed extends Command
{
    protected $signature = 'facebook:sync
                            {feed? : The code of a specific feed to sync. Omit to sync all active feeds.}
                            {--full : Fetch all pages of posts from the API instead of only the first page.}';

    protected $description = 'Sync Facebook Page posts into the local database via the Graph API.';

    public function handle(): int
    {
        $feedCode = $this->argument('feed');
        $full     = $this->option('full');

        $query = Feed::where('is_active', true);

        if ($feedCode) {
            $query->where('code', $feedCode);
        }

        $feeds = $query->get();

        if ($feeds->isEmpty()) {
            $this->error($feedCode
                ? "No active feed found with code \"{$feedCode}\"."
                : 'No active feeds found.'
            );
            return 1;
        }

        foreach ($feeds as $feed) {
            $this->syncFeed($feed, $full);
        }

        return 0;
    }

    protected function syncFeed(Feed $feed, bool $full): void
    {
        $this->line("Syncing feed: <info>{$feed->name}</info> (code: {$feed->code})");

        try {
            $service = new GraphApiService($feed);
            $posts   = $full ? $service->fetchAllPosts() : ($service->fetchPosts()['data'] ?? []);
        } catch (Exception $e) {
            $this->error("  Failed to fetch posts: " . $e->getMessage());
            return;
        }

        $synced = 0;

        foreach ($posts as $apiPost) {
            $fbPostId = $apiPost['id'] ?? null;

            if (!$fbPostId) {
                continue;
            }

            $attachments = null;
            if (isset($apiPost['attachments']['data'])) {
                $attachments = $apiPost['attachments']['data'];
            }

            $fbCreatedAt = isset($apiPost['created_time'])
                ? Carbon::parse($apiPost['created_time'])
                : null;

            Post::updateOrCreate(
                ['fb_post_id' => $fbPostId],
                [
                    'feed_id'      => $feed->id,
                    'message'      => $apiPost['message'] ?? null,
                    'full_picture' => $apiPost['full_picture'] ?? null,
                    'attachments'  => $attachments,
                    'fb_created_at' => $fbCreatedAt,
                ]
            );

            $synced++;
        }

        $feed->last_synced_at = Carbon::now();
        $feed->save();

        $this->line("  Upserted <info>{$synced}</info> post(s).");
    }
}
