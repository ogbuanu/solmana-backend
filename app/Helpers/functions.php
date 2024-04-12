<?php

function toArray(object $data)
{
  return json_decode(json_encode($data), true);
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