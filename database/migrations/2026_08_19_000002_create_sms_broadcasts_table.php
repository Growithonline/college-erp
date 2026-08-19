<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_template_id')->constrained('sms_templates')->restrictOnDelete();
            $table->json('template_values')->nullable();

            $table->string('audience_type'); // staff | student

            // null = no restriction on that dimension (matches Notice targeting's existing convention)
            $table->json('target_course_ids')->nullable();
            $table->json('target_stream_ids')->nullable();
            $table->json('target_semesters')->nullable();
            $table->json('target_staff_role_ids')->nullable();

            $table->string('recipient_mode')->default('all'); // all | specific
            $table->json('specific_recipient_ids')->nullable();

            // Notice is posted only when this broadcast is actually confirmed & sent (not at
            // draft time) — title/body are staged here until then; linked_notice_id fills in
            // once the Notice row actually gets created.
            $table->string('notice_title')->nullable();
            $table->text('notice_body')->nullable();
            $table->foreignId('linked_notice_id')->nullable()->constrained('notices')->nullOnDelete();

            $table->string('status')->default('draft'); // draft | queued | sending | sent | failed | partial
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_broadcasts');
    }
};
