#!/usr/bin/php
<?php 

// Your personal API key
$api_key = 'XXXXXXXXXXXXXXXXXXXXXX';

# Quit unless we have the correct number of command-line args
$num_args = $argc + 1;
if ($num_args != 4) {
  print "\nUsage: ".$argv[0]." vote channel\n";
  exit;
}

// Command-line variables
$vote = strtolower($argv[1]);
$channelkey = strtolower($argv[2]);

if ($vote == 'down' || $vote == 'up') {
  // We have a vote
} else {
  echo "You can only vote up or down\n";
  exit;
}

// Get channel ID
$channel = getChannel($channelkey);

// Get recently played tracks
$json_playlist = getRecentTracks();

// Get latest track
$track = getCurrentTrack($json_playlist);

// Do the vote
$track['votes'] = doVote($api_key, $track['id'], $vote);

// Tell the user what we did
// Voting UP Star Driver & Kodex Feat. El Poco Maro - Tokyo Sunrise (4:39) [votes: +5 -4]

$feedback = sprintf('Voting %s %s - %s (%s) [votes: +%d -%d] [DI %s]', $vote, $track['artist'], $track['title'], $track['length'], $track['votes']['up'], $track['votes']['down'], $channel['name']);
echo "$feedback\n";


///////////////////////////////

function doVote($api_key, $track_id, $vote) {
  $time = time();
  $callback = 'jQuery'.$time.$time;

  $difm_url_vote = "http://api.audioaddict.com/v1/di/tracks/$track_id/vote/60/$vote.jsonp?api_key=$api_key&_=$time&_method=POST&callback=$callback";
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
      break;
    }
  }

  return $track;
}

function getRecentTracks() {
  $time = time();
  // Fetch the current track ID
  $difm_url_history = "http://api.audioaddict.com/v1/di/track_history/channel/60.jsonp?callback=_AudioAddict_TrackHistory_WP&_=$time";
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