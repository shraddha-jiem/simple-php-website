#!/bin/bash

# Generate Test Documentation Diagrams
# Usage: ./generate-docs.sh [commits_to_check] [test_dir] [output_dir]

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
COMMITS_TO_CHECK=${1:-5}
TEST_DIR=${2:-"tests/Acceptance"}
OUTPUT_DIR=${3:-"docs/diagrams"}

echo -e "${BLUE}🚀 Generating Test Documentation Diagrams${NC}"
echo -e "${BLUE}📊 Checking last ${COMMITS_TO_CHECK} commits for changes${NC}"

# Run the PHP script
php scripts/generate-test-diagrams.php "$COMMITS_TO_CHECK" "$TEST_DIR" "$OUTPUT_DIR"

echo -e "${GREEN}✅ Documentation generated successfully!${NC}"
echo -e "${YELLOW}📁 Check ${OUTPUT_DIR}/ for generated files${NC}"

# Show generated files
echo -e "${BLUE}📋 Generated files:${NC}"
ls -la "$OUTPUT_DIR/"
