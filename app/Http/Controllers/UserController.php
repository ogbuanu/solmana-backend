<?php

namespace App\Http\Controllers;

use App\Models\ActionPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Http;
use Atymic\Twitter\Facade\Twitter;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Base64Url\Base64Url;
use Hash;
use Exception;

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
             $secretKey = config('services.blockpass.secret_key');
             $webhookData = $request->getContent();

         Log::info(json_encode($webhookData));
        
        $receivedSignature = $request->header('X-Signature');

          // $receivedSignature = $request->header('X-Hub-Signature');
        
        $expectedSignature = hash_hmac('sha256', $webhookData, $secretKey, true);
        $expectedSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
        
       
        if (hash_equals($expectedSignatureEncoded, $receivedSignature)) {
            Log::info('Blockpass webhook verified successfully.');

               $now =Carbon::now();

              if ($data->event === "user.approved") {
               User::where(['id'=>$data->refId, 'email'=>$data->email])->update(["kyc_verified"=>"TRUE",'kyc_verified_at'=> $now,"kyc_status"=>'APPROVED']);
                 $kycEarning =  ActionPoint::where(["user_id" => $data->refId])->first();

                 if ($kycEarning->last_kyc_earning === null) {
                     $kycEarning->last_kyc_earning = $now;
                     $kycEarning->balance = $kycEarning->balance +10;
                     $kycEarning->save();
                 }
              }
            Log::info('successfully.');
           
            if ($data->status === "user.inreview" || $data->status === "user.waiting") {
               User::where(['id'=>$data->refId, 'email'=>$data->email])->update(["kyc_verified"=>"FALSE",'kyc_verified_at'=> $now,"kyc_status"=>'PENDING']);   
            }

            if ($data->status === "review.rejected") {
               User::where(['id'=>$data->refId, 'email'=>$data->email])->update(["kyc_verified"=>"FALSE",'kyc_verified_at'=> $now,"kyc_status"=>'REJECTED']);   
            }





            // if ($data->status === "approved") {
            //    User::where(['id'=>$data->refId, 'email'=>$data->email])->update(["kyc_verified"=>"TRUE",'kyc_verified_at'=> $now,"kyc_status"=>'APPROVED']);
            //      $kycEarning =  ActionPoint::where(["user_id" => $data->refId])->first();

            //      if ($kycEarning->last_kyc_earning === null) {
            //          $kycEarning->last_kyc_earning = $now;
            //          $kycEarning->balance = $kycEarning->balance +10;
            //          $kycEarning->save();
            //      }
            // }
            // Log::info('successfully.');
           
            // if ($data->status === "inreview" || $data->status === "waiting") {
            //    User::where(['id'=>$data->refId, 'email'=>$data->email])->update(["kyc_verified"=>"FALSE",'kyc_verified_at'=> $now,"kyc_status"=>'PENDING']);   
            // }

            // if ($data->status === "rejected") {
            //    User::where(['id'=>$data->refId, 'email'=>$data->email])->update(["kyc_verified"=>"FALSE",'kyc_verified_at'=> $now,"kyc_status"=>'REJECTED']);   
            // }

              Log::info('Blockpass webhook verified successfully.');
              return response()->json(['message' => 'Webhook verified successfully'], 200);
           } else {
            // Signature verification failed, log an error
              Log::error('Blockpass webhook verification failed.');

            return response()->json(['error' => 'Webhook verification failed'], 403);
           }
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
             $key =  env('TWITTER_CONSUMER_KEY');

            $consumer_key = "WZbMssxskLYLqkq7GIl8Jhguq";
            $consumer_secret = "Wd4dXLQySBXZdYrLCZuaRBfdkqy0wRBPLgTerJ8geQrvaYDJtI";
            $access_token = "1076644233480683526-flIHl6J8M1MGbbGqR1J8hNvXfr84f8";
            $access_token_secret = "GbamxnoicZlcX0GekkUwWiOp5rNxQkT7trTVDGcf53H76";

            $tweetBearer="AAAAAAAAAAAAAAAAAAAAAB5WtQEAAAAAXK%2BqVYFW3guPKwe3jQX8QAc82kQ%3D60eXjtJtDebLVoEl70KfhERn0ijv48NFX9e26GTVi4Gv8aX8yk";
           
          try {
            $user = auth()->user();
            if (isset($data->tweet_link) && $data->tweet_link != null) {
               $parts = explode("/", $data->tweet_link);
               $tweetId = end($parts);

               $tweetId ="1780107798908440863";



        


               print_r($key);

            //   $connection = new TwitterOAuth(
            //     $twitterConsumerKey,
            //     $twitterConsumerSecret,
            //     $twitterAccessToken,
            //     $twitterAccessTokenSecret
            // );


                $connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);  

                  $connection->setApiVersion('2');

  
            $tweets = $connection->get("status/show", ['id' => $tweetId,]);


     print_r($tweets);
         dd($tweets);
     
                


    // https://api.twitter.com/2/tweets/$tweetId
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

  // 
              //  $respon = Http::withheaders(["authorization"=> "Bearer" . $tweetBearer])
              //   ->get("https://api.twitter.com/2/tweets/$tweetId");

              //            dd($respon);

            // if ($response->successful()) {
            //     $data = $response->json();
            //     return response()->json($data);
            // }

            // Handle specific error codes (e.g., 401, 403)
            // return $this->handleErrorResponse($response);



        //             $consumerKey = env('TWITTER_CONSUMER_KEY');
        // $consumerSecret = env('TWITTER_CONSUMER_SECRET');

        // Validate credentials (optional)
   

        // $encodedCredentials = base64_encode("$twitterConsumerKey:$twitterConsumerSecret");

   
        //     $respse = Http::withBasicAuth('', $encodedCredentials)
        //         ->get("https://api.twitter.com/2/tweets/$tweetId");

        //   dd($respse);

        

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


    public function kyc(Request $request){
       view("kyc");
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