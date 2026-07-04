<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('channel_partners', 'restrict_fee_collection_types')) {
            Schema::table('channel_partners', function (Blueprint $table) {
                $table->boolean('restrict_fee_collection_types')->default(false)->after('max_discount_pct');
            });
        }

        if (!Schema::hasTable('channel_partner_fee_discount_permissions')) {
            Schema::create('channel_partner_fee_discount_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->onDelete('cascade');
                $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['channel_partner_id', 'fee_type_id']);
            });
        }

        if (!Schema::hasTable('channel_partner_fee_collection_permissions')) {
            Schema::create('channel_partner_fee_collection_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_partner_id')->constrained('channel_partners')->onDelete('cascade');
                $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['channel_partner_id', 'fee_type_id'], 'channel_partner_fee_collect_perm_unique');
            });
        }

        if (Schema::hasColumn('centers', 'can_waive_fee')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->dropColumn('can_waive_fee');
            });
        }

        if (Schema::hasColumn('channel_partners', 'can_waive_fee')) {
            Schema::table('channel_partners', function (Blueprint $table) {
                $table->dropColumn('can_waive_fee');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('channel_partners', 'can_waive_fee')) {
            Schema::table('channel_partners', function (Blueprint $table) {
                $table->boolean('can_waive_fee')->default(false);
            });
        }

        if (!Schema::hasColumn('centers', 'can_waive_fee')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->boolean('can_waive_fee')->default(false);
            });
        }

        Schema::dropIfExists('channel_partner_fee_collection_permissions');
        Schema::dropIfExists('channel_partner_fee_discount_permissions');

        if (Schema::hasColumn('channel_partners', 'restrict_fee_collection_types')) {
            Schema::table('channel_partners', function (Blueprint $table) {
                $table->dropColumn('restrict_fee_collection_types');
            });
        }
    }
};
