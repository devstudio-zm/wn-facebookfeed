<?php namespace ImpulseTechnologies\FacebookFeed\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFeedsTable extends Migration
{
    public function up()
    {
        Schema::create('impulsetechnologies_facebookfeed_feeds', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('page_id');
            $table->text('access_token');
            $table->string('sync_frequency')->default('daily');
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('impulsetechnologies_facebookfeed_feeds');
    }
}
