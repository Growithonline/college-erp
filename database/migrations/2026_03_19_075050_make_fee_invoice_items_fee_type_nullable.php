<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // fee_invoice_items.fee_type_id NOT NULL tha
    // lekin subject/practical fees ka koi fee_type nahi hota (SubjectFeeRule se aate hain)
    // Isliye nullable karna zaroori hai

    public function up(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            // Foreign key pehle drop karo, phir nullable ke saath re-add karo
            $table->dropForeign(['fee_type_id']);
            $table->unsignedBigInteger('fee_type_id')->nullable()->change();
            $table->foreign('fee_type_id')->references('id')->on('fee_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['fee_type_id']);
            $table->unsignedBigInteger('fee_type_id')->nullable(false)->change();
            $table->foreign('fee_type_id')->references('id')->on('fee_types')->onDelete('cascade');
        });
    }
};