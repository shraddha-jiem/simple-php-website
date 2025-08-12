#!/bin/bash

# Test Documentation Generator Script
# Generates Mermaid diagrams from Codeception test cases

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Test Documentation Generator${NC}"
echo "=================================="

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${YELLOW}⚠️  PHP is required but not installed${NC}"
    exit 1
fi

# Create docs directory if it doesn't exist
mkdir -p docs/diagrams

# Run the PHP diagram generator
echo -e "${BLUE}📊 Generating test diagrams...${NC}"
php scripts/generate-test-diagrams.php

# Check if diagrams were generated
if [ -d "docs/diagrams" ] && [ "$(ls -A docs/diagrams)" ]; then
    echo -e "${GREEN}✅ Test diagrams generated successfully!${NC}"
    echo ""
    echo "Generated files:"
    ls -la docs/diagrams/
    echo ""
    echo -e "${BLUE}📖 View diagrams:${NC}"
    echo "- Open docs/diagrams/README.md for an overview"
    echo "- Individual test class diagrams are in docs/diagrams/"
    echo "- Use GitHub, GitLab, or any Markdown viewer that supports Mermaid"
else
    echo -e "${YELLOW}⚠️  No diagrams were generated${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 Documentation generation complete!${NC}"
