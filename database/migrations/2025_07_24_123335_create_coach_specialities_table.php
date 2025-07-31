<?php
// Migration: create_coach_specialities_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coach_specialities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->onDelete('cascade');
            $table->foreignId('speciality_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['coach_id', 'speciality_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coach_specialities');
    }
};
