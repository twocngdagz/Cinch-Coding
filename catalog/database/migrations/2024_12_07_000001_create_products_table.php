<?php
};
    }
        Schema::dropIfExists('products');
    {
    public function down(): void

    }
        });
            $table->timestamps();
            $table->string('status');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('title');
            $table->id();
        Schema::create('products', function (Blueprint $table) {
    {
    public function up(): void
{
return new class extends Migration

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


