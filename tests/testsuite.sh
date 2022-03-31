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
    rm -rf project vendor
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

echo '{"require":{"phpunit/phpunit":"9.5.19"}}' > composer.json

# Vanilla install
runInstall /output/vanilla-install-output.txt

# Configure Composer 2.2.x to allow plugins
if [[ "${composerVersion}" == 2.2.* ]]; then
    composer config -g allow-plugins.symfony/flex true
    composer config -g allow-plugins.isaac/composer-velocita true
fi

# Velocita install
installVelocita
enableVelocita
runInstall /output/velocita-install-output.txt

# Symfony Flex install
disableVelocita
if [[ "${phpVersion}" == 7.4.* ]]; then
    composer global require symfony/flex:1.18.5
else
    composer global require symfony/flex:2.1.6
fi
runInstall /output/flex-install-output.txt

# Velocita + Symfony Flex install
enableVelocita
runInstall /output/velocita-flex-install-output.txt
composer global remove symfony/flex

# Vanilla create-project
if [[ "${phpVersion}" == 7.4.* ]]; then
    symfonyVersion="v5.4.99"
else
    symfonyVersion="v6.0.99"
fi
disableVelocita
runCreateProject symfony/skeleton:${symfonyVersion} /output/vanilla-create-project-output.txt

# Velocita + Symfony Flex create-project
enableVelocita
runCreateProject symfony/skeleton:${symfonyVersion} /output/velocita-create-project-output.txt
