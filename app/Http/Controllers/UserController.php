<?php

namespace App\Http\Controllers;

use App\Models\ActionPoint;
use App\Models\User;
use Carbon\Carbon;
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

    public function kycDailyUpdate(){
        $Response = new Response();
        $response = $Response::get();
          try {
            $users = User::with('actionPoint')->get();
      foreach ($users  as $userData) {
        // Check if 'action_point' is set and not null
         if (isset($userData['actionPoint']) && $userData['actionPoint'] !== null) {
           $lastKycEarning =  $userData['actionPoint']["last_kyc_earning"];
                 // for testing
                //  $minutesDifference = Carbon::now()->diffInMinutes($lastKycEarning,true);
                    $hoursDifference = Carbon::now()->diffInHours($lastKycEarning,true);

                 // for testing
                //  $passed = $minutesDifference > 1;
                 $passed = $hoursDifference > 24;
                 print_r("passed: $passed\n");
            if ( $userData["kyc_verified"] == 'TRUE' && $userData["kyc_status"] == 'APPROVED' && $passed) {
              ActionPoint::where('user_id', $userData['id'])->update([
                   'balance' => \DB::raw('balance + 50'),
                   'last_kyc_earning' => Carbon::now(),
             ]);
           }
        }
      }
          $response = $Response::set(["message" => "kyc Daily Update rain successfully"],true);
   
          } catch (\Throwable $th) {
             $response = $Response::set(["message" => "{$th->getMessage()}"], false);
          }
        return response()->json($response,  $response->code);
    }

     public function userkyc(){
        $Response = new Response();
        $response = $Response::get();
          try {
     
     
          $response = $Response::set(["message" => "kyc Daily Update rain successfully"],true);
   
          } catch (\Throwable $th) {
             $response = $Response::set(["message" => "{$th->getMessage()}"], false);
          }
        return response()->json($response,  $response->code);
    }

}

