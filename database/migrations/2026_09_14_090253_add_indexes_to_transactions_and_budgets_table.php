<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type', 'date']);
            $table->index(['user_id', 'category', 'date']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->index(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['user_id', 'type', 'date']);
            $table->dropIndex(['user_id', 'category', 'date']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'month']);
        });
    }
};
