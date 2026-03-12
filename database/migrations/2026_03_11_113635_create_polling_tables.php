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
    
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title',128);
            $table->text('description')->nullable();
            $table->string('slug', 128)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('end_at')->nullable();
            $table->unsignedBigInteger('total_votes')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'end_at']);
            $table->index('slug');
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('text', 64);
            $table->unsignedBigInteger('votes_count')->default(0);
            $table->timestamps();
            $table->index('poll_id');
        });

         Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 16);
            $table->timestamps();
            $table->unique(['poll_id', 'user_id']); // for authenticated user
            $table->unique(['poll_id', 'ip_address']); // For guest 
            $table->index('poll_option_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        Schema::dropIfExists('polls');

        Schema::dropIfExists('poll_options');

        Schema::dropIfExists('votes');

    }
};
