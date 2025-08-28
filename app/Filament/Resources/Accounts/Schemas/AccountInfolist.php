<?php

namespace App\Filament\Resources\Accounts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name'),
                TextEntry::make('name'),
                TextEntry::make('type'),
                TextEntry::make('account_number'),
                TextEntry::make('bank_name'),
                TextEntry::make('bank_branch'),
                TextEntry::make('routing_number'),
                TextEntry::make('swift_code'),
                TextEntry::make('wallet_provider'),
                TextEntry::make('wallet_id'),
                TextEntry::make('balance')
                    ->numeric(),
                TextEntry::make('credit_limit')
                    ->numeric(),
                TextEntry::make('minimum_balance')
                    ->numeric(),
                TextEntry::make('currency'),
                TextEntry::make('interest_rate')
                    ->numeric(),
                TextEntry::make('monthly_fee')
                    ->numeric(),
                TextEntry::make('overdraft_fee')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                IconEntry::make('is_primary')
                    ->boolean(),
                IconEntry::make('include_in_net_worth')
                    ->boolean(),
                TextEntry::make('color'),
                TextEntry::make('icon'),
                TextEntry::make('opened_date')
                    ->date(),
                TextEntry::make('closed_date')
                    ->date(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
