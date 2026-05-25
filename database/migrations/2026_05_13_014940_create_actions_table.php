<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['post', 'reel', 'carousel', 'story', 'tiktok_video'])->default('post');
            $table->enum('platform', ['instagram', 'tiktok', 'facebook', 'youtube'])->default('instagram');
            $table->enum('status', ['draft', 'generating', 'ready', 'scheduled', 'published', 'failed'])->default('draft');
            $table->string('title');
            $table->text('brief')->nullable();
            $table->text('caption')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('product_ids')->nullable();
            $table->string('resolution')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
