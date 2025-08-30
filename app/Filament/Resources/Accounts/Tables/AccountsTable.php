<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Models\Account;
use App\Services\CurrencyService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Account $record): string => $record->description ?? ''),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Account::TYPE_CHECKING_ACCOUNT, Account::TYPE_SAVINGS_ACCOUNT => 'success',
                        Account::TYPE_CREDIT_CARD => 'warning',
                        Account::TYPE_CASH => 'info',
                        Account::TYPE_DIGITAL_WALLET => 'purple',
                        Account::TYPE_INVESTMENT => 'indigo',
                        Account::TYPE_LOAN, Account::TYPE_MORTGAGE => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(
                        fn(string $state): string =>
                        Account::getAccountTypes()[$state] ?? $state
                    ),

                TextColumn::make('balance')
                    ->sortable()
                    ->alignEnd()
                    ->color(
                        fn(Account $record): string =>
                        $record->isDebtAccount() && $record->balance > 0 ? 'danger' : 'success'
                    )
                    ->formatStateUsing(function (Account $record): string {
                        $amount = $record->isDebtAccount() ? -$record->balance : $record->balance;
                        return CurrencyService::formatAmount($amount, $record->currency);
                    })
                    ->description(function (Account $record): ?string {
                        if ($record->currency === 'USD') {
                            return null;
                        }
                        $usdAmount = CurrencyService::convertAmount($record->balance, $record->currency, 'USD');
                        if ($usdAmount) {
                            $displayAmount = $record->isDebtAccount() ? -$usdAmount : $usdAmount;
                            return '≈ $' . number_format($displayAmount, 2) . ' USD';
                        }
                        return null;
                    }),

                TextColumn::make('currency')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'USD' => 'success',
                        'EUR' => 'info',
                        'GBP' => 'warning',
                        'JPY' => 'danger',
                        'BTC', 'ETH' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => $state)
                    ->toggleable(),

                TextColumn::make('bank_name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('account_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—')
                    ->formatStateUsing(
                        fn(?string $state): string =>
                        $state ? '****' . substr($state, -4) : '—'
                    ),

                TextColumn::make('wallet_provider')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->label('Active'),

                IconColumn::make('is_primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->label('Primary')
                    ->toggleable(),

                ColorColumn::make('color')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('opened_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Owner'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(Account::getAccountTypes())
                    ->multiple(),

                SelectFilter::make('currency')
                    ->options(CurrencyService::getTrendingCurrencies())
                    ->searchable()
                    ->multiple()
                    ->label('Currency'),

                SelectFilter::make('is_active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->label('Status'),

                SelectFilter::make('is_primary')
                    ->options([
                        1 => 'Primary Account',
                        0 => 'Regular Account',
                    ])
                    ->label('Primary'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
