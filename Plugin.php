<?php namespace ImpulseTechnologies\FacebookFeed;

use System\Classes\PluginBase;
use Backend\Classes\BackendController;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name'        => 'impulsetechnologies.facebookfeed::lang.plugin.name',
            'description' => 'impulsetechnologies.facebookfeed::lang.plugin.description',
            'author'      => 'Impulse Technologies',
            'icon'        => 'fab icon-facebook',
        ];
    }

    public function registerComponents(): array
    {
        return [
                'ImpulseTechnologies\FacebookFeed\Components\FbFeed' => 'fbFeed',
            ];
        }

    public function registerPageSnippets()
    {
        return [
            \ImpulseTechnologies\FacebookFeed\Components\FbFeed::class => 'fbFeed'
        ];
    }

    public function registerSettings()
    {
    }

    public function registerNavigation(): array
    {
        return [
            'main-menu-item' => [
                'label'       => 'impulsetechnologies.facebookfeed::lang.plugin.name',
                'url'         => \Backend::url('impulsetechnologies/facebookfeed/feeds'),
                'icon'        => 'icon-facebook',
                'iconSvg'     => null,
                'permissions' => ['impulsetechnologies.facebookfeed.*'],
                'order'       => 500,
                'sideMenu'    => [
                    'feeds' => [
                        'label'       => 'impulsetechnologies.facebookfeed::lang.nav.feeds',
                        'icon'        => 'icon-rss',
                        'url'         => \Backend::url('impulsetechnologies/facebookfeed/feeds'),
                        'permissions' => ['impulsetechnologies.facebookfeed.manage_feeds'],
                    ],
                    'posts' => [
                        'label'       => 'impulsetechnologies.facebookfeed::lang.nav.posts',
                        'icon'        => 'icon-pencil',
                        'url'         => \Backend::url('impulsetechnologies/facebookfeed/posts'),
                        'permissions' => ['impulsetechnologies.facebookfeed.manage_posts'],
                    ],
                ],
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'impulsetechnologies.facebookfeed.manage_feeds' => [
                'tab'   => 'impulsetechnologies.facebookfeed::lang.plugin.name',
                'label' => 'impulsetechnologies.facebookfeed::lang.permissions.manage_feeds',
            ],
            'impulsetechnologies.facebookfeed.manage_posts' => [
                'tab'   => 'impulsetechnologies.facebookfeed::lang.plugin.name',
                'label' => 'impulsetechnologies.facebookfeed::lang.permissions.manage_posts',
            ],
        ];
    }

    public function registerSchedule($schedule): void
    {
        $schedule->command('facebook:sync')->daily();
    }

    public function boot(): void
    {
        $this->registerConsoleCommand('facebook:sync', 'ImpulseTechnologies\FacebookFeed\Console\SyncFacebookFeed');
    }
}
