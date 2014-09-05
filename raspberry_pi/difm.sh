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

if [ "$listenkey" == "" ]; then
  echo "Using public playlists"
else
  echo "Using premium playlists"
fi

# List of DI.FM channels
url="http://listen.di.fm/public3"

for name in `wget -q -nv -O - $url | grep -o '"key":"[^"]*"' | sed 's/"key":"\([^"]*\)"/\1/g'`; do
echo "- Fetching $name"
  file="$dir/$prefix-$name.m3u"
  if [ "$listenkey" == "" ]; then
    wget -q -nv -O $file "http://listen.di.fm/public3/$name.pls"
  else
    wget -q -nv -O $file "http://listen.di.fm/premium_high/$name.pls?$listenkey"
  fi
  sed -n -i 's/^File[0-9]*=//p' $file
done

# Create new radiostations.js file
cat <<EOF > radiostations.js
/**
 * Default Radiostations which appear in the webinterface. Edit if you like.
 * Take care when editting. Only edit the stuff between ''
 * And don't use the default Windows Notepad for this (use Notepad++ on Windows)
 */

var radioStations = [];
EOF

PLAYLISTFILES="$dir/*.m3u"
for f in $PLAYLISTFILES
do
  STREAMURL=$(head -1 $f)
  filename=$(basename "$f")
  extension="${filename##*.}"
  filename="${filename%.*}"
  filename=${filename#$prefix-}
  echo "radioStations.push(['$filename', '$STREAMURL']);" >> radiostations.js
done

