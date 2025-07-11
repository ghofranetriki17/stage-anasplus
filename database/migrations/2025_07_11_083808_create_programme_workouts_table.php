<?php
// Migration: create_programme_workouts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('programme_workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->integer('week_day')->nullable(); // 1-7 (Monday to Sunday)
            $table->timestamps();
            
            $table->unique(['programme_id', 'workout_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('programme_workouts');
    }
};