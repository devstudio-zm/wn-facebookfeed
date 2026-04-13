<?php namespace ImpulseTechnologies\FacebookFeed\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('impulsetechnologies_facebookfeed_posts', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('feed_id')->unsigned()->index();
            $table->string('fb_post_id')->unique();
            $table->text('message')->nullable();
            $table->string('full_picture', 2048)->nullable();
            $table->text('attachments')->nullable();
            $table->timestamp('fb_created_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('feed_id')
                  ->references('id')
                  ->on('impulsetechnologies_facebookfeed_feeds')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('impulsetechnologies_facebookfeed_posts');
    }
}
