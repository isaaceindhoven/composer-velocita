#!/bin/ash
set -eu

# Show versions
php -v
composer --version

runInstall() {
    local outputPath="$1"

    rm -rf vendor
    composer clear-cache
    composer install --no-autoloader --no-suggest --profile 2>&1 | tee "${outputPath}"
}

installVelocita() {
    composer global config repositories.velocita-src path /usr/src/velocita/
    composer global require isaac/composer-velocita:@dev
}

enableVelocita() {
    composer velocita:enable "${VELOCITA_URL}"
}

disableVelocita() {
    composer velocita:disable
}

echo '{"require":{"phpunit/phpunit":"^8.5"}}' > composer.json
composer install

# Vanilla
runInstall /output/vanilla-output.txt

# ISAAC Velocita
installVelocita
enableVelocita
runInstall /output/velocita-output.txt
disableVelocita

# Symfony Flex
composer global require symfony/flex:^1.9
runInstall /output/flex-output.txt

# ISAAC Velocita + Symfony Flex
enableVelocita
runInstall /output/velocita-flex-output.txt
disableVelocita
composer global remove symfony/flex

# Hirak Prestissimo
composer global require hirak/prestissimo:^0.3
runInstall /output/prestissimo-output.txt

# ISAAC Velocita + Hirak Prestissimo
enableVelocita
runInstall /output/velocita-prestissimo-output.txt
composer global remove hirak/prestissimo
