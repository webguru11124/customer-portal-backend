<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customerID' => $this->faker->randomNumber(7, true),
            'paymentProfileID' => $this->faker->randomNumber(7, true),
            'description' => $this->faker->catchPhrase(),
            'billingName' => $this->faker->catchPhrase(),
            'billingAddress1' => $this->faker->address(),
            'billingCity' => $this->faker->city(),
            'billingState' => $this->faker->stateAbbr(),
            'billingZip' => $this->faker->postcode(),
            'officeID' => 12,
            'billingPhone' => $this->faker->numerify('##########'),
            'billingEmail' => $this->faker->unique()->safeEmail(),
            'lastFour' => $this->faker->numberBetween(1000, 9999),
            'bankName' => $this->faker->company(),
            'accountNumber' => $this->faker->numberBetween(1000, 9999999999),
            'routingNumber' => 021000021,
        ];
    }
}
