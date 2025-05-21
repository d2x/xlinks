#!/bin/bash

# Change to the script's directory to ensure we're working with the correct files
cd "$(dirname "$0")"

# Step 1: Determine the most recent version from CHANGELOG.md
# Assumes CHANGELOG.md has entries like "## [1.1.1] - YYYY-MM-DD"
VERSION=$(sed -n 's/^## \[\([0-9.]*\)\].*/\1/p' CHANGELOG.md | head -1)
if [ -z "$VERSION" ]; then
  echo "Error: Could not determine version from CHANGELOG.md"
  exit 1
fi
echo "Preparing to release version $VERSION"

# Step 2: Update xlinks.php
# Update the plugin header (e.g., "Version: 1.1.1")
sed -i "s/^Version: .*/Version: $VERSION/" xlinks.php
# Update the update checker (e.g., "$current_version = '1.1.1';")
sed -i "s/\$current_version = '[0-9.]*';/\$current_version = '$VERSION';/" xlinks.php
echo "Updated version in xlinks.php"

# Step 3: Update readme.txt
# Update the Stable tag (e.g., "Stable tag: 1.1.1")
sed -i "s/^Stable tag: .*/Stable tag: $VERSION/" readme.txt

# Extract the full changes (including heading) and the changes list (excluding heading) from CHANGELOG.md
FULL_CHANGES=$(sed -n "/^## \[$VERSION\]/,/^## \[/p" CHANGELOG.md | sed '$d')
CHANGES=$(echo "$FULL_CHANGES" | tail -n +2)

# Format changes for readme.txt Changelog section (convert "-" to "*")
FORMATTED_CHANGES=$(echo "$CHANGES" | sed 's/^-/ */')

# Insert new Changelog entry after "== Changelog =="
NEW_ENTRY="= $VERSION =\n$FORMATTED_CHANGES\n"
awk -v new="$NEW_ENTRY" '/== Changelog ==/ {print; print new; next} 1' readme.txt > tmp && mv tmp readme.txt

# Prompt user for Upgrade Notice and insert it after "== Upgrade Notice =="
read -p "Enter the upgrade notice for version $VERSION: " UPGRADE_NOTICE
NEW_UPGRADE="= $VERSION =\n$UPGRADE_NOTICE\n"
awk -v new="$NEW_UPGRADE" '/== Upgrade Notice ==/ {print; print new; next} 1' readme.txt > tmp && mv tmp readme.txt
echo "Updated Stable tag, Changelog, and Upgrade Notice in readme.txt"

# Step 4: Commit changes
git commit -am "Release version $VERSION"
echo "Committed changes with message: 'Release version $VERSION'"

# Step 5: Push changes to the remote repository
git push
echo "Pushed changes to remote repository"

# Step 6: Create GitHub release using the full changes (including heading) as release notes
gh release create "v$VERSION" --title "Version $VERSION" --notes "$FULL_CHANGES"
echo "Created GitHub release for version $VERSION"