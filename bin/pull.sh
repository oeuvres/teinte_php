#!/usr/bin/env bash
dir=`dirname -- "$0"`
pushd $dir/..
git subtree pull --prefix src/xsl https://github.com/oeuvres/teinte_xsl main --squash
popd
