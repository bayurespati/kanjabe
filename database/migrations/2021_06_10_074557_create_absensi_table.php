<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->enum('kehadiran', ['WFH', 'WFO', 'Satelit'])->nullable();
            $table->enum('kondisi', ['Sehat', 'Sakit', 'izin', 'sppd', 'cuti']);
            $table->boolean('is_shift');
            $table->enum('checkout_status', ['Normal', 'System'])->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('absensi');
    }
}
