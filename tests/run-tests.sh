#!/bin/bash
set -euo pipefail

pushd $(dirname $0)/../ >/dev/null

velocitaUrl="${1:-}"
if [ -z "${velocitaUrl}" ]; then
    echo 'Please provide a URL to a running Velocita instance.'
    echo
    echo "    Example: $0 https://path.to.velocita.tld"
    echo
    exit 1
fi

phpVersions=(7.4 8.0 8.1 8.2)
composerVersions=(2.2.21 2.4.4 2.5.8 2.6.5)
testImage=velocita-test-image

buildImage() {
    local phpVersion=$1
    local composerVersion=$2
    local userUid=$(id -u)

    docker build \
        --build-arg PHP_VERSION="${phpVersion}" \
        --build-arg COMPOSER_VERSION="${composerVersion}" \
        --build-arg USER_UID="${userUid}" \
        -t "${testImage}:php-${phpVersion}-composer-${composerVersion}" \
        -f tests/Dockerfile.test \
        .
}

runTestSuite() {
    local phpVersion=$1
    local composerVersion=$2

    local outputDir="test-results/php-${phpVersion}-composer-${composerVersion}"
    mkdir -p "${outputDir}"

    buildImage "${phpVersion}" "${composerVersion}"
    docker run -t \
        --env VELOCITA_URL="${velocitaUrl}" \
        --mount type=bind,source=$(pwd)/${outputDir},target=/output \
        "${testImage}:php-${phpVersion}-composer-${composerVersion}"
}

for phpVersion in "${phpVersions[@]}"; do
    for composerVersion in "${composerVersions[@]}"; do
        runTestSuite "${phpVersion}" "${composerVersion}"
    done
done

echo 'All tests executed.'
