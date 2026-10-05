<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('specialist_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status')->default('confirmed');
            $table->unsignedInteger('price');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['specialist_id', 'starts_at']);
            $table->index(['client_id', 'starts_at']);
        });

        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_range_check CHECK (ends_at > starts_at)');

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_no_overlap
            EXCLUDE USING gist (
                specialist_id WITH =,
                tstzrange(starts_at, ends_at, '[)') WITH &&
            )
            WHERE (status <> 'cancelled')
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
