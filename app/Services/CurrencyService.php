<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    private const CACHE_KEY = 'exchange_rates';
    private const CACHE_DURATION = 3600; // 1 hour

    /**
     * Get popular currency options grouped by region
     */
    public static function getCurrencyOptionsByRegion(): array
    {
        return [
            'Major Currencies' => [
                'USD' => 'USD - US Dollar ($)',
                'EUR' => 'EUR - Euro (€)',
                'GBP' => 'GBP - British Pound (£)',
                'JPY' => 'JPY - Japanese Yen (¥)',
                'CHF' => 'CHF - Swiss Franc (₣)',
                'CAD' => 'CAD - Canadian Dollar (C$)',
                'AUD' => 'AUD - Australian Dollar (A$)',
            ],
            'Asian Currencies' => [
                'CNY' => 'CNY - Chinese Yuan (¥)',
                'KRW' => 'KRW - South Korean Won (₩)',
                'SGD' => 'SGD - Singapore Dollar (S$)',
                'HKD' => 'HKD - Hong Kong Dollar (HK$)',
                'INR' => 'INR - Indian Rupee (₹)',
                'THB' => 'THB - Thai Baht (฿)',
                'MYR' => 'MYR - Malaysian Ringgit (RM)',
                'PHP' => 'PHP - Philippine Peso (₱)',
            ],
            'European Currencies' => [
                'SEK' => 'SEK - Swedish Krona (kr)',
                'NOK' => 'NOK - Norwegian Krone (kr)',
                'DKK' => 'DKK - Danish Krone (kr)',
                'PLN' => 'PLN - Polish Zloty (zł)',
                'CZK' => 'CZK - Czech Koruna (Kč)',
                'HUF' => 'HUF - Hungarian Forint (Ft)',
                'TRY' => 'TRY - Turkish Lira (₺)',
            ],
            'Middle East & Africa' => [
                'AED' => 'AED - UAE Dirham (د.إ)',
                'SAR' => 'SAR - Saudi Riyal (﷼)',
                'ILS' => 'ILS - Israeli Shekel (₪)',
                'ZAR' => 'ZAR - South African Rand (R)',
                'NGN' => 'NGN - Nigerian Naira (₦)',
                'EGP' => 'EGP - Egyptian Pound (£)',
            ],
            'Americas' => [
                'BRL' => 'BRL - Brazilian Real (R$)',
                'MXN' => 'MXN - Mexican Peso ($)',
                'ARS' => 'ARS - Argentine Peso ($)',
                'COP' => 'COP - Colombian Peso ($)',
                'CLP' => 'CLP - Chilean Peso ($)',
                'PEN' => 'PEN - Peruvian Sol (S/)',
            ],
            'Cryptocurrencies' => [
                'BTC' => 'BTC - Bitcoin (₿)',
                'ETH' => 'ETH - Ethereum (Ξ)',
                'USDT' => 'USDT - Tether ($)',
                'USDC' => 'USDC - USD Coin ($)',
            ],
        ];
    }

    /**
     * Get all currencies as flat array
     */
    public static function getAllCurrencies(): array
    {
        $grouped = self::getCurrencyOptionsByRegion();
        $flat = [];

        foreach ($grouped as $group => $currencies) {
            $flat = array_merge($flat, $currencies);
        }

        return $flat;
    }

    /**
     * Get currency symbol
     */
    public static function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => '₣',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'CNY' => '¥',
            'KRW' => '₩',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'INR' => '₹',
            'THB' => '฿',
            'MYR' => 'RM',
            'PHP' => '₱',
            'IDR' => 'Rp',
            'VND' => '₫',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            'ILS' => '₪',
            'TRY' => '₺',
            'ZAR' => 'R',
            'NGN' => '₦',
            'BRL' => 'R$',
            'MXN' => '$',
            'ARS' => '$',
            'COP' => '$',
            'CLP' => '$',
            'PEN' => 'S/',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
            'RUB' => '₽',
            'EGP' => '£',
            'BTC' => '₿',
            'ETH' => 'Ξ',
            'USDT' => '$',
            'USDC' => '$',
        ];

        return $symbols[$currency] ?? $currency;
    }

    /**
     * Get exchange rate from USD to target currency
     */
    public static function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Try to get cached rates
        $rates = Cache::get(self::CACHE_KEY);

        if (!$rates) {
            $rates = self::fetchExchangeRates();
            if ($rates) {
                Cache::put(self::CACHE_KEY, $rates, self::CACHE_DURATION);
            }
        }

        if (!$rates || !isset($rates[$toCurrency])) {
            return null;
        }

        // Convert from USD base
        if ($fromCurrency === 'USD') {
            return $rates[$toCurrency];
        }

        // Convert through USD
        if (!isset($rates[$fromCurrency])) {
            return null;
        }

        return $rates[$toCurrency] / $rates[$fromCurrency];
    }

    /**
     * Convert amount between currencies
     */
    public static function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = self::getExchangeRate($fromCurrency, $toCurrency);

        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    /**
     * Format amount with currency symbol
     */
    public static function formatAmount(float $amount, string $currency): string
    {
        $symbol = self::getCurrencySymbol($currency);

        // Different formatting for different currencies
        switch ($currency) {
            case 'JPY':
            case 'KRW':
            case 'VND':
                return $symbol . number_format($amount, 0);
            case 'BTC':
                return $symbol . number_format($amount, 8);
            case 'ETH':
                return $symbol . number_format($amount, 6);
            default:
                return $symbol . number_format($amount, 2);
        }
    }

    /**
     * Fetch current exchange rates from external API
     * Using exchangerate-api.com (free tier: 1500 requests/month)
     */
    private static function fetchExchangeRates(): ?array
    {
        try {
            // You can get a free API key from https://exchangerate-api.com/
            $apiKey = env('EXCHANGE_RATE_API_KEY');

            if (!$apiKey) {
                // Return mock rates for development
                return self::getMockExchangeRates();
            }

            $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD");

            if ($response->successful()) {
                $data = $response->json();
                return $data['conversion_rates'] ?? null;
            }

            Log::warning('Failed to fetch exchange rates: ' . $response->status());
            return self::getMockExchangeRates();
        } catch (\Exception $e) {
            Log::error('Exchange rate API error: ' . $e->getMessage());
            return self::getMockExchangeRates();
        }
    }

    /**
     * Mock exchange rates for development/fallback
     */
    private static function getMockExchangeRates(): array
    {
        return [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'JPY' => 110.0,
            'CHF' => 0.92,
            'CAD' => 1.25,
            'AUD' => 1.35,
            'CNY' => 6.45,
            'KRW' => 1180.0,
            'SGD' => 1.35,
            'HKD' => 7.8,
            'INR' => 74.5,
            'THB' => 33.2,
            'MYR' => 4.15,
            'PHP' => 50.2,
            'AED' => 3.67,
            'SAR' => 3.75,
            'ILS' => 3.25,
            'TRY' => 8.75,
            'ZAR' => 14.8,
            'NGN' => 411.0,
            'BRL' => 5.2,
            'MXN' => 20.1,
            'ARS' => 98.5,
            'COP' => 3800.0,
            'CLP' => 800.0,
            'PEN' => 3.9,
            'PLN' => 3.9,
            'CZK' => 21.5,
            'HUF' => 295.0,
            'SEK' => 8.6,
            'NOK' => 8.9,
            'DKK' => 6.4,
        ];
    }

    /**
     * Get trending currencies (most used)
     */
    public static function getTrendingCurrencies(): array
    {
        return [
            'USD' => 'USD - US Dollar ($)',
            'EUR' => 'EUR - Euro (€)',
            'GBP' => 'GBP - British Pound (£)',
            'JPY' => 'JPY - Japanese Yen (¥)',
            'CAD' => 'CAD - Canadian Dollar (C$)',
            'AUD' => 'AUD - Australian Dollar (A$)',
            'CHF' => 'CHF - Swiss Franc (₣)',
            'CNY' => 'CNY - Chinese Yuan (¥)',
        ];
    }
}
