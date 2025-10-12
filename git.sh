#!/bin/bash

# Change this to your local project folder
PROJECT_DIR="./"

# Remote repo SSH URL
REMOTE_REPO="git@github.com:TechSolutionFactory/Carekori-App-Backend.git"

# New branch name
NEW_BRANCH="dev-4.0"

# Move to project directory
cd "$PROJECT_DIR" || { echo "Project directory not found"; exit 1; }

# Initialize Git if not already initialized
if [ ! -d ".git" ]; then
    git init
fi

# Add remote if not already added
if ! git remote | grep -q origin; then
    git remote add origin "$REMOTE_REPO"
fi

# Fetch remote history
git fetch origin

# Create and switch to new branch based on remote default branch
git checkout -b "$NEW_BRANCH" origin/main

# Copy local files into branch (excluding .git)
rsync -av --progress ./ /tmp/tmp_project --exclude .git
rsync -av --progress /tmp/tmp_project/ ./ --exclude .git

# Add all files and commit
git add .
git commit -m "Add local ZIP project files to $NEW_BRANCH"

# Push the new branch to remote
git push -u origin "$NEW_BRANCH"

echo "✅ Local project pushed to remote branch '$NEW_BRANCH'"
