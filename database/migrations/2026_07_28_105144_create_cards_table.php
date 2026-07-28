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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('variant');
            $table->unsignedSmallInteger('year');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['variant', 'year', 'name']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();

            $table->unique(['card_id', 'name']);
        });

        Schema::create('hands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order');
            $table->unsignedSmallInteger('points');
            $table->boolean('concealed')->default(false);
            $table->json('structure');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hands');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('cards');
    }
};
