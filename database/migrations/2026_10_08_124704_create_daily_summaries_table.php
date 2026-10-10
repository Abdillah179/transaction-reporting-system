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
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedSmallInteger('category_id');
            $table->unsignedTinyInteger('status_id');
            $table->unsignedInteger('total_count');
            $table->decimal('total_amount', 20, 2);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['date', 'category_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
