<?php namespace ImpulseTechnologies\FacebookFeed\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Exception;
use ImpulseTechnologies\FacebookFeed\Models\Feed;
use ImpulseTechnologies\FacebookFeed\Models\Post;
use ImpulseTechnologies\FacebookFeed\Classes\GraphApiService;
use Carbon\Carbon;

class Feeds extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('ImpulseTechnologies.FacebookFeed', 'main-menu-item', 'feeds');
    }

    public function onSyncFeed()
    {
        $id   = $this->params[0] ?? null;
        $feed = Feed::findOrFail($id);

        try {
            $service = new GraphApiService($feed);
            $posts   = $service->fetchAllPosts();
            $synced  = 0;

            foreach ($posts as $apiPost) {
                $fbPostId = $apiPost['id'] ?? null;
                if (!$fbPostId) continue;

                $attachments = isset($apiPost['attachments']['data'])
                    ? $apiPost['attachments']['data']
                    : null;

                $fbCreatedAt = isset($apiPost['created_time'])
                    ? Carbon::parse($apiPost['created_time'])
                    : null;

                Post::updateOrCreate(
                    ['fb_post_id' => $fbPostId],
                    [
                        'feed_id'       => $feed->id,
                        'message'       => $apiPost['message'] ?? null,
                        'full_picture'  => $apiPost['full_picture'] ?? null,
                        'attachments'   => $attachments,
                        'fb_created_at' => $fbCreatedAt,
                    ]
                );

                $synced++;
            }

            $feed->last_synced_at = Carbon::now();
            $feed->save();

            Flash::success("Sync complete — {$synced} post(s) imported/updated.");
        } catch (Exception $e) {
            Flash::error('Sync failed: ' . $e->getMessage());
        }

        return \Backend::redirect('impulsetechnologies/facebookfeed/feeds/update/' . $feed->id);
    }
}
