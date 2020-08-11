#!/bin/bash
set -euo pipefail

velocitaUrl="${1:-}"
if [ -z "${velocitaUrl}" ]; then
    echo 'Please provide a URL to a running Velocita instance.'
    echo
    echo "    Example: $0 https://path.to.velocita.tld"
    echo
    exit 1
fi

phpVersions=(7.2 7.3 7.4)
composerVersions=(1.9.3 1.10.10 2.0.0-alpha3)

buildImage() {
    local phpVersion=$1
    local composerVersion=$2

    local contextDir=$(dirname $0)/../

    DOCKER_BUILDKIT=1 docker build \
        --build-arg PHP_VERSION="${phpVersion}" \
        --build-arg COMPOSER_VERSION="${composerVersion}" \
        -t test-image \
        -f Dockerfile.test "${contextDir}"
}

runTestSuite() {
    local phpVersion=$1
    local composerVersion=$2

    local outputDir="results/php-${phpVersion}-composer-${composerVersion}"
    mkdir -p "${outputDir}"

    buildImage "${phpVersion}" "${composerVersion}"
    docker run -ti \
        --env VELOCITA_URL="${velocitaUrl}" \
        --mount type=bind,source=$(pwd)/${outputDir},target=/output \
        test-image
}

for phpVersion in "${phpVersions[@]}"; do
    for composerVersion in "${composerVersions[@]}"; do
        runTestSuite "${phpVersion}" "${composerVersion}"
    done
done
