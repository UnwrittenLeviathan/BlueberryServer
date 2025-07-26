#!/bin/bash

# Define source and destination directories
SOURCE_DIR="/mnt/usb/ESP32-Cam"
DATE=$(date +"%Y-%m-%d")
echo "Today's date is: $DATE"
DEST_DIR="/home/savile/Documents/ESP32-Cam/$DATE"
KEYWORD="temp_"

# Create destination directory if it doesn't exist
mkdir -p "$DEST_DIR"

# Find matching files and calculate total size
FILES=($(find "$SOURCE_DIR" -type f ! -name "*$KEYWORD*"))
TOTAL_BYTES=0

for FILE in "${FILES[@]}"; do
    TOTAL_BYTES=$((TOTAL_BYTES + $(stat -c%s "$FILE")))
done

MOVED_BYTES=0

echo "Moving files containing \"$KEYWORD\" from $SOURCE_DIR to $DEST_DIR"
echo "Total size to move: $TOTAL_BYTES bytes"
echo ""

# Progress bar function based on bytes moved
progress_bar() {
    local PROG=$((MOVED_BYTES * 100 / TOTAL_BYTES))
    local DONE=$((PROG / 2))
    local LEFT=$((50 - DONE))
    local FILL=$(printf "%${DONE}s" | tr ' ' '=')
    local EMPTY=$(printf "%${LEFT}s")
    printf "\rProgress: [${FILL}${EMPTY}] %d%% (%s / %s bytes)" $PROG $MOVED_BYTES $TOTAL_BYTES
}

# Move files and update progress
for FILE in "${FILES[@]}"; do
    FILE_SIZE=$(stat -c%s "$FILE")
    mv "$FILE" "$DEST_DIR/"
    MOVED_BYTES=$((MOVED_BYTES + FILE_SIZE))
    progress_bar
    sleep 0.1  # Optional: makes the progress visible
done

echo -e "\nDone!"