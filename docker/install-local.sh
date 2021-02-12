#!/bin/bash

cd docker

# load local environment variables
if [ ! -e ".env" ]; then
  echo "The .env (environment variables) file is missing"
  exit 1
fi

. ./.env

/bin/bash ./build_assets.sh

docker-compose -f ../docker-compose.yml build

/bin/bash ./init-selfcert.sh
