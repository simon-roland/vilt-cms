<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('title');
            $table->string('slug');
            $table->json('layout');
            $table->json('meta')->nullable();
            $table->json('blocks')->nullable();
            $table->boolean('is_frontpage')->nullable()->default(null)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['slug', 'status'], 'unique_slug_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
