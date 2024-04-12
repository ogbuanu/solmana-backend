<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    //
    public $response;
    static $appStatus;
    public function __construct()
    {
        self::$appStatus = object(config("variables.approvalStatus"));
        $this->response = object(config("responseCode"));
    }

    public function login(Request $request, bool $internal = false)
    {

        $Response = new Response();
        $response = $Response::get();
        $fields = array_extract($request->toArray(), ["email", "password"]);
        // Check required fields
        $isFilled = isRequired($fields);
        if ($isFilled === true) {

            $credentials = request(array_keys($fields));
            $user = User::where("email", "=", $credentials["email"])->first();
            if ($token = auth()->attempt($credentials)) {
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

        $required = ["email", "password",];
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
                                // hash password
                                $data->password = app("hash")->make($data->password);


                                $creator = User::create(toArray($data));

                                // Send Mails
                                $mailer = new MailController;
                                $creator->full_name = ucwords(implode(" ", [$creator->title, $creator->first_name, $creator->last_name]));
                                $creator_name = $creator->username;

                                // Send the creator an email verification link if his email is still unverified
                                $token = TokenVerification::create(["user_id" => $creator->id, "tokenFor" => "EMAIL_VERIFICATION"]);
                                $creator_email_link = env("APP_LINK") . "/verify-token/{$token->id}";
                                $response->mail = $mailer->sendMail(
                                    object(['subject' => "Email Verification", 'from' => env("APP_EMAIL"), 'to' => $creator->email, 'from_name' =>  env("APP_NAME"), 'to_name' => $creator_name, 'template' => 'verify', 'link' => $creator_email_link])
                                );


                                $data = $this->login($request, true);

                                $response = $Response::set(["message" => "Registration successful", "data" =>  $data], true);
                            } catch (\Exception $th) {
                                Errorlog::create(["action" => "Registration", "tracer" => $data->email, "description" => $th->getMessage()]);
                                $response->message = "An error occured, contact support";
                            }
                            $required = ["email", "phone", "password", "account_type", "business_name", "country", "industry", "tel_code"];
                            $data->password = app("hash")->make($data->password);
                            $fields = object(array_extract($data, $required, true));

                            $account = User::where("email", $fields->email)->update(toArray($fields));
                            $account = User::where("email", $fields->email)->first();
                            $account->business_ref = $account->id;
                            // $account->password = app("hash")->make($fields->password);
                            $account->email_verified = self::$appStatus->approved;
                            $account->phone_verified = self::$appStatus->approved;
                            $account->email_verified_at = date("Y-m-d H:i:s");
                            $account->phone_verified_at = date("Y-m-d H:i:s");
                            $account->save();

                            $data = $this->login($request, true);

                            $response = $Response::set(["message" => "Registration successful", "data" =>  $data], true);
                        } else $response->message = "User already exists, try resetting password instead";
                    } else $response->message = $isValidPassword;
                } else $response->message = "Invalid email type";
            } catch (\Throwable $th) {
                //throw $th;
                $response = $Response::set(["message" => "{$th->getMessage()}"], false);
            }
        } else $response->message = "Required fields are empty";

        return response()->json($response,  $response->code);
    }

    public function resetPassword(Request $request)
    {
        $Response = new Response();
        $response = $Response::get();

        $required = ["password", "password2"];
        $fields = object(array_extract($request->toArray(), $required, true));

        // Check required fields
        $isFilled = isRequired($fields);
        if ($isFilled === true) {

            $user = auth()->user();

            //  validate passowrd
            $isValidPassword = $this->validatePassword($fields->password);
            // return response()->json($user);
            if (!empty($user->id)) {
                if ($fields->password === $fields->password2) {
                    if ($isValidPassword === true) {

                        try {
                            // hash password
                            $fields->password = app("hash")->make($fields->password);
                            $userType = object(config('variables.userType'));
                            $appStatus = object(config('variables.approvalStatus'));

                            $data = ["password" => $fields->password, "type" => $userType->registered];
                            if ($user->email_verified !== $appStatus->approved) {
                                $data["email_verified"] = $appStatus->approved;
                                $data["phone_verified"] = $appStatus->approved;
                                $data["email_verified_at"] = date("Y-m-d H:i:s");
                                $data["phone_verified_at"] = date("Y-m-d H:i:s");
                            }

                            $User = User::find($user->id);
                            foreach ($data as $key => $value) {
                                $User->{$key} = $value;
                            }
                            $User->save();
                            auth()->logout(true);
                            $response = $Response::set(["Successfuly Updated"], true);
                        } catch (\Exception $th) {
                            Errorlog::create(["action" => "Registration", "tracer" => $user->email, "description" => $th->getMessage()]);
                            $response->message = $th->getMessage();
                        }
                    } else $response = $Response::set(["message" => $isValidPassword]);
                } else $response = $Response::set(["message" => "Password does not match"]);
            } else $response = $Response::set(["message" => "Unauthorized Access", "data" => $user, "code" => "unauthorized"]);
        } else $response = $Response::set(["message" => "Required fields are empty"]);

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
