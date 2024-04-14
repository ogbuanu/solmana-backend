<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //

        public function userDetails(Request $request){

        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));
        $data = (object)$request->all();

          try {
            $userId = auth()->user()->id;
            $user = User::where('id', $userId)->with('actionPoint')->first();

            // ::with('customer', 'provider', 'service', 'bookingRating', 'bookingPostJob')->where('id', $id)->first();

            if ($user) {
              $response = $Response::set(["data"=> $user], true);
            } else  $response = $Response::set(["message" => "user record is empty"], false);
          } catch (\Throwable $th) {
             $response = $Response::set(["message" => "{$th->getMessage()}"], false);
          }
        return response()->json($response,  $response->code);
    }

}
