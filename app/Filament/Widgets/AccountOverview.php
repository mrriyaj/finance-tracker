<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Services\CurrencyService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAccounts = Account::count();
        $activeAccounts = Account::where('is_active', true)->count();
        $uniqueCurrencies = Account::distinct('currency')->count('currency');

        // Calculate total balance in USD equivalent
        $totalBalanceUSD = 0;
        $accounts = Account::where('include_in_net_worth', true)->get();

        foreach ($accounts as $account) {
            $usdAmount = CurrencyService::convertAmount($account->balance, $account->currency, 'USD');
            if ($usdAmount !== null) {
                $totalBalanceUSD += $account->isDebtAccount() ? -$usdAmount : $usdAmount;
            }
        }

        $creditCardDebt = Account::where('type', Account::TYPE_CREDIT_CARD)->sum('balance');

        return [
            Stat::make('Total Accounts', $totalAccounts)
                ->description('All accounts in the system')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Active Accounts', $activeAccounts)
                ->description('Currently active accounts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Net Worth (USD)', '$' . number_format($totalBalanceUSD, 2))
                ->description('Combined balance across all currencies')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalBalanceUSD >= 0 ? 'success' : 'danger'),

            Stat::make('Currencies Used', $uniqueCurrencies)
                ->description('Different currencies in accounts')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning'),
        ];
    }
}
