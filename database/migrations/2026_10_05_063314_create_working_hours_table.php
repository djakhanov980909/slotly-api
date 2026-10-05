<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['specialist_id', 'weekday']);
        });

        DB::statement('ALTER TABLE working_hours ADD CONSTRAINT working_hours_weekday_check CHECK (weekday BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE working_hours ADD CONSTRAINT working_hours_time_check CHECK (end_time > start_time)');
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
