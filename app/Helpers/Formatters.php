<?php

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount): string
    {
        $decimal = (string)($amount - floor($amount));
        $money = floor($amount);
        $length = strlen($money);
        $delimiter = '';
        $money = strrev($money);

        for ($i = 0; $i < $length; $i++) {
            if (($i == 3 || ($i > 3 && ($i - 1) % 2 == 0)) && $i != $length) {
                $delimiter .= ',';
            }
            $delimiter .= $money[$i];
        }

        $result = strrev($delimiter);
        $decimal = preg_replace("/0\./i", '.', $decimal);
        $decimal = substr($decimal, 0, 3);

        if ($decimal != '0') {
            $result .= $decimal;
        } else {
            $result .= ".00";
        }

        return $result;
    }
}
