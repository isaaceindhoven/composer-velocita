#!/bin/ash
set -eu

# Show versions
phpVersion=$(php -i | grep -m 1 'PHP Version' | cut -d' ' -f4)
composerVersion=$(composer --version | cut -d' ' -f3)
echo
echo "PHP ${phpVersion} - Composer ${composerVersion}"
echo '----------'
echo

cleanup() {
    rm -rf vendor
    composer clear-cache
}

runInstall() {
    local outputPath="$1"
    cleanup
    composer install --no-interaction --no-autoloader --no-scripts --profile -vvv 2>&1 | tee "${outputPath}"
}

runCreateProject() {
    local packageName="$1"
    local outputPath="$2"
    cleanup
    composer create-project --no-interaction --profile -vvv "${packageName}" project 2>&1 | tee "${outputPath}"
}

installVelocita() {
    composer global config repositories.velocita-src path /usr/src/velocita/
    composer global require isaac/composer-velocita @dev
}

enableVelocita() {
    composer velocita:enable "${VELOCITA_URL}"
}

disableVelocita() {
    composer velocita:disable
}

echo '{"require":{"phpunit/phpunit":"^9.5"}}' > composer.json

# Vanilla
runInstall /output/vanilla-install-output.txt

# Velocita
installVelocita
enableVelocita
runInstall /output/velocita-install-output.txt
disableVelocita

# Symfony Flex
composer global require symfony/flex:^1.12
runInstall /output/flex-install-output.txt

# Velocita + Symfony Flex
enableVelocita
runInstall /output/velocita-flex-install-output.txt

# Velocita + Symfony Flex create-project
composer global remove symfony/flex
runCreateProject symfony/skeleton:v5.2.99 /output/velocita-create-project-output.txt
