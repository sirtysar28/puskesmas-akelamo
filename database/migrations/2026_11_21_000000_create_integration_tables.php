<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel integrasi layanan:
 * - users.email          → target notifikasi email (SMTP)
 * - app_notifications    → notifikasi in-app (lonceng topbar)
 * - settings             → pengaturan aplikasi (SMTP dll, tersimpan terenkripsi)
 * - prescriptions + items→ resep dokter ↔ pasien ↔ obat (farmasi)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->unique()->after('username');
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();          // keuangan | pasien | resep | survei | sistem
            $table->string('title', 170);
            $table->text('body')->nullable();
            $table->string('url', 255)->nullable();       // link internal (path relatif)
            $table->foreignId('actor_id')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();            // nilai sensitif dienkripsi aplikasi
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // pembuat resep (akun)
            $table->string('status', 20)->default('aktif'); // aktif | diserahkan
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->string('dose', 140);                  // aturan pakai, mis. "3x1 sesudah makan"
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('app_notifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
