<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountTypes = array_keys(Account::getAccountTypes());
        $type = $this->faker->randomElement($accountTypes);

        return [
            'user_id' => User::factory(),
            'name' => $this->getAccountName($type),
            'description' => $this->faker->optional()->sentence(),
            'type' => $type,
            'account_number' => $this->faker->optional()->bankAccountNumber(),
            'bank_name' => $this->faker->optional()->company(),
            'bank_branch' => $this->faker->optional()->city(),
            'routing_number' => $this->faker->optional()->numerify('#########'),
            'swift_code' => $this->faker->optional()->regexify('[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}[A-Z0-9]{3}'),
            'wallet_provider' => $type === Account::TYPE_DIGITAL_WALLET
                ? $this->faker->randomElement(['PayPal', 'Apple Pay', 'Google Pay', 'Venmo', 'Cash App'])
                : null,
            'wallet_id' => $type === Account::TYPE_DIGITAL_WALLET
                ? $this->faker->email()
                : null,
            'balance' => $this->getAccountBalance($type),
            'credit_limit' => $type === Account::TYPE_CREDIT_CARD
                ? $this->faker->randomFloat(2, 1000, 50000)
                : null,
            'minimum_balance' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'currency' => 'USD',
            'interest_rate' => $this->faker->optional()->randomFloat(4, 0.01, 0.15),
            'monthly_fee' => $this->faker->optional()->randomFloat(2, 0, 25),
            'overdraft_fee' => $this->faker->optional()->randomFloat(2, 25, 40),
            'is_active' => $this->faker->boolean(90),
            'is_primary' => false,
            'include_in_net_worth' => true,
            'color' => $this->faker->optional()->hexColor(),
            'icon' => $this->getAccountIcon($type),
            'opened_date' => $this->faker->optional()->dateTimeBetween('-5 years', 'now'),
            'closed_date' => null,
            'metadata' => null,
        ];
    }

    private function getAccountName(string $type): string
    {
        return match ($type) {
            Account::TYPE_BANK_ACCOUNT => $this->faker->company() . ' Bank Account',
            Account::TYPE_SAVINGS_ACCOUNT => $this->faker->company() . ' Savings',
            Account::TYPE_CHECKING_ACCOUNT => $this->faker->company() . ' Checking',
            Account::TYPE_CREDIT_CARD => $this->faker->company() . ' Credit Card',
            Account::TYPE_CASH => 'Cash Wallet',
            Account::TYPE_DIGITAL_WALLET => $this->faker->randomElement(['PayPal', 'Apple Pay', 'Google Pay']) . ' Wallet',
            Account::TYPE_INVESTMENT => $this->faker->company() . ' Investment',
            Account::TYPE_LOAN => $this->faker->company() . ' Loan',
            Account::TYPE_MORTGAGE => 'Home Mortgage',
            default => $this->faker->company() . ' Account',
        };
    }

    private function getAccountBalance(string $type): float
    {
        return match ($type) {
            Account::TYPE_CASH => $this->faker->randomFloat(2, 50, 2000),
            Account::TYPE_DIGITAL_WALLET => $this->faker->randomFloat(2, 10, 1000),
            Account::TYPE_SAVINGS_ACCOUNT => $this->faker->randomFloat(2, 1000, 50000),
            Account::TYPE_CHECKING_ACCOUNT => $this->faker->randomFloat(2, 100, 10000),
            Account::TYPE_INVESTMENT => $this->faker->randomFloat(2, 5000, 100000),
            Account::TYPE_CREDIT_CARD => $this->faker->randomFloat(2, 0, 5000),
            Account::TYPE_LOAN, Account::TYPE_MORTGAGE => $this->faker->randomFloat(2, 5000, 300000),
            default => $this->faker->randomFloat(2, 100, 10000),
        };
    }

    private function getAccountIcon(string $type): ?string
    {
        return match ($type) {
            Account::TYPE_BANK_ACCOUNT, Account::TYPE_SAVINGS_ACCOUNT, Account::TYPE_CHECKING_ACCOUNT => 'bank',
            Account::TYPE_CREDIT_CARD => 'credit-card',
            Account::TYPE_CASH => 'cash',
            Account::TYPE_DIGITAL_WALLET => 'device-mobile',
            Account::TYPE_INVESTMENT => 'chart-line',
            Account::TYPE_LOAN, Account::TYPE_MORTGAGE => 'home',
            default => 'wallet',
        };
    }

    public function primary(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
            'closed_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Account::TYPE_CASH,
            'name' => 'Cash Wallet',
            'balance' => $this->faker->randomFloat(2, 50, 2000),
        ]);
    }

    public function bankAccount(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Account::TYPE_BANK_ACCOUNT,
            'account_number' => $this->faker->bankAccountNumber(),
            'bank_name' => $this->faker->company() . ' Bank',
            'routing_number' => $this->faker->numerify('#########'),
        ]);
    }

    public function digitalWallet(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Account::TYPE_DIGITAL_WALLET,
            'wallet_provider' => $this->faker->randomElement(['PayPal', 'Apple Pay', 'Google Pay', 'Venmo']),
            'wallet_id' => $this->faker->email(),
        ]);
    }
}
