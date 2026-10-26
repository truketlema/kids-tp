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
        Schema::create('commitments', function (Blueprint $table) {
            $table->id();
             $table->foreignId('family_id')
        ->nullable()
        ->constrained('families')
        ->cascadeOnDelete();

    $table->foreignId('category_id')
        ->constrained('categories')
        ->restrictOnDelete();

    $table->string('title');
    $table->text('description')->nullable();

    $table->unsignedInteger('weight_multiplier')->default(1);
    $table->unsignedInteger('default_tp')->default(1);
    $table->unsignedInteger('suggested_deduction')->default(1);

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commitments');
    }
};
