#!/usr/bin/env bash
# Packages the compiled Vite assets (public/build) for one exact source commit.
#
#   scripts/package-frontend.sh <full-40-char-commit-sha> <output-dir>
#
# Produces, in <output-dir>:
#   brivia-frontend-<sha>.tar.gz          only the "build/" directory (manifest + hashed assets + build-info.json)
#   brivia-frontend-<sha>.tar.gz.sha256   "<sha256>  brivia-frontend-<sha>.tar.gz"
#
# Used by .github/workflows/frontend-build.yml. Contains no secrets: the build needs no .env.
set -Eeuo pipefail

die() { echo "package-frontend: ERROR: $*" >&2; exit 1; }

[[ $# -eq 2 ]] || die "usage: $0 <full-commit-sha> <output-dir>"
sha="$1"
out="$2"
[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || die "expected a full 40-character lowercase commit SHA, got '$sha'"

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
build="$root/public/build"

[[ -f "$build/manifest.json" ]] || die "public/build/manifest.json not found; run 'npm run build' first"
[[ ! -e "$root/public/hot" ]] || die "public/hot exists (Vite dev server); refusing to package a dev build"
if find "$build" -name '*.map' -print -quit | grep -q .; then
    die "source maps found in public/build; production builds must not ship them"
fi
if find "$build" \( -type l -o -name '.env*' \) -print -quit | grep -q .; then
    die "symlinks or .env files found in public/build"
fi

# Record exactly which commit these assets belong to; deploy.sh refuses a mismatch.
printf '{"source_sha":"%s","built_at":"%s","builder":"%s"}\n' \
    "$sha" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "${GITHUB_RUN_ID:+github-actions-run-$GITHUB_RUN_ID}" \
    > "$build/build-info.json"

mkdir -p "$out"
name="brivia-frontend-$sha.tar.gz"

# Deterministic-ish archive: sorted entries, no owner/user names.
tar --sort=name --owner=0 --group=0 --numeric-owner \
    -czf "$out/$name" -C "$root/public" build

(cd "$out" && sha256sum "$name" > "$name.sha256")

echo "package-frontend: created $out/$name"
cat "$out/$name.sha256"
