<?php
// database/migrations/2025_08_12_000000_add_media_to_movements_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->string('media_url')->nullable()->after('video_url');
            $table->string('media_type', 10)->nullable()->after('media_url'); // 'image'|'video'
        });

        // Backfill: si video_url existe déjà, on la recopie
        DB::table('movements')
          ->whereNotNull('video_url')
          ->update(['media_url' => DB::raw('video_url'), 'media_type' => 'video']);
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn(['media_url','media_type']);
        });
    }
};
