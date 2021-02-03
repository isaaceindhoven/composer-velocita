#!/bin/ash
set -eu

# Show versions
phpVersion=$(php -i | grep -m 1 'PHP Version' | cut -d' ' -f4)
composerVersion=$(composer --version | cut -d' ' -f3)
echo
echo "PHP ${phpVersion} - Composer ${composerVersion}"
echo '-----'
echo

runInstall() {
    local outputPath="$1"

    rm -rf vendor
    composer clear-cache
    composer install --no-interaction --no-autoloader --no-suggest --profile -vvv 2>&1 | tee "${outputPath}"
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

echo '{"require":{"phpunit/phpunit":"^9.5"}}' > composer.json
composer install --no-interaction --no-autoloader --no-suggest

# Vanilla
runInstall /output/vanilla-output.txt

# ISAAC Velocita
installVelocita
enableVelocita
runInstall /output/velocita-output.txt
disableVelocita

# Symfony Flex
composer global require symfony/flex:^1.12
runInstall /output/flex-output.txt

# ISAAC Velocita + Symfony Flex
enableVelocita
runInstall /output/velocita-flex-output.txt
disableVelocita
composer global remove symfony/flex
