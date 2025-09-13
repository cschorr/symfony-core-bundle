#!/usr/bin/env bash

php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --no-interaction

#php bin/console doctrine:diagram:class --filename=assets/docs/diagram-class.svg
#php bin/console doctrine:diagram:er --filename=assets/docs/diagram-er.svg
