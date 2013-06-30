#!/usr/bin/perl
#
# changetitle v1.0
# http://code.google.com/p/nilicule/source/browse/trunk/perl/changetitle.pl
#
# changetitle sets the title for the current track on a shoutcast server
#
# Copyright (C) 2011 Remco B. Brink <remco@rc6.org>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# The GNU General Public License is available at:
# http://www.gnu.org/copyleft/gpl.html

use LWP::UserAgent;
use Getopt::Std;

$version="1.0";
$host="example.com";
$pass="password";
$port="8000";

my %options=();
getopts("p:t:", \%options);

print "\n";
print "Shoutcast TitleChanger v$version\n";

# If given, use the password supplied on the command-line
if ($options{p}) {
  $pass = $options{p};
}

# Check if the user has set a title
if (!$options{t}) {
  print " - Please enter a title you want to write to the Shoutcast server\n";
  exit;
} else {
  $song = $options{t};
}

# Create web user agent class
my $ua = LWP::UserAgent->new;
$ua->timeout(10);
$ua->env_proxy;
$ua->agent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/534.51.22 (KHTML, like Gecko) Version/5.1.1 Safari/534.51.22');

# Call shoutcast
my $response = $ua->get("http://$host:$port/admin.cgi?pass=$pass&song=$song&mode=updinfo");

# Output information to user
print " - Setting title of stream at $host:$port to $song\n";

if ($response->is_success) {
#  print $response->decoded_content; # or whatever
} else {
#  die $response->status_line;
}

# Add empty line
print "\n";
