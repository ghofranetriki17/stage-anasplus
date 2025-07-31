<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // In the migration file
public function up()
{
    Schema::create('group_session_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('group_training_session_id')->constrained()->onDelete('cascade');
        $table->timestamp('booked_at')->useCurrent();
        $table->timestamps();
        
        $table->unique(['user_id', 'group_training_session_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_session_user');
    }
};
