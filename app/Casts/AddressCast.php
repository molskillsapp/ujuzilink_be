<?php
/*
 * File name: AddressCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\Address;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Class AddressCast
 * @package App\Casts
 */
class AddressCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): Address
    {
        $decodedValue = json_decode($value, true);
        $address = Address::find($decodedValue['id']);
        if (!empty($address)) {
            return $address;
        }
        $address = new Address($decodedValue);
        $address->fillable[] = 'id';
        $address->id = $decodedValue['id'];
        return $address;
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
//        if (!$value instanceof AddressModel) {
//            throw new InvalidArgumentException('The given value is not an Address instance.');
//        }

        return ['address' => json_encode([
            'id' => $value['id'],
            'description' => $value['description'],
            'address' => $value['address'],
            'latitude' => $value['latitude'],
            'longitude' => $value['longitude'],
        ])];
    }
}
