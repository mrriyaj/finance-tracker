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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // Using 3-column layout for better 1:2 ratios
            ->components([
                // User selection - hidden for now, auto-assign to current user
                Hidden::make('user_id')
                    ->default(1), // Will be updated when we add proper auth

                // PRIORITY 1: Essential Information Section
                Section::make('Essential Information')
                    ->description('Core account details (required)')
                    ->icon('heroicon-o-star')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        // Priority fields in 1:2 ratio layout
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2) // Takes 2/3 of the width
                            ->placeholder('e.g., Chase Checking Account')
                            ->helperText('Choose a descriptive name to easily identify this account'),

                        Select::make('type')
                            ->required()
                            ->options(Account::getAccountTypes())
                            ->live()
                            ->columnSpan(1) // Takes 1/3 of the width
                            ->placeholder('Select type')
                            ->helperText('Account category'),

                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('balance')
                                    ->required()
                                    ->numeric()
                                    ->default(0.00)
                                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(1)
                                    ->helperText(fn($get, $state) => self::getBalanceHelperText($get('currency'), $state)),

                                Select::make('currency')
                                    ->required()
                                    ->default('USD')
                                    ->searchable()
                                    ->live()
                                    ->options(CurrencyService::getAllCurrencies())
                                    ->getSearchResultsUsing(fn(string $search): array => self::searchCurrencies($search))
                                    ->getOptionLabelUsing(fn($value): ?string => CurrencyService::getAllCurrencies()[$value] ?? $value)
                                    ->columnSpan(1)
                                    ->placeholder('Currency')
                                    ->helperText('Account currency'),

                                Toggle::make('is_active')
                                    ->default(true)
                                    ->columnSpan(1)
                                    ->inline(false)
                                    ->helperText('Account status'),
                            ]),

                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(2)
                            ->placeholder('Optional notes about this account'),
                    ]),

                // PRIORITY 2: Financial Configuration Section
                Section::make('Financial Configuration')
                    ->description('Account-specific financial settings')
                    ->icon('heroicon-o-banknotes')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        // Credit Limit - Only for Credit Cards (High Priority)
                        TextInput::make('credit_limit')
                            ->numeric()
                            ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                            ->step(0.01)
                            ->columnSpan(1)
                            ->helperText('Credit limit')
                            ->visible(fn($get) => $get('type') === Account::TYPE_CREDIT_CARD),

                        // Interest Rate - High Priority for relevant accounts
                        TextInput::make('interest_rate')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1)
                            ->helperText(fn($get) => self::getInterestRateHelper($get('type')))
                            ->visible(fn($get) => in_array($get('type'), [
                                Account::TYPE_CREDIT_CARD,
                                Account::TYPE_LOAN,
                                Account::TYPE_MORTGAGE,
                                Account::TYPE_SAVINGS_ACCOUNT,
                                Account::TYPE_INVESTMENT
                            ])),

                        // Minimum Balance - Medium Priority
                        TextInput::make('minimum_balance')
                            ->numeric()
                            ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                            ->step(0.01)
                            ->columnSpan(1)
                            ->helperText('Min. balance')
                            ->visible(fn($get) => in_array($get('type'), [
                                Account::TYPE_BANK_ACCOUNT,
                                Account::TYPE_CHECKING_ACCOUNT,
                                Account::TYPE_SAVINGS_ACCOUNT
                            ])),

                        // Fees in 1:2 ratio layout
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('monthly_fee')
                                    ->numeric()
                                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                                    ->step(0.01)
                                    ->columnSpan(1)
                                    ->helperText('Monthly fee')
                                    ->visible(fn($get) => in_array($get('type'), [
                                        Account::TYPE_BANK_ACCOUNT,
                                        Account::TYPE_CHECKING_ACCOUNT,
                                        Account::TYPE_SAVINGS_ACCOUNT
                                    ])),

                                TextInput::make('overdraft_fee')
                                    ->numeric()
                                    ->prefix(fn($get) => CurrencyService::getCurrencySymbol($get('currency') ?? 'USD'))
                                    ->step(0.01)
                                    ->columnSpan(2) // Takes 2/3 width - lower priority
                                    ->helperText('Overdraft fee amount')
                                    ->visible(fn($get) => $get('type') === Account::TYPE_CHECKING_ACCOUNT),
                            ]),
                    ]),

                // PRIORITY 3: Institution Details Section
                Section::make('Institution & Account Details')
                    ->description('Banking and account identification')
                    ->icon('heroicon-o-building-library')
                    ->columnSpanFull()
                    ->columns(3)
                    ->visible(fn($get) => in_array($get('type'), [
                        Account::TYPE_BANK_ACCOUNT,
                        Account::TYPE_CHECKING_ACCOUNT,
                        Account::TYPE_SAVINGS_ACCOUNT,
                        Account::TYPE_CREDIT_CARD
                    ]))
                    ->schema([
                        // Bank name gets priority with 2:1 ratio
                        TextInput::make('bank_name')
                            ->maxLength(255)
                            ->columnSpan(2)
                            ->placeholder('e.g., Chase Bank, Bank of America')
                            ->helperText('Financial institution name'),

                        TextInput::make('bank_branch')
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->placeholder('Branch location')
                            ->helperText('Branch or location'),

                        // Account details in even distribution
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('account_number')
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->placeholder('Account #')
                                    ->helperText('Account number'),

                                TextInput::make('routing_number')
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->placeholder('Routing #')
                                    ->helperText('9-digit routing')
                                    ->visible(fn($get) => in_array($get('type'), [
                                        Account::TYPE_BANK_ACCOUNT,
                                        Account::TYPE_CHECKING_ACCOUNT,
                                        Account::TYPE_SAVINGS_ACCOUNT
                                    ])),

                                TextInput::make('swift_code')
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->placeholder('SWIFT/BIC')
                                    ->helperText('International code'),
                            ]),
                    ]),

                // PRIORITY 4: Digital Wallet Section
                Section::make('Digital Wallet Information')
                    ->description('Digital payment provider details')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->columnSpanFull()
                    ->columns(3)
                    ->visible(fn($get) => $get('type') === Account::TYPE_DIGITAL_WALLET)
                    ->schema([
                        Select::make('wallet_provider')
                            ->options([
                                'PayPal' => 'PayPal',
                                'Apple Pay' => 'Apple Pay',
                                'Google Pay' => 'Google Pay',
                                'Venmo' => 'Venmo',
                                'Cash App' => 'Cash App',
                                'Zelle' => 'Zelle',
                                'Wise' => 'Wise',
                                'Revolut' => 'Revolut',
                                'Chime' => 'Chime',
                                'Other' => 'Other',
                            ])
                            ->columnSpan(1)
                            ->placeholder('Provider')
                            ->helperText('Wallet provider'),

                        TextInput::make('wallet_id')
                            ->maxLength(255)
                            ->columnSpan(2) // Gets more space with 2:1 ratio
                            ->placeholder('Email or wallet ID')
                            ->helperText('Email address or unique identifier'),
                    ]),

                // PRIORITY 5: Appearance & Settings Section
                Section::make('Appearance & Behavior')
                    ->description('Visual customization and account settings')
                    ->icon('heroicon-o-paint-brush')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        // Visual settings in 1:1:1 ratio
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                ColorPicker::make('color')
                                    ->label('Color')
                                    ->columnSpan(1)
                                    ->helperText('Account color'),

                                Select::make('icon')
                                    ->label('Icon')
                                    ->columnSpan(1)
                                    ->options(self::getIconOptions())
                                    ->helperText('Account icon'),

                                // Empty space or additional field can go here
                            ]),

                        // Account behavior toggles in 1:2 ratio
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('is_primary')
                                    ->columnSpan(1)
                                    ->default(false)
                                    ->inline(false)
                                    ->helperText('Primary account'),

                                Toggle::make('include_in_net_worth')
                                    ->columnSpan(2) // Gets more space
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Include in net worth calculations'),
                            ]),
                    ]),

                // PRIORITY 6: Important Dates Section (Lowest Priority)
                Section::make('Timeline')
                    ->description('Account lifecycle dates')
                    ->icon('heroicon-o-calendar-days')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        DatePicker::make('opened_date')
                            ->label('Opened')
                            ->columnSpan(1)
                            ->placeholder('Opening date')
                            ->helperText('When opened'),

                        DatePicker::make('closed_date')
                            ->label('Closed')
                            ->columnSpan(2) // Gets more space for lower priority
                            ->placeholder('Closing date')
                            ->helperText('When closed (if applicable)')
                            ->visible(fn($get) => !$get('is_active')),
                    ]),
            ]);
    }

    /**
     * Get icon options based on account type
     */
    public static function getIconOptions(): array
    {
        return [
            'bank' => 'Bank Building',
            'credit-card' => 'Credit Card',
            'cash' => 'Cash Money',
            'device-mobile' => 'Mobile/Digital',
            'chart-line' => 'Investment/Growth',
            'home' => 'Property/Mortgage',
            'wallet' => 'Wallet',
            'building' => 'Institution',
            'currency-dollar' => 'Dollar/Currency',
            'lock-closed' => 'Secure/Savings',
        ];
    }

    /**
     * Get interest rate helper text based on account type
     */
    public static function getInterestRateHelper(?string $type): string
    {
        if (!$type) {
            return 'Annual interest rate';
        }

        return match ($type) {
            Account::TYPE_CREDIT_CARD => 'Annual Percentage Rate (APR) charged on balances',
            Account::TYPE_LOAN, Account::TYPE_MORTGAGE => 'Annual interest rate on the loan',
            Account::TYPE_SAVINGS_ACCOUNT => 'Annual Percentage Yield (APY) earned on savings',
            Account::TYPE_INVESTMENT => 'Expected annual return rate',
            default => 'Annual interest rate'
        };
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
