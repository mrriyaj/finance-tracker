<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Models\Account;
use App\Services\CurrencyService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // User selection - hidden for now, auto-assign to current user
                Hidden::make('user_id')
                    ->default(1), // Will be updated when we add proper auth

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->placeholder('e.g., Chase Checking Account'),

                Select::make('type')
                    ->required()
                    ->options(Account::getAccountTypes())
                    ->live()
                    ->placeholder('Select account type'),

                Textarea::make('description')
                    ->columnSpanFull()
                    ->rows(3)
                    ->placeholder('Optional description or notes about this account'),

                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                    ->step(0.01)
                    ->live(onBlur: true)
                    ->helperText(fn($get, $state) => self::getBalanceHelperText($get('currency'), $state)),

                Select::make('currency')
                    ->required()
                    ->default('USD')
                    ->searchable()
                    ->live()
                    ->options(CurrencyService::getAllCurrencies())
                    ->getSearchResultsUsing(fn(string $search): array => self::searchCurrencies($search))
                    ->getOptionLabelUsing(fn($value): ?string => CurrencyService::getAllCurrencies()[$value] ?? $value)
                    ->helperText('Select your account currency. Supports 70+ currencies including crypto')
                    ->placeholder('Search currencies...'),

                TextInput::make('credit_limit')
                    ->numeric()
                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                    ->step(0.01)
                    ->helperText('Maximum credit limit (for credit cards)'),

                TextInput::make('minimum_balance')
                    ->numeric()
                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                    ->step(0.01)
                    ->helperText('Minimum required balance'),

                TextInput::make('interest_rate')
                    ->numeric()
                    ->suffix('%')
                    ->step(0.01)
                    ->minValue(0)
                    ->maxValue(100)
                    ->helperText('Annual interest rate'),

                TextInput::make('monthly_fee')
                    ->numeric()
                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                    ->step(0.01)
                    ->helperText('Monthly maintenance fee'),

                TextInput::make('overdraft_fee')
                    ->numeric()
                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                    ->step(0.01)
                    ->helperText('Overdraft fee amount'),

                // Bank Details
                TextInput::make('bank_name')
                    ->maxLength(255)
                    ->placeholder('e.g., Chase Bank'),

                TextInput::make('account_number')
                    ->maxLength(255)
                    ->placeholder('Account number'),

                TextInput::make('routing_number')
                    ->maxLength(255)
                    ->placeholder('9-digit routing number'),

                TextInput::make('swift_code')
                    ->maxLength(255)
                    ->placeholder('SWIFT/BIC code'),

                TextInput::make('bank_branch')
                    ->maxLength(255)
                    ->placeholder('Branch location'),

                // Digital Wallet Details
                Select::make('wallet_provider')
                    ->options([
                        'PayPal' => 'PayPal',
                        'Apple Pay' => 'Apple Pay',
                        'Google Pay' => 'Google Pay',
                        'Venmo' => 'Venmo',
                        'Cash App' => 'Cash App',
                        'Zelle' => 'Zelle',
                        'Other' => 'Other',
                    ])
                    ->placeholder('Select wallet provider'),

                TextInput::make('wallet_id')
                    ->maxLength(255)
                    ->placeholder('Email or wallet ID'),

                // UI Customization
                ColorPicker::make('color')
                    ->label('Account Color'),

                Select::make('icon')
                    ->label('Account Icon')
                    ->options([
                        'bank' => 'Bank',
                        'credit-card' => 'Credit Card',
                        'cash' => 'Cash',
                        'device-mobile' => 'Mobile/Digital',
                        'chart-line' => 'Investment',
                        'home' => 'Property/Mortgage',
                        'wallet' => 'Wallet',
                        'building' => 'Institution',
                    ]),

                // Account Settings
                Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Whether this account is currently active'),

                Toggle::make('is_primary')
                    ->default(false)
                    ->helperText('Set as primary account'),

                Toggle::make('include_in_net_worth')
                    ->default(true)
                    ->helperText('Include in net worth calculations'),

                // Important Dates
                DatePicker::make('opened_date')
                    ->label('Date Opened')
                    ->placeholder('When was this account opened?'),

                DatePicker::make('closed_date')
                    ->label('Date Closed')
                    ->placeholder('When was this account closed?'),
            ]);
    }

    /**
     * Search currencies by code or name
     */
    public static function searchCurrencies(string $search): array
    {
        $currencies = CurrencyService::getAllCurrencies();
        $search = strtolower($search);

        return array_filter($currencies, function ($label, $code) use ($search) {
            return str_contains(strtolower($code), $search) ||
                str_contains(strtolower($label), $search);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get balance helper text with currency conversion
     */
    public static function getBalanceHelperText(?string $currency, $balance): string
    {
        if (!$currency || !$balance || $currency === 'USD') {
            return 'Current account balance';
        }

        $usdAmount = CurrencyService::convertAmount(floatval($balance), $currency, 'USD');

        if ($usdAmount) {
            return 'Current account balance (≈ $' . number_format($usdAmount, 2) . ' USD)';
        }

        return 'Current account balance';
    }

    /**
     * Get popular currencies for quick selection
     */
    public static function getPopularCurrencies(): array
    {
        return CurrencyService::getTrendingCurrencies();
    }
}
