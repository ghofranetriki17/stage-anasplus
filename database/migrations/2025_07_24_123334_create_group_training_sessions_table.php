<?php
// Migration: create_group_training_sessions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade'); // locationId
            $table->foreignId('coach_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->dateTime('session_date');
            $table->integer('duration'); // in minutes
            $table->string('title');
            $table->boolean('is_for_women')->default(false);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_for_kids')->default(false);
            $table->integer('max_participants')->nullable();
            $table->integer('current_participants')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_training_sessions');
    }
};