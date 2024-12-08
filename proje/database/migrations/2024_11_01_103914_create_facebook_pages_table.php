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
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User tablosuyla ilişki
            $table->unsignedBigInteger('instagram_business_account_id')->unique(); // Instagram Business Account ID'si
            $table->string('name')->nullable(); // Sayfa adı
            $table->longText('profile_picture_url')->nullable(); // Profil fotoğraf URL'si
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('facebook_pages');
    }
};

