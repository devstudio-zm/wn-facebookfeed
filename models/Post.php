<?php namespace ImpulseTechnologies\FacebookFeed\Models;

use Model;

class Post extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'impulsetechnologies_facebookfeed_posts';

    protected $fillable = [
        'feed_id', 'fb_post_id', 'message', 'full_picture',
        'attachments', 'fb_created_at', 'is_published', 'sort_order',
    ];

    protected $jsonable = ['attachments'];

    protected $dates = ['fb_created_at'];

    public $rules = [
        'feed_id'    => 'required',
        'fb_post_id' => 'required',
    ];

    public $belongsTo = [
        'feed' => ['ImpulseTechnologies\FacebookFeed\Models\Feed'],
    ];
}
