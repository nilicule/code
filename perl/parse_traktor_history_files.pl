#!/usr/bin/perl
#
# Small script to parse the HTML tables generated by Traktor
#

use HTML::TableExtract;
use Data::Dumper;

open FILE, "history.html" or die "Couldn't open file: $!"; 
$html_string = join("", <FILE>); 
close FILE;

$te = HTML::TableExtract->new( headers => [qw(Title Artist)] );
$te->parse($html_string);

foreach $row ($te->rows) {
  $title = @$row[0];
  $artist = @$row[1];

  $track = "$artist - $title";

  print "$track\n";
}