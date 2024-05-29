<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
    * @OA\Get(
    *     path="/api/country/index",
    *     summary="Get list of countries",
    *     tags={"Countries"},
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="value", type="integer", description="Country ID"),
    *                 @OA\Property(property="label", type="string", description="Country Name"),
    *                 @OA\Property(property="phonecode", type="integer", description="Country Phone Code")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Something went wrong",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function index()
    {
        $country = Country::select('id as value', 'name as label', 'phonecode')->where('status', 1)->where('trash', 1)->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * @OA\Get(
    *     path="/api/country/getcountrylist",
    *     summary="Get list of countries",
    *     tags={"Countries"},
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer", description="Country ID"),
    *                 @OA\Property(property="name", type="string", description="Country Name"),
    *                 @OA\Property(property="iso", type="string", description="ISO"),
    *                 @OA\Property(property="iso3", type="string", description="ISO3"),
    *                 @OA\Property(property="nicename", type="string", description="Nice Name"),
    *                 @OA\Property(property="numcode", type="integer", description="Num Code"),
    *                 @OA\Property(property="phonecode", type="integer", description="Phone Code")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Something went wrong",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function list()
    {
        $country = Country::select('id', 'name', 'iso', 'iso3', 'nicename', 'numcode', 'phonecode')->orderBy('name', 'asc')->get()->toArray();
        if ($country !== null) {
            $return = $country;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}

?>
