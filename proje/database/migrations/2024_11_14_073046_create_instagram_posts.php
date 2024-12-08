<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instagram_account_id'); // Instagram benzersiz hesap ID'si (foreign key)
            $table->string('post_id')->unique();
            $table->longText('caption')->nullable();
            $table->string('feed_link')->nullable();
            $table->longText('thumbnail_url')->nullable();
            $table->longText('media_url')->nullable();
            $table->longText('media_type')->nullable();
            $table->integer('comments_count')->nullable();
            $table->integer('like_count')->nullable();
            $table->timestamps();

            // Foreign key relationship to instagram_accounts
            $table->foreign('instagram_account_id')
                ->references('instagram_id')
                ->on('instagram_accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('instagram_posts');
    }
};
