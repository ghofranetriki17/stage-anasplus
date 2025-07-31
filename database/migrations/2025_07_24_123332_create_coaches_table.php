<?php
// Migration: create_coaches_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->decimal('hourly_rate_online', 8, 2);
            $table->decimal('hourly_rate_presential', 8, 2);
            $table->text('bio')->nullable();
            $table->text('certifications')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_sessions')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coaches');
    }
};
