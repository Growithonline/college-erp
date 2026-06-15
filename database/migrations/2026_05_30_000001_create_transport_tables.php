<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transport_vehicles')) {
            Schema::create('transport_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->onDelete('cascade');
                $table->string('vehicle_no', 50);
                $table->string('registration_no', 60)->nullable();
                $table->string('model', 100)->nullable();
                $table->unsignedSmallInteger('capacity')->default(0);
                $table->string('fuel_type', 30)->nullable();
                $table->date('insurance_expiry')->nullable();
                $table->date('permit_expiry')->nullable();
                $table->date('fitness_expiry')->nullable();
                $table->date('pollution_expiry')->nullable();
                $table->date('service_due_date')->nullable();
                $table->boolean('status')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['institute_id', 'vehicle_no']);
            });
        }

        if (!Schema::hasTable('transport_drivers')) {
            Schema::create('transport_drivers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->onDelete('cascade');
                $table->string('name', 120);
                $table->string('mobile', 20)->nullable();
                $table->string('license_no', 80)->nullable();
                $table->date('license_expiry')->nullable();
                $table->string('helper_name', 120)->nullable();
                $table->string('helper_mobile', 20)->nullable();
                $table->boolean('status')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transport_routes')) {
            Schema::create('transport_routes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->onDelete('cascade');
                $table->string('route_code', 40);
                $table->string('name', 120);
                $table->string('start_point', 180)->nullable();
                $table->string('end_point', 180)->nullable();
                $table->decimal('distance_km', 8, 2)->nullable();
                $table->decimal('fee_amount', 12, 2)->default(0);
                $table->time('morning_time')->nullable();
                $table->time('evening_time')->nullable();
                $table->boolean('status')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['institute_id', 'route_code']);
            });
        }

        if (!Schema::hasTable('transport_route_stops')) {
            Schema::create('transport_route_stops', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->string('stop_name', 160);
                $table->string('landmark', 180)->nullable();
                $table->unsignedInteger('sequence')->default(1);
                $table->time('pickup_time')->nullable();
                $table->time('drop_time')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->unique(['transport_route_id', 'sequence']);
            });
        }

        if (!Schema::hasTable('transport_allocations')) {
            Schema::create('transport_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('transport_route_stop_id')->nullable()->constrained('transport_route_stops')->nullOnDelete();
                $table->foreignId('transport_vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->foreignId('transport_driver_id')->nullable()->constrained('transport_drivers')->nullOnDelete();
                $table->decimal('fee_amount', 12, 2)->default(0);
                $table->decimal('charged_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->enum('status', ['active', 'partial', 'paid', 'closed'])->default('active');
                $table->boolean('is_active')->default(true);
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'academic_session_id', 'is_active'], 'ta_student_session_active_idx');
            });
        }

        if (!Schema::hasTable('transport_payments')) {
            Schema::create('transport_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_allocation_id')->constrained('transport_allocations')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('payment_date');
                $table->string('payment_mode', 30)->default('cash');
                $table->string('reference_no', 100)->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('by_user_id')->nullable();
                $table->foreignId('student_transaction_id')->nullable()->constrained('student_transactions')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('student_transactions', 'transport_allocation_id')) {
            Schema::table('student_transactions', function (Blueprint $table) {
                $table->foreignId('transport_allocation_id')
                    ->nullable()
                    ->after('promotion_log_id')
                    ->constrained('transport_allocations')
                    ->nullOnDelete();

                $table->foreignId('transport_payment_id')
                    ->nullable()
                    ->after('transport_allocation_id')
                    ->constrained('transport_payments')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('student_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transport_payment_id');
            $table->dropConstrainedForeignId('transport_allocation_id');
        });

        Schema::dropIfExists('transport_payments');
        Schema::dropIfExists('transport_allocations');
        Schema::dropIfExists('transport_route_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_drivers');
        Schema::dropIfExists('transport_vehicles');
    }
};
