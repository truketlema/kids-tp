<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 100)->nullable();
        });

        Schema::table('commitments', function (Blueprint $table) {
            $table->decimal('weight_multiplier', 5, 2)->default(1)->change();
        });

        Schema::create('child_commitment', function (Blueprint $table) {
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commitment_id')->constrained()->cascadeOnDelete();
            $table->primary(['child_id', 'commitment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_commitment');

        Schema::table('commitments', function (Blueprint $table) {
            $table->unsignedInteger('weight_multiplier')->default(1)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
