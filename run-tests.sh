#!/bin/bash

# Test runner script for Codeception with various options
# Usage: ./run-tests.sh [option]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if server is running
check_server() {
    if curl -s http://localhost:8000 > /dev/null; then
        print_success "Server is running on http://localhost:8000"
        return 0
    else
        print_warning "Server is not running on http://localhost:8000"
        return 1
    fi
}

# Start server if not running
start_server() {
    if ! check_server; then
        print_status "Starting PHP development server..."
        php -S localhost:8000 > /dev/null 2>&1 &
        SERVER_PID=$!
        echo $SERVER_PID > .server.pid
        
        # Wait for server to start
        sleep 3
        
        if check_server; then
            print_success "Server started with PID $SERVER_PID"
        else
            print_error "Failed to start server"
            exit 1
        fi
    fi
}

# Stop server
stop_server() {
    if [ -f .server.pid ]; then
        SERVER_PID=$(cat .server.pid)
        if kill -0 $SERVER_PID 2>/dev/null; then
            kill $SERVER_PID
            rm .server.pid
            print_success "Server stopped"
        else
            print_warning "Server process not found"
            rm -f .server.pid
        fi
    else
        print_warning "No server PID file found"
    fi
}

# Run tests
run_tests() {
    local suite=${1:-""}
    local options=${2:-""}
    
    if [ -n "$suite" ]; then
        print_status "Running $suite tests..."
        vendor/bin/codecept run $suite $options
    else
        print_status "Running all tests..."
        vendor/bin/codecept run $options
    fi
}

# Show usage
show_usage() {
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  start               Start the PHP development server"
    echo "  stop                Stop the PHP development server"
    echo "  test                Run all tests"
    echo "  acceptance          Run acceptance tests only"
    echo "  navigation          Run website navigation tests only"
    echo "  database            Run database status tests only"
    echo "  steps               Run tests with detailed steps"
    echo "  debug               Run tests with debug output"
    echo "  docs                Generate test documentation diagrams"
    echo "  help                Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 start                          # Start server"
    echo "  $0 test                           # Run all tests"
    echo "  $0 acceptance                     # Run acceptance tests"
    echo "  $0 navigation                     # Run navigation tests only"
    echo "  $0 database                       # Run database tests only"
    echo "  $0 steps                          # Run with step details"
    echo "  $0 debug                          # Run with debug output"
    echo "  $0 docs                           # Generate documentation"
}

# Main script logic
case "$1" in
    "start")
        start_server
        ;;
    "stop")
        stop_server
        ;;
    "test")
        start_server
        run_tests
        ;;
    "acceptance")
        start_server
        run_tests "Acceptance"
        ;;
    "navigation")
        start_server
        vendor/bin/codecept run Acceptance WebsiteNavigationCest
        ;;
    "database")
        start_server
        vendor/bin/codecept run Acceptance DatabaseStatusCest
        ;;
    "steps")
        start_server
        run_tests "" "--steps"
        ;;
    "debug")
        start_server
        run_tests "" "--debug"
        ;;
    "docs")
        print_status "Generating test documentation diagrams..."
        ./scripts/generate-docs.sh
        ;;
    "help"|"--help"|"-h")
        show_usage
        ;;
    "")
        print_status "No command specified, running all tests..."
        start_server
        run_tests
        ;;
    *)
        print_error "Unknown command: $1"
        show_usage
        exit 1
        ;;
esac

print_success "Test run completed!"
