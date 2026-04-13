<?php return [
    'plugin' => [
        'name'        => 'Facebook Feed',
        'description' => 'Syncs Facebook Page posts via the Graph API and renders them on the front end.',
    ],
    'nav' => [
        'feeds' => 'Feeds',
        'posts' => 'Posts',
    ],
    'permissions' => [
        'manage_feeds' => 'Manage Facebook Feeds',
        'manage_posts' => 'Manage Facebook Posts',
    ],
    'component' => [
        'name'                       => 'Facebook Feed',
        'description'                => 'Displays posts from a synced Facebook Page feed.',
        'feed_code'                  => 'Feed Code',
        'feed_code_description'      => 'The unique code of the feed to display.',
        'posts_per_page'             => 'Posts Per Page',
        'posts_per_page_description' => 'Number of posts to show per page.',
        'sort_by'                    => 'Sort By',
        'sort_by_description'        => 'Order in which posts are displayed.',
    ],
    'fields' => [
        'feed' => [
            'name'           => 'Feed Name',
            'code'           => 'Feed Code',
            'page_id'        => 'Facebook Page ID',
            'access_token'   => 'Page Access Token',
            'sync_frequency' => 'Sync Frequency',
            'is_active'      => 'Active',
            'last_synced_at' => 'Last Synced At',
        ],
        'post' => [
            'fb_post_id'    => 'Facebook Post ID',
            'message'       => 'Message',
            'full_picture'  => 'Image URL',
            'fb_created_at' => 'Posted At (Facebook)',
            'is_published'  => 'Published',
            'sort_order'    => 'Sort Order',
        ],
    ],
];