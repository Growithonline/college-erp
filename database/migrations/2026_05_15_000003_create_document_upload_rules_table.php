<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per course + user_type combination: which documents are required/optional/skip
        Schema::create('document_upload_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            // online = student via online admission, center = center staff, partner = channel partner, staff = institute staff
            $table->enum('user_type', ['online', 'center', 'partner', 'staff']);
            $table->enum('requirement', ['required', 'optional', 'skip'])->default('optional');
            $table->timestamps();

            $table->unique(['course_id', 'document_type_id', 'user_type'], 'doc_rule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_upload_rules');
    }
};
