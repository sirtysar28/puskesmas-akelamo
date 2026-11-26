<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrasi layanan pasien online:
 * - patients.user_id    → tautan akun login pasien (role: pasien)
 * - appointments        → pendaftaran berobat online (antrian) oleh pasien
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // pembuat (pasien/petugas)
            $table->date('visit_date');
            $table->string('complaint', 500)->nullable();
            $table->string('status', 20)->default('Menunggu'); // Menunggu | Dikonfirmasi | Selesai | Batal
            $table->timestamps();
            $table->index(['visit_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
