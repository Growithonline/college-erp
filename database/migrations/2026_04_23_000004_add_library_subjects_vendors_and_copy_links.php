<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });

        Schema::create('library_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });

        Schema::table('library_books', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('publisher_id')->constrained('library_subjects')->nullOnDelete();
        });

        Schema::table('library_book_copies', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('rack_id')->constrained('library_vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('library_book_copies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });

        Schema::table('library_books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
        });

        Schema::dropIfExists('library_vendors');
        Schema::dropIfExists('library_subjects');
    }
};
