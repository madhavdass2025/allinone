<?php
class TaxService {
    public static function calculateTax($qty, $price, $discount = 0, $gstRate = 0, $sourceState = '', $destState = '') {
        $taxableAmount = ($qty * $price) - $discount;
        $totalTax = $taxableAmount * ($gstRate / 100);

        $cgst = 0;
        $sgst = 0;
        $igst = 0;

        $isInterState = (!empty($sourceState) && !empty($destState) && $sourceState !== $destState);

        if ($isInterState) {
            $igst = $totalTax;
        } else {
            $cgst = $totalTax / 2;
            $sgst = $totalTax / 2;
        }

        return [
            'taxable_amount' => $taxableAmount,
            'total_tax' => $totalTax,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total_amount' => $taxableAmount + $totalTax
        ];
    }
}
?>
