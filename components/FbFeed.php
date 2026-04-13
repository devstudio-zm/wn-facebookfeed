<?php namespace ImpulseTechnologies\FacebookFeed\Components;

use Cms\Classes\ComponentBase;
use ImpulseTechnologies\FacebookFeed\Models\Feed;
use ImpulseTechnologies\FacebookFeed\Models\Post;

class FbFeed extends ComponentBase
{
    public function componentDetails(): array
    {
        return [
            'name'        => 'impulsetechnologies.facebookfeed::lang.component.name',
            'description' => 'impulsetechnologies.facebookfeed::lang.component.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'feedCode' => [
                'title'       => 'impulsetechnologies.facebookfeed::lang.component.feed_code',
                'description' => 'impulsetechnologies.facebookfeed::lang.component.feed_code_description',
                'type'        => 'string',
                'required'    => true,
            ],
            'postsPerPage' => [
                'title'             => 'impulsetechnologies.facebookfeed::lang.component.posts_per_page',
                'description'       => 'impulsetechnologies.facebookfeed::lang.component.posts_per_page_description',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Must be a whole number.',
                'default'           => '10',
            ],
            'sortBy' => [
                'title'       => 'impulsetechnologies.facebookfeed::lang.component.sort_by',
                'description' => 'impulsetechnologies.facebookfeed::lang.component.sort_by_description',
                'type'        => 'dropdown',
                'default'     => 'fb_created_at',
                'options'     => [
                    'fb_created_at' => 'Date posted (newest first)',
                    'sort_order'    => 'Manual order',
                ],
            ],
        ];
    }

    public function onRun(): void
    {
        $feedCode = $this->property('feedCode');
        $perPage  = (int) $this->property('postsPerPage', 10);
        $sortBy   = $this->property('sortBy', 'fb_created_at');

        $feed = Feed::where('code', $feedCode)->where('is_active', true)->first();

        if (!$feed) {
            $this->page['posts'] = collect();
            $this->page['feed']  = null;
            return;
        }

        $order     = $sortBy === 'sort_order' ? 'asc' : 'desc';
        $posts     = Post::where('feed_id', $feed->id)
                         ->where('is_published', true)
                         ->orderBy($sortBy, $order)
                         ->paginate($perPage);

        $this->page['feed']  = $feed;
        $this->page['posts'] = $posts;
    }
}
