# Orders API Documentation

## Overview
The Orders API provides REST endpoints for managing order statuses. All endpoints require **API token authentication** and return JSON responses.

## Base URL
```
http://yourserver.com/index.php?page=orders
```

---

## Authentication

### Token-Based Authentication
All API endpoints require an API token for authentication. The token can be provided in three ways:

#### 1. Authorization Header (Recommended)
```
Authorization: Bearer YOUR_API_TOKEN
```

#### 2. Query Parameter
```
GET ?page=orders&action=api_update_order_status&api_token=YOUR_API_TOKEN
```

#### 3. POST Parameter
```
POST data: api_token=YOUR_API_TOKEN
```

### Generating an API Token

#### Endpoint
```
POST ?page=orders&action=api_generate_token
```

#### Description
Generate a new API token using either:
1. **Username/Password** - For external applications or non-logged-in users
2. **Session Authentication** - For logged-in users in the web application

#### Request

**Method:** POST

**Content-Type:** `application/x-www-form-urlencoded` or `application/json`

**Parameters:**

| Parameter | Type | Optional | Description |
|-----------|------|----------|-------------|
| `username` | string | Yes* | User email or phone number for credential-based auth |
| `password` | string | Yes* | User password for credential-based auth |
| `expiry_days` | integer | Yes | Number of days until token expires (1-3650, default: 365) |

*Either provide `username` and `password` for credential-based auth, OR be logged in to the session for session-based auth.

#### Example 1: Credential-Based Request (Username/Password)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -d "username=user@example.com" \
  -d "password=userpassword123" \
  -d "expiry_days=365"
```

#### Example 2: Credential-Based Request (Phone)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -d "username=9876543210" \
  -d "password=userpassword123" \
  -d "expiry_days=730"
```

#### Example 3: Credential-Based JSON Request
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "user@example.com",
    "password": "userpassword123",
    "expiry_days": 365
  }'
```

#### Example 4: Session-Based Request (Logged-in User)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -d "expiry_days=365" \
  --cookie "PHPSESSID=your_session_id"
```

#### Response (Success - 200)
```json
{
  "success": true,
  "message": "API token generated successfully.",
  "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
  "expires_at": "2027-03-22 10:30:00",
  "user_id": 5
}
```

#### Response (Error - 401 - Invalid Credentials)
```json
{
  "success": false,
  "message": "Invalid username or password."
}
```

#### Response (Error - 401 - No Auth)
```json
{
  "success": false,
  "message": "Unauthorized: Provide username/password or login to generate API token."
}
```

#### Response (Error - 400 - Invalid Expiry)
```json
{
  "success": false,
  "message": "Invalid expiry_days. Must be between 1 and 3650."
}
```

### Token Security

- **Store securely**: Never commit tokens to version control
- **Expiration**: Tokens automatically expire after the specified duration
- **Revocation**: Tokens can be deactivated in the database if needed
- **Scope**: Tokens inherit the permissions of the user who created them
- **Rotation**: Generate new tokens periodically and revoke old ones

---

## 1. Update Single Order Status

### Endpoint
```
POST ?page=orders&action=api_update_order_status
```

### Description
Update the status of a single order with optional metadata (remarks, ESD, priority, agent assignment).

### Request

**Method:** POST

**Content-Type:** `application/x-www-form-urlencoded` or `application/json`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_number` | string | Yes | The order number (will be used to fetch order_id) |
| `status` | string | Yes | New order status (e.g., 'pending', 'processing', 'shipped') |
| `remarks` | string | No | Notes or comments about the order |
| `esd` | string | No | Expected Ship Date (format: YYYY-MM-DD) |
| `priority` | string | No | Order priority level |
| `agent_id` | integer | No | ID of the agent to assign the order to |

### Example Request (Form Data with Bearer Token)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_update_order_status" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d "order_number=ORD-2026-001" \
  -d "status=shipped" \
  -d "esd=2026-03-25" \
  -d "priority=high" \
  -d "remarks=Order ready for dispatch"
```

### Example Request (JSON with Bearer Token)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_update_order_status" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_number": "ORD-2026-001",
    "status": "shipped",
    "esd": "2026-03-25",
    "priority": "high",
    "remarks": "Order ready for dispatch"
  }'
```

### Example Request (Query Parameter Token)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_update_order_status&api_token=YOUR_API_TOKEN" \
  -d "order_number=ORD-2026-001" \
  -d "status=shipped"
```

### Response (Success - 200)
```json
{
  "success": true,
  "message": "Order status updated successfully.",
  "data": {
    "order_id": 123,
    "previous_status": "processing",
    "new_status": "shipped",
    "updated_at": "2026-03-22 14:30:00"
  }
}
```

### Response (Error - 400/404/500)
```json
{
  "success": false,
  "message": "Error description here"
}
```

### Status Codes
- `200 OK` - Status updated successfully
- `400 Bad Request` - Missing or invalid parameters
- `404 Not Found` - Order not found
- `405 Method Not Allowed` - Wrong HTTP method
- `500 Internal Server Error` - Server error

---

## 2. Bulk Update Order Status

### Endpoint
```
POST ?page=orders&action=api_bulk_update_order_status
```

### Description
Update the status of multiple orders at once.

### Request

**Method:** POST

**Content-Type:** `application/x-www-form-urlencoded` or `application/json`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_numbers[]` or `order_numbers` | array/string | Yes | Array of order numbers OR comma-separated order numbers |
| `status` | string | Yes | New status for all orders |

### Example Request (Form Data - Array with Bearer Token)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_bulk_update_order_status" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d "order_numbers[]=ORD-2026-101" \
  -d "order_numbers[]=ORD-2026-102" \
  -d "order_numbers[]=ORD-2026-103" \
  -d "status=shipped"
```

### Example Request (Form Data - Comma-separated with Bearer Token)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_bulk_update_order_status" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d "order_numbers=ORD-2026-101,ORD-2026-102,ORD-2026-103" \
  -d "status=shipped"
```

### Example Request (JSON with Bearer Token)
```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_bulk_update_order_status" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_numbers": ["ORD-2026-101", "ORD-2026-102", "ORD-2026-103"],
    "status": "shipped"
  }'
```

### Response (Success - 200)
```json
{
  "success": true,
  "message": "3 order(s) updated successfully.",
  "data": {
    "successful": 3,
    "failed": 0,
    "errors": []
  }
}
```

### Response (Partial Success - 200)
```json
{
  "success": false,
  "message": "2 order(s) updated successfully.",
  "data": {
    "successful": 2,
    "failed": 1,
    "errors": [
      "Order not found with number: ORD-2026-999"
    ]
  }
}
```

---

## 3. Get Order Status History

### Endpoint
```
GET ?page=orders&action=api_order_status_history&order_number=ORD-2026-001
```

### Description
Retrieve the complete status history/audit log for an order.

### Request

**Method:** GET

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_number` | string | Yes | The order number |

### Example Request (with Bearer Token)
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  "http://yourserver.com/index.php?page=orders&action=api_order_status_history&order_number=ORD-2026-001"
```

### Example Request (with Query Parameter Token)
```bash
curl "http://yourserver.com/index.php?page=orders&action=api_order_status_history&order_number=ORD-2026-001&api_token=YOUR_API_TOKEN"
```

### Response (Success - 200)
```json
{
  "success": true,
  "order_id": 123,
  "order_number": "ORD-2026-001",
  "current_status": "shipped",
  "history": [
    {
      "id": 1,
      "order_id": 123,
      "status": "Status: pending",
      "changed_by": 5,
      "changed_by_username": "John Doe",
      "change_date": "2026-03-20 10:15:00"
    },
    {
      "id": 2,
      "order_id": 123,
      "status": "Status: processing",
      "changed_by": 5,
      "changed_by_username": "John Doe",
      "change_date": "2026-03-21 09:30:00"
    },
    {
      "id": 3,
      "order_id": 123,
      "status": "Status: shipped",
      "changed_by": 5,
      "changed_by_username": "John Doe",
      "change_date": "2026-03-22 14:30:00"
    }
  ]
}
```

### Response (Error - 404)
```json
{
  "success": false,
  "message": "Order not found."
}
```

---

## Automatic Logging & Notifications

When using these API endpoints, the system automatically:
- ✅ Logs status changes to `vp_order_status_log` table
- ✅ Logs agent assignments when agent_id changes
- ✅ Logs ESD, priority, and remarks changes separately
- ✅ Sends notifications to assigned agents
- ✅ Updates `agent_assign_date` when agent changes

## Error Handling

All endpoints return appropriate HTTP status codes:
- `200` - Success
- `400` - Bad Request (missing/invalid parameters)
- `401` - Unauthorized (missing or invalid API token)
- `404` - Not Found (order doesn't exist)
- `405` - Method Not Allowed (wrong HTTP verb)
- `500` - Internal Server Error

### Common Error Responses

#### Missing API Token (401)
```json
{
  "success": false,
  "message": "Unauthorized: API token is required."
}
```

#### Invalid API Token (401)
```json
{
  "success": false,
  "message": "Unauthorized: Invalid or expired API token."
}
```

#### Order Not Found (404)
```json
{
  "success": false,
  "message": "Order not found with number: ORD-2026-001"
}
```

#### Missing Required Parameter (400)
```json
{
  "success": false,
  "message": "Error: order_number and status are required parameters."
}
```

Each response includes a `success` boolean and descriptive `message` field.

## Integration Examples

### JavaScript/Fetch API (with Bearer Token)
```javascript
// First, generate a token
const generateToken = async () => {
  const response = await fetch('index.php?page=orders&action=api_generate_token', {
    method: 'POST',
    body: new URLSearchParams({
      expiry_days: '365'
    })
  });
  const data = await response.json();
  return data.token;
};

// Use token in API calls
const apiToken = 'YOUR_API_TOKEN_HERE';

// Update single order
fetch('index.php?page=orders&action=api_update_order_status', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    order_number: 'ORD-2026-001',
    status: 'shipped',
    remarks: 'Dispatched via carrier'
  })
})
.then(res => res.json())
.then(data => console.log(data));

// Bulk update
fetch('index.php?page=orders&action=api_bulk_update_order_status', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    order_numbers: ['ORD-2026-101', 'ORD-2026-102', 'ORD-2026-103'],
    status: 'shipped'
  })
})
.then(res => res.json())
.then(data => console.log(data));

// Get status history
fetch(`index.php?page=orders&action=api_order_status_history&order_number=ORD-2026-001`, {
  headers: {
    'Authorization': `Bearer ${apiToken}`
  }
})
  .then(res => res.json())
  .then(data => console.log(data));
```

### PHP/cURL (with Bearer Token)
```php
$apiToken = 'YOUR_API_TOKEN_HERE';

// Update single order
$ch = curl_init('http://yourserver.com/index.php?page=orders&action=api_update_order_status');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer ' . $apiToken,
  'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
  'order_number' => 'ORD-2026-001',
  'status' => 'shipped',
  'remarks' => 'Ready for dispatch'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

var_dump($data);

// Generate token
$ch = curl_init('http://yourserver.com/index.php?page=orders&action=api_generate_token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
  'expiry_days' => 365
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$tokenResponse = curl_exec($ch);
$tokenData = json_decode($tokenResponse, true);
curl_close($ch);

echo "Generated Token: " . $tokenData['token'];
```

---

## Token Management

### Authentication Methods

#### 1. Credential-Based Authentication (Recommended for External Applications)
External applications can generate tokens directly using username and password without needing a browser session:

```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "user@example.com",
    "password": "userpassword123",
    "expiry_days": 365
  }'
```

**Advantages:**
- No session needed
- Can be called from scripts, external apps, CI/CD pipelines
- More secure for server-to-server communication
- Username can be email or phone number

#### 2. Session-Based Authentication (For Web Application)
Logged-in users in the web application can generate tokens:

```bash
curl -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -d "expiry_days=365" \
  --cookie "PHPSESSID=your_session_id"
```

**Advantages:**
- Uses existing web session
- User already authenticated
- Convenient in web interface

### Storing Tokens Safely

1. **Never hardcode tokens** in your source code
2. **Use environment variables** for production tokens
3. **Store in .env file** (not committed to git)
4. **Use secrets management** for CI/CD pipelines
5. **Rotate tokens regularly** - generate new ones and revoke old ones
6. **Use different tokens** for different integrations

```bash
# Example .env file (NOT committed to git)
API_USERNAME=service-account@example.com
API_PASSWORD=strong-password-here
API_TOKEN=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
API_BASE_URL=http://yourserver.com
```

### Auto-Generating Tokens from Code

Instead of storing tokens, generate them dynamically from credentials:

```php
// credentials.php (in secure location, not web-accessible)
define('API_USERNAME', 'service-account@example.com');
define('API_PASSWORD', 'strong-password-here');

// api-usage.php
require 'credentials.php';

function getApiToken() {
    $ch = curl_init('http://yourserver.com/index.php?page=orders&action=api_generate_token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => API_USERNAME,
        'password' => API_PASSWORD,
        'expiry_days' => 1  // Get fresh token daily
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data['success'] ? $data['token'] : null;
}

// Use token for API calls
$token = getApiToken();
if ($token) {
    // Make API calls with token
}
```

This ensures you never hardcode tokens in your codebase.

#### PHP Example: Generate token using credentials (for external applications)
```php
// External application - no session required
$username = 'user@example.com';  // or phone number
$password = 'userpassword123';
$expiryDays = 365;

$ch = curl_init('http://yourserver.com/index.php?page=orders&action=api_generate_token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
  'username' => $username,
  'password' => $password,
  'expiry_days' => $expiryDays
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tokenData = json_decode($response, true);

if ($httpCode === 200 && $tokenData['success']) {
  $apiToken = $tokenData['token'];
  echo "Token generated: " . $apiToken;
  echo "Expires: " . $tokenData['expires_at'];
  
  // Now use this token for subsequent API calls
  // Store it securely in a .env file or environment variable
} else {
  echo "Error: " . $tokenData['message'];
}
```

#### Node.js Example: Generate token and make API calls
```javascript
const https = require('https');

async function generateToken(username, password, expiryDays = 365) {
  return new Promise((resolve, reject) => {
    const data = JSON.stringify({
      username: username,
      password: password,
      expiry_days: expiryDays
    });

    const options = {
      hostname: 'yourserver.com',
      path: '/index.php?page=orders&action=api_generate_token',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length
      }
    };

    const req = https.request(options, (res) => {
      let body = '';
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        try {
          const result = JSON.parse(body);
          if (res.statusCode === 200 && result.success) {
            resolve(result.token);
          } else {
            reject(new Error(result.message));
          }
        } catch (e) {
          reject(e);
        }
      });
    });

    req.on('error', reject);
    req.write(data);
    req.end();
  });
}

async function updateOrderStatus(token, orderNumber, status) {
  const data = JSON.stringify({
    order_number: orderNumber,
    status: status
  });

  return new Promise((resolve, reject) => {
    const options = {
      hostname: 'yourserver.com',
      path: '/index.php?page=orders&action=api_update_order_status',
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Content-Length': data.length
      }
    };

    const req = https.request(options, (res) => {
      let body = '';
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        const result = JSON.parse(body);
        resolve(result);
      });
    });

    req.on('error', reject);
    req.write(data);
    req.end();
  });
}

// Usage
(async () => {
  try {
    // Generate token once
    const token = await generateToken('user@example.com', 'userpassword123', 365);
    console.log('Token:', token);

    // Use token for multiple API calls
    const result = await updateOrderStatus(token, 'ORD-2026-001', 'shipped');
    console.log('Update result:', result);
  } catch (error) {
    console.error('Error:', error.message);
  }
})();
```

#### Python Example: Generate token using requests library
```python
import requests
import json
from datetime import datetime, timedelta

class OrdersAPI:
    def __init__(self, base_url):
        self.base_url = base_url
        self.token = None

    def generate_token(self, username, password, expiry_days=365):
        """Generate API token using credentials"""
        url = f"{self.base_url}/index.php?page=orders&action=api_generate_token"
        
        payload = {
            'username': username,
            'password': password,
            'expiry_days': expiry_days
        }
        
        response = requests.post(url, json=payload, verify=False)
        
        if response.status_code == 200:
            data = response.json()
            if data['success']:
                self.token = data['token']
                return {
                    'success': True,
                    'token': data['token'],
                    'expires_at': data['expires_at']
                }
        
        return {
            'success': False,
            'error': response.json().get('message', 'Token generation failed')
        }

    def update_order_status(self, order_number, status, **kwargs):
        """Update order status using token"""
        if not self.token:
            return {'success': False, 'error': 'No token available. Generate one first.'}
        
        url = f"{self.base_url}/index.php?page=orders&action=api_update_order_status"
        
        headers = {
            'Authorization': f'Bearer {self.token}',
            'Content-Type': 'application/json'
        }
        
        payload = {
            'order_number': order_number,
            'status': status,
            **kwargs
        }
        
        response = requests.post(url, json=payload, headers=headers, verify=False)
        return response.json()

# Usage
api = OrdersAPI('http://yourserver.com')

# Generate token once
result = api.generate_token('user@example.com', 'userpassword123', 365)
if result['success']:
    print(f"Token generated: {result['token']}")
    print(f"Expires: {result['expires_at']}")
    
    # Use token for API calls
    update_result = api.update_order_status(
        'ORD-2026-001',
        'shipped',
        remarks='Order dispatched',
        esd='2026-03-25'
    )
    print(update_result)
else:
    print(f"Error: {result['error']}")
```

### Storing Tokens Safely

1. **Never hardcode tokens** in your source code
2. **Use environment variables** for production tokens
3. **Store in .env file** (not committed to git)
4. **Use secrets management** for CI/CD pipelines
5. **Rotate tokens regularly** - generate new ones and revoke old ones

```bash
# Example .env file (NOT committed to git)
API_TOKEN=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
API_BASE_URL=http://yourserver.com
```

### Revoking Tokens

To revoke a token, update the `is_active` flag in the `order_api_tokens` table:

```sql
UPDATE order_api_tokens 
SET is_active = 0 
WHERE token = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
```

### Troubleshooting Token Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| 401 Unauthorized (API calls) | Missing Bearer token | Add `Authorization: Bearer TOKEN` header to requests |
| 401 Unauthorized (API calls) | Invalid token | Generate new token or verify token format |
| 401 Unauthorized (API calls) | Expired token | Tokens expire after the specified days; generate new token |
| 401 Unauthorized (API calls) | Inactive token | Check is_active flag in order_api_tokens table; token may have been revoked |
| 401 Unauthorized (token generation) | Missing credentials | Provide either username/password OR ensure user is logged in |
| 401 Unauthorized (token generation) | Invalid username | Verify email or phone number exists in vp_users table |
| 401 Unauthorized (token generation) | Invalid password | Verify password is correct (case-sensitive) |
| 401 Unauthorized (token generation) | User is deleted | Cannot generate tokens for soft-deleted users (is_deleted = 1) |
| 400 Bad Request | Invalid expiry_days | Use value between 1 and 3650 |
| 500 Internal Server Error (token generation) | Database error | Check database connectivity and order_api_tokens table exists |
| Different token each time | Expected behavior | New token generated each request; store the token for reuse |

### Debugging API Issues

```bash
# Test token generation with verbose output
curl -v -X POST "http://yourserver.com/index.php?page=orders&action=api_generate_token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "user@example.com",
    "password": "userpassword123",
    "expiry_days": 365
  }'

# Test with generated token
TOKEN="your_generated_token_here"
curl -v -X GET "http://yourserver.com/index.php?page=orders&action=api_order_status_history&order_number=ORD-2026-001" \
  -H "Authorization: Bearer $TOKEN"

# Check if token is still valid in database
mysql -u root -p your_database << EOF
SELECT id, token, is_active, expires_at, NOW() as current_time 
FROM order_api_tokens 
WHERE token = 'your_token_here' 
LIMIT 1;
EOF
```

---

## Common Use Cases for External Integrations

### E-Commerce Platform Integration
Automatically update order status when fulfillment is complete:

```bash
#!/bin/bash
# fetch-orders.sh - Run daily via cron

API_USERNAME="ecommerce-user@example.com"
API_PASSWORD="ecommerce-password"
BASE_URL="http://yourserver.com"

# Get token
TOKEN_RESPONSE=$(curl -s -X POST "$BASE_URL/index.php?page=orders&action=api_generate_token" \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"$API_USERNAME\",\"password\":\"$API_PASSWORD\",\"expiry_days\":1}")

TOKEN=$(echo $TOKEN_RESPONSE | jq -r '.token')

# Update order statuses
curl -s -X POST "$BASE_URL/index.php?page=orders&action=api_bulk_update_order_status" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_numbers": ["ORD-2026-001", "ORD-2026-002", "ORD-2026-003"],
    "status": "shipped"
  }'
```

### Third-Party Logistics Provider Integration
Update order status from tracking API:

```php
<?php
class LogisticsIntegration {
    private $apiUrl;
    private $username;
    private $password;
    private $token;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
        $this->apiUrl = 'http://yourserver.com';
    }

    private function getToken() {
        if ($this->token) return $this->token;

        $ch = curl_init($this->apiUrl . '/index.php?page=orders&action=api_generate_token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'username' => $this->username,
            'password' => $this->password,
            'expiry_days' => 1
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $this->token = $data['token'] ?? null;
    }

    public function updateShipmentStatus($orderNumber, $trackingId, $status) {
        $token = $this->getToken();
        if (!$token) return false;

        $ch = curl_init($this->apiUrl . '/index.php?page=orders&action=api_update_order_status');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'order_number' => $orderNumber,
            'status' => $status,
            'remarks' => 'Tracking ID: ' . $trackingId
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['success'] ?? false;
    }
}

// Usage
$logistics = new LogisticsIntegration('logistics-user@example.com', 'logistics-password');
$logistics->updateShipmentStatus('ORD-2026-001', 'TRACK123456', 'shipped');
```

### ERP System Sync
Sync order status with ERP system every hour:

```javascript
// erp-sync.js - Run via scheduler or cron
const API_USERNAME = process.env.API_USERNAME;
const API_PASSWORD = process.env.API_PASSWORD;
const API_URL = process.env.API_URL;

async function syncOrderStatus() {
  try {
    // Generate token
    const tokenRes = await fetch(`${API_URL}/index.php?page=orders&action=api_generate_token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: API_USERNAME,
        password: API_PASSWORD,
        expiry_days: 1
      })
    });

    const { success, token } = await tokenRes.json();
    if (!success) throw new Error('Failed to generate token');

    // Get order history
    const historyRes = await fetch(`${API_URL}/index.php?page=orders&action=api_order_status_history&order_number=ORD-2026-001`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    const history = await historyRes.json();
    
    // Sync to ERP database
    await syncToERP(history);
    console.log('Sync completed successfully');
  } catch (error) {
    console.error('Sync failed:', error);
  }
}

syncOrderStatus();
```

---

- All timestamps are in server timezone (Asia/Kolkata)
- Order numbers are case-sensitive and used to look up order_id internally
- Agent IDs must be valid integers
- Status strings are case-sensitive
- ESD dates should be in YYYY-MM-DD format
- Changes are immediately visible in order status log
- Bulk update supports both array and comma-separated order numbers
- API tokens are cryptographically secure (32 random bytes encoded as hex)
- Tokens are user-specific and inherit user's permissions
- Token expiry is checked against current server time on each request
