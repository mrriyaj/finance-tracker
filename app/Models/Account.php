<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'account_number',
        'bank_name',
        'bank_branch',
        'routing_number',
        'swift_code',
        'wallet_provider',
        'wallet_id',
        'balance',
        'credit_limit',
        'minimum_balance',
        'currency',
        'interest_rate',
        'monthly_fee',
        'overdraft_fee',
        'is_active',
        'is_primary',
        'include_in_net_worth',
        'color',
        'icon',
        'opened_date',
        'closed_date',
        'metadata',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'minimum_balance' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'monthly_fee' => 'decimal:2',
        'overdraft_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'include_in_net_worth' => 'boolean',
        'opened_date' => 'date',
        'closed_date' => 'date',
        'metadata' => 'array',
    ];

    // Account types constants
    public const TYPE_BANK_ACCOUNT = 'bank_account';
    public const TYPE_SAVINGS_ACCOUNT = 'savings_account';
    public const TYPE_CHECKING_ACCOUNT = 'checking_account';
    public const TYPE_CREDIT_CARD = 'credit_card';
    public const TYPE_CASH = 'cash';
    public const TYPE_DIGITAL_WALLET = 'digital_wallet';
    public const TYPE_INVESTMENT = 'investment';
    public const TYPE_LOAN = 'loan';
    public const TYPE_MORTGAGE = 'mortgage';
    public const TYPE_OTHER = 'other';

    public static function getAccountTypes(): array
    {
        return [
            self::TYPE_BANK_ACCOUNT => 'Bank Account',
            self::TYPE_SAVINGS_ACCOUNT => 'Savings Account',
            self::TYPE_CHECKING_ACCOUNT => 'Checking Account',
            self::TYPE_CREDIT_CARD => 'Credit Card',
            self::TYPE_CASH => 'Cash',
            self::TYPE_DIGITAL_WALLET => 'Digital Wallet',
            self::TYPE_INVESTMENT => 'Investment',
            self::TYPE_LOAN => 'Loan',
            self::TYPE_MORTGAGE => 'Mortgage',
            self::TYPE_OTHER => 'Other',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIncludedInNetWorth($query)
    {
        return $query->where('include_in_net_worth', true);
    }

    // Accessors & Mutators
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    public function getAccountTypeNameAttribute(): string
    {
        return self::getAccountTypes()[$this->type] ?? $this->type;
    }

    // Helper methods
    public function isDebtAccount(): bool
    {
        return in_array($this->type, [
            self::TYPE_CREDIT_CARD,
            self::TYPE_LOAN,
            self::TYPE_MORTGAGE,
        ]);
    }

    public function isAssetAccount(): bool
    {
        return !$this->isDebtAccount();
    }

    public function getNetWorthContribution(): float
    {
        if (!$this->include_in_net_worth) {
            return 0;
        }

        return $this->isDebtAccount() ? -$this->balance : $this->balance;
    }
}
