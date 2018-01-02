#!/usr/bin/env bash

set -eu -o pipefail

# args
BRANCH=${BRANCH-'trunk'}

# paths
TOP_DIR=$(git rev-parse --show-toplevel)
SRC_DIR=$TOP_DIR/plugins/panegyric
DEST_DIR=$TOP_DIR/svn/$BRANCH

# make sure the destination dir exists
if [ ! -d $DEST_DIR ]; then
    echo "No SVN dir"
    mkdir -p $DEST_DIR
    svn add $DEST_DIR
fi

# delete everything except .svn dirs
for file in $(find $DEST_DIR/* -type f -and -not -path "*.svn*")
do
	rm $file
done

# copy everything over from git
rsync --recursive --exclude='*.git*' $SRC_DIR/* $DEST_DIR

cd $DEST_DIR

# check .svnignore
for file in $(cat "$SRC_DIR/.svnignore" 2> /dev/null)
do
	rm -rf $DEST_DIR/$file
done

# svn addremove
svn stat | awk '/^\?/ {print $2}' | xargs svn add > /dev/null 2>&1
svn stat | awk '/^\!/ {print $2}' | xargs svn rm --force

svn stat

echo "Remember to run svn ci"
#svn ci -m "$MSG"