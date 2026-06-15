<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });

        Schema::create('library_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });

        Schema::create('library_publishers', function (Blueprint $table) {
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

        Schema::create('library_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('room_name')->nullable();
            $table->string('rack_code', 50);
            $table->string('shelf_code', 50)->nullable();
            $table->string('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'rack_code', 'shelf_code']);
        });

        Schema::create('library_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('member_type', ['student', 'staff', 'faculty']);
            $table->unsignedInteger('max_books')->default(2);
            $table->unsignedInteger('loan_days')->default(14);
            $table->decimal('fine_per_day', 10, 2)->default(0);
            $table->unsignedInteger('grace_days')->default(0);
            $table->unsignedInteger('max_renewals')->default(1);
            $table->boolean('allow_reservation')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('library_categories')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('library_publishers')->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('isbn', 50)->nullable();
            $table->string('edition', 50)->nullable();
            $table->string('language', 50)->default('English');
            $table->string('subject_name')->nullable();
            $table->text('author_text')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('library_book_author', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('library_authors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['book_id', 'author_id']);
        });

        Schema::create('library_book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('rack_id')->nullable()->constrained('library_racks')->nullOnDelete();
            $table->string('accession_no', 80);
            $table->string('barcode', 120)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['available', 'issued', 'reserved', 'lost', 'damaged', 'withdrawn'])->default('available');
            $table->string('condition_note')->nullable();
            $table->timestamps();

            $table->unique(['institute_id', 'accession_no']);
            $table->unique(['institute_id', 'barcode']);
        });

        Schema::create('library_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->enum('member_type', ['student', 'staff', 'faculty']);
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->foreignId('rule_set_id')->nullable()->constrained('library_rule_sets')->nullOnDelete();
            $table->string('member_code', 80);
            $table->string('name');
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'blocked', 'inactive'])->default('active');
            $table->date('joined_on')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->timestamps();

            $table->unique(['institute_id', 'member_code']);
            $table->unique(['student_id']);
            $table->unique(['staff_member_id']);
        });

        Schema::create('library_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_member_id')->constrained('library_members')->cascadeOnDelete();
            $table->foreignId('library_book_copy_id')->constrained('library_book_copies')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('txn_type', ['issue']);
            $table->enum('current_status', ['issued', 'returned', 'lost', 'damaged'])->default('issued');
            $table->unsignedInteger('renew_count')->default(0);
            $table->date('issued_on');
            $table->date('due_on');
            $table->date('returned_on')->nullable();
            $table->decimal('loan_days_snapshot', 10, 2)->default(0);
            $table->decimal('fine_per_day_snapshot', 10, 2)->default(0);
            $table->unsignedInteger('grace_days_snapshot')->default(0);
            $table->unsignedInteger('max_renewals_snapshot')->default(0);
            $table->string('rule_name_snapshot')->nullable();
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->decimal('fine_paid', 10, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->string('issued_by')->nullable();
            $table->string('returned_by')->nullable();
            $table->timestamps();
        });

        Schema::create('library_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_member_id')->constrained('library_members')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('fulfilled_copy_id')->nullable()->constrained('library_book_copies')->nullOnDelete();
            $table->enum('status', ['pending', 'fulfilled', 'cancelled', 'expired'])->default('pending');
            $table->date('reserved_on');
            $table->date('expires_on')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('library_fine_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_member_id')->constrained('library_members')->cascadeOnDelete();
            $table->foreignId('library_transaction_id')->constrained('library_transactions')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_mode', 30)->default('cash');
            $table->date('payment_date');
            $table->string('receipt_no', 80)->nullable();
            $table->string('remarks')->nullable();
            $table->string('collected_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_fine_payments');
        Schema::dropIfExists('library_reservations');
        Schema::dropIfExists('library_transactions');
        Schema::dropIfExists('library_members');
        Schema::dropIfExists('library_book_copies');
        Schema::dropIfExists('library_book_author');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('library_rule_sets');
        Schema::dropIfExists('library_racks');
        Schema::dropIfExists('library_publishers');
        Schema::dropIfExists('library_authors');
        Schema::dropIfExists('library_categories');
    }
};
