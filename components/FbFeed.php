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
            'snippetAjax' => true
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
        $this->addCss('/plugins/impulsetechnologies/facebookfeed/assets/css/fbfeed.css');

        $feedCode = $this->property('feedCode');
        $perPage  = (int) $this->property('postsPerPage', 10);
        $sortBy   = $this->property('sortBy', 'fb_created_at');

        $feed = Feed::where('code', $feedCode)->where('is_active', true)->first();

        if (!$feed) {
            $this->page['posts']   = [];
            $this->page['feed']    = null;
            $this->page['hasMore'] = false;
            return;
        }

        $order = $sortBy === 'sort_order' ? 'asc' : 'desc';
        $paged = Post::where('feed_id', $feed->id)
                     ->where('is_published', true)
                     ->orderBy($sortBy, $order)
                     ->paginate($perPage);

        $this->page['feed']    = $feed;
        $this->page['posts']   = $paged->items();
        $this->page['hasMore'] = $paged->hasMorePages();
    }

    public function onLoadMore(): array
    {
        $feedCode = $this->property('feedCode');
        $perPage  = (int) $this->property('postsPerPage', 10);
        $sortBy   = $this->property('sortBy', 'fb_created_at');
        $page     = max(2, (int) post('page', 2));

        $feed = Feed::where('code', $feedCode)->where('is_active', true)->first();

        if (!$feed) {
            return ['posts_html' => '', 'has_more' => false, 'next_page' => $page];
        }

        $order = $sortBy === 'sort_order' ? 'asc' : 'desc';
        $paged = Post::where('feed_id', $feed->id)
                     ->where('is_published', true)
                     ->orderBy($sortBy, $order)
                     ->paginate($perPage, ['*'], 'page', $page);

        return [
            'posts_html' => $this->renderPartial('::_posts_batch', ['posts' => $paged->items()]),
            'has_more'   => $paged->hasMorePages(),
            'next_page'  => $page + 1,
        ];
    }


}
