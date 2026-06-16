<?php
/**
 * Direct Aramex WSDL Inspection Tool
 * Access: ?page=dispatch&action=test_aramex_wsdl or direct visit
 * Purpose: Inspect WSDL and list all available SOAP methods
 */

require_once __DIR__ . '/helpers/courier/credential_urls.php';

// WSDL URL to test
$wsdl = 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl';

echo "<h2>Aramex WSDL Inspection</h2>";
echo "<p>WSDL URL: <code>" . htmlspecialchars($wsdl) . "</code></p>";

try {
    echo "<h3>1. Testing WSDL Connectivity...</h3>";
    
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $wsdlContent = @file_get_contents($wsdl, false, $context);
    
    if ($wsdlContent === false) {
        echo "<p style='color:red;'>❌ WSDL not accessible via HTTP</p>";
    } else {
        echo "<p style='color:green;'>✓ WSDL is accessible</p>";
        
        // Check if it's actually WSDL content
        if (stripos($wsdlContent, '<definitions') !== false || stripos($wsdlContent, '<wsdl:definitions') !== false) {
            echo "<p style='color:green;'>✓ Valid WSDL document</p>";
            
            // Extract WSDL size
            $wsdlSize = strlen($wsdlContent);
            echo "<p>WSDL Size: " . number_format($wsdlSize) . " bytes</p>";
            
            // Look for methods in WSDL
            preg_match_all('/<wsdl:operation name="([^"]+)"/', $wsdlContent, $operationMatches);
            if (empty($operationMatches[1])) {
                preg_match_all('/<operation name="([^"]+)"/', $wsdlContent, $operationMatches);
            }
            
            if (!empty($operationMatches[1])) {
                echo "<h3>2. SOAP Operations Found in WSDL:</h3>";
                echo "<ul>";
                foreach ($operationMatches[1] as $op) {
                    $isRateOp = stripos($op, 'rate') !== false ? ' ⭐' : '';
                    echo "<li>" . htmlspecialchars($op) . $isRateOp . "</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color:red;'>❌ Content is not valid WSDL (may be HTML error page)</p>";
            echo "<p>First 500 chars:</p><pre>" . htmlspecialchars(substr($wsdlContent, 0, 500)) . "</pre>";
        }
    }
    
    echo "<h3>3. Testing SoapClient Connection...</h3>";
    
    $soapOptions = [
        'trace' => 1,
        'exceptions' => true,
        'connection_timeout' => 10,
        'cache_wsdl' => WSDL_CACHE_NONE
    ];
    
    $client = new SoapClient($wsdl, $soapOptions);
    echo "<p style='color:green;'>✓ SoapClient initialized successfully</p>";
    
    // Get available functions
    echo "<h3>4. Available SOAP Functions:</h3>";
    $functions = $client->__getFunctions();
    
    if (empty($functions)) {
        echo "<p style='color:red;'>❌ No functions found in WSDL</p>";
    } else {
        echo "<p>Total functions: " . count($functions) . "</p>";
        echo "<ul>";
        foreach ($functions as $func) {
            // Highlight rate-related functions
            $highlight = (stripos($func, 'rate') !== false) ? 'style="background-color: yellow; font-weight: bold;"' : '';
            echo "<li $highlight><code>" . htmlspecialchars($func) . "</code></li>";
        }
        echo "</ul>";
    }
    
    // Get services
    echo "<h3>5. Available SOAP Services (Types):</h3>";
    try {
        $types = $client->__getTypes();
        echo "<p>Total types: " . count($types) . "</p>";
        
        // Show first 20 types
        echo "<ol>";
        $count = 0;
        foreach ($types as $type) {
            if ($count++ >= 20) {
                echo "<li>... and " . (count($types) - 20) . " more types</li>";
                break;
            }
            echo "<li><code>" . htmlspecialchars(substr($type, 0, 100)) . "</code></li>";
        }
        echo "</ol>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠ Could not retrieve types: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (SoapFault $fault) {
    echo "<p style='color:red;'>❌ SOAP Fault: " . htmlspecialchars($fault->faultstring) . "</p>";
    if (isset($fault->detail)) {
        echo "<pre>" . htmlspecialchars(json_encode($fault->detail, JSON_PRETTY_PRINT)) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
}
?>
