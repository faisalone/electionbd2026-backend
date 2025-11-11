#!/bin/bash

# GitHub Actions Setup Helper Script
# This script helps you gather the information needed for GitHub Secrets

echo "======================================"
echo "GitHub Actions CI/CD Setup Helper"
echo "======================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}This script will help you gather the information needed for GitHub Secrets.${NC}"
echo ""

# Get Server Host
echo -e "${GREEN}1. SERVER_HOST${NC}"
echo "Your server IP address or domain:"
SERVER_HOST=$(curl -s ifconfig.me 2>/dev/null || echo "Unable to detect")
if [ "$SERVER_HOST" != "Unable to detect" ]; then
    echo "   Detected: $SERVER_HOST"
else
    echo "   Please enter your server IP:"
    read -r SERVER_HOST
fi
echo ""

# Get Server Username
echo -e "${GREEN}2. SERVER_USERNAME${NC}"
echo "Your SSH username (usually 'root', 'ubuntu', or 'webadmin'):"
read -r SERVER_USER
echo ""

# Get Server Port
echo -e "${GREEN}3. SERVER_PORT${NC}"
echo "SSH port (press Enter for default 22):"
read -r SERVER_PORT
SERVER_PORT=${SERVER_PORT:-22}
echo ""

# Get SSH Private Key
echo -e "${GREEN}4. SSH_PRIVATE_KEY${NC}"
echo "Looking for SSH private keys in ~/.ssh/..."
echo ""

if [ -f ~/.ssh/id_rsa ]; then
    echo "Found: ~/.ssh/id_rsa"
    KEY_FILE=~/.ssh/id_rsa
elif [ -f ~/.ssh/id_ed25519 ]; then
    echo "Found: ~/.ssh/id_ed25519"
    KEY_FILE=~/.ssh/id_ed25519
else
    echo "No default SSH key found."
    echo "Enter the path to your SSH private key:"
    read -r KEY_FILE
fi
echo ""

# Get API URL for frontend
echo -e "${GREEN}5. NEXT_PUBLIC_API_URL (Frontend only)${NC}"
echo "Your Laravel API URL (e.g., https://api.electionbd2026.com or http://$SERVER_HOST:8000):"
read -r API_URL
echo ""

echo "======================================"
echo "Summary of Information Gathered"
echo "======================================"
echo ""
echo "SERVER_HOST: $SERVER_HOST"
echo "SERVER_USERNAME: $SERVER_USER"
echo "SERVER_PORT: $SERVER_PORT"
echo "SSH_PRIVATE_KEY: [Content of $KEY_FILE]"
echo "NEXT_PUBLIC_API_URL: $API_URL"
echo ""

echo "======================================"
echo "GitHub Secrets Configuration"
echo "======================================"
echo ""
echo "Now, add these secrets to your GitHub repositories:"
echo ""
echo "1. Go to: https://github.com/faisalone/electionbd2026-backend/settings/secrets/actions"
echo "2. Click 'New repository secret'"
echo "3. Add the following secrets:"
echo ""
echo "   Name: SERVER_HOST"
echo "   Value: $SERVER_HOST"
echo ""
echo "   Name: SERVER_USERNAME"
echo "   Value: $SERVER_USER"
echo ""
echo "   Name: SERVER_PORT"
echo "   Value: $SERVER_PORT"
echo ""
echo "   Name: SSH_PRIVATE_KEY"
echo "   Value: [Copy the entire content below]"
echo ""
if [ -f "$KEY_FILE" ]; then
    echo "   --- BEGIN SSH PRIVATE KEY ---"
    cat "$KEY_FILE"
    echo "   --- END SSH PRIVATE KEY ---"
else
    echo "   [SSH key file not found: $KEY_FILE]"
fi
echo ""
echo ""
echo "4. For FRONTEND repository, go to:"
echo "   https://github.com/faisalone/electionbd2026-frontend/settings/secrets/actions"
echo "   And add all the above secrets PLUS:"
echo ""
echo "   Name: NEXT_PUBLIC_API_URL"
echo "   Value: $API_URL"
echo ""
echo "======================================"
echo "Quick Copy Format (for easy pasting)"
echo "======================================"
echo ""
echo "SERVER_HOST=$SERVER_HOST"
echo "SERVER_USERNAME=$SERVER_USER"
echo "SERVER_PORT=$SERVER_PORT"
echo "NEXT_PUBLIC_API_URL=$API_URL"
echo ""
echo "======================================"
echo "Next Steps"
echo "======================================"
echo ""
echo "1. Copy the secrets to GitHub (both repositories)"
echo "2. Merge your 'faisal' branch to 'main' branch"
echo "3. Watch the Actions tab on GitHub for automatic deployment!"
echo ""
echo "Documentation: See .github/CI_CD_SETUP.md for detailed instructions"
echo ""
