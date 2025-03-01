<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('request_logs', function (Blueprint $table) {
            // Change column types from json to longtext
            $table->longText('headers')->nullable()->change();
            $table->longText('input_params')->nullable()->change();
            $table->longText('response_body')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('request_logs', function (Blueprint $table) {
            // Revert changes if needed
            $table->json('headers')->nullable()->change();
            $table->json('input_params')->nullable()->change();
            $table->json('response_body')->nullable()->change();
        });
    }
};
