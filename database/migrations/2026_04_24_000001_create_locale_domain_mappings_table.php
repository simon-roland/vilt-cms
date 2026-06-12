<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locale_domain_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->index();
            $table->string('domain')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locale_domain_mappings');
    }
};
