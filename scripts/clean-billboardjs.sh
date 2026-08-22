#!/usr/bin/env bash
set -eu
declare -a directories=(
  "web/libraries/billboard/dist-esm"
  "web/libraries/billboard/src"
  "web/libraries/billboard/types"
  "web/libraries/billboard/dist/plugin"
  "web/libraries/billboard/dist/theme"
)
counter=0
echo "Deleting unneeded directories inside web/libraries/billboard"
for directory in "${directories[@]}"
  do
    if [ -d $directory ]; then
      echo "Deleting $directory"
      rm -rf $directory
      counter=$((counter+1))
    fi
  done
echo "$counter folders were deleted"
declare -a files=(
  "web/libraries/billboard/CONTRIBUTING.md"
  "web/libraries/billboard/README.md"
  "web/libraries/billboard/LICENSE"
  "web/libraries/billboard/package.json"
  "web/libraries/billboard/dist/package.json"
)
counter=0
echo "Deleting unneeded files inside web/libraries/billboard"
for file in "${files[@]}"
  do
    if [[ -f $file ]]; then
      echo "Deleting $file"
      rm $file
      counter=$((counter+1))
    fi
  done
echo "$counter files were deleted"
