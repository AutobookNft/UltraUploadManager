<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('uconfig_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uconfig_id')->nullable();
            $table->string('action')->nullable(); // 'created', 'updated', 'deleted'
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // Chiavi esterne
            $table->foreign('uconfig_id')->references('id')->on('uconfig')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('uconfig_audit');
    }
};
