<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultLocale = config('cms.default_locale', config('app.locale', 'en'));

        Schema::table('navigations', function (Blueprint $table) use ($defaultLocale) {
            $table->string('locale', 10)->default($defaultLocale)->after('type');
        });

        Schema::table('navigations', function (Blueprint $table) {
            $table->dropUnique(['type']);
            $table->unique(['type', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('navigations', function (Blueprint $table) {
            $table->dropUnique(['type', 'locale']);
        });

        Schema::table('navigations', function (Blueprint $table) {
            $table->dropColumn('locale');
            $table->unique('type');
        });
    }
};
