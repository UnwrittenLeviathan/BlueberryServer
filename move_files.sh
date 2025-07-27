#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n'

# ─── CONFIG ────────────────────────────────────────────────────────────────────
SOURCE_DIR="/home/savile/Documents/ESP32-Cam"
KEYWORD="temp_"
DATE=$(date +%F)
DEST_DIR="/home/savile/Documents/NAS-ESP32-Images/$DATE"

mkdir -p "$DEST_DIR"

# ─── GATHER MP4 FILES (RELATIVE PATHS) ────────────────────────────────────────
# -printf '%P' drops leading './'
mapfile -d '' FILES_REL < <(
  cd "$SOURCE_DIR"
  find . \
    -type f \
    -iname '*.mp4' \
    ! -name "*${KEYWORD}*" \
    -print0 \
    | sed -z 's|^\./||'
)

# ─── EXIT IF NOTHING TO DO ─────────────────────────────────────────────────────
if (( ${#FILES_REL[@]} == 0 )); then
  echo "⚠️  No .mp4 files found (excluding '*${KEYWORD}*'). Exiting."
  exit 0
fi

# ─── PRINT FILE LIST ───────────────────────────────────────────────────────────
echo "📂 Files to transfer (${#FILES_REL[@]}):"
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