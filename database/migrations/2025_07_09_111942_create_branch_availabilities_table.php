<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('branch_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('day_of_week'); // Monday, Tuesday,..
            $table->time('opening_hour')->nullable();
            $table->time('closing_hour')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            
            $table->unique(['branch_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_availabilities');
    }
};