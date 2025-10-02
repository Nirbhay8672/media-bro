<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('username')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamp('visited_at');
            $table->timestamps();

            // Ensure unique visits per template per IP
            $table->unique(['template_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_visits');
    }
};