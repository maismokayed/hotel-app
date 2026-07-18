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

            $table->string('name_ar', 100);
            $table->string('name_en', 100);

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('address_ar');
            $table->text('address_en');

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
