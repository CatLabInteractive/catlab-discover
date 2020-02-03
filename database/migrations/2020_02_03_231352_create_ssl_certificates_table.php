<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSslCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('order_url', 128);
            $table->unique('order_url');

            $table->bigInteger('device_id')->unsigned();
            $table->foreign('device_id')->references('id')->on('devices');

            $table->text('public_key');
            $table->text('private_key');
            $table->text('certificate');

            $table->string('status', 64);
            $table->dateTime('expires');

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
        Schema::dropIfExists('ssl_certificates');
    }
}
