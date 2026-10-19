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
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            $table->string('document', 14)->nullable()->after('name');
            $table->string('phone', 11)->nullable()->after('document');
            $table->string('photo_path')->nullable()->after('phone');

            $table->dropUnique('users_email_unique');
            $table->unique(['business_id', 'email'], 'users_business_email_unique');
            $table->unique(['business_id', 'document'], 'users_business_document_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_business_email_unique');
            $table->dropUnique('users_business_document_unique');
            $table->unique('email');
            $table->dropColumn(['business_id', 'document', 'phone', 'photo_path']);
        });
    }
};
