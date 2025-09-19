#!/bin/bash

# StreamPulse Release Script
# This script helps tag and release versions of StreamPulse

# Text formatting
BOLD='\033[1m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BOLD}${BLUE}StreamPulse Release Manager${NC}"
echo "=================================================="

# Check if git is available
if ! command -v git &> /dev/null; then
    echo -e "${RED}Error: git is not installed or not in the PATH${NC}"
    exit 1
fi

# Check if we're in a git repository
if ! git rev-parse --is-inside-work-tree &> /dev/null; then
    echo -e "${RED}Error: Not in a git repository${NC}"
    exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}Warning: You have uncommitted changes${NC}"
    echo "It's recommended to commit all changes before creating a release"
    read -p "Continue anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Release canceled"
        exit 1
    fi
fi

# Fetch the latest changes
echo -e "\n${BLUE}Fetching latest changes from remote...${NC}"
git fetch

# Show current version
echo -e "\n${BLUE}Current tags:${NC}"
git tag | sort -V | tail -n 5

# Ask for version
echo -e "\n${BLUE}Enter the new version tag (e.g., v0.1.0-beta):${NC}"
read version

# Validate version format (simple check)
if [[ ! $version =~ ^v[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9]+)?$ ]]; then
    echo -e "${YELLOW}Warning: Version format doesn't match the recommended pattern v0.0.0(-suffix)${NC}"
    read -p "Continue anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Release canceled"
        exit 1
    fi
fi

# Check if tag already exists
if git rev-parse "$version" >/dev/null 2>&1; then
    echo -e "${RED}Error: Tag $version already exists${NC}"
    exit 1
fi

# Ask for release notes
echo -e "\n${BLUE}Enter release notes (multi-line, press Ctrl+D when done):${NC}"
release_notes=$(cat)

# Confirm
echo -e "\n${YELLOW}You are about to create the following release:${NC}"
echo -e "${BOLD}Version:${NC} $version"
echo -e "${BOLD}Release Notes:${NC}\n$release_notes"
echo
read -p "Create release? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Release canceled"
    exit 1
fi

# Create and push tag
echo -e "\n${BLUE}Creating and pushing tag $version...${NC}"
git tag -a "$version" -m "$release_notes"
git push origin "$version"

echo -e "\n${GREEN}Success! Tag $version has been created and pushed to the repository.${NC}"
echo -e "Your release is now available at: https://github.com/saravanasai/stream-pulse/releases/tag/$version"
echo
echo -e "${BLUE}Next steps:${NC}"
echo "1. Go to GitHub and edit the release to add more details if needed"
echo "2. Announce the release to the community"
echo "3. Monitor for feedback and issues"
echo
echo -e "${BOLD}${GREEN}StreamPulse $version has been released!${NC}"
