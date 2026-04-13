<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Graph API Version
    |--------------------------------------------------------------------------
    | The Facebook Graph API version to use for all requests.
    */
    'graph_api_version' => 'v25.0',

    /*
    |--------------------------------------------------------------------------
    | Graph API Base URL
    |--------------------------------------------------------------------------
    */
    'graph_base_url' => 'https://graph.facebook.com',

    /*
    |--------------------------------------------------------------------------
    | Post Fields
    |--------------------------------------------------------------------------
    | Comma-separated list of fields to request from the Graph API.
    */
    'posts_fields' => 'message,attachments,full_picture,created_time',

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    | Guzzle HTTP request timeout in seconds.
    */
    'request_timeout' => 30,

];
