#!/bin/bash
cd "$( dirname "$0" )"
rm -Rf target
mkdir target
cd target

echo "** BUILDING DEFAULT **"

mkdir default
cd default
cp -R ../../src/default/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-RP-1.17.zip *
cd ..

echo "** BUILDING SKULLS **"

mkdir default-skulls
cd default-skulls
mkdir assets
cd assets
cp -R ../../../src/skulls/assets/* .
cd ..
cp -R ../../src/default/* .
rm -R assets/minecraft/textures/item/spells
rm -R assets/minecraft/textures/item/brushes
rm -R assets/minecraft/models/item/spells
rm -R assets/minecraft/models/item/spells_disabled
rm -R assets/minecraft/models/item/brushes
rm assets/minecraft/models/item/diamond_axe.json
rm assets/minecraft/models/item/diamond_hoe.json
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-skulls-RP-1.17.zip *
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
cp -R ../../src/default/* .
cp -R ../../src/painterly/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-painterly-RP-1.17.zip *
cd ..

echo "** BUILDING LOW-RES **"

mkdir lowres
cd lowres
cp -R ../../src/default/* .
cp -R ../../src/lowres/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-lowres-RP-1.17.zip *
cd ..

echo "** BUILDING POTTER **"

mkdir potter
cd potter
cp -R ../../src/default/* .
cp -R ../../src/potter/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-potter-RP-1.17.zip *
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
zip -q -X -r ../Magic-war-RP-1.17.zip *
cd ..

echo "** BUILDING ROBES **"

mkdir robes
cd robes
cp -R ../../src/default/* .
mkdir assets/minecraft/textures/
cp -R ../../src/chainmail/assets/minecraft/textures/* assets/minecraft/textures/
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-robes-RP-1.17.zip *
cd ..

echo "** BUILDING HTTYD **"

mkdir httyd
cd httyd
cp -R ../../src/httyd/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-RP-httyd-1.17.zip *
cd ..

echo "** BUILDING BRAWL **"

mkdir brawl
cd brawl
cp -R ../../src/brawl/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-RP-brawl-1.17.zip *
cd ..

echo "** BUILDING ALL **"

mkdir all
cd all
cp -R ../../src/default/* .
mkdir assets/minecraft/textures/
cp -R ../../src/chainmail/assets/minecraft/textures/* assets/minecraft/textures/
cp -R ../../src/war/assets/minecraft/sounds/* assets/minecraft/sounds/
cp -R ../../src/war/assets/minecraft/models/item/* assets/minecraft/models/item/
cp -R ../../src/war/assets/minecraft/textures/misc assets/minecraft/textures/
cp -R ../../src/war/assets/minecraft/textures/item/custom/* assets/minecraft/textures/item/custom/
sed -e '$ d' ../../src/default/assets/minecraft/sounds.json > assets/minecraft/sounds.json
echo , >> assets/minecraft/sounds.json
tail -n +2 ../../src/war/assets/minecraft/sounds.json >> assets/minecraft/sounds.json
../../merge_folder.php ../../src/httyd .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-all-RP-1.17.zip *
cd ..

echo "** BUILDING MODEL ENGINE **"

mkdir modelengine
cd modelengine
cp -R ../all/* .
../../merge_folder.php ../../src/modelengine .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-modelengine-RP-1.17.zip *
cd ..

echo "** BUILDING SLIMEFUN **"

mkdir slimefun
cd slimefun
cp -R ../all/* .
../../merge_folder.php ../../src/slimefun .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-slimefun-RP-1.17.zip *
cd ..

echo "** BUILDING HIRES-ALL **"

mkdir hires-all
cd hires-all
cp -R ../all/* .
cp -R ../../src/hires/assets/minecraft/models/item/* assets/minecraft/models/item/
cp -R ../../src/hires/assets/minecraft/textures/item/* assets/minecraft/textures/item/
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-all-RP-1.17.zip *
cd ..

echo "** BUILDING HIRES-ROBES **"

mkdir hires-robes
cd hires-robes
cp -R ../robes/* .
cp -R ../../src/hires/assets/minecraft/models/item/* assets/minecraft/models/item/
cp -R ../../src/hires/assets/minecraft/textures/item/* assets/minecraft/textures/item/
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-robes-RP-1.17.zip *
cd ..

echo "** BUILDING HIRES **"

mkdir hires
cd hires
cp -R ../default/* .
cp -R ../../src/hires/* .
find . -name ".DS_Store" -type f -delete
zip -q -X -r ../Magic-hires-RP-1.17.zip *
cd ..