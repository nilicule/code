#!/usr/bin/perl

use strict; use warnings;
use HTML::TableExtract;
use Data::Dumper;

my $track_number;
my $track_artist;
my $track_title;

my $te = HTML::TableExtract->new(
  headers => [qw(Num. Artist Title)]
);

my $filename = $ARGV[0];

$te->parse_file($filename);

foreach my $table ( $te->tables ) {
  foreach my $row ($table->rows) {
    $track_number = $row->[0];
    $track_artist = $row->[1];
    $track_title = $row->[2];
    $track_title =~ s/- \d{1,2}\w - \d{3}//i;
    #print "$track_number - $track_artist - $track_title\n";
    print "$track_artist - $track_title\n";
  }
}
