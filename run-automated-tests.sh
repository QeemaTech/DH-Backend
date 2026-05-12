#!/bin/bash

# Automated API Testing Script
# Usage: ./run-automated-tests.sh

echo "🚀 Starting Automated API Tests..."
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Run PHPUnit tests
echo -e "${YELLOW}Running PHPUnit Tests...${NC}"
php artisan test --filter=ApiAutomatedTest

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ PHPUnit Tests Passed${NC}"
else
    echo -e "${RED}✗ PHPUnit Tests Failed${NC}"
fi

echo ""
echo -e "${YELLOW}Running API Endpoints Tests...${NC}"
php artisan test --filter=ApiEndpointsTest

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ API Endpoints Tests Passed${NC}"
else
    echo -e "${RED}✗ API Endpoints Tests Failed${NC}"
fi

echo ""
echo "📊 Test Summary:"
php artisan test --filter=ApiAutomatedTest --filter=ApiEndpointsTest --compact

echo ""
echo -e "${GREEN}Automated Testing Complete!${NC}"
