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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
           $table->foreignId('family_id')
        ->nullable()
        ->constrained('families')
        ->cascadeOnDelete();

    $table->string('name', 100);
    $table->text('description')->nullable();

    $table->timestamps();

    $table->unique(['family_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
