<?php

// functions

function force_ssl_in_production() {
  // require ssl when in production
  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "http") {
    if (!headers_sent()) {
      header("Status: 301 Moved Permanently");
      header(sprintf(
          'Location: https://%s%s',
          $_SERVER['HTTP_HOST'],
          $_SERVER['REQUEST_URI']
      ));
      exit();
    }
  }
}

function force_hostname() {
  if (isset($_ENV['FORCE_HOSTNAME']) && $_SERVER['HTTP_HOST'] != $_ENV['FORCE_HOSTNAME']) {
    if (!headers_sent()) {
      header("Status: 301 Moved Permanently");
      header(sprintf(
          'Location: https://%s%s',
          $_ENV['FORCE_HOSTNAME'],
          $_SERVER['REQUEST_URI']
      ));
      exit();
    }
  }
}

function init_env() {
  if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
  }
}

use \TANIOS\Airtable\Airtable;

function get_url($str) {
  if (preg_match("/^https?:/", $str)) { return $str; }
  return "http://$str";
}

function get_scheme() {
  $scheme = 'http';
  if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
    $scheme = 'https';
  }
  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $scheme = 'https';
  }
  return $scheme;
}

function get_this_url($scheme=null) {
  if (!$scheme) { $scheme = get_scheme(); }
  return sprintf("%s://%s%s",
    $scheme,
    $_SERVER['SERVER_NAME'],
    $_SERVER['PHP_SELF']);
}

function get_voter_info($address, $zipcode) {
  $api_url = sprintf(
    'https://www.googleapis.com/civicinfo/v2/representatives?key=%s&address=%s&electionId=%s',
    $_ENV['GOOGLE_API_KEY'],
    urlencode("$address $zipcode"),
    $_ENV['GOOGLE_ELECTION_ID']
  );

//  error_log("api url: $api_url");

  $response = Requests::get($api_url, array('Content-Type' => 'application/json'));

//  error_log(var_export($response, true));
//  error_log(var_export($response->body, true));

  $voter_info = array();
  $resp = json_decode($response->body, true);

  foreach(array_keys($resp['divisions']) as $div) {
    if (preg_match("/ocd-division\/country:us\/state:ks\/sldu:(\w+)/", $div, $matches)) {
      $voter_info['sd'] = $matches[1];
    } elseif (preg_match("/ocd-division\/country:us\/state:ks\/sldl:(\w+)/", $div, $matches)) {
      $voter_info['hd'] = $matches[1];
    } elseif (preg_match("/ocd-division\/country:us\/state:ks\/cd:(\w+)/", $div, $matches)) {
      $voter_info['cd'] = $matches[1];
    }
  }

  return $voter_info;
}

// ?? create global var $REDIS_CLIENT
use RedisClient\RedisClient;
use RedisClient\Client\Version\RedisClient2x6;
use RedisClient\ClientFactory;

function redis_client() {
  $redis_url = str_replace("redis://", "", $_ENV['REDIS_URL']);
  if (preg_match("/^h:(.+?)@(.+)/", $redis_url, $matches)) {
    $redis = new RedisClient(['server' => $matches[2], 'timeout' => 2]);
    $redis->auth($matches[1]);
    return $redis;
  } else {
    return new RedisClient([ 'server' => $redis_url, 'timeout' => 2 ]);
  }
}

function get_candidate_questions() {
  $redis = redis_client();
  $questions = $redis->get('questions');
  if ($questions) {
    return json_decode($questions);
  }

  $airtable = new Airtable(array(
    'api_key' => $_ENV['AIRTABLE_API_KEY'],
    'base'    => $_ENV['AIRTABLE_BASE_ID']
  ));

  $request = $airtable->getContent('fields');

  $fields = $request->getResponse()['records'];
  $questions = array();
  foreach($fields as $f) {
    if (!$f->{'fields'} || !$f->{'fields'}->{'Name'}) {
      continue;
    }
    $questions[$f->{'fields'}->{'Name'}] = $f->{'fields'}->{'question'};
  }
  $redis->set('questions', json_encode($questions), 3600); // TTL 1 hour
  return $questions;
}

function get_candidate_info($senate_district, $house_district, $cong_district) {
  $airtable = new Airtable(array(
    'api_key' => $_ENV['AIRTABLE_API_KEY'],
    'base'    => $_ENV['AIRTABLE_BASE_ID']
  ));

  $filters = array(
    "State Representative District $house_district",
    "State Senate District $senate_district",
    "U.S. Representative District $cong_district",
  );

  $mapper = function($filter) { return "Race = '$filter'"; };

  $params = array(
    'filterByFormula' => sprintf("OR( %s )", implode(',', array_map($mapper, $filters))),
  );

  //error_log(var_export($params, true));

  $request = $airtable->getContent('candidates', $params);

  $records = $request->getResponse()['records'];

  return $records;
}

function get_sd() {
  if (preg_match("/^\d+$/", $_GET['sd'])) {
    return $_GET['sd'];
  }
}

function get_hd() {
  if (preg_match("/^\d+$/", $_GET['hd'])) {
    return $_GET['hd'];
  }
}

function get_cd() {
  if (preg_match("/^\d+$/", $_GET['cd'])) {
    return $_GET['cd'];
  }
}

function get_senate_candidates($profile) {
  $senate = array();
  foreach($profile as $candidate) {
    if ($candidate->{'fields'}->{'Chamber'} == 'State Senate') {
      array_push($senate, $candidate);
    }
  }
  return $senate;
}

function get_house_candidates($profile) {
  $house = array();
  foreach($profile as $candidate) {
    if ($candidate->{'fields'}->{'Chamber'} == 'State Representative') {
      array_push($house, $candidate);
    }
  }
  return $house;
}

function get_congressional_candidates($profile) {
  $cong = array();
  foreach($profile as $candidate) {
    if ($candidate->{'fields'}->{'Chamber'} == 'U.S. Representative') {
      array_push($cong, $candidate);
    }
  }
  return $cong;
}
