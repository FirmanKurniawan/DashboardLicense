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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreignId('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->string('license_key')->unique();
            $table->dateTime('purchase_date');
            $table->integer('valid_until');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('activation_at')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
