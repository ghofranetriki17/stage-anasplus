<?php
// Migration: create_exercises_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('machine_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('title')->nullable();
            $table->integer('sets')->default(1);
            $table->integer('reps')->default(1);
            $table->foreignId('charge_id')->nullable()->constrained()->onDelete('set null');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('exercises');
    }
};