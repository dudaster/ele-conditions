#!/bin/bash
# deploy-svn.sh — sync to WordPress.org SVN trunk + assets, then create a tag
# Usage:
#   ./deploy-svn.sh          — sync to trunk+assets only (review before committing)
#   ./deploy-svn.sh --commit — sync, commit trunk+assets, and create a version tag

set -e

SVN_URL="https://plugins.svn.wordpress.org/ele-conditions"
PLUGIN_DIR="$(cd "$(dirname "$0")/ele-conditions" && pwd)"
ASSETS_DIR="$(cd "$(dirname "$0")/wordpress-assets" && pwd)"
VERSION="$(grep 'Version:' "$PLUGIN_DIR/ele-conditions.php" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
SVN_TMP="$(mktemp -d)/svn"
SVN_ASSETS_TMP="$(mktemp -d)/svn-assets"

echo "Plugin version : $VERSION"
echo "Plugin dir     : $PLUGIN_DIR"
echo "Assets dir     : $ASSETS_DIR"
echo ""

# ── Checkout trunk ───────────────────────────────────────────
echo "Checking out SVN trunk (shallow)..."
svn checkout "$SVN_URL/trunk" "$SVN_TMP" --depth immediates
svn update "$SVN_TMP" --set-depth infinity

# ── Sync ele-conditions/ → trunk ─────────────────────────────
echo "Syncing trunk files..."
rsync -a --delete \
  --exclude='.DS_Store' \
  --exclude='.git' \
  --exclude='.svn' \
  "$PLUGIN_DIR/" "$SVN_TMP/"

# ── Stage trunk new/deleted files in SVN ─────────────────────
cd "$SVN_TMP"
svn add --force . --auto-props --parents --depth infinity -q
svn status | grep '^!' | awk '{print $2}' | while IFS= read -r f; do
  svn delete "$f"
done

echo ""
echo "────────────────────────────────"
echo " SVN status (trunk)"
echo "────────────────────────────────"
svn status
echo ""

# ── Checkout assets ───────────────────────────────────────────
echo "Checking out SVN assets..."
svn checkout "$SVN_URL/assets" "$SVN_ASSETS_TMP" --depth immediates
svn update "$SVN_ASSETS_TMP" --set-depth infinity

# ── Sync wordpress-assets/ → SVN assets/ ─────────────────────
echo "Syncing assets files..."
rsync -a --delete \
  --exclude='.DS_Store' \
  --exclude='.svn' \
  "$ASSETS_DIR/" "$SVN_ASSETS_TMP/"

# ── Stage assets new/deleted files in SVN ────────────────────
cd "$SVN_ASSETS_TMP"
svn add --force . --auto-props --parents --depth infinity -q
svn status | grep '^!' | awk '{print $2}' | while IFS= read -r f; do
  svn delete "$f"
done

echo ""
echo "────────────────────────────────"
echo " SVN status (assets)"
echo "────────────────────────────────"
svn status
echo ""

if [ "${1}" == "--commit" ]; then
  echo "Committing trunk..."
  cd "$SVN_TMP"
  svn commit -m "Release v$VERSION" --username dudaster --force-interactive
  echo ""
  echo "Committing assets..."
  cd "$SVN_ASSETS_TMP"
  svn commit -m "Screenshots v$VERSION" --username dudaster --force-interactive
  echo ""
  echo "Creating tag $VERSION..."
  svn copy "$SVN_URL/trunk" "$SVN_URL/tags/$VERSION" \
    --message "Tag v$VERSION" --username dudaster --force-interactive
  echo ""
  echo "✓ Released v$VERSION to WordPress.org"
else
  echo "Dry run complete. SVN working copies at:"
  echo "  trunk  : $SVN_TMP"
  echo "  assets : $SVN_ASSETS_TMP"
  echo ""
  echo "To commit, run:"
  echo "  ./deploy-svn.sh --commit"
fi
