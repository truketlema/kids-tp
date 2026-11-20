<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->foreignId('recorded_by')
                ->nullable()
                ->change();
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('ledgers')->whereNull('recorded_by')->exists()) {
            throw new RuntimeException('Cannot restore required ledger attribution while deleted-parent entries exist.');
        }

        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->foreignId('recorded_by')
                ->nullable(false)
                ->change();
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};
