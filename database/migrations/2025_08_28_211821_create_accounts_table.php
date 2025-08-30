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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Basic account information
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'bank_account',
                'savings_account',
                'checking_account',
                'credit_card',
                'cash',
                'digital_wallet',
                'investment',
                'loan',
                'mortgage',
                'other'
            ]);

            // Account details
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('swift_code')->nullable();

            // Digital wallet specific
            $table->string('wallet_provider')->nullable(); // PayPal, Apple Pay, Google Pay, etc.
            $table->string('wallet_id')->nullable();

            // Financial information
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('minimum_balance', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');

            // Interest and fees
            $table->decimal('interest_rate', 5, 4)->nullable();
            $table->decimal('monthly_fee', 10, 2)->nullable();
            $table->decimal('overdraft_fee', 10, 2)->nullable();

            // Status and settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->boolean('include_in_net_worth')->default(true);
            $table->string('color')->nullable(); // For UI customization
            $table->string('icon')->nullable(); // For UI customization

            // Metadata
            $table->date('opened_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->json('metadata')->nullable(); // For additional custom fields

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
