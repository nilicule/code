#/bin/sh

listenkey=$1; dir=$2 prefix=$3

if [ "$listenkey" == "-h" ]; then
  echo "Usage: di.sh [LISTEN_KEY] [DIR] [PREFIX]"
  echo
  echo "  LISTEN_KEY - Your premium listener key"
  echo "  DIR        - The directory you want to store playlists in"
  echo "  PREFIX     - The prefix for the playlist files"
  echo
  echo " If you don't specify your premium key, the public playlists will be downloaded"
  echo
  exit
fi

if [ "$dir" == "" ]; then
  dir="."
fi

if [ "$prefix" == "" ]; then
  prefix="difm"
fi

# List of DI.FM channels
url="http://listen.di.fm/public3"

for name in `wget -nv -O - $url | grep -o '"key":"[^"]*"' | sed 's/"key":"\([^"]*\)"/\1/g'`; do
echo $name
  file="$dir/$prefix-$name.m3u"
  if [ "$listenkey" == "" ]; then
    wget -nv -O $file "http://listen.di.fm/public3/$name.pls"
  else
    wget -nv -O $file "http://listen.di.fm/premium_high/$name.pls?$listenkey"
  fi
  sed -n -i 's/^File[0-9]*=//p' $file
done

