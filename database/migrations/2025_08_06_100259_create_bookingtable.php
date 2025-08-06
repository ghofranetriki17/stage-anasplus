<?php
// Migration: 2025_08_06_100259_create_bookingtable.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingtable extends Migration
{
    public function up()
    {
        Schema::create('group_session_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_training_session_id')->constrained()->onDelete('cascade');
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamps();

            // Ensure a user can only book a session once
            $table->unique(['user_id', 'group_training_session_id']);
            
            // Add indexes for better performance
            $table->index(['user_id', 'booked_at']);
            $table->index(['group_training_session_id', 'booked_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_session_bookings');
    }
}