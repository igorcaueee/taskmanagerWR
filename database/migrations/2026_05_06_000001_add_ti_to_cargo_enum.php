<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN cargo ENUM('diretor','supervisor','analista','assistente','auxiliar','ti') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN cargo ENUM('diretor','supervisor','analista','assistente','auxiliar') NOT NULL");
    }
};
