<?php
// Migration: create_workout_exercises_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->decimal('achievement', 5, 2)->default(0); // percentage
            $table->boolean('is_done')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['workout_id', 'exercise_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workout_exercises');
    }
};
