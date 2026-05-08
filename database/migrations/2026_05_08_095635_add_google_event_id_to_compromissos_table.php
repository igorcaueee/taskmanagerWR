<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('compromissos', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('criado_por');
            $table->index('google_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compromissos', function (Blueprint $table) {
            $table->dropIndex(['google_event_id']);
            $table->dropColumn('google_event_id');
        });
    }
};
