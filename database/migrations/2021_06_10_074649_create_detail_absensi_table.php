<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailAbsensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_absensi', function (Blueprint $table) {
            $table->id();
            $table->integer('absensi_id');
            $table->enum('tipe_cek', ['in', 'out']);
            $table->text('lokasi');
            $table->text('long_lat');
            $table->string('photo')->nullable();
            $table->dateTime('jam');
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
        Schema::dropIfExists('detail_absensi');
    }
}
