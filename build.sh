#!/bin/bash
cd "$( dirname "$0" )"
rm -Rf target
mkdir target
cd target

echo "** BUILDING DEFAULT **"

mkdir default
cd default
cp -R ../../src/default/* .

# Halloween
../../merge_folder.php ../../src/halloween .

# Copy glyph icons
cp -R ../../src/fonticons/assets/magic/* ./assets/magic/
mkdir ./assets/minecraft/font
php ../../merge_fonts.php ../../src/fonticons/assets/magic/font ./assets/minecraft/font/magic.json

# Clean and zip
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-RP-1.21.5.zip *
cd ..

if [ -n "$1" ]
then
  exit
fi

echo "** BUILDING SKULLS **"

mkdir default-skulls
cd default-skulls
cp -R ../../src/skulls/* .
mkdir assets/magic
cp -R ../default/assets/magic/sounds assets/magic/.
cp -R ../default/assets/minecraft/sounds.json assets/minecraft/.
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-skulls-RP-1.21.5.zip *
cd ..

echo "** BUILDING FLAT SKULLS **"

mkdir skulls
cd skulls
mkdir assets
cp -R ../../src/skulls/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../flat-skulls.zip *
cd ..

echo "** BUILDING PAINTERLY **"

mkdir painterly
cd painterly
cp -R ../default/* .
cp -R ../../src/painterly/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-painterly-RP-1.21.5.zip *
cd ..

echo "** BUILDING LOW-RES **"

mkdir lowres
cd lowres
cp -R ../default/* .
cp -R ../../src/lowres/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-lowres-RP-1.21.5.zip *
cd ..

echo "** BUILDING POTTER **"

mkdir potter
cd potter
cp -R ../default/* .
cp -R ../../src/potter/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-potter-RP-1.21.5.zip *
cd ..

echo "** BUILDING WAR **"

mkdir war
cd war
cp -R ../../src/war/* .
sed -e '$ d' ../../src/war/assets/minecraft/sounds.json > assets/minecraft/sounds.json
echo , >> assets/minecraft/sounds.json
tail -n +2 ../../src/war/assets/minecraft/sound-overrides.json >> assets/minecraft/sounds.json
rm assets/minecraft/sound-overrides.json
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-war-RP-1.21.5.zip *
cd ..

echo "** BUILDING ROBES **"

mkdir robes
cd robes
cp -R ../default/* .
mkdir assets/minecraft/textures/
cp -R ../../src/chainmail/assets/minecraft/textures/* assets/minecraft/textures/
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-robes-RP-1.21.5.zip *
cd ..

echo "** BUILDING HTTYD **"

mkdir httyd
cd httyd
cp -R ../../src/httyd/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-httyd-RP-1.21.5.zip *
cd ..

echo "** BUILDING BRAWL **"

mkdir brawl
cd brawl
cp -R ../../src/brawl/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-brawl-RP-1.21.5.zip *
cd ..

echo "** BUILDING ALL **"

mkdir all
cd all
cp -R ../robes/* .
cp -R ../../src/war/assets/magic/* assets/magic/
mkdir -p assets/minecraft/models/item/
cp ../../src/war/assets/minecraft/items/diamond_pickaxe.json assets/minecraft/items/
sed -e '$ d' ../../src/default/assets/minecraft/sounds.json > assets/minecraft/sounds.json
echo , >> assets/minecraft/sounds.json
tail -n +2 ../default/assets/minecraft/sounds.json >> assets/minecraft/sounds.json
../../merge_folder.php ../../src/httyd .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-all-RP-1.21.5.zip *
cd ..

echo "** BUILDING MODEL ENGINE **"

mkdir modelengine
cd modelengine
cp -R ../all/* .
../../merge_folder.php ../../src/modelengine .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-modelengine-RP-1.21.5.zip *
cd ..

echo "** BUILDING HIRES-MODELENGINE **"

mkdir hires-modelengine
cd hires-modelengine
cp -R ../modelengine/* .
cp -R ../../src/hires/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-modelengine-RP-1.21.5.zip *
cd ..

echo "** BUILDING HIRES-ALL **"

mkdir hires-all
cd hires-all
cp -R ../all/* .
cp -R ../../src/hires/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-all-RP-1.21.5.zip *
cd ..

echo "** BUILDING HIRES-ROBES **"

mkdir hires-robes
cd hires-robes
cp -R ../robes/* .
cp -R ../../src/hires/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-robes-RP-1.21.5.zip *
cd ..

echo "** BUILDING HIRES **"

mkdir hires
cd hires
cp -R ../default/* .
cp -R ../../src/hires/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-RP-1.21.5.zip *
cd ..

echo "** BUILDING VANILLA **"

mkdir vanilla
cd vanilla
cp -R ../default/* .
cp -R ../../src/vanilla/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-vanilla-RP-1.21.5.zip *
cd ..

echo "** BUILDING VANILLA-ALL **"

mkdir vanilla-all
cd vanilla-all
cp -R ../all/* .
cp -R ../../src/vanilla/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-vanilla-all-RP-1.21.5.zip *
cd ..

echo "** BUILDING VANILLA-ROBES **"

mkdir vanilla-robes
cd vanilla-robes
cp -R ../robes/* .
cp -R ../../src/vanilla/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-vanilla-robes-RP-1.21.5.zip *
cd ..

echo "** COPYING TO MINECRAFT **"
cp *.zip ~/Library/Application\ Support/minecraft/resourcepacks/
