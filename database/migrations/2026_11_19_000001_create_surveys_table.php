<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->jsonb('target_programs');
            $table->jsonb('target_year_levels');
            $table->jsonb('schema');
            $table->unsignedInteger('target_respondents')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('collect_respondent_info')->default(false);
            $table->string('status')->default('pending_review');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
