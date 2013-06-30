#!/usr/bin/perl

die "Can't run without a filename" if ( !$ARGV[0] );

$filename = $ARGV[0];

open( FILE, "< $filename" ) or die "Can't open $filename : $!";

while (<FILE>) {
  chomp;
  if (length($_) > 0) {
    $_ =~ s/^\s+//;
    $_ =~ s/\s+$//;
    print "[B]" . $_ . "[/B]\n";
  }
}
