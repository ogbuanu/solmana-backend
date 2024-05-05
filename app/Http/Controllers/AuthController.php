<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\NotifyMail;
use App\Mail\PasswordMail;
use App\Mail\VerifyMail;
use Illuminate\Http\Request;
use App\Models\ActionPoint;
use App\Models\TaskLogs;
use App\Models\TokenVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\env;

class AuthController extends Controller
{
    public $response;
    static $appStatus;
    public function __construct()
    {
        self::$appStatus = object(config("variables.approvalStatus"));
        $this->response = object(config("responseCode"));
    }

    public function generateReferralCode($length = 14)
    {
        $referralCode = "ref_" . random($length);

        $referralCodeExist = User::where("referral_code", $referralCode)->exists();

        if ($referralCodeExist) {
            return $this->generateReferralCode($length);
        }

        return $referralCode;
    }



    private function createForgotPasswordToken(string $email, string $tokenType, string $code)
    {

        $tokenFor = object(config('variables.tokenFor'));
        $TokenExpiresInMinutes = config('variables.TokenExpiresInMinutes');

        $tokenData = [
            'email' => $email,
            'token_for' => $tokenFor->passwordReset,
            'expires_at' => Carbon::now()->addMinutes($TokenExpiresInMinutes),
        ];

        $token = TokenVerification::where(['email' => $email, 'token_for' => $tokenType, 'status' => "NOTUSED"])->first();
        if ($token) {
            $difference = Carbon::parse($token->expires_at)->diffInMinutes(Carbon::now());
            if ($difference > 0) {
                return $token;
            }
        }

        $token = TokenVerification::where($tokenData);

        return $token;
    }

    public function login(LoginRequest $request, bool $internal = false)
    {

        $Response = new Response();
        $response = $Response::get();

        if (isset($request->login_type)) {
            if ($request->login_type !== "" && $request->login_type == "twitter") {
                $request->merge(['password' => config('variables.defaultPassword')]);
            }
            unset($request->login_type);
        }

        if ($request->password == "" && !isset($request->login_type)) {
            $response = $Response::set(["message" => "password is required"], true);
        }

        $fields = array_extract($request->toArray(), ["email", "password"]);

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

        if ($internal) return $response->data;
        else  return response()->json($response,  $response->code);
    }

    public function register(RegisterRequest $request)
    {
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));

        if (isset($request->register_type)) {
            if ($request->register_type !== "" && $request->register_type == "twitter") {
                $request->merge(['password' => config('variables.defaultPassword')]);
            }

            if ($request->register_type !== "" && $request->register_type !== "twitter") {
                $request->merge(['password' => config('variables.defaultPassword')]);
            }
            unset($request->register_type);
        };

        $fields = (object)  $request->all();
        $data = (object) $request->all();

        try {
            // Check if user already exists
            if (!User::where("email", $fields->email)->exists()) {
                try {

                    $data->referral_code =  self::generateReferralCode();

                    if (isset($data->referred_by) && $data->referred_by !== "") {
                        $referralCodeIsValid = User::where("referral_code", $data->referred_by)->first();
                        if ($referralCodeIsValid) {
                            $referral = ActionPoint::where("user_id", $referralCodeIsValid->id)->first();
                            $referral->balance += 10;
                            $referral->last_referral = Carbon::now();
                            $referral->save();
                        } else {
                            $data->referred_by = null;
                        };
                    }

                    // hash password
                    $data->password = app("hash")->make($data->password);

                    $creator = User::create(toArray($data));

                    ActionPoint::create(["user_id" => $creator->id]);

                    // Send Mails

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

                    $creator_email_link = config('app.app_link') . "/verify-token/{$token->id}";


                    $details = [
                        'subject' => "Email Verification",
                        'from' => env("APP_EMAIL"), 'to' => $creator->email,
                        'from_name' =>  env("APP_NAME"), 'to_name' => $creator_name,
                        'template' => 'verify',
                        'link' => $creator_email_link
                    ];

                    $response->mail = Mail::to($creator->email)->send(
                        new VerifyMail($details)
                    );

                    Log::info(json_encode($response->mail));

                    // unset($request->register_type);
                    // unset($request->referred_by);
                    // unset($request->name);

                    $request = new LoginRequest();
                    $data = $this->login($request, true);

                    log::info(json_encode($data));
                    $response = $Response::set(["message" => "Registration successful", "data" =>  $data], true);
                } catch (\Exception $th) {
                    throw $th;
                    $response->message = "An error occured, contact support";
                }
            } else $response->message = "User already exists, try resetting password instead";
        } catch (\Throwable $th) {
            throw $th;
            $response = $Response::set(["message" => "{$th->getMessage()}"], false);
        }


        return response()->json($response,  $response->code);
    }

    public function requestEmail(Request $request)
    {
        $Response = new Response();
        $response = $Response::get();
        $tokenFor = object(config('variables.tokenFor'));

        $now = Carbon::now();
        $TokenExpiresInMinutes = config('variables.TokenExpiresInMinutes');
        $TokenExpires = $now->addMinutes($TokenExpiresInMinutes);

        try {
            $user = auth()->user();
            if ($user->email_verified_at == null) {
                $TokenVerifi = TokenVerification::where(['email' => $user->email, 'token_for' => $tokenFor->emailVerification])->first();

                $TokenVerifi->expires_at = $TokenExpires;
                $TokenVerifi->save();

                $creator_email_link = config('app.app_link') . "/verify-token/{$TokenVerifi->id}";
                $details = ['subject' => "Email Verification", 'from' => env("APP_EMAIL"), 'to' => $user->email, 'from_name' =>  env("APP_NAME"), 'to_name' => $user->name, 'template' => 'verify', 'link' => $creator_email_link];

                $response->mail   = Mail::to($user->email)->send(
                    new VerifyMail($details)
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

    public function verifyEmail(Request $request)
    {
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
                        User::where('email', $data->email)->update(['email_verified_at' =>  $currentDateTime]);

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

                        $tokenVerifi->status = "USED";
                        $tokenVerifi->save();

                        $data->password = app("hash")->make($data->password);

                        User::where('email', $data->email)->update(['password' => $data->password]);

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

    public function forgotPassword(Request $request)
    {
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

                if ($user) {

                    $TokenVerifi = TokenVerification::where(['email' => $data->email, 'token_for' => $tokenFor->passwordReset])->first();

                    if ($TokenVerifi) {
                        $TokenVerifi->expires_at = $TokenExpires;
                        $TokenVerifi->save();
                    } else {
                        $TokenVerifi = TokenVerification::create(["email" => $data->email, "token_for" => $tokenFor->passwordReset, "expires_at" =>  $TokenExpires]);
                    }
                    $reset_password_link = config('app.app_link') . "/reset-password/{$TokenVerifi->id}";

                    $details = ['subject' => "RESET PASSWORD", 'from' => env("APP_EMAIL"), 'to' => $data->email, 'from_name' =>  env("APP_NAME"), 'to_name' => $user->name, 'template' => 'verify', 'link' => $reset_password_link];

                    $response->mail  = Mail::to($user->email)->send(
                        new PasswordMail($details)
                    );
                    Log::info(json_encode($response->mail));
                    $response = $Response::set(["message" => "Reset password link has been sent to your email address"], true);
                } else  $response = $Response::set(["message" => "invalid email address"], false);
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
