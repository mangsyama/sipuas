<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Master Data Staff
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('position', 100); // Jabatan: Perawat, Bidan, CS, Satpam, Dokter, dll.
            $table->string('nip', 50)->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('total_points')->default(100);
            $table->integer('praise_count')->default(0);
            $table->integer('complaint_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTimeTz('last_point_update_at')->nullable();
            $table->timestampsTz();

            $table->index('is_active');
            $table->index('position');
        });

        // 2. Tabel Pivot Penempatan Staf di Ruangan (Many-to-Many)
        Schema::create('room_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['staff_id', 'room_id'], 'uq_room_staff');
            $table->index('room_id');
            $table->index('staff_id');
        });

        // 3. Tambahkan staff_id ke tabel report_staff & staff_kpi_logs sebagai fallback relasi verifikasi
        if (Schema::hasTable('report_staff')) {
            Schema::table('report_staff', function (Blueprint $table) {
                if (!Schema::hasColumn('report_staff', 'staff_id')) {
                    $table->foreignId('staff_id')->nullable()->after('report_id')->constrained('staff')->nullOnDelete();
                }
                // Ubah user_id menjadi nullable jika sebelumnya required
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('staff_kpi_logs')) {
            Schema::table('staff_kpi_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_kpi_logs', 'staff_id')) {
                    $table->foreignId('staff_id')->nullable()->after('user_id')->constrained('staff')->nullOnDelete();
                }
                // Ubah user_id menjadi nullable jika sebelumnya required
                $table->foreignId('user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('staff_kpi_logs') && Schema::hasColumn('staff_kpi_logs', 'staff_id')) {
            Schema::table('staff_kpi_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('staff_id');
            });
        }

        if (Schema::hasTable('report_staff') && Schema::hasColumn('report_staff', 'staff_id')) {
            Schema::table('report_staff', function (Blueprint $table) {
                $table->dropConstrainedForeignId('staff_id');
            });
        }

        Schema::dropIfExists('room_staff');
        Schema::dropIfExists('staff');
    }
};
