<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Login-ID counters — mirrors admission_counters/fee_invoice_counters
        Schema::create('staff_id_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('last_seq')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'year']);
        });

        Schema::create('partner_id_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('last_seq')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'year']);
        });

        Schema::create('center_id_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('last_seq')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'year']);
        });

        Schema::create('library_staff_id_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('last_seq')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'year']);
        });

        // New login-UID columns — nullable+unique for now (backfilled in a later phase,
        // NULLs never collide under a unique index so this is safe pre-backfill)
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('staff_uid')->nullable()->unique()->after('id');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->string('partner_uid')->nullable()->unique()->after('id');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->string('center_uid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('center_uid');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropColumn('partner_uid');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('staff_uid');
        });

        Schema::dropIfExists('library_staff_id_counters');
        Schema::dropIfExists('center_id_counters');
        Schema::dropIfExists('partner_id_counters');
        Schema::dropIfExists('staff_id_counters');
    }
};
