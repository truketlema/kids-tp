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
        Schema::create('ledgers', function (Blueprint $table) {
    $table->id();

    $table->foreignId('child_id')
        ->constrained('children')
        ->restrictOnDelete();

    $table->foreignId('commitment_id')
        ->nullable()
        ->constrained('commitments')
        ->restrictOnDelete();

    $table->foreignId('recorded_by')
        ->constrained('users')
        ->restrictOnDelete();

    $table->string('type', 20);
    $table->integer('tp_amount');
    $table->decimal('conversion_rate_snapshot', 10, 2);
    $table->text('remarks')->nullable();

    $table->timestamps();

    $table->index(['child_id', 'created_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
