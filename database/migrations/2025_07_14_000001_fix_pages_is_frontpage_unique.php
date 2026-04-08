<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('pages_is_frontpage_unique');
            $table->unique(['is_frontpage', 'status'], 'unique_is_frontpage_status');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('unique_is_frontpage_status');
            $table->unique(['is_frontpage'], 'pages_is_frontpage_unique');
        });
    }
};
