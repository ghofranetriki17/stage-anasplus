<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->string('photo_url')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn('photo_url');
        });
    }
};
