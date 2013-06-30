<?php
// Set a short timeout
ini_set( "default_socket_timeout", 1 );

// Poke to see if our own server is up and running
$listenpls = @file_get_contents( "http://rc.vc:8000/listen.pls" );

// If our server is down, forward the user to DI
if(empty($listenpls)) {
 header( "Location: http://www.di.fm/mp3/techhouse.pls" );
 exit();
}

// Apparently our server is up and running, feed the user our playlist
echo $listenpls;

?>
