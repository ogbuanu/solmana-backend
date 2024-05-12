<?php

namespace App\Http\Controllers;

use App\Models\SocialAction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function fetchTweetActions()
    {

        //
        $Response = new Response();
        $response = $Response::get();
        $data = [];
        try {
            $data['pending_tweet_action'] = TweetAction::where('status', "PENDING")->get();
            $data['approved_tweet_action'] = TweetAction::where('status', " APPROVED")->get();
            $data['rejected_tweet_action'] = TweetAction::where('status', "REJECTED")->get();
            $response = $Response::set(["data" => $data], true);
        } catch (\Throwable $th) {
            //throw $th;
            $response = $Response::set(["message" => "{$th->getMessage()}"], false);
        }
        return response()->json($response,  $response->code);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function TweetAction(Request $request)
    {
        //
        $Response = new Response();
        $response = $Response::get();
        $data = (object) $request->all();
        $approvalStatus = config('variables.approvalStatus');
        try {
            if (isset($data->action) && $data->action != null && isset($data->id) && $data->id != null) {
                $tweet = TweetAction::find($data->id);
                if ($tweet) {
                    $tweet->status = $data->action == "APPROVE" ? $approvalStatus->approved : $approvalStatus->rejected;
                    $tweet->save();
                    $response = $Response::set(["message" => "Action has been {$data->action} successfully"], true);
                }
            } else $response = $Response::set(["message" => "Action is required"], false);
        } catch (\Throwable $th) {
            //throw $th;
            $response = $Response::set(["message" => "{$th->getMessage()}"], false);
        }
        return response()->json($response,  $response->code);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function fetchSocialActions(Request $request)
    {
        //
        $Response = new Response();
        $response = $Response::get();
        $data = [];
        try {
            $data['pending_social_action'] = SocialAction::where('status', "PENDING")->get();
            $data['approved_social_action'] = SocialAction::where('status', " APPROVED")->get();
            $data['rejected_social_action'] = SocialAction::where('status', "REJECTED")->get();
            $response = $Response::set(["data" => $data], true);
        } catch (\Throwable $th) {
            //throw $th;
            $response = $Response::set(["message" => "{$th->getMessage()}"], false);
        }
        return response()->json($response,  $response->code);
    }

    /**
     * Display the specified resource.
     */
    public function socialAction(Request $request)
    {
        //
        //
        $Response = new Response();
        $response = $Response::get();
        $data = (object) $request->all();
        $approvalStatus = config('variables.approvalStatus');
        try {
            if (isset($data->action) && $data->action != null && isset($data->id) && $data->id != null) {
                $social = SocialAction::find($data->id);
                if ($social) {
                    $social->status = $data->action == "APPROVE" ? $approvalStatus->approved : $approvalStatus->rejected;
                    $social->save();
                    $response = $Response::set(["message" => "Action has been {$data->action} successfully"], true);
                }
            } else $response = $Response::set(["message" => "Action is required"], false);
        } catch (\Throwable $th) {
            //throw $th;
            $response = $Response::set(["message" => "{$th->getMessage()}"], false);
        }
        return response()->json($response,  $response->code);
    }
}
