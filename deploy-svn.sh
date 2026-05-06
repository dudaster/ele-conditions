#!/bin/bash
# deploy-svn.sh — sync plugin/ to WordPress.org SVN trunk, then create a tag
# Usage:
#   ./deploy-svn.sh          — sync to trunk only (review before committing)
#   ./deploy-svn.sh --commit — sync, commit to trunk, and create a version tag

set -e

SVN_URL="https://plugins.svn.wordpress.org/ele-conditions"
PLUGIN_DIR="$(cd "$(dirname "$0")/ele-conditions" && pwd)"
VERSION="$(grep 'Version:' "$PLUGIN_DIR/ele-conditions.php" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
SVN_TMP="$(mktemp -d)/svn"

echo "Plugin version : $VERSION"
echo "Plugin dir     : $PLUGIN_DIR"
echo ""

# ── Checkout trunk ───────────────────────────────────────────
echo "Checking out SVN trunk (shallow)..."
svn checkout "$SVN_URL/trunk" "$SVN_TMP" --depth immediates
svn update "$SVN_TMP" --set-depth infinity

# ── Sync plugin/ → trunk ─────────────────────────────────────
echo "Syncing files..."
rsync -a --delete \
  --exclude='.DS_Store' \
  --exclude='.git' \
  --exclude='.svn' \
  "$PLUGIN_DIR/" "$SVN_TMP/"

# ── Stage new/deleted files in SVN ───────────────────────────
cd "$SVN_TMP"

# Add new files
svn add --force . --auto-props --parents --depth infinity -q

# Remove files that were deleted
svn status | grep '^!' | awk '{print $2}' | while IFS= read -r f; do
  svn delete "$f"
done

echo ""
echo "────────────────────────────────"
echo " SVN status (trunk)"
echo "────────────────────────────────"
svn status
echo ""

if [ "${1}" == "--commit" ]; then
  echo "Committing to trunk..."
  svn commit -m "Release v$VERSION" --username dudaster
  echo ""
  echo "Creating tag $VERSION..."
  svn copy "$SVN_URL/trunk" "$SVN_URL/tags/$VERSION" \
    --message "Tag v$VERSION" --username dudaster
  echo ""
  echo "✓ Released v$VERSION to WordPress.org"
else
  echo "Dry run complete. SVN working copy at:"
  echo "  $SVN_TMP"
  echo ""
  echo "To commit, run:"
  echo "  ./deploy-svn.sh --commit"
fi
