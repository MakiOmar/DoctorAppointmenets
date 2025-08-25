#!/bin/bash

# Staging API Test Script
# This script tests the API endpoints on the staging server

STAGING_URL="https://staging.jalsah.app"
API_BASE="/api/ai"

echo "🔍 Testing Staging API Endpoints"
echo "=================================="
echo "Staging URL: $STAGING_URL"
echo "API Base: $API_BASE"
echo "Test Time: $(date)"
echo ""

# Function to test an endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    echo "🧪 Testing: $method $endpoint"
    echo "----------------------------------------"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" "$STAGING_URL$API_BASE$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X "$method" \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$STAGING_URL$API_BASE$endpoint")
    fi
    
    # Extract status code (last line)
    status_code=$(echo "$response" | tail -n1)
    # Extract response body (all lines except last)
    response_body=$(echo "$response" | head -n -1)
    
    echo "Status Code: $status_code"
    echo "Response:"
    echo "$response_body" | jq '.' 2>/dev/null || echo "$response_body"
    echo ""
}

# Test 1: Ping endpoint
echo "1️⃣ Testing Ping Endpoint"
test_endpoint "GET" "/ping"

# Test 2: Debug endpoint
echo "2️⃣ Testing Debug Endpoint"
test_endpoint "GET" "/debug"

# Test 3: Registration endpoint
echo "3️⃣ Testing Registration Endpoint"
registration_data='{
    "first_name": "Test",
    "last_name": "User",
    "age": 25,
    "email": "test'$(date +%s)'@example.com",
    "phone": "+1234567890",
    "whatsapp": "+1234567890",
    "country": "Egypt",
    "password": "testpassword123",
    "nonce": "'$(openssl rand -hex 16)'"
}'
test_endpoint "POST" "/auth/register" "$registration_data"

# Test 4: Login endpoint
echo "4️⃣ Testing Login Endpoint"
login_data='{
    "email": "test@example.com",
    "password": "testpassword123",
    "nonce": "'$(openssl rand -hex 16)'"
}'
test_endpoint "POST" "/auth" "$login_data"

# Test 5: Therapists endpoint
echo "5️⃣ Testing Therapists Endpoint"
test_endpoint "GET" "/therapists"

# Test 6: Diagnoses endpoint
echo "6️⃣ Testing Diagnoses Endpoint"
test_endpoint "GET" "/diagnoses"

echo "✅ All tests completed!"
echo ""
echo "📊 Summary:"
echo "- Check the status codes above"
echo "- 200-299: Success"
echo "- 400-499: Client error (check request format)"
echo "- 500-599: Server error (check server logs)"
echo "- 401: Unauthorized (authentication issue)"
echo "- 404: Not found (endpoint doesn't exist)"
