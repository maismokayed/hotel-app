<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Http\Resources\CityResource;

class CityController extends Controller
{
    public function index()
    {
        return CityResource::collection(City::with('media')->get());
    }
}
