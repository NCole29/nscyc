#!/usr/bin/env bash
set -eu
declare -a directories=(
  "web/libraries/c3/.circleci"
  "web/libraries/c3/.github"
  "web/libraries/c3/data"
  "web/libraries/c3/design"
  "web/libraries/c3/docs"
  "web/libraries/c3/extensions"
  "web/libraries/c3/htdocs"
  "web/libraries/c3/spec"
  "web/libraries/c3/src"
)
counter=0
echo "Deleting unneeded directories inside web/libraries/c3"
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
  "web/libraries/c3/CONTRIBUTING.md"
  "web/libraries/c3/README.md"
  "web/libraries/c3/LICENSE"
  "web/libraries/c3/package.json"
  "web/libraries/c3/.bmp.yml"
  "web/libraries/c3/.editorconfig"
  "web/libraries/c3/.gitignore"
  "web/libraries/c3/.jshintrc"
  "web/libraries/c3/.prettierrc.json"
  "web/libraries/c3/.travis.yml"
  "web/libraries/c3/bower.json"
  "web/libraries/c3/codecov.yml"
  "web/libraries/c3/component.json"
  "web/libraries/c3/config.rb"
  "web/libraries/c3/Gemfile"
  "web/libraries/c3/Gemfile.lock"
  "web/libraries/c3/karma.conf.js"
  "web/libraries/c3/MAINTAINANCE.md"
  "web/libraries/c3/rollup.config.js"
  "web/libraries/c3/tsconfig.json"
  "web/libraries/c3/yarn.lock"
)
counter=0
echo "Deleting unneeded files inside web/libraries/c3"
for file in "${files[@]}"
  do
    if [[ -f $file ]]; then
      echo "Deleting $file"
      rm $file
      counter=$((counter+1))
    fi
  done
echo "$counter files were deleted"