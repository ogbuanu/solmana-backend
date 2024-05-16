<?php

namespace App\Http\Controllers;

use App\Models\ActionPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\SocialAction;
use App\Models\TweetAction;
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

  public function userDetails(Request $request)
  {

    $Response = new Response();
    $response = $Response::get();


    try {
      $userId = auth()->user()->id;
      $user = User::where('id', $userId)->with('actionPoint')->first();

      $number_referral = User::where("referred_by", $user->referral_code)->count();
      $social_action = SocialAction::where("user_id", $user->id)->where("status", "APPROVED")->first();
      $user['number_referral'] = $number_referral;
      $user['daily_tweet_pending'] = $user['actionPoint']['is_pending'] == "TRUE" ? true : false;
      $user['has_followed_socials'] = $social_action ? true : false;

      if ($user) {
        $response = $Response::set(["data" => $user], true);
      } else  $response = $Response::set(["message" => "user record is empty"], false);
    } catch (\Throwable $th) {
      $response = $Response::set(["message" => "{$th->getMessage()}"], false);
    }
    return response()->json($response,  $response->code);
  }

  public function kycDailyUpdate()
  {
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
          $hoursDifference = Carbon::now()->diffInHours($lastKycEarning, true);

          // for testing
          //  $passed = $minutesDifference > 1;
          $passed = $hoursDifference > 24;
          print_r("passed: $passed\n");
          if ($userData["kyc_verified"] == 'TRUE' && $userData["kyc_status"] == 'APPROVED' && $passed) {
            ActionPoint::where('user_id', $userData['id'])->update([
              'balance' => \DB::raw('balance + 50'),
              'last_kyc_earning' => Carbon::now(),
            ]);
          }
        }
      }
      $response = $Response::set(["message" => "kyc Daily Update rain successfully"], true);
    } catch (\Throwable $th) {
      $response = $Response::set(["message" => "{$th->getMessage()}"], false);
    }
    return response()->json($response,  $response->code);
  }

  public function userkyc(Request $request)
  {

    $Response = new Response();
    $response = $Response::get();
    $data = (object) $request->all();

    try {
      $websecretKey = config('services.blockpass.webhook_secret_key');
      $webhookData = $request->getContent();
      Log::info("webhookData", $webhookData);

      // $receivedSignature = $request->header('X-Signature');

      $receivedSignature = $request->header('X-Hub-Signature');
      Log::info("receivedSignature", $receivedSignature);

      $expectedSignature = hash_hmac('sha256', $webhookData, $websecretKey, true);
      Log::info("expectedSignature", $expectedSignature);

      $expectedSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));

      Log::info("expectedSignatureEncoded", $expectedSignatureEncoded);

      Log::info("hash_equals", hash_equals($expectedSignatureEncoded, $receivedSignature));


      if (hash_equals($expectedSignatureEncoded, $receivedSignature)) {
        Log::info('Blockpass webhook verified successfully.');

        $now = Carbon::now();

        if ($data->event === "user.approved") {
          User::where(['id' => $data->refId, 'email' => $data->email])->update(["kyc_verified" => "TRUE", 'kyc_verified_at' => $now, "kyc_status" => 'APPROVED']);
          $kycEarning =  ActionPoint::where(["user_id" => $data->refId])->first();

          if ($kycEarning->last_kyc_earning === null) {
            $kycEarning->last_kyc_earning = $now;
            $kycEarning->balance = $kycEarning->balance + 10;
            $kycEarning->save();
          }
        }
        Log::info('successfully.');

        if ($data->status === "user.inreview" || $data->status === "user.waiting") {
          User::where(['id' => $data->refId, 'email' => $data->email])->update(["kyc_verified" => "FALSE", 'kyc_verified_at' => $now, "kyc_status" => 'PENDING']);
        }

        if ($data->status === "review.rejected") {
          User::where(['id' => $data->refId, 'email' => $data->email])->update(["kyc_verified" => "FALSE", 'kyc_verified_at' => $now, "kyc_status" => 'REJECTED']);
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
    } catch (\Exception $e) {
      $response = $Response::set(["message" => "{$e->getMessage()}"], false);
    }
    return response()->json($response,  $response->code);
  }

  public function verifyTweet(Request $request)
  {
    $Response = new Response();
    $response = $Response::get();
    $data = (object) $request->all();

    try {
      $user = auth()->user();
      if (isset($data->tweet_link) && $data->tweet_link != null) {

        TweetAction::create(["user_id" => $user->id, "tweet_link" => $data->tweet_link]);
        ActionPoint::where(["user_id" => $user->id])->update(["is_pending" => "TRUE"]);
        $response = $Response::set(["message" => "Tweet has been posted!"], true);
        // } else $response = $Response::set(["message" => $res?->message], false);
      } else $response = $Response::set(["message" => "tweet link is required"], false);
    } catch (\Throwable $th) {
      $response = $Response::set(["message" => "{$th->getMessage()}"], false);
    } catch (\Exception $e) {
      $response = $Response::set(["message" => "{$e->getMessage()}"], false);
    }
    return response()->json($response,  $response->code);
  }

  public function verifySocialFollow(Request $request)
  {
    $Response = new Response();
    $response = $Response::get();
    $data = (object) $request->all();

    try {
      $user = auth()->user();
      if (isset($data->proof_img) && $data->proof_img != null) {

        SocialAction::create(["user_id" => $user->id, "proof_img" => $data->proof_img]);
        $response = $Response::set(["message" => "Task submitted successfully"], true);
        // } else $response = $Response::set(["message" => $res?->message], false);
      } else $response = $Response::set(["message" => "img proof is required"], false);
    } catch (\Throwable $th) {
      $response = $Response::set(["message" => "{$th->getMessage()}"], false);
    } catch (\Exception $e) {
      $response = $Response::set(["message" => "{$e->getMessage()}"], false);
    }
    return response()->json($response,  $response->code);
  }

  public function kyc(Request $request)
  {
    view("kyc");
  }


  public function addWalletAddress(Request $request)
  {
    $Response = new Response();
    $response = $Response::get();

    $data = (object) $request->all();

    $userId = auth()->user()->id;

    $trimmedString = Str::trim($data->wallet_address);
    $AddressLen =  Str::length($trimmedString);

    if ($AddressLen > 31) {
      $user = User::where('id', $userId)->with('actionPoint')->first();

      if (empty($user["actionPoint"]['wallet_address'])) {
        ActionPoint::where("user_id", $userId)->update(["wallet_address" => $data->wallet_address, 'balance' => \DB::raw('balance + 60')]);
        $response = $Response::set(["message" => "User's wallet address added successfully"], true);
      } else {
        $response = $Response::set(["message" => "User wallet address is already added"], true);
      }
    } else {
      $response = $Response::set(["message" => "Invalid address"], false);
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
