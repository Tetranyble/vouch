#!/usr/bin/env bash

set -euo pipefail

version="${1:?A semantic version is required.}"

if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Invalid release version: $version" >&2
    exit 1
fi

repository_root="$(git rev-parse --show-toplevel)"
cd "$repository_root"

distribution_directory="dist"
release_directory="$distribution_directory/vouch-v$version"
archive_name="vouch-v$version.zip"

rm -rf -- "$release_directory"
mkdir -p "$release_directory"

# Archive only the release commit, never uncommitted CI workspace files.
git archive --format=tar HEAD | tar -xf - -C "$release_directory"

# Build browser assets, which are intentionally excluded from source control.
npm run build
mkdir -p "$release_directory/public/build"
cp -R public/build/. "$release_directory/public/build/"

# Include only production PHP dependencies in the downloadable deployment package.
(
    cd "$release_directory"
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
)

(
    cd "$distribution_directory"
    rm -f -- "$archive_name" "$archive_name.sha256"
    zip -qr "$archive_name" "vouch-v$version"

    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$archive_name" > "$archive_name.sha256"
    else
        shasum -a 256 "$archive_name" > "$archive_name.sha256"
    fi
)
