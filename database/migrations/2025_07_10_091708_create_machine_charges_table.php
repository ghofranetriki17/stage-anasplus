<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('machine_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignId('charge_id')->constrained('charges')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['machine_id', 'charge_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('machine_charges');
    }
};