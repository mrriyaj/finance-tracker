<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Models\Account;
use App\Services\CurrencyService;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class AccountInfolist
{
    /**
     * Map potentially invalid icon names to valid Heroicons
     */
    private static function getValidIcon(string $icon): string
    {
        $iconMap = [
            'device-mobile' => 'device-tablet',
            'device-phone-mobile' => 'device-tablet',
            'mobile' => 'device-tablet',
            'phone' => 'device-tablet',
            'bank' => 'building-library',
            'credit-card' => 'credit-card',
            'wallet' => 'wallet',
            'cash' => 'banknotes',
            'investment' => 'presentation-chart-line',
            'loan' => 'banknotes',
            'mortgage' => 'home-modern',
            'savings' => 'building-library',
            'checking' => 'building-library',
        ];

        return $iconMap[$icon] ?? $icon;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Hero Section with Account Overview
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Left side - Account identity
                                Group::make([
                                    TextEntry::make('name')
                                        ->weight(FontWeight::Bold)
                                        ->color('primary')
                                        ->icon(fn($record) => 'heroicon-o-' . self::getValidIcon($record->icon ?? 'bank')),

                                    TextEntry::make('type')
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            Account::TYPE_BANK_ACCOUNT, Account::TYPE_CHECKING_ACCOUNT => 'success',
                                            Account::TYPE_SAVINGS_ACCOUNT => 'info',
                                            Account::TYPE_CREDIT_CARD => 'warning',
                                            Account::TYPE_LOAN, Account::TYPE_MORTGAGE => 'danger',
                                            Account::TYPE_INVESTMENT => 'purple',
                                            Account::TYPE_DIGITAL_WALLET => 'gray',
                                            default => 'secondary',
                                        })
                                        ->formatStateUsing(
                                            fn(string $state): string =>
                                            Account::getAccountTypes()[$state] ?? $state
                                        ),

                                    TextEntry::make('description')
                                        ->color('gray')
                                        ->visible(fn($record) => !empty($record->description)),
                                ])->columnSpan(1),

                                // Right side - Balance and status
                                Group::make([
                                    TextEntry::make('balance')
                                        ->weight(FontWeight::Bold)
                                        ->money(fn($record) => $record->currency ?? 'USD')
                                        ->color(
                                            fn($record, $state) =>
                                            $record->isDebtAccount() && $state > 0 ? 'danger' : 'success'
                                        )
                                        ->icon('heroicon-o-banknotes'),

                                    TextEntry::make('currency')
                                        ->badge()
                                        ->color('gray')
                                        ->formatStateUsing(
                                            fn(string $state): string =>
                                            strtoupper($state) . ' • ' . CurrencyService::getCurrencySymbol($state)
                                        ),

                                    Grid::make(3)
                                        ->schema([
                                            IconEntry::make('is_active')
                                                ->label('Active')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-x-circle')
                                                ->trueColor('success')
                                                ->falseColor('danger'),

                                            IconEntry::make('is_primary')
                                                ->label('Primary')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-star')
                                                ->falseIcon('heroicon-o-star')
                                                ->trueColor('warning')
                                                ->falseColor('gray'),

                                            IconEntry::make('include_in_net_worth')
                                                ->label('Net Worth')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-presentation-chart-line')
                                                ->falseIcon('heroicon-o-presentation-chart-line')
                                                ->trueColor('success')
                                                ->falseColor('gray'),
                                        ]),
                                ])->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Financial Details Section
                Section::make('Financial Information')
                    ->description('Account financial details and limits')
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        // Balance Information
                        Group::make([
                            TextEntry::make('balance')
                                ->label('Current Balance')
                                ->money(fn($record) => $record->currency ?? 'USD')
                                ->weight(FontWeight::SemiBold)
                                ->color(
                                    fn($record, $state) =>
                                    $record->isDebtAccount() && $state > 0 ? 'danger' : 'success'
                                ),

                            TextEntry::make('usd_equivalent')
                                ->label('USD Equivalent')
                                ->state(function ($record) {
                                    if ($record->currency === 'USD') return null;
                                    $usdAmount = CurrencyService::convertAmount($record->balance, $record->currency, 'USD');
                                    return $usdAmount ? '$' . number_format($usdAmount, 2) : 'N/A';
                                })
                                ->color('gray')
                                ->visible(fn($record) => $record->currency !== 'USD'),
                        ])->columnSpan(1),

                        // Credit & Limits
                        Group::make([
                            TextEntry::make('credit_limit')
                                ->label('Credit Limit')
                                ->money(fn($record) => $record->currency ?? 'USD')
                                ->placeholder('Not applicable')
                                ->visible(fn($record) => $record->type === Account::TYPE_CREDIT_CARD),

                            TextEntry::make('minimum_balance')
                                ->label('Minimum Balance')
                                ->money(fn($record) => $record->currency ?? 'USD')
                                ->placeholder('No minimum')
                                ->visible(fn($record) => in_array($record->type, [
                                    Account::TYPE_BANK_ACCOUNT,
                                    Account::TYPE_CHECKING_ACCOUNT,
                                    Account::TYPE_SAVINGS_ACCOUNT
                                ])),

                            TextEntry::make('available_credit')
                                ->label('Available Credit')
                                ->state(function ($record) {
                                    if ($record->type !== Account::TYPE_CREDIT_CARD || !$record->credit_limit) {
                                        return null;
                                    }
                                    $available = $record->credit_limit - $record->balance;
                                    return CurrencyService::getCurrencySymbol($record->currency) . number_format($available, 2);
                                })
                                ->color('success')
                                ->visible(fn($record) => $record->type === Account::TYPE_CREDIT_CARD && $record->credit_limit),
                        ])->columnSpan(1),

                        // Rates & Fees
                        Group::make([
                            TextEntry::make('interest_rate')
                                ->label(fn($record) => match ($record->type) {
                                    Account::TYPE_CREDIT_CARD => 'APR Rate',
                                    Account::TYPE_SAVINGS_ACCOUNT => 'APY Rate',
                                    Account::TYPE_LOAN, Account::TYPE_MORTGAGE => 'Interest Rate',
                                    default => 'Interest Rate'
                                })
                                ->suffix('%')
                                ->color(fn($record) => $record->type === Account::TYPE_CREDIT_CARD ? 'danger' : 'success')
                                ->placeholder('Not set')
                                ->visible(fn($record) => in_array($record->type, [
                                    Account::TYPE_CREDIT_CARD,
                                    Account::TYPE_LOAN,
                                    Account::TYPE_MORTGAGE,
                                    Account::TYPE_SAVINGS_ACCOUNT,
                                    Account::TYPE_INVESTMENT
                                ])),

                            TextEntry::make('monthly_fee')
                                ->label('Monthly Fee')
                                ->money(fn($record) => $record->currency ?? 'USD')
                                ->placeholder('No fee')
                                ->color('warning')
                                ->visible(fn($record) => in_array($record->type, [
                                    Account::TYPE_BANK_ACCOUNT,
                                    Account::TYPE_CHECKING_ACCOUNT,
                                    Account::TYPE_SAVINGS_ACCOUNT
                                ])),

                            TextEntry::make('overdraft_fee')
                                ->label('Overdraft Fee')
                                ->money(fn($record) => $record->currency ?? 'USD')
                                ->placeholder('No fee')
                                ->color('danger')
                                ->visible(fn($record) => $record->type === Account::TYPE_CHECKING_ACCOUNT),
                        ])->columnSpan(1),
                    ]),

                // Institution Details Section
                Section::make('Institution Details')
                    ->description('Banking and account identification information')
                    ->icon('heroicon-o-building-library')
                    ->columns(2)
                    ->visible(fn($record) => in_array($record->type, [
                        Account::TYPE_BANK_ACCOUNT,
                        Account::TYPE_CHECKING_ACCOUNT,
                        Account::TYPE_SAVINGS_ACCOUNT,
                        Account::TYPE_CREDIT_CARD
                    ]))
                    ->schema([
                        Group::make([
                            TextEntry::make('bank_name')
                                ->label('Financial Institution')
                                ->icon('heroicon-o-building-library')
                                ->placeholder('Not specified')
                                ->weight(FontWeight::SemiBold),

                            TextEntry::make('bank_branch')
                                ->label('Branch Location')
                                ->icon('heroicon-o-map-pin')
                                ->placeholder('Not specified')
                                ->color('gray'),

                            TextEntry::make('account_number')
                                ->label('Account Number')
                                ->icon('heroicon-o-identification')
                                ->placeholder('Not provided')
                                ->copyable()
                                ->copyMessage('Account number copied!')
                                ->formatStateUsing(
                                    fn(?string $state): string =>
                                    $state ? '••••' . substr($state, -4) : 'Not provided'
                                ),
                        ])->columnSpan(1),

                        Group::make([
                            TextEntry::make('routing_number')
                                ->label('Routing Number')
                                ->icon('heroicon-o-hashtag')
                                ->placeholder('Not provided')
                                ->copyable()
                                ->copyMessage('Routing number copied!')
                                ->visible(fn($record) => in_array($record->type, [
                                    Account::TYPE_BANK_ACCOUNT,
                                    Account::TYPE_CHECKING_ACCOUNT,
                                    Account::TYPE_SAVINGS_ACCOUNT
                                ])),

                            TextEntry::make('swift_code')
                                ->label('SWIFT/BIC Code')
                                ->icon('heroicon-o-globe-alt')
                                ->placeholder('Not provided')
                                ->copyable()
                                ->copyMessage('SWIFT code copied!')
                                ->badge()
                                ->color('info'),
                        ])->columnSpan(1),
                    ]),

                // Digital Wallet Section
                Section::make('Digital Wallet Information')
                    ->description('Digital payment provider details')
                    ->icon('heroicon-o-wallet')
                    ->columns(2)
                    ->visible(fn($record) => $record->type === Account::TYPE_DIGITAL_WALLET)
                    ->schema([
                        TextEntry::make('wallet_provider')
                            ->label('Wallet Provider')
                            ->icon('heroicon-o-credit-card')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('wallet_id')
                            ->label('Wallet ID / Email')
                            ->icon('heroicon-o-at-symbol')
                            ->copyable()
                            ->copyMessage('Wallet ID copied!'),
                    ]),

                // Account Settings & Appearance
                Section::make('Settings & Appearance')
                    ->description('Account customization and behavior settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(3)
                    ->schema([
                        Group::make([
                            ColorEntry::make('color')
                                ->label('Account Color'),

                            TextEntry::make('icon')
                                ->label('Account Icon')
                                ->formatStateUsing(
                                    fn(?string $state): string =>
                                    $state ? ucfirst(str_replace('-', ' ', $state)) : 'Default'
                                )
                                ->badge()
                                ->color('gray'),
                        ])->columnSpan(1),

                        Group::make([
                            IconEntry::make('is_active')
                                ->label('Account Status')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger'),

                            IconEntry::make('is_primary')
                                ->label('Primary Account')
                                ->boolean()
                                ->trueIcon('heroicon-o-star')
                                ->falseIcon('heroicon-o-star')
                                ->trueColor('warning')
                                ->falseColor('gray'),
                        ])->columnSpan(1),

                        Group::make([
                            IconEntry::make('include_in_net_worth')
                                ->label('Include in Net Worth')
                                ->boolean()
                                ->trueIcon('heroicon-o-presentation-chart-line')
                                ->falseIcon('heroicon-o-presentation-chart-line')
                                ->trueColor('success')
                                ->falseColor('gray'),

                            TextEntry::make('net_worth_contribution')
                                ->label('Net Worth Impact')
                                ->state(function ($record) {
                                    if (!$record->include_in_net_worth) return 'Not included';
                                    $contribution = $record->getNetWorthContribution();
                                    $symbol = CurrencyService::getCurrencySymbol($record->currency);
                                    return $symbol . number_format(abs($contribution), 2) .
                                        ($contribution >= 0 ? ' (Asset)' : ' (Liability)');
                                })
                                ->color(
                                    fn($record) =>
                                    !$record->include_in_net_worth ? 'gray' : ($record->getNetWorthContribution() >= 0 ? 'success' : 'danger')
                                ),
                        ])->columnSpan(1),
                    ]),

                // Timeline Section
                Section::make('Account Timeline')
                    ->description('Important dates in account history')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('opened_date')
                            ->label('Date Opened')
                            ->date()
                            ->placeholder('Not specified')
                            ->icon('heroicon-o-calendar-days'),

                        TextEntry::make('closed_date')
                            ->label('Date Closed')
                            ->date()
                            ->placeholder('Still active')
                            ->color('danger')
                            ->icon('heroicon-o-x-circle')
                            ->visible(fn($record) => !$record->is_active),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime()
                            ->color('gray')
                            ->icon('heroicon-o-plus-circle'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->color('gray')
                            ->since()
                            ->icon('heroicon-o-pencil-square'),
                    ]),

                // Account Owner Section
                Section::make('Account Owner')
                    ->description('Account ownership information')
                    ->icon('heroicon-o-user')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Account Holder')
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::SemiBold)
                            ->color('primary'),
                    ]),
            ]);
    }
}
