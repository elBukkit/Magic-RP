#!/bin/bash
cd "$( dirname "$0" )"
cd target

echo "** BUILDING LIMINAL for 26.1 **"

mkdir liminal-26.1
cd liminal-26.1
cp -R ../../src/liminal/* .
cp -R ../../src/liminal-26.1/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-liminal-RP-26.1.zip *
cd ..

echo "** BUILDING LIMINAL-ALL for 26.1 **"

mkdir liminal-all-26.1
cd liminal-all-26.1
cp -R ../all/* .
../../merge_folder.php ../../src/liminal .
cp -R ../../src/liminal-26.1/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-liminal-all-RP-26.1.zip *
cd ..
