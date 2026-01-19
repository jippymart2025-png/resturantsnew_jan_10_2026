<?php

namespace App\Services;

class PricingCalculationService
{
    const GST_PERCENTAGE = 5; // 5% GST

    /**
     * Calculate online price based on merchant price, subscription type, and GST agreement
     *
     * @param float $merchantPrice Base merchant price
     * @param bool $hasSubscription Whether vendor has active subscription
     * @param float $commissionPercentage Commission percentage (dynamically fetched from commission plan)
     * @param bool $gstAgreed Whether merchant agreed to GST (gst = 1)
     * @param string $planType Subscription plan type ('commission' or 'subscription')
     * @return array ['onlinePrice' => float, 'settlement' => float, 'calculation' => string]
     */
    public static function calculatePrice(
        float $merchantPrice,
        bool $hasSubscription = false,
        float $commissionPercentage = 0,
        bool $gstAgreed = false,
        string $planType = 'commission'
    ): array {
        $gstPercentage = self::GST_PERCENTAGE;
        $onlinePrice = 0;
        $settlement = 0;
        $calculation = [];

        // Determine if it's commission-based or subscription-based
        $isCommissionBased = !$hasSubscription || $planType === 'commission';

        if ($isCommissionBased) {
            // Scenario 1: Commission-Based Model (Commission percentage from commission plan)
            $commission = $merchantPrice * ($commissionPercentage / 100);
            $priceBeforeGst = $merchantPrice + $commission;

            if ($gstAgreed) {
                // Case 1: Merchant AGREED for GST (gst = 1)
                // Online Price = Merchant Price + Commission (GST absorbed by platform)
                $onlinePrice = $priceBeforeGst;
                // Settlement = Merchant Price - 5% GST
                $settlement = $merchantPrice - ($merchantPrice * ($gstPercentage / 100));

                $calculation = [
                    'merchantPrice' => $merchantPrice,
                    'commission' => $commission,
                    'commissionPercentage' => $commissionPercentage,
                    'priceBeforeGst' => $priceBeforeGst,
                    'gstAbsorbed' => true,
                    'onlinePrice' => $onlinePrice,
                    'settlement' => $settlement,
                ];
            } else {
                // Case 2: Merchant NOT AGREED for GST (gst = 0)
                // GST is 5% of Merchant Price (not of price before GST)
                $gstAmount = $merchantPrice * ($gstPercentage / 100);
                // Online Price = (Merchant Price + Commission) + GST (5% of Merchant Price)
                $onlinePrice = $priceBeforeGst + $gstAmount;
                // Settlement = Merchant Price (full amount)
                $settlement = $merchantPrice;

                $calculation = [
                    'merchantPrice' => $merchantPrice,
                    'commission' => $commission,
                    'commissionPercentage' => $commissionPercentage,
                    'priceBeforeGst' => $priceBeforeGst,
                    'gstAdded' => $gstAmount,
                    'gstPercentage' => $gstPercentage,
                    'onlinePrice' => $onlinePrice,
                    'settlement' => $settlement,
                ];
            }
        } else {
            // Scenario 2: Subscription-Based Model (No Commission in online price)
            // IMPORTANT: For subscription plans, $commissionPercentage is actually the place percentage
            $subscriptionPlacePercentage = $commissionPercentage;

            if ($gstAgreed) {
                // Case 1: Merchant AGREED for GST (gst = 1)
                // Online Price = Merchant Price (GST absorbed by platform)
                $onlinePrice = $merchantPrice;
                // Settlement = Merchant Price - 5% GST - subscription place percentage
                $gstDeduction = $merchantPrice * ($gstPercentage / 100);
                $subscriptionDeduction = $merchantPrice * ($subscriptionPlacePercentage / 100);
                $settlement = $merchantPrice - $gstDeduction - $subscriptionDeduction;

                $calculation = [
                    'merchantPrice' => $merchantPrice,
                    'gstAbsorbed' => true,
                    'gstDeduction' => $gstDeduction,
                    'subscriptionPlacePercentage' => $subscriptionPlacePercentage,
                    'subscriptionDeduction' => $subscriptionDeduction,
                    'onlinePrice' => $onlinePrice,
                    'settlement' => $settlement,
                ];
            } else {
                // Case 2: Merchant NOT AGREED for GST (gst = 0)
                // Online Price = Merchant Price + 5% GST
                $onlinePrice = $merchantPrice + ($merchantPrice * ($gstPercentage / 100));
                // Settlement = Merchant Price - subscription place percentage ONLY (no GST deduction)
                $subscriptionDeduction = $merchantPrice * ($subscriptionPlacePercentage / 100);
                $settlement = $merchantPrice - $subscriptionDeduction;

                $calculation = [
                    'merchantPrice' => $merchantPrice,
                    'gstAdded' => $merchantPrice * ($gstPercentage / 100),
                    'gstPercentage' => $gstPercentage,
                    'subscriptionPlacePercentage' => $subscriptionPlacePercentage,
                    'subscriptionDeduction' => $subscriptionDeduction,
                    'onlinePrice' => $onlinePrice,
                    'settlement' => $settlement,
                ];
            }
        }

        return [
            'onlinePrice' => round($onlinePrice, 2),
            'settlement' => round($settlement, 2),
            'calculation' => $calculation,
        ];
    }

    /**
     * Get formatted calculation text for display
     *
     * @param array $result Result from calculatePrice()
     * @param string $currencySymbol Currency symbol (default ₹)
     * @return string Formatted calculation text
     */
    public static function getCalculationText(array $result, string $currencySymbol = '₹'): string
    {
        $calc = $result['calculation'];
        $text = "Pricing Calculation:\n";
        $text .= "Merchant Price: {$currencySymbol}" . number_format($calc['merchantPrice'], 2) . "\n";

        if (isset($calc['commission'])) {
            $text .= "Subscription Type: Commission ({$calc['commissionPercentage']}%)\n";
            $text .= "Commission: {$currencySymbol}" . number_format($calc['commission'], 2) . " (added to customer price)\n";
        } else {
            $text .= "Subscription Type: Subscription (No Commission)\n";
        }

        $text .= "GST Agreement: " . (isset($calc['gstAbsorbed']) && $calc['gstAbsorbed'] ? "Yes" : "No") . "\n";

        if (isset($calc['gstAdded'])) {
            $text .= "GST ({$calc['gstPercentage']}%): {$currencySymbol}" . number_format($calc['gstAdded'], 2) . " (added to customer price)\n";
        } elseif (isset($calc['gstAbsorbed']) && $calc['gstAbsorbed']) {
            $text .= "GST (5%) absorbed by platform\n";
        }

        // Show subscription deduction only for subscription plans (not commission plans)
        if (isset($calc['subscriptionPlacePercentage']) && $calc['subscriptionPlacePercentage'] > 0) {
            $text .= "Subscription Plan Commission ({$calc['subscriptionPlacePercentage']}%): -{$currencySymbol}" . number_format($calc['subscriptionDeduction'], 2) . "\n";
        }

        $text .= "Online Price: {$currencySymbol}" . number_format($result['onlinePrice'], 2) . "\n";
        $text .= "Final Settlement to Merchant: {$currencySymbol}" . number_format($result['settlement'], 2);

        return $text;
    }
}

