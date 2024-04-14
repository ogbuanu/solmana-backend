<?php

function toArray(object $data)
{
  return json_decode(json_encode($data), true);
}

function see($data)
{
  echo "<pre>";
  if (in_array(gettype($data), ["object", "array"])) {
    print_r($data);
  } else die($data);
  die();
}

function object(array $data): object
{
  return (object)$data;
}


// Generates token string
function random($l = 8)
{
  return substr(md5(uniqid(mt_rand(), true)), 0, $l);
}


//Extract values of specific keys from an array
function array_extract(array|object $main_array, array $extr_keys, bool $strict_extraction = true, bool $associate_keys = true)
{
  $return = [];
  if (gettype($main_array) === "object") $main_array = toArray($main_array);
  foreach ($extr_keys as $key) {
    if (isset($main_array[$key])) {
      if ($associate_keys) {
        $return[$key] = $main_array[$key];
      } else array_push($return, $main_array[$key]);
    } elseif ($strict_extraction) return [];
  }
  return $return;
}

function isRequired(array|object|null $data = []): bool
{
  $response = [];
  if ($data === null) return false;
  if (gettype($data) === "object") $data = toArray($data);
  if (empty($data)) return false;
  foreach ($data as $k => $v) {
    if (empty($v)) array_push($response, $k);
  }
  return !count($response);
}