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
        Schema::create('instagram_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_id')->unique();
            $table->string('token', 19)->unique(); // 16 karakter + 3 adet '-'
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_token');
    }
};
