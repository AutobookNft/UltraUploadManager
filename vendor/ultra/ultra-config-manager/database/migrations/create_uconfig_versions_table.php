<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void // Era 'up()' prima, correggo in 'up(): void' per PHP moderno
    {
        Schema::create('uconfig_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uconfig_id');
            $table->integer('version')->default(1);
            $table->string('key');
            $table->string('category')->nullable();
            $table->string('source_file')->nullable()->index()->comment('Source config file (e.g., app.php) or manual input indicator');
            $table->text('note')->nullable();
            $table->longText('value');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID dell\'utente che ha effettuato la modifica');
            $table->timestamps();

            // Chiavi esterne
            $table->foreign('uconfig_id')->references('id')->on('uconfig')->onDelete('cascade');
            
            // Chiave esterna per l'utente che ha effettuato la modifica
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Potrebbe essere utile un indice sulla versione per performance
            $table->index(['uconfig_id', 'version']);
        });
    }

    public function down(): void
    {
        // Versione semplificata per evitare dipendenze da Doctrine/DBAL in test
        Schema::dropIfExists('uconfig_versions');
    }
};
