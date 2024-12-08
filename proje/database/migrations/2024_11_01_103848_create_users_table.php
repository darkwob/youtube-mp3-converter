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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_id')->unique(); // Kullanıcının Facebook ID'si
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('password');
            $table->text('access_token')->nullable(); // Facebook Access Token
            $table->timestamp('token_expires_at')->nullable(); // Token geçerlilik süresi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
