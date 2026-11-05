<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('display_name', 40);
            $table->date('birth_date');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('age_verified_at')->nullable();
            $table->string('age_verify_method', 30)->nullable();
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('active');
            $table->json('rating')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
