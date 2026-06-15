<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Students table mein ye columns migration mein the hi nahi
    // lekin Model fillable mein the — isliye INSERT fail ho raha tha
    // Production safe: sab nullable() hain — existing rows affected nahi honge

    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            // Admission source
            if (!Schema::hasColumn('students', 'admission_source')) {
                $table->enum('admission_source', ['direct', 'center', 'channel_partner'])
                      ->default('direct')
                      ->nullable()
                      ->after('admission_type');
            }

            if (!Schema::hasColumn('students', 'admission_source_id')) {
                $table->unsignedBigInteger('admission_source_id')
                      ->nullable()
                      ->after('admission_source')
                      ->comment('Center ID or ChannelPartner ID');
            }

            // Permanent address — perm_village missing tha
            if (!Schema::hasColumn('students', 'perm_village')) {
                $table->string('perm_village')->nullable()->after('guardian_relation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['admission_source', 'admission_source_id', 'perm_village']);
        });
    }
};