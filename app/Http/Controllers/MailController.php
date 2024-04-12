<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class MailController extends Controller
{
  public function request()
  {
    return $this->sendMail(object(request()->all()), true);
  }

  public function sendMail($data, $includeHeaders = false)
  {

    // parameter validation
    $required_params = ['subject', 'from', 'to', 'from_name', 'to_name', 'template'];
    $components = ["vendor" => ['contract_id', 'link'], "creator" => ['project_id', 'link'], "verify" => ['link'], "notify" => ["body"]];
    if (isset($components[$data->template])) $required_params = array_merge($required_params, $components[$data->template]);

    $build["host"] = env("APP_LINK");
    $build = array_merge($build, toArray($data));
    foreach ($required_params as $field) {
      if (!isset($data->{$field})) {
        return ("$field field is not defined for sending email parameters");
      }
    }

    try {
      \Illuminate\Support\Facades\Mail::send("email.{$data->template}", $build, function (\Illuminate\Mail\Message $message) use ($data) {
        $message->to($data->to, $data->to_name);
        $message->from($data->from, $data->from_name);
        $message->subject($data->subject);

        if (!empty($data->pdf)) {
          $message->attachData($data->pdf['pdf']->output(), $data->pdf['name'], [
            'mime' => 'application/pdf'
          ]);
        }
      });
      return $includeHeaders ? response()->json(["status" => true, "message" => "Email Sent"]) : object(["status" => true, "message" => "Email Sent"]);
    } catch (\Throwable $th) {
      return response()->json(["status" => false, "message" => $th->getMessage()]);
    }
  }
}
