<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data pasien — telepon, alamat, dan catatan medis disimpan terenkripsi (AES-256-GCM).
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('medical_record_no', 40)->unique();
            $table->string('full_name', 160);
            $table->string('nik', 16)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('gender', 12)->nullable(); // Laki-laki | Perempuan
            $table->text('phone')->nullable();        // encrypted cast
            $table->text('address')->nullable();      // encrypted cast
            $table->text('medical_notes')->nullable(); // encrypted cast
            $table->timestamps();
        });

        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 180);
            $table->string('unit', 50)->default('Pcs');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(10);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('nip', 32)->nullable()->unique();
            $table->string('position', 160);
            $table->string('unit', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('specialty', 100);
            $table->string('sip_number', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->string('day', 10); // Senin..Minggu
            $table->time('time_start');
            $table->time('time_end');
            $table->string('room', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('type', 16); // Pemasukan | Pengeluaran
            $table->string('category', 120);
            $table->text('description')->nullable();
            $table->decimal('amount', 16, 2);
            $table->timestamps();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('service', 100);
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->text('suggestion')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('entity', 40);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('finances');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('patients');
    }
};
