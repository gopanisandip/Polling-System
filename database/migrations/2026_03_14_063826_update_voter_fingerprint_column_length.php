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
        /**
         * Add new column and indexes first so MySQL has an index
         * covering poll_id before we drop the old unique constraints
         */
        Schema::table('votes', function (Blueprint $table) {
            $table->string('voter_fingerprint', 128)->after('ip_address');
            $table->unique(['poll_id', 'voter_fingerprint']);
            $table->index('user_id');
            $table->index('created_at');
        });

        /** 
         * old unique constraints remove
        */
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique(['poll_id', 'user_id']);
            $table->dropUnique(['poll_id', 'ip_address']);
            $table->string('ip_address', 45)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropUnique(['poll_id', 'voter_fingerprint']);
            $table->dropColumn('voter_fingerprint');

            $table->string('ip_address', 16)->change();

            $table->unique(['poll_id', 'user_id']);
            $table->unique(['poll_id', 'ip_address']);
        });
    }
};
