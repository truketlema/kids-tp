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
      Schema::create('families', function (Blueprint $table) {
    $table->id();
    $table->string('family_name');
    $table->decimal('conversion_rate', 10, 2)->default(10.00);
    $table->unsignedInteger('daily_deduction_cap')->default(3);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
