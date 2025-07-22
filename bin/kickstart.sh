#!/usr/bin/env bash

ddev php bin/console doctrine:database:drop --force
ddev php bin/console doctrine:database:create
ddev php bin/console doctrine:schema:update --force
ddev php bin/console doctrine:fixtures:load --no-interaction

#ddev php bin/console doctrine:diagram:class --filename=assets/docs/diagram-class.svg
#ddev php bin/console doctrine:diagram:er --filename=assets/docs/diagram-er.svg