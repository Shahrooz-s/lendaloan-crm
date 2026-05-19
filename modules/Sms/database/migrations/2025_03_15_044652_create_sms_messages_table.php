<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId("user_id")->constrained("users")->onDelete('cascade');
            $table->foreignId("activity_type_id")->constrained("activity_types")->onDelete('cascade');
            $table->string('sid', 500)->nullable();
            $table->enum('direction', ['sent','received']);
            $table->string('from', 50);
            $table->string('to', 50);
            $table->string('message', 1600)->nullable()->default(null);
            $table->enum('status', ['request_error','accepted','queued','sending','sent','receiving','received','delivered','undelivered','failed','read'])->default('request_error');
            // Indexes
            $table->unique(['sid']);
            $table->index(['from']);
            $table->index(['to']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
