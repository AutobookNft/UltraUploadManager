<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('uconfig_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uconfig_id');
            $table->integer('version')->default(1);
            $table->string('key')->nullable();
            $table->string('category')->nullable();
            $table->text('note')->nullable();
            $table->longText('value');
            $table->timestamps();

            // Chiave esterna
            $table->foreign('uconfig_id')->references('id')->on('uconfig')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('uconfig_versions');
    }
};
