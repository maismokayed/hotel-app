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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
             $table->string('name', 100);
             $table->text('description')->nullable();
             $table->string('city');
             $table->text('address');
        $table->string('phone', 20)->nullable();
        $table->string('email')->nullable();
        $table->unsignedTinyInteger('star_rating')->nullable();
        $table->boolean('is_active')->default(true);

        $table->foreignId('user_id')
    ->nullable()
    ->constrained()
    ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
