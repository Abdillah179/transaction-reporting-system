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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->dateTime('transaction_date');
            $table->unsignedSmallInteger('category_id');
            $table->unsignedTinyInteger('status_id');
            $table->string('payment_method', 20);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('import_id')->nullable()->index();
            $table->timestamp('created_at')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('status_id')->references('id')->on('statuses')->restrictOnDelete();
            $table->index('transaction_date');
            $table->index(['status_id', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
