#!/usr/bin/php
<?php 
/**
 * divote.php - a voting script for Digitally Imported
 *
 * @author     Remco Brink <remco@rc6.org>
 * @link       https://github.com/nilicule/code/blob/master/difm/divote.php
 */

// Get commandline parameters
$options = getopt("c:v:sqh");

// Parse commandline parameters
if (!isset($options['c']) || (!isset($options['v']) && !isset($options['s'])) || isset($options['h'])) {
  echo "\nUsage:\n\n";
  echo "  To vote for a channel:\n";
  echo "     ".$argv[0]." -c <channel> -v <up/down>\n";
  echo "\n  To show the currently playing track for a channel:\n";
  echo "     ".$argv[0]." -c <channel> -s\n\n";
  exit;
} else {
  // Get the parameters
  if (isset($options['q'])) {
    $quietmode = true;
  } else {
    $quietmode = false;
  }

  if (isset($options['s'])) {
    // Just show what's playing now
    $show = true;
  } else {
    // We're voting
    $show = false;

    // We're going to actually vote
    $vote       = strtolower($options['v']);

    // Make sure we either vote up or down
    if ($vote != 'down' && $vote != 'up') {
      echo "Vote needs to be either up or down\n";
      exit;
    }
  }

  // The channel
  $channelkey = strtolower($options['c']);
}

// Read configuration
$config = readConfiguration();

// Get channel ID
$channel = getChannel($channelkey);

// Get recently played tracks
$json_playlist = getRecentTracks($channel['id']);

// Get latest track
$track = getCurrentTrack($json_playlist);
if ($show) {
  $feedback = sprintf('np: %s - %s (%s/%s) [DI %s]', $track['artist'], $track['title'], $track['playing'], $track['length'], $channel['name']);
  echo "$feedback\n";
  exit;
}

// Do the vote
$track['votes'] = doVote($config['api_key'], $track['id'], $channel['id'], $vote);

// Tell the user what we did
if (!$quietmode) {
  $feedback = sprintf('Voting %s %s - %s (%s/%s) [votes: +%d -%d] [DI %s]', $vote, $track['artist'], $track['title'], $track['playing'], $track['length'], $track['votes']['up'], $track['votes']['down'], $channel['name']);
  echo "$feedback\n";
}


///////////////////////////////

function doVote($api_key, $track_id, $channel_id, $vote) {
  $time = time();
  $callback = 'jQuery'.$time.$time;

  $difm_url_vote = "http://api.audioaddict.com/v1/di/tracks/$track_id/vote/$channel_id/$vote.jsonp?api_key=$api_key&_=$time&_method=POST&callback=$callback";
  $result = file_get_contents($difm_url_vote);

  // Get the new votes
  $vote_result = json_decode(substr(substr($result, strlen($callback) + 1), 0, -2), true);

  $vote_result = array("up" => $vote_result['up'], "down" => $vote_result['down']);
  return $vote_result;
}

function getChannel($channelkey) {
  $channel_result = array();
  $request = 'difm.channels';

  if (CheckCache($request)) {
    $channels_json = getCachedFile($request);
  } else {
    $channels_json = file_get_contents('http://listen.di.fm/streamlist');

    // Make sure we have the file
    $responsecode_string = $http_response_header[0];
    $responsecode = explode(" ", $responsecode_string);

    if($responsecode[1] != "200") {
      echo "Unable to fetch channel list\n";
      exit;
    }

    // Store file in cache
    SetCache($request, $channels_json);
  }

  // Decode JSON
  $channels = json_decode($channels_json, true);

  foreach ($channels as $channel) {
    if ($channel['key'] == $channelkey) {
      $channel_result = array("id" => $channel['id'], "key" => $channel['key'], "name" => $channel['name']);
    }
  }

  if (!count($channel_result)) {
    echo "Channel $channelkey could not be found!\n";
    exit;
  }

  return $channel_result;
}

function getCurrentTrack($json_playlist) {
  $track = array();

  // Find the first non-advertisement
  foreach ($json_playlist as $json) {
    if ($json['type'] == 'advertisement') {
      continue;
    } else {
      $track['id']        = $json['track_id'];
      $track['artist']    = $json['artist'];
      $track['title']     = $json['title'];
      $track['duration']  = $json['duration'];
      $track['length']    = convertDuration($track['duration']);
      $track['playing']   = convertDuration(time() - $json['started']);
      break;
    }
  }

  return $track;
}

function getRecentTracks($channel_id) {
  $time = time();
  // Fetch the current track ID
  $difm_url_history = "http://api.audioaddict.com/v1/di/track_history/channel/$channel_id.jsonp?callback=_AudioAddict_TrackHistory_WP&_=$time";
  $result = file_get_contents($difm_url_history);
  $json_playlist = json_decode(substr(substr($result,29), 0, -2), true);

  return $json_playlist;
}

function convertDuration($sec, $padHours = false) {
  // start with a blank string
  $hms = "";
    
  // do the hours first: there are 3600 seconds in an hour, so if we divide
  // the total number of seconds by 3600 and throw away the remainder, we're
  // left with the number of hours in those seconds
  $hours = intval(intval($sec) / 3600); 

  if ($hours > 0) {
    // add hours to $hms (with a leading 0 if asked for)
    $hms .= ($padHours) 
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
          : $hours. ":";
  }
  
  // dividing the total seconds by 60 will give us the number of minutes
  // in total, but we're interested in *minutes past the hour* and to get
  // this, we have to divide by 60 again and then use the remainder
  $minutes = intval(($sec / 60) % 60); 

  // add minutes to $hms (with a leading 0 if needed)
  $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";

  // seconds past the minute are found by dividing the total number of seconds
  // by 60 and using the remainder
  $seconds = intval($sec % 60); 

  // add seconds to $hms (with a leading 0 if needed)
  $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

  // done!
  return $hms;
}

function CheckCache($request) {
  if (file_exists(dirname(__FILE__)."/cache/" . escapeFileName($request))) {
    if ((time()-filemtime(dirname(__FILE__)."/cache/" . escapeFileName($request)) > 12 * 3600) != true) {
      return true;
    }
  }

  return false;
}

function getCachedFile($request) {
  return file_get_contents(dirname(__FILE__)."/cache/" . escapeFileName($request));
}

function SetCache($request, $staticContent) {
  $update = (array)json_decode($staticContent, true);
  file_put_contents(dirname(__FILE__)."/cache/" . escapeFileName($request), json_encode($update));
}

function escapeFileName($filename) {
  return preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);
}

function readConfiguration() {
  if (!file_exists(dirname(__FILE__)."/cache")) {
    echo "Could not find a cache folder, creating one now.\n";
    if (!mkdir(dirname(__FILE__)."/cache")) {
      echo "Unable to create cache folder ".dirname(__FILE__)."/cache \n";
      exit;
    }
  }

  if (!file_exists(dirname(__FILE__)."/divote.ini")) {
    echo "Couldn't find configuration file divote.ini in ".dirname(__FILE__).", creating one now.\n";
    $ini_template = "[general]\nusername = john@doe.net\npassword = supersecret\n";
    file_put_contents(dirname(__FILE__)."/divote.ini", $ini_template);
    exit;
  }

  // Get credentials from configuration file
  $ini_array = parse_ini_file("divote.ini");

  if (!isset($ini_array['api_key'])) {
    if (isset($ini_array['username']) && isset($ini_array['password'])) {
      // Get new API key from DI.fm
      $ini_array['api_key'] = getApikeys($ini_array['username'], $ini_array['password']);  

      // Write the API key to the configuration file
      $config_file = array (
        'general' => array (
          'username' => $ini_array['username'],
          'password' => $ini_array['password'],
          'api_key'  => $ini_array['api_key'],
        )
      );

      writeINI(dirname(__FILE__)."/divote.ini", $config_file);
    } else {
      echo "Couldn't find a username and password in ".dirname(__FILE__)."/divote.ini\n";
      exit;
    }
  }

  return $ini_array;
}

function getApiKeys($username, $password) {
  $data = array("username" => "$username", "password" => "$password");
  $data_string = json_encode($data);

  $ch = curl_init('https://api.audioaddict.com/v1/di/members/authenticate');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string))
  );

  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

  //execute post
  $result = curl_exec($ch);

  //close connection
  curl_close($ch);

  $auth_array_raw = json_decode($result, true);

  $api_key = $auth_array_raw['api_key'];

  if (!strlen($api_key)) {
    echo "No API key received from Digitally Imported, exiting.\n";
    exit;
  }

  return $api_key;
}

function writeINI($file, $array, $i = 0) {
  $str="";
  foreach ($array as $k => $v) {
    if (is_array($v)) {
      $str.=str_repeat(" ",$i*2)."[$k]".PHP_EOL;
      $str.= writeINI("",$v, $i+1);
    } else {
      $str.=str_repeat(" ",$i*2)."$k = $v".PHP_EOL;
    }
  }

  if($file) {
    // Create backup file
    $backup_file = $file . ".bak";

    try {
      copy($file, $backup_file);
    } catch (Exception $e) {
      // Unable to copy file
      echo "Unable to copy configuration file\n";
      exit;
    }

    return file_put_contents($file,$str);
  } else {
    return $str;
  }
}

