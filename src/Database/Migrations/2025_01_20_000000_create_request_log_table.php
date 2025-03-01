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
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();

            // Request details
            $table->string('method', 10);
            $table->string('path');
            $table->text('full_url');

            // Client information
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Request metadata - changed from json to longtext
            $table->longText('headers')->nullable();
            $table->longText('input_params')->nullable();

            // Response details
            $table->integer('response_status');
            $table->float('response_time', 8, 2)->comment('Response time in milliseconds');

            // Response body - changed from json to longtext
            $table->longText('response_body')->nullable();

            // User association (optional)
            $table->unsignedBigInteger('user_id')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexing for performance
            $table->index(['method', 'created_at']);
            $table->index('response_status');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
