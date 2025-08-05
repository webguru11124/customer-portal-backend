<?php

namespace Database\Factories;

use App\Enums\Models\TransactionSetupStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionSetupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => Str::random(6),
            'account_number' => Str::random(8),
            'status' => TransactionSetupStatus::INITIATED,
            'auto_pay' => 0,
        ];
    }

    public function initiated()
    {
        return $this->state(function (array $attributes) {
            return [
                'transaction_setup_id' => Str::random(24),
            ];
        });
    }

    public function generated()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => TransactionSetupStatus::GENERATED,
                'transaction_setup_id' => Str::random(24),
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => TransactionSetupStatus::COMPLETE,
            ];
        });
    }

    public function withAddress()
    {
        return $this->state(function (array $attributes) {
            return [
                'billing_name' => $this->faker->name(),
                'billing_address_line_1' => $this->faker->streetAddress(),
                'billing_address_line_2' => $this->faker->buildingNumber(),
                'billing_city' => $this->faker->city(),
                'billing_state' => 'FL',
                'billing_zip' => substr($this->faker->postcode(), 0, 5),
            ];
        });
    }
}
