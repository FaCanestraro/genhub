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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('area');
            $table->string('action');
            $table->string('status')->default('success');
            $table->text('description');
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('ai_model')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index(['account_id', 'area']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
