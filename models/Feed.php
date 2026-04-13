<?php namespace ImpulseTechnologies\FacebookFeed\Models;

use Model;

class Feed extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'impulsetechnologies_facebookfeed_feeds';

    protected $encrypted = ['access_token'];

    public $rules = [
        'name'           => 'required',
        'code'           => 'required|unique:impulsetechnologies_facebookfeed_feeds,code',
        'page_id'        => 'required',
        'access_token'   => 'required',
        'sync_frequency' => 'required|in:hourly,daily,weekly',
    ];

    protected $dates = ['last_synced_at'];

    public $hasMany = [
        'posts' => ['ImpulseTechnologies\FacebookFeed\Models\Post'],
    ];

    public function getSyncFrequencyOptions()
    {
        return [
            'hourly'  => 'Hourly',
            'daily'   => 'Daily',
            'weekly'  => 'Weekly',
        ];
    }
}
