#!/usr/bin/env bash

export PATH="$HOME/.nvm/versions/node/v22.19.0/bin:$PATH"
pm2 stop server

set -euo pipefail
IFS=$'\n'

# ─── CONFIG ────────────────────────────────────────────────────────────────────
SOURCE_DIR="/home/savile/Documents/ESP32-Cam"
DATE=$(date +%F)   # e.g. 2025-09-21
DEST_DIR="/home/savile/Documents/NAS-ESP32-Images/Shared-Red/ESP32-Cam/$DATE"

mkdir -p "$DEST_DIR"

# ─── GATHER MP4 FILES (RELATIVE PATHS) ────────────────────────────────────────
# -printf '%P' drops leading './'
mapfile -d '' FILES_REL < <(
  cd "$SOURCE_DIR"
  find . \
    -type f \
    -iname '*.mp4' \
    ! -name "*${DATE}*" \
    -print0 \
    | sed -z 's|^\./||'
)

# ─── EXIT IF NOTHING TO DO ─────────────────────────────────────────────────────
if (( ${#FILES_REL[@]} == 0 )); then
  echo "⚠️  No .mp4 files found (excluding today's date: ${DATE}). Exiting."
  pm2 start server --node-args="--report-uncaught-exception --report-on-fatalerror --report-on-signal --report-signal SIGUSR2"
  exit 0
fi

# ─── PRINT FILE LIST ───────────────────────────────────────────────────────────
echo "📂 Files to transfer (${#FILES_REL[@]}) (excluding today's date: ${DATE}):"
for rel in "${FILES_REL[@]}"; do
  printf "  - %s/%s\n" "$SOURCE_DIR" "$rel"
done

# ─── CALCULATE TOTAL BYTES ─────────────────────────────────────────────────────
TOTAL_BYTES=0
for rel in "${FILES_REL[@]}"; do
  TOTAL_BYTES=$(( TOTAL_BYTES + $(stat --printf="%s" "$SOURCE_DIR/$rel") ))
done

# ─── TRANSFER VIA TAR → PV → TAR ───────────────────────────────────────────────
# Requires: pv   (apt-get install pv)
#
# 1. tar --remove-files ­–cf - …    → create an archive of all files, removing them from SOURCE_DIR  
# 2. | pv -s $TOTAL_BYTES          → show a single progress bar across the entire archive  
# 3. | tar -xf - ­–C DEST_DIR       → extract into DEST_DIR, preserving paths/timestamps

echo
tar --remove-files -C "$SOURCE_DIR" -cf - "${FILES_REL[@]}" \
  | pv -s "$TOTAL_BYTES" \
  | tar -xf - -C "$DEST_DIR"

echo -e "\n🎯 Transfer complete!"
pm2 start server --node-args="--report-uncaught-exception --report-on-fatalerror --report-on-signal --report-signal SIGUSR2"