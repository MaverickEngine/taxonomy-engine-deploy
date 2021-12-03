#!/bin/sh

rm -rf includes
rm -rf templates
rm -rf data
rm -rf dist
wget https://github.com/j-norwood-young/taxonomy-engine/archive/refs/heads/main.zip
unzip main.zip
rm main.zip
rm taxonomy-engine-main/README.md
mv taxonomy-engine-main/* .
rm -rf taxonomy-engine-main
npm install
npm run build
composer install --prefer-dist --no-progress --ignore-platform-reqs
rm -rf src
rm -rf node_modules
rm webpack.*
rm package.*
rm composer.*