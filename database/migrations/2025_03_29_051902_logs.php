<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tv_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_format', 1);
            $table->string('program_class', 3);
            $table->string('affiliation_type', 2);
            $table->string('call_sign', 6);
            $table->string('log_date', 6); // Keep as YYMMDD for now
            $table->string('start_time', 6); // HHMMSS
            $table->string('end_time', 6); // HHMMSS
            $table->string('duration', 6); // HHMMSS, we'll convert to seconds
            $table->string('program_title', 50);
            $table->string('program_sub_title', 50)->nullable();
            $table->string('producer1', 6)->nullable();
            $table->string('producer2', 6)->nullable();
            $table->string('production_number', 6)->nullable();
            $table->string('special_attention', 1)->nullable();
            $table->string('origin', 1)->nullable();
            $table->string('timecredits', 1)->nullable();
            $table->string('exhibition', 1)->nullable();
            $table->string('production_source', 1)->nullable();
            $table->string('target_audience', 1)->nullable();
            $table->string('categories', 3)->nullable();
            $table->string('accessible_programming', 2)->nullable();
            $table->string('dubbing_credit', 1)->nullable();
            $table->string('ethnic_program', 1)->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('call_sign');
            $table->index('program_class');
            $table->index('log_date');
            $table->index('program_title');
        });

        // SQLite optimizations
        DB::statement('PRAGMA journal_mode=WAL;');
        DB::statement('PRAGMA cache_size=-20000;');
        DB::statement('PRAGMA synchronous=NORMAL;');
    }

    public function down(): void {
        Schema::dropIfExists('tv_logs');
    }
};