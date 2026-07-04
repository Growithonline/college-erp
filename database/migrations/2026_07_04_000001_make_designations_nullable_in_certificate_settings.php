
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            $table->string('principal_designation')->default('Principal')->nullable()->change();
            $table->string('registrar_designation')->default('Registrar')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            $table->string('principal_designation')->default('Principal')->nullable(false)->change();
            $table->string('registrar_designation')->default('Registrar')->nullable(false)->change();
        });
    }
};
