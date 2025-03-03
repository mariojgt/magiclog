<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('reason')->nullable();
            $table->timestamp('banned_until');
            $table->json('attack_details')->nullable();
            $table->integer('request_count')->default(0);
            $table->integer('ban_count')->default(1);
            $table->timestamps();

            // Index for quick lookups
            $table->index(['ip_address', 'banned_until']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banned_ips');
    }
};
