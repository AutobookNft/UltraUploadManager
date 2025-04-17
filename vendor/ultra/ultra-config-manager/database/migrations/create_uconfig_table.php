<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('uconfig', function (Blueprint $table) {
            $table->id(); // Chiave primaria
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('category')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Supporto per soft delete
        });
    }

    public function down()
    {
        Schema::dropIfExists('uconfig');
    }
};
