<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ValidationController extends Controller
{
    private const AMEX_CODES = [
        34,
        37
    ];
    private const CVV_LEN = 3;
    private const AMEX_CVV_LEN = 4;

    public function validateData(Request $request): RedirectResponse
    {
        $request->validate([
            'card-number' => [
                'required',
                'numeric',
                'digits_between:16,19',
                'regex:/^[0-9]+$/',
                function($attribute, $value, $fail) use ($request) {
                    if (!$this->isValidCard($request->input('card-number'))) {
                        $fail("Invalid card number");
                    }
                },
            ],
            'expiration-date' => [
                'required',
                'date_format:m/y',
                'after:today',
            ],
            'security-code' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $cardNumber = $request->input('card-number');
                    $isAmex = Str::startsWith($cardNumber, self::AMEX_CODES);
                    $isValidLength = strlen($value) === ($isAmex ? self::AMEX_CVV_LEN : self::CVV_LEN);

                    if (!$isValidLength) {
                        $fail("The $attribute must be " . ($isAmex ? self::AMEX_CVV_LEN : self::CVV_LEN) . ' digits.');
                    }
                },
            ],
        ]);

        return Redirect::to('/')->with('successMessage', 'Thank you for your purchase!');
    }

    private function isValidCard(string $cardNumber):bool
    {
        $sum = 0;
        $numDigits = strlen($cardNumber);
        $pair = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = intval($cardNumber[$i]);

            if ($i % 2 === $pair) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
