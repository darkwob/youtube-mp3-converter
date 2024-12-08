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
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id(); // Local primary key
            $table->unsignedBigInteger('user_id'); // Foreign key to users
            $table->unsignedBigInteger('instagram_business_account_id'); // Foreign key to facebook_pages
            $table->unsignedBigInteger('instagram_id')->unique(); // Instagram benzersiz hesap ID'si
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->longText('profile_picture_url')->nullable();
            $table->string('website')->nullable();
            $table->integer('followers_count')->default(0);
            $table->integer('follows_count')->default(0);
            $table->integer('media_count')->default(0);
            $table->text('biography')->nullable();
            $table->timestamps();

            // Foreign key relationship to users
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Foreign key relationship to facebook_pages
            $table->foreign('instagram_business_account_id')
                ->references('instagram_business_account_id')
                ->on('facebook_pages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('instagram_accounts');
    }
};
