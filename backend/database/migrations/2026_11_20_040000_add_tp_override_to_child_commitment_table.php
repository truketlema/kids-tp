<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_commitment', function (Blueprint $table) {
            $table->unsignedInteger('tp_override')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('child_commitment', function (Blueprint $table) {
            $table->dropColumn('tp_override');
        });
    }
};
