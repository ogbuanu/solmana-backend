<?php

namespace App\Http\Controllers;

use App\Models\ActionPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{

    public function userDetails(Request $request){

        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));
        $data = (object)$request->all();

          try {
            $userId = auth()->user()->id;
            $user = User::where('id', $userId)->with('actionPoint')->first();

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

     public function userkyc(Request $request){
        $Response = new Response();
        $response = $Response::get();
          $data = (object)$request->all();
          try {

            dd($data);
   
          } catch (\Throwable $th) {
             $response = $Response::set(["message" => "{$th->getMessage()}"], false);
          }catch (\Exception $e) {
             $response = $Response::set(["message" => "{$e->getMessage()}"], false);
          }
        return response()->json($response,  $response->code);
    }

    public function verifyTweet(Request $request){
        $Response = new Response();
        $response = $Response::get();
        $data = (object)$request->all();

            $twitterConsumerKey = "WZbMssxskLYLqkq7GIl8Jhguq";
            $twitterConsumerSecret = "Wd4dXLQySBXZdYrLCZuaRBfdkqy0wRBPLgTerJ8geQrvaYDJtI";
            $twitterAccessToken = "1076644233480683526-flIHl6J8M1MGbbGqR1J8hNvXfr84f8";
            $twitterAccessTokenSecret = "GbamxnoicZlcX0GekkUwWiOp5rNxQkT7trTVDGcf53H76";

            $tweetBearer="AAAAAAAAAAAAAAAAAAAAAB5WtQEAAAAAXK%2BqVYFW3guPKwe3jQX8QAc82kQ%3D60eXjtJtDebLVoEl70KfhERn0ijv48NFX9e26GTVi4Gv8aX8yk";
           
          try {
            $user = auth()->user();
            if (isset($data->tweet_link) && $data->tweet_link != null) {
               $parts = explode("/", $data->tweet_link);
               $tweetId = end($parts);

              //  $tweetId ="1780107798908440863";

            //   $connection = new TwitterOAuth(
            //     $twitterConsumerKey,
            //     $twitterConsumerSecret,
            //     $twitterAccessToken,
            //     $twitterAccessTokenSecret
            // );

            // $response = $connection->get("statuses/show", [
            //     'id' => $tweetId,
            // ]);

            // dd($response);

            // if (isset($response->errors)) {
            //     // Handle errors returned by Twitter API
            //     return response()->json([
            //         'message' => 'Failed to retrieve tweet: ' . $response->errors[0]->message,
            //     ], $response->http_code);
            // }

            // return response()->json($response);
          

               $response = Http::withToken($tweetBearer)
                ->get("https://api.twitter.com/2/tweets/$tweetId");

                         dd($response);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            }

            // Handle specific error codes (e.g., 401, 403)
            // return $this->handleErrorResponse($response);


   

        

              //  if ($response->ok()) {
                  //  ActionPoint::where('user_id',$user->id)->update([
                  //  'balance' => \DB::raw('balance + 10'),
                  //  ]);
                  // $data =  $res;
                $response = $Response::set(["message" => "kyc Daily Update rain successfully"],true);
              // } else $response = $Response::set(["message" => $res?->message], false);
            } else $response = $Response::set(["message" => "tweet link is required"], false);
          } catch (\Throwable $th) {
             $response = $Response::set(["message" => "{$th->getMessage()}"], false);
          }catch (\Exception $e) {
             $response = $Response::set(["message" => "{$e->getMessage()}"], false);
          }
        return response()->json($response,  $response->code);
    }

}

              //  $retrieveTweetUrl = "https://api.twitter.com/1.1/statuses/show.json?id=210462857140252672";



              //  $retrieveTweetUrlMain=  str_replace(':id', $tweetId, $retrieveTweetUrl);
     
              //  $Secret  = "Bearer" . " " . env('TWITTER_API_KEY');
        
              //   $response = Http::withHeaders(["Authorization" => $Secret])->get($retrieveTweetUrlMain);
              //   $res = $response->json();
             
              //  if ($response->ok()) {

              //      ActionPoint::where('user_id',$user->id)->update([
              //      'balance' => \DB::raw('balance + 10'),
              //      ]);

              //     $data =  $res;