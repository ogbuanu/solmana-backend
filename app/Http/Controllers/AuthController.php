<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActionPoint;
use App\Models\TaskLogs;
use App\Models\TokenVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
        public $response;
    static $appStatus;
    public function __construct()
    {
        self::$appStatus = object(config("variables.approvalStatus"));
        $this->response = object(config("responseCode"));
    }

    public function generateReferralCode($length = 14) {
        $referralCode = "ref_" . random($length);

        $referralCodeExist = User::where("referral_code", $referralCode)->exists();

        if ($referralCodeExist) {
            return $this->generateReferralCode($length);
        }

        return $referralCode;
    }



    private function createForgotPasswordToken(string $email, string $tokenType, string $code) {

     $tokenFor = object(config('variables.tokenFor'));
     $TokenExpiresInMinutes = config('variables.TokenExpiresInMinutes');

     $tokenData = [
        'email' => $email,
        'token_for' => $tokenFor->passwordReset,
        'expires_at' => Carbon::now()->addMinutes($TokenExpiresInMinutes),
     ];

     $token = TokenVerification::where(['email' => $email,'token_for' => $tokenType,'status' => "NOTUSED"])->first();
     if ($token) {
        $difference = Carbon::parse($token->expires_at)->diffInMinutes(Carbon::now());
        if ($difference > 0) {
            return $token;
        }
     }

     $token = TokenVerification::where($tokenData);

     return $token;
   }

    public function login(Request $request, bool $internal = false)
    {

        $Response = new Response();
        $response = $Response::get();

        if(isset($request->login_type)){
            if ($request->login_type !== "" && $request->login_type == "twitter") {
               $request->merge(['password' => config('variables.defaultPassword')]);
            }
           unset($request->login_type);
        }

        $fields = array_extract($request->toArray(), ["email", "password"]);
        // Check required fields
        $isFilled = isRequired($fields);
        if ($isFilled === true) {

            $credentials = request(array_keys($fields));
            $user = User::where("email", "=", $credentials["email"])->first();
            if (auth()->attempt($credentials)) {
            
             $token = $user->createToken('auth_token')->plainTextToken;
                
                $response->status = true;
                $response->message = "Successful";
                $response->code = $this->response->success;
                $user = auth()->user();
                $response->data = ["user" => $user, "auth" => $this->respondWithToken($token)];
            } else {
                $response->message = "Incorrect email or password";
                $response->code = $this->response->unauthorized;
            }
        } else $response->message = "Required fields are empty";

        if ($internal) return $response->data;
        else  return response()->json($response,  $response->code);
    }

    public function register(Request $request)
    {
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));

        $required = ["email", "password","name"];
        if(isset($request->register_type)){
            if ($request->register_type !== "" && $request->register_type == "twitter") {
               $request->merge(['password' => config('variables.defaultPassword')]);
            }
           unset($request->register_type);
        };

        $fields = object(array_extract($request->toArray(), $required, true));
        $data = (object) $request->all();
        // Check required fields
        $isFilled = isRequired($fields);
        if ($isFilled === true) {

            try {
                // Validate email
                if (filter_var($fields->email, FILTER_VALIDATE_EMAIL)) {

                    //  validate passowrd
                    $isValidPassword = $this->validatePassword($fields->password);
                    if ($isValidPassword === true) {
                        // Check if user already exists
                        if (!User::where("email", $fields->email)->exists()) {
                            try {

                                $data->referral_code =  self::generateReferralCode();

                                     if(isset($data->referred_by) && $data->referred_by !== ""){
                                        $referralCodeIsValid = User::where("referral_code", $data->referred_by)->first();
                                        if ($referralCodeIsValid) {
                                          $referral = ActionPoint::where("user_id", $referralCodeIsValid->id)->first();
                                          $referral->balance += 1;  
                                          $referral->last_referral = Carbon::now();  
                                          $referral->save();
                                        }else{
                                          $data->referred_by = null;
                                        };
                                     }

                                // hash password
                                $data->password = app("hash")->make($data->password);

                                $creator = User::create(toArray($data));

                                ActionPoint::create(["user_id" => $creator->id]);

                                // Send Mails
                                $mailer = new MailController;
                           
                                $creator_name = $creator->name;

                                $now = Carbon::now();
                        
                                 $TokenExpiresInMinutes = config('variables.TokenExpiresInMinutes');
                                 $TokenExpires = $now->addMinutes($TokenExpiresInMinutes);
                                

                                // Send the creator an email verification link if his email is still unverified
                                $token = TokenVerification::create([
                                 "token_for" => $tokenFor->emailVerification,
                                 "email" =>  $creator->email,
                                 "expires_at" =>  $TokenExpires
                                ]);

                                $creator_email_link = env("APP_LINK") . "/verify-token/{$token->id}";

                                $response->mail = $mailer->sendMail(
                                    object(['subject' => "Email Verification", 'from' => env("APP_EMAIL"), 'to' => $creator->email, 'from_name' =>  env("APP_NAME"), 'to_name' => $creator_name, 'template' => 'verify', 'link' =>$creator_email_link])
                                );



                                       Log::info(json_encode($response->mail));

                                $data = $this->login($request, true);

                                $response = $Response::set(["message" => "Registration successful", "data" =>  $data], true);
                            } catch (\Exception $th) {
                                                throw $th;
                                $response->message = "An error occured, contact support";
                            }
                        } else $response->message = "User already exists, try resetting password instead";
                    } else $response->message = $isValidPassword;
                } else $response->message = "Invalid email type";
            } catch (\Throwable $th) {
                throw $th;
                $response = $Response::set(["message" => "{$th->getMessage()}"], false);
            }
        } else $response->message = "Required fields are empty";

        return response()->json($response,  $response->code);
    }

    public function requestEmail(Request $request){
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));

        $now = Carbon::now();            
        $TokenExpiresInMinutes = config('variables.TokenExpiresInMinutes');
        $TokenExpires = $now->addMinutes($TokenExpiresInMinutes);

            try {
                $user = auth()->user();
                if ($user->email_verified_at == null) {
               $TokenVerifi = TokenVerification::where(['email' => $user->email,'token_for' => $tokenFor->emailVerification])->first();

                 $TokenVerifi->expires_at = $TokenExpires;
                 $TokenVerifi->save();

                   $mailer = new MailController;
                    
                    $creator_email_link = env("APP_LINK") . "/verify-token/{$TokenVerifi->id}";

                     $response->mail = $mailer->sendMail(
                    object(['subject' => "Email Verification", 'from' => env("APP_EMAIL"), 'to' => $user->email, 'from_name' =>  env("APP_NAME"), 'to_name' => $user, 'template' => 'verify', 'link' =>$creator_email_link])
                    );

                          Log::info(json_encode($response->mail));


              $response = $Response::set(["message" => "Email verification link has been sent to your email address"], true);
            } else $response->message = "Email is already verified";
            } catch (\Throwable $th) {
                //throw $th;
                $response = $Response::set(["message" => "{$th->getMessage()}"], false);
            }
        return response()->json($response,  $response->code);
    }

    public function verifyEmail(Request $request){
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));

        $required = ["id", "email"];
        $fields = object(array_extract($request->toArray(), $required, true));
        $data = (object) $request->all();
        // Check required fields
        $isFilled = isRequired($fields);
        if ($isFilled === true) {
            try {
                // Validate email
             if (filter_var($fields->email, FILTER_VALIDATE_EMAIL)) {    
                // Check if user already exists
                $tokenVerifi = TokenVerification::where([
                'email' => $data->email,
                'id' => $data->id,
                'token_for' => $tokenFor->emailVerification
                ])->first();

                $currentDateTime = Carbon::now();

                 $tokenExpiration = Carbon::parse($tokenVerifi->expires_at);
                 $tokenHasExpired = Carbon::now()->gt($tokenExpiration);

                if ($tokenVerifi && $tokenHasExpired) {
                  $tokenVerifi->status = "USED";
                  $tokenVerifi->save();
                   User::where('email', $data->email)->update(['email_verified_at' =>  $currentDateTime ]);

                  $response = $Response::set(["message" => "Email verified successfully", "data" =>  $data], true);
                } else  $response->message = "Invalid email verification link";
             } else $response->message = "Invalid email type";
            } catch (\Throwable $th) {
                $response = $Response::set(["message" => "{$th->getMessage()}"], false);
            }
        } else $response->message = "Required fields are empty";
        return response()->json($response,  $response->code);
    }

    public function resetPassword(Request $request)
    {
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));

        $required = ["id", "email", 'password'];
        $fields = object(array_extract($request->toArray(), $required, true));
        $data = (object) $request->all();
        // Check required fields
        $isFilled = isRequired($fields);
        if ($isFilled === true) {
            try {
                // Validate email
             if (filter_var($fields->email, FILTER_VALIDATE_EMAIL)) {    
                // Check if user already exists
                $tokenVerifi = TokenVerification::where([
                'email' => $data->email,
                'id' => $data->id,
                'token_for' => $tokenFor->passwordReset
                ])->first();

                 $tokenExpiration = Carbon::parse($tokenVerifi->expires_at);
                 $tokenHasExpired = Carbon::now()->gt($tokenExpiration);

                if ($tokenVerifi && $tokenHasExpired) {

                     $tokenVerifi->status ="USED";
                     $tokenVerifi->save();

                    $data->password = app("hash")->make($data->password);

                   User::where('email', $data->email)->update(['password' => $data->password ]);

                  $response = $Response::set(["message" => "Email verified successfully", "data" =>  $data], true);
                } else  $response->message = "Invalid email verification link";
             } else $response->message = "Invalid email type";
            } catch (\Throwable $th) {
                //  throw $th;
                $response = $Response::set(["message" => "{$th->getMessage()}"], false);
            }
        } else $response->message = "Required fields are empty";
        return response()->json($response,  $response->code);
    }

    public function forgotPassword(Request $request){
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));
        $data = (object)$request->all();

        $required = ["email"];
        $fields = object(array_extract($request->toArray(), $required, true));
        // Check required fields
        $isFilled = isRequired($fields);

     if ($isFilled) {
        $now = Carbon::now();            
        $TokenExpiresInMinutes = config('variables.TokenExpiresInMinutes');
        $TokenExpires = $now->addMinutes($TokenExpiresInMinutes);
            try {

             $user = User::where(['email' =>  $data->email])->first();

                if($user){

                $TokenVerifi = TokenVerification::where(['email' => $data->email,'token_for' => $tokenFor->passwordReset])->first();

                 if ( $TokenVerifi) {
                     $TokenVerifi->expires_at = $TokenExpires;
                     $TokenVerifi->save();
                 } else {
                    $TokenVerifi = TokenVerification::create(["email" => $data->email,"token_for" => $tokenFor->passwordReset,"expires_at" =>  $TokenExpires]);
                 }

                     $mailer = new MailController;
                     $reset_password_link = env("APP_LINK") . "/reset-password/{$TokenVerifi->id}";

                     $response->mail = $mailer->sendMail(
                    object(['subject' => "RESET PASSWORD", 'from' => env("APP_EMAIL"), 'to' => $data->email, 'from_name' =>  env("APP_NAME"), 'to_name' => $user, 'template' => 'verify', 'link' =>$reset_password_link])
                    );

                          Log::info(json_encode($response->mail));


                   $response = $Response::set(["message" => "Reset password link has been sent to your email address"], true);
                }else  $response = $Response::set(["message" => "invalid email address"], false);
            } catch (\Throwable $th) {
                //throw $th;
                $response = $Response::set(["message" => "{$th->getMessage()}"], false);
            }
       } else  $response = $Response::set(["message" => "email is required"], false);
        return response()->json($response,  $response->code);
    }

    public function logout(Request $request)
    {
        auth()->logout(true);
        return response()->json(["status" => true, "message" => "Logout Successful"]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return object([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 60 * 60 * 2 //2 hrs
        ]);
    }

    protected function validatePassword(string $password)
    {
        if (strlen($password) < 6) return "Password must be up to 6 characters";
        return true;
    }
}
