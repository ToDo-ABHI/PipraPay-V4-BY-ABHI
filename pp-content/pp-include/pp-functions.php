<?php
    declare(strict_types=1);

    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if (date_default_timezone_get() !== 'UTC') {
        date_default_timezone_set('UTC');
    }

    $pp_functions_loaded = true;
    
    function pp_site_url($type = "Full") {
        // Detect protocol
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";

        // Full host with subdomain
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Request URI (path after domain)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Extract main domain
        $hostParts = explode('.', $host);
        $numParts = count($hostParts);

        if ($numParts >= 2) {
            // Handles domains like example.com or sub.example.com
            $mainDomain = $hostParts[$numParts - 2] . '.' . $hostParts[$numParts - 1];
        } else {
            $mainDomain = $host; // fallback
        }

        switch (strtolower($type)) {
            case "fulldomain":
                return $protocol.$host; // subdomain + main domain
            case "maindomain":
                return $mainDomain; // main domain only
            case "full":
            default:
                return $protocol . $host . $requestUri; // full URL
        }
    }

    function getAdminPath($url) {
        // Remove query string
        $url = explode('?', $url)[0];

        // Find position of admin/
        $pos = strpos($url, 'admin/');
        if ($pos === false) return ''; // admin/ not found

        // Get everything after admin/
        $path = substr($url, $pos + strlen('admin/'));

        // Remove leading/trailing slashes
        $path = trim($path, '/');

        return $path;
    }

    function getAuthorizationHeader() {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $key => $val) {
                    if (strcasecmp($key, 'MHS-PIPRAPAY-API-KEY') === 0) {
                        return trim((string)$val);
                    }
                }
            }
        }
    
        foreach ($_SERVER as $key => $value) {
            if (strcasecmp($key, 'HTTP_MHS_PIPRAPAY_API_KEY') === 0) {
                return trim((string)$value);
            }
        }
    
        return null;
    }

    function connectDatabase() {
        global $db_host, $db_port, $db_user, $db_pass, $db_name;
        $db_port = $db_port ?? 3306; // fallback

    try {
            // Build DSN
            $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";

            // Create PDO instance
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Throw exceptions on error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays
                PDO::ATTR_EMULATE_PREPARES => false               // Use native prepared statements
            ]);

            return $pdo;
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    function timeAgo($datetime) {
        global $global_response_brand;

        // Determine user timezone or default to Dhaka
        $userTimezone = (!empty($global_response_brand['response'][0]['timezone']) && $global_response_brand['response'][0]['timezone'] !== '--')
            ? $global_response_brand['response'][0]['timezone']
            : 'Asia/Dhaka';

        // Create DateTime objects in the user's timezone
        $tz = new DateTimeZone($userTimezone);

        // Convert the input datetime (assumed UTC) to user's timezone
        $past = new DateTime($datetime, new DateTimeZone('UTC'));
        $past->setTimezone($tz);

        // Get current time in user's timezone
        $now = new DateTime('now', $tz);

        // Calculate difference
        $diff = $now->diff($past);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    function getCurrentDatetime($format = 'Y-m-d H:i:s') {
        $currentDatetime = new DateTime();

        return $currentDatetime->format($format);
    }   

    function getUserDeviceInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (strpos($ipAddress, ',') !== false) {
            $ipParts = explode(',', $ipAddress);
            $ipAddress = trim($ipParts[0]);
        }
    
        if (preg_match('/mobile/i', $userAgent)) {
            $deviceType = "Mobile";
        } elseif (preg_match('/tablet/i', $userAgent)) {
            $deviceType = "Tablet";
        } else {
            $deviceType = "Desktop";
        }
    
        if (preg_match('/Windows/i', $userAgent)) {
            $os = "Windows";
        } elseif (preg_match('/Mac/i', $userAgent)) {
            $os = "Mac OS";
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = "Linux";
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = "Android";
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $os = "iOS";
        } else {
            $os = "Unknown OS";
        }
    
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = "Internet Explorer";
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = "Firefox";
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = "Chrome";
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = "Safari";
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = "Opera";
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = "Edge";
        } else {
            $browser = "Unknown Browser";
        }
    
        return [
            'ip_address' => $ipAddress,
            'device' => $deviceType,
            'os' => $os,
            'browser' => $browser
        ];
    }

    // Set a cookie securely (supports all panels)
    function setsCookie($cookieName, $cookieValue, $days = 365) {
        $expiryTime = time() + ($days * 24 * 60 * 60);
    
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
        setcookie($cookieName, $cookieValue, [
            'expires' => $expiryTime,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax', // Use 'None' if cross-domain needed (and use HTTPS)
        ]);
    }
    
    // Get the value of a cookie
    function getCookie($cookieName) {
        return $_COOKIE[$cookieName] ?? null;
    }
    
    // Logout: clear all cookies and destroy session
    function logoutCookie() {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        // Expire all cookies
        foreach ($_COOKIE as $name => $value) {
            setcookie($name, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    
        // Clear session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    function escape_string($value) {
        if (!is_string($value)) {
            return $value;
        }
        try {
            $pdo = connectDatabase();
            $quoted = $pdo->quote($value);
            if ($quoted !== false && strlen($quoted) >= 2) {
                return substr($quoted, 1, -1);
            }
        } catch (Exception $e) {
            // fallback
        }
        return addslashes($value);
    }   

    function getData($tableName, $coloum_name, $type = "* FROM", $params = []) {
        $pdo = connectDatabase(); // PDO connection

        // Build SQL
        $sql = "SELECT $type `$tableName` $coloum_name";

        try {
            $stmt = $pdo->prepare($sql); // prepare statement

            // Bind parameters if any
            foreach ($params as $key => $value) {
                // Detect integer for proper PDO type
                $pdoType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value, $pdoType);
            }

            $stmt->execute(); // execute

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                foreach ($row as $col => $val) {
                    if (is_null($val)) {
                        $row[$col] = '--';
                    }
                }
            }

            if ($data) {
                return json_encode(['status' => true, 'response' => $data]);
            } else {
                return json_encode(['status' => false, 'response' => []]);
            }

        } catch (PDOException $e) {
            error_log("getData PDO Error: " . $e->getMessage());
            return json_encode(['status' => false, 'response' => []]);
        }
    }

    function insertData($tableName, $columns, $values) {
        $pdo = connectDatabase(); 

        try {
            $stmtColumns = $pdo->prepare("SHOW COLUMNS FROM `$tableName`");
            $stmtColumns->execute();
            $tableCols = $stmtColumns->fetchAll(PDO::FETCH_ASSOC);

            $finalColumns = [];
            $finalValues = [];
            $placeholders = [];

            $userData = array_combine($columns, $values);

            foreach ($tableCols as $col) {
                $colName = $col['Field'];

                if (strpos(strtolower($col['Extra']), 'auto_increment') !== false && !isset($userData[$colName])) {
                    continue;
                }

                $finalColumns[] = $colName;
                $placeholders[] = ":val_$colName";

                if (isset($userData[$colName])) {
                    $finalValues[$colName] = $userData[$colName];
                } else {
                    if ($col['Default'] !== null) {
                        $finalValues[$colName] = $col['Default'];
                    } else {
                        $finalValues[$colName] = "--";
                    }
                }
            }

            $sql = "INSERT INTO `$tableName` (" . implode(", ", $finalColumns) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $pdo->prepare($sql);

            foreach ($finalValues as $colName => $val) {
                $stmt->bindValue(":val_$colName", $val);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Insert failed: " . $e->getMessage());
            return false;
        }
    }

    function updateData($tableName, $columns, $values, $condition) {
        $pdo = connectDatabase(); 

        $setClauses = [];
        foreach ($columns as $index => $col) {
            $setClauses[] = "$col = :val$index";
        }
        $setString = implode(", ", $setClauses);

        $sql = "UPDATE `$tableName` SET $setString WHERE $condition";

        try {
            $stmt = $pdo->prepare($sql);

            foreach ($values as $index => $value) {
                if ($value === "" || is_null($value)) {
                    $value = "--";
                }

                $stmt->bindValue(":val$index", $value);
            }

            return $stmt->execute(); 
        } catch (PDOException $e) {
            error_log("updateData PDO Error: " . $e->getMessage());
            return false;
        }
    }

    function deleteData($tableName, $condition) {
        $pdo = connectDatabase(); // PDO connection

        $sql = "DELETE FROM `$tableName` WHERE $condition";

        try {
            $stmt = $pdo->prepare($sql);
            return $stmt->execute(); // returns true/false
        } catch (PDOException $e) {
            error_log("deleteData PDO Error: " . $e->getMessage());
            return false;
        }
    }

    function limit_checker($tableName, $db_prefix) {
        $count = 1;

        if($tableName == "transactions"){
            $response_limit = json_decode(getData($db_prefix.'transaction',' WHERE status = "completed"'),true);
            if($response_limit['status'] == true){
                foreach($response_limit['response'] as $row){
                    $count = $count+1;
                }
            }
        }else{
            $response_limit = json_decode(getData($db_prefix.'domain',' '),true);
            if($response_limit['status'] == true){
                foreach($response_limit['response'] as $row){
                    $count = $count+1;
                }
            }
        }

        return $count; 
    }

    function generateStrongPassword($length = 8) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#$%&!';
        return substr(str_shuffle(str_repeat($chars, 5)), 0, $length);
    }

    function generateItemID($length = 10, $maxLength = 10)
    {
        // Ensure length does not exceed max
        $length = ($length > $maxLength) ? $maxLength : $length;

        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= mt_rand(0, 9);
        }

        return $id;
    }

    function getNameChars(string $fullName, int $length = 2): string
    {
        $fullName = trim($fullName);

        if ($fullName === '' || $length <= 0) {
            return '';
        }

        // Split name by spaces (remove extra spaces)
        $parts = array_values(array_filter(explode(' ', $fullName)));

        // If multiple words, use first + last
        if (count($parts) > 1) {
            $first = $parts[0];
            $last  = end($parts);

            $result = strtoupper(
                substr($first, 0, 1) .
                substr($last, 0, max(0, $length - 1))
            );
        } else {
            // Single name
            $result = strtoupper(substr($parts[0], 0, $length));
        }

        return $result;
    }

    function moneyToInt(string $amount, int $decimals = 2): int {
        $amount = money_sanitize($amount);
        $multiplier = bcpow("10", (string)$decimals);
        return (int) bcmul($amount, $multiplier, 0);
    }

    function intToMoney(int $amount, int $decimals = 2): string {
        $divisor = bcpow("10", (string)$decimals);
        return bcdiv((string)$amount, $divisor, $decimals);
    }

    function money_sanitize(string|int|float|null $value): string {
        if (is_numeric($value)) {
            return (string)$value;
        }
        return "0";
    }

    function money_add($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        return bcadd($a, $b, $scale);
    }

    function money_sub($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        return bcsub($a, $b, $scale);
    }

    function money_mul($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        return bcmul($a, $b, $scale);
    }

    function money_div($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        if (bccomp($b, '0', $scale) === 0) {
            return "0";
        }
        return bcdiv($a, $b, $scale);
    }

    function money_round($amount, int $decimals = 2): string {
        $amount = money_sanitize($amount);
        if ($amount === '' || $amount === '0') {
            return number_format(0, $decimals, '.', '');
        }
        $isNegative = str_starts_with($amount, '-');
        $unsigned = ltrim($amount, '-');
        $add = '0.' . str_repeat('0', $decimals) . '5';
        $res = bcadd($unsigned, $add, $decimals + 1);
        $rounded = bcdiv($res, '1', $decimals);
        return ($isNegative && bccomp($rounded, '0', $decimals) !== 0 ? '-' : '') . $rounded;
    }

    function pp_get_gateway_options($gateway_id = '', $brand_id = ''){
        global $db_prefix;

        $options = [];

        if ($gateway_id === '' || $brand_id === '') {
            return $options;
        }

        $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $brand_id ];
        $response_gateways_parameter = json_decode(getData($db_prefix.'gateways_parameter','WHERE gateway_id = :gateway_id AND brand_id = :brand_id', '* FROM', $params),true);

        if ($response_gateways_parameter['status'] == true) {
            foreach($response_gateways_parameter['response'] as $field){
                $value = $field['value'];

                if(!empty($field['multiple']) && !empty($value)){
                    $value = is_array($value) ? $value : json_decode($value, true);
                }

                $options[$field['option_name']] = $value;
            }
        }

        return $options;
    }

    function pp_bkash_tokenized_refund($transaction = [], $refund = []){
        $gateway_id = $transaction['gateway_id'] ?? '';
        $brand_id = $transaction['brand_id'] ?? '';

        if ($gateway_id === '' || $brand_id === '') {
            return [
                'status' => false,
                'message' => 'Gateway or brand not found.',
            ];
        }

        $options = $refund['options'] ?? pp_get_gateway_options($gateway_id, $brand_id);

        if (empty($options)) {
            return [
                'status' => false,
                'message' => 'bKash configuration is missing.',
            ];
        }

        if (isset($options['auto_refund']) && $options['auto_refund'] === 'off') {
            return [
                'status' => false,
                'message' => 'Auto refund is disabled for this gateway.',
            ];
        }

        $gateway_path = __DIR__ . '/../pp-modules/pp-gateways/bkash-api-tokenized/class.php';
        if (!file_exists($gateway_path)) {
            return [
                'status' => false,
                'message' => 'bKash gateway not installed.',
            ];
        }

        require_once $gateway_path;

        if (!class_exists('BkashApiTokenizedGateway')) {
            return [
                'status' => false,
                'message' => 'bKash gateway class not found.',
            ];
        }

        $gateway = new BkashApiTokenizedGateway();

        if (!method_exists($gateway, 'refund')) {
            return [
                'status' => false,
                'message' => 'Refund not supported by this gateway.',
            ];
        }

        $payload = [
            'transaction' => $transaction,
            'options' => $options,
            'refund' => $refund,
        ];

        return $gateway->refund($payload);
    }


    function verifyPaymentTolerance(string $checkout, string $paid, string $tolerance): bool{
        $checkout  = money_round($checkout);
        $paid      = money_round($paid);
        $tolerance = money_round($tolerance);

        if (bccomp($checkout, "0", 8) <= 0 || bccomp($paid, "0", 8) <= 0) {
            return false;
        }

        // max allowed = checkout + tolerance
        $maxAllowed = money_add($checkout, $tolerance);

        return (
            bccomp($paid, $checkout, 8) >= 0 &&
            bccomp($paid, $maxAllowed, 8) <= 0
        );
    }

    function dateformat($date, $format = 'd/m/Y') {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    function convertUTCtoUserTZ($utc_time, $user_tz = 'UTC', $format = 'Y-m-d H:i:s') {
        if (empty($utc_time) || $utc_time === '--') {
            return '';
        }
        try {
            if (empty($user_tz) || $user_tz === '--') {
                $user_tz = 'Asia/Dhaka';
            }
            $dt = new DateTime((string)$utc_time, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone((string)$user_tz));
            return $dt->format($format);
        } catch (Throwable $e) {
            return (string)$utc_time;
        }
    }

    function isExpired($expires_at){
        if (empty($expires_at) || $expires_at === '--') {
            return false; 
        }

        $timestamp = strtotime($expires_at);

        if ($timestamp === false) {
            return true;
        }

        if (preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}$/', $expires_at)) {
            $timestamp = strtotime(date('Y-m-d 23:59:59', $timestamp));
        }

        return time() > $timestamp;
    }

    function getParam(array $params, string $key): ?string {
        if (!isset($params[$key]) || !is_string($params[$key])) {
            return null;
        }

        $value = trim($params[$key]);
        if ($value === '') return null;

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
            return null;
        }

        return escape_string($value);
    }

    function getDomainValue($input) {
        $input = trim($input);

        if ($input === '') {
            return false;
        }

        if (!preg_match('#^https?://#i', $input)) {
            $input = 'http://' . $input;
        }

        $host = parse_url($input, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $host = preg_replace('/^www\./i', '', $host);

        if (!preg_match('/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,}$/i', $host)) {
            return false;
        }

        return strtolower($host);
    }

    function sendIPN(string $url, array $payload): int {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $json,
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/json',
                'Connection: close'
            ],
            CURLOPT_RETURNTRANSFER  => false,
            CURLOPT_HEADER          => false,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_NOSIGNAL        => true,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_WRITEFUNCTION   => function($ch, $data) { return strlen($data); },
        ]);

        $result = curl_exec($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $httpCode = 0; 
        }

        curl_close($ch);

        return $httpCode;
    }

    function sendIPNMulti(array $jobs): array{
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($jobs as $job) {
            $json = json_encode($job['payload'], JSON_UNESCAPED_UNICODE);

            $ch = curl_init($job['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $json,
                CURLOPT_HTTPHEADER      => [
                    'Content-Type: application/json',
                    'Connection: close'
                ],
                CURLOPT_RETURNTRANSFER  => false,
                CURLOPT_CONNECTTIMEOUT  => 3,
                CURLOPT_TIMEOUT         => 5,
                CURLOPT_FORBID_REUSE    => true,
                CURLOPT_NOSIGNAL        => true,
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_SSL_VERIFYHOST  => 2,
                CURLOPT_WRITEFUNCTION   => fn($ch, $data) => strlen($data),
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[(int)$ch] = [
                'handle' => $ch,
                'id'     => $job['id']
            ];
        }

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($handles as $item) {
            $ch = $item['handle'];
            $id = $item['id'];

            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 0) {
                $code = 0;
            }

            $results[$id] = $code;

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        return $results; 
    }

    function senderWhitelist(?string $sender = null, ?string $providerKey = null, string $mode = 'provider', ?string $providerName = null) {
        $providers = [
            'bkash' => [
                'name'     => 'bKash',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['bkash'],
            ],
            'nagad' => [
                'name'     => 'Nagad',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['nagad'],
            ],
            'rocket' => [
                'name'     => 'Rocket',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['16216'],
            ],
            'upay' => [
                'name'     => 'Upay',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['upay'],
            ],
            'tap' => [
                'name'     => 'Tap',
                'currency' => 'USD',
                'balance_verify' => 'true',
                'senders'  => ['tap.'],
            ],
            'cellfin' => [
                'name'     => 'Cellfin',
                'currency' => 'BDT',
                'balance_verify' => 'false',
                'senders'  => ['ibbl', 'cellfin', 'ibbl .'],
            ],
            'okwallet' => [
                'name'     => 'Ok Wallet',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['01847-348685'],
            ],
            'mcash' => [
                'name'     => 'mCash',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['16259'],
            ],
            'pathaopay' => [
                'name'     => 'Pathao Pay',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['pathaopay'],
            ],
            'telecash' => [
                'name'     => 'TeleCash',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['telecash'],
            ],            
            'tallypay' => [
                'name'     => 'TallyPay',
                'currency' => 'BDT',
                'balance_verify' => 'false',
                'senders'  => ['tallypay'],
            ],
            'ipay' => [
                'name'     => 'Ipay',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['09638-900800'],
            ],
        ];

        if ($mode === 'senders') {
            $allSenders = [];
            foreach ($providers as $provider) {
                $allSenders = array_merge($allSenders, $provider['senders']);
            }
            $allSenders = array_values(array_unique($allSenders));
            return $allSenders;
        }

        if ($sender !== null) {
            $sender = strtolower(trim($sender));
            foreach ($providers as $key => $provider) {
                foreach ($provider['senders'] as $s) {
                    if (strtolower($s) === $sender) {
                        return [
                            'provider_key'   => $key,
                            'name'           => $provider['name'],
                            'currency'       => $provider['currency'],
                            'balance_verify'       => $provider['balance_verify'],
                            'sender'         => $sender,
                        ];
                    }
                }
            }
            return false; 
        }

        if ($providerKey !== null) {
            return $providers[$providerKey] ?? false;
        }

        if ($providerName !== null) {
            $providerName = strtolower(trim($providerName));
            foreach ($providers as $key => $provider) {
                if (strtolower($provider['name']) === $providerName) {
                    return [
                        'provider_key' => $key,
                        'name'         => $provider['name'],
                        'currency'     => $provider['currency'],
                        'balance_verify'     => $provider['balance_verify'],
                        'senders'      => $provider['senders'],
                    ];
                }
            }
            return false;
        }

        return $providers;
    }


    function MFSMessageVerified(string $mfs, string $message){
        $message = trim(preg_replace('/\s+/', ' ', $message));

        $formats = [
            'tallypay' => [
                // Bangla QR payments received in a TallyPay wallet (sender number is masked, e.g. 015***6719)
                [
                    'type'     => 'Merchant',
                    'priority' => 100,
                    'pattern'  => '/Tk ([\d,.]+) received from ([0-9*xX]+) via TallyPay QR payment\.? A\/c balance: Tk ([\d,.]+) Txn ?ID: ([A-Z0-9]+) (\d{1,2} [A-Za-z]{3} \d{4} \d{1,2}:\d{2} ?[AP]M)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid', 'datetime']
                ],
            ],

            'bkash' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/You have received Tk ([\d,.]+) from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'fee', 'balance', 'trxid', 'datetime']
                ],
                [
                    'type'     => 'Personal',
                    'priority' => 90,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'fee', 'balance', 'trxid', 'datetime']
                ],
                [
                    'type'     => 'Merchant',
                    'priority' => 80,
                    'pattern'  => '/You have received payment Tk ([\d,.]+) from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'fee', 'balance', 'trxid', 'datetime']
                ],

                /*
                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'nagad' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Money Received\. Amount: Tk ([\d,.]+) Sender: (\d+)(?:\s*Ref[:\-]?\s*(\S+))? TxnID: ([A-Z0-9]+) Balance: Tk ([\d,.]+) ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'trxid', 'balance', 'datetime']
                ],
                [
                    'type'     => 'Personal',
                    'priority' => 90,
                    'pattern'  => '/Cash In Received\. Amount: Tk ([\d,.]+) Uddokta: (\d+) TxnID: ([A-Z0-9]+) Balance: ([\d,.]+) ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'balance', 'datetime']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'rocket' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Tk([\d,.]+) received from A\/C:([*\d]+) Fee:Tk([\d,.]+)\, Your A\/C Balance: Tk([\d,.]+) TxnId:([A-Z0-9]+)(?: Date:([\w\-:\s]+))?/i',
                    'map'      => ['amount', 'sender', 'fee', 'balance', 'trxid', 'datetime']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'upay' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Tk\. ([\d,.]+) has been received from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Balance Tk\. ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)\./i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid', 'datetime']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'tap' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Received Tk ([\d,.]+) from (\d+)\. Balance Tk\. ([\d,.]+)\. TxID: ([A-Z0-9]+)\./i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'cellfin' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Islami Bank CellFin Received ([\d,.]+) Tk From CellFin: (\d+) To CellFin: (\d+) TrxId: ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'receiver', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'okwallet' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/\(OK Wallet\) Successfully received Tk ([\d,.]+) from A\/C (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'mcash' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/IBBL mCash You have received Tk: ([\d,.]+) From: (\d+)(?:\s*Reference:\s*(\S*))? Balance Tk: ([\d,.]+) TrxID: ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'pathaopay' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/You have received BDT ([\d,.]+) from (\+?\d+)\. Balance BDT ([\d,.]+) TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],




        ];

        if (!isset($formats[strtolower($mfs)])) {
            return false;
        }

        // 🔥 Sort by priority (DESC)
        usort($formats[strtolower($mfs)], fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($formats[strtolower($mfs)] as $format) {
            if (preg_match($format['pattern'], $message, $matches)) {

                $data = [
                    'mfs'  => strtolower($mfs),
                    'type' => $format['type'],
                    'raw'  => $message,
                ];

                // Map values safely
                foreach ($format['map'] as $i => $key) {
                    $data[$key] = $matches[$i + 1] ?? null;
                }

                // Normalize numbers
                foreach (['amount', 'balance', 'fee'] as $field) {
                    if (isset($data[$field]) && $data[$field] !== null) {
                        $data[$field] = str_replace(',', '', $data[$field]);
                    }
                }

                return $data;
            }
        }

        return false;
    }

    function reconcileByLongestChain($device_id, $sender_key, $type){
        global $db_prefix;

        $resBalance = json_decode(getData($db_prefix.'balance_verification', 'WHERE device_id="'.$device_id.'" AND sender_key="'.$sender_key.'" AND type="'.$type.'"'),true);

        $canonicalBalanceInt = 0;

        if (!empty($resBalance['response'][0]['current_balance'])) {
            $canonicalBalanceInt = moneyToInt($resBalance['response'][0]['current_balance']);
        }

        $res = json_decode(getData($db_prefix.'sms_data','WHERE device_id="'.$device_id.'" AND sender_key="'.$sender_key.'" AND type="'.$type.'" AND status IN ("approved","awaiting-review") AND source IN ("app") ORDER BY id ASC'),true);

        $smsList = $res['response'] ?? [];
        if (count($smsList) < 1) return;

        foreach ($smsList as &$s) {
            $amountInt  = moneyToInt($s['amount'] ?? "0");
            $balanceInt = moneyToInt($s['balance'] ?? "0");

            if ($amountInt <= 0 || $balanceInt <= 0) continue;

            $s['amount_int']  = $amountInt;
            $s['balance_int'] = $balanceInt;

            $s['prev'] = $balanceInt - $amountInt;
            $s['bal']  = $balanceInt;
        }
        unset($s);

        $next = [];

        foreach ($smsList as $s) {
            if (!isset($s['prev'])) continue;
            $next[$s['prev']][] = $s;
        }

        $bestChain = [];
        $queue = [$canonicalBalanceInt];

        while (!empty($queue)) {

            $current = array_shift($queue);

            if (!isset($next[$current])) continue;

            foreach ($next[$current] as $sms) {
                $chain = [];
                $tempCurrent = $current;
                $tempNext = $next;

                while (isset($tempNext[$tempCurrent]) && count($tempNext[$tempCurrent]) > 0) {

                    $smsInChain = array_shift($tempNext[$tempCurrent]);

                    $chain[] = $smsInChain;
                    $tempCurrent = $smsInChain['bal'];
                }

                if (count($chain) > count($bestChain)) {
                    $bestChain = $chain;
                }
            }
        }

        if (count($bestChain) < 1) return;

        $idsToApprove = array_column($bestChain, 'id');

        if (!empty($idsToApprove)) {

            updateData($db_prefix.'sms_data',['status','reason','updated_date'],['approved','--',getCurrentDatetime('Y-m-d H:i:s')],'id IN ('.implode(',', $idsToApprove).')');
        }

        $last = end($bestChain);
        $finalBalanceInt = $last['bal'];

        $finalBalance = intToMoney($finalBalanceInt, 2);

        updateData($db_prefix.'balance_verification', ['current_balance','updated_date'], [$finalBalance, getCurrentDatetime('Y-m-d H:i:s')], 'device_id="'.$device_id.'" AND sender_key="'.$sender_key.'" AND type="'.$type.'"');
    }

    function permissionSchema(){
        $permissionSchema = [
            'resources' => [
                'customers' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'transaction' => [
                    'edit'      => true,
                    'delete'    => true,
                    'approve'   => true,
                    'cancel'   => true,
                    'refund'    => true,
                    'send_ipn'  => true
                ],
                'invoice' => [
                    'create'    => true,
                    'edit'      => true,
                    'delete'    => true
                ],
                'payment_link' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'gateways' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'addons' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'brand_settings' => [
                    'view' => true,
                    'edit'   => true
                ],
                'api_settings' => [
                    'view' => true,
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'theme_settings' => [
                    'view' => true,
                    'edit'   => true
                ],
                'faq_settings' => [
                    'view' => true,
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'currency_settings' => [
                    'view' => true,
                    'sync_rate' => true,
                    'import'   => true,
                    'edit'   => true
                ],
                'sms_data' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'device' => [
                    'connect' => true,
                    'delete'  => true,
                    'balance_verification_for'  => true
                ],
                'brands' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'staff' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true,
                    'assign_brand_to' => true,
                    'edit_permission' => true,
                    'view_permission_list' => true,
                    'delete_permission_of' => true
                ],
                'domains' => [
                    'whitelist' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'system_settings' => [
                    'manage_general' => true,
                    'manage_cron' => true,
                    'manage_update'   => true,
                    'manage_import'   => true,
                    'manage_notification' => true
                ],
            ],
            'pages' => [
                'dashboard' => true,
                'reports' => true,
                'customers' => true,
                'transaction' => true,
                'invoice' => true,
                'payment_link' => true,
                'gateways' => true,
                'addons' => true,
                'brand_settings' => true,
                'sms_data' => true,
                'device' => true,
                'brands' => true,
                'staff_management' => true,
                'domains' => true,
                'system_settings' => true,
            ]
        ];

        return $permissionSchema ?? [];
    }

    function countPermissions($tabKey, $tabData) {
        $count = 0;

        if ($tabKey === 'resources') {
            foreach ($tabData as $module => $actions) {
                $count += count($actions);
            }
        }

        if ($tabKey === 'pages') {
            $count = count($tabData);
        }

        return $count;
    }
    function hasPermission($permissions, $module, $action = 'view', $adminType = 'staff') {
        if ($adminType === 'admin') {
            return true;
        }

        return isset($permissions['resources'][$module][$action])
            && $permissions['resources'][$module][$action] === true;
    }

    function canAccessPage($permissions, $page, $adminType = 'staff') {
        if ($adminType === 'admin') {
            return true;
        }

        return !empty($permissions['pages'][$page]);
    }

    function get_env($option_name, $brand_id = 'both') {
        global $db_prefix;

        $option_name = escape_string($option_name);
        $brand_id = escape_string($brand_id);

        $params = [ ':brand_id' => $brand_id, ':option_name' => $option_name ];

        $response_env = json_decode(getData($db_prefix.'env','WHERE brand_id = :brand_id AND option_name = :option_name', '* FROM', $params),true);
        if($response_env['status'] == true){
            $value = $response_env['response'][0]['value'];

            if($value == '--'){
                $value = '';
            }
        }else{
            $columns = ['brand_id', 'option_name', 'value', 'created_date', 'updated_date'];
            $values = [$brand_id, $option_name, '--', getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

            insertData($db_prefix.'env', $columns, $values);

            $value = '';
        }

        return $value;
    }

    function set_env($option_name, $value, $brand_id = 'both') {
        global $db_prefix;

        $option_name = escape_string($option_name);
        $value = escape_string($value);
        $brand_id = escape_string($brand_id);

        $params = [ ':brand_id' => $brand_id, ':option_name' => $option_name ];

        $response_env = json_decode(getData($db_prefix.'env','WHERE brand_id = :brand_id AND option_name = :option_name', '* FROM', $params),true);
        if($response_env['status'] == true){
            $columns = ['brand_id', 'value', 'updated_date'];
            $values = [$brand_id, $value, getCurrentDatetime('Y-m-d H:i:s')];
            $condition = "id = '".$response_env['response'][0]['id']."'"; 
            
            updateData($db_prefix.'env', $columns, $values, $condition);
        }else{
            $columns = ['brand_id', 'option_name', 'value', 'created_date', 'updated_date'];
            $values = [$brand_id, $option_name, $value, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

            insertData($db_prefix.'env', $columns, $values);
        }

        return $value;
    }

    function generateRandomFilename($extension) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < 30; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString . "." . $extension;
    }

    function uploadImage($file, $max_file_size) {
        if (!is_dir(__DIR__.'/../../pp-media/storage')) {
            if (mkdir(__DIR__.'/../../pp-media/storage', 0755, true)) {
                $upload_directory = __DIR__ . '/../../pp-media/storage/';
            } else {
                return json_encode(['status' => false, 'message' => 'Failed to create folder!']);
            }
        }else{
            $upload_directory = __DIR__ . '/../../pp-media/storage/';
        }

        // ─────────── VALIDATION ───────────
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return json_encode(['status' => false, 'message' => 'No file uploaded or upload failed.']);
        }
    
        if ($file['size'] > $max_file_size) {
            return json_encode(['status' => false, 'message' => 'File size exceeds maximum allowed.']);
        }
    
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_info          = pathinfo($file['name']);
        $file_extension     = strtolower($file_info['extension']);
    
        if (!in_array($file_extension, $allowed_extensions)) {
            return json_encode(['status' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP files are allowed.']);
        }
    
        // ─────────── FILE NAME ───────────
        $random_filename = generateRandomFilename($file_extension);
        $full_path       = $upload_directory . $random_filename;
    
        // ─────────── TRY IMAGICK ───────────
        try {
            if (!extension_loaded('imagick')) {
                throw new Exception('Imagick extension not installed.');
            }
    
            $img = new Imagick($file['tmp_name']);
    
            $hasAlpha = $img->getImageAlphaChannel();
    
            if ($hasAlpha && Imagick::queryFormats('WEBP')) {
                $img->setImageFormat('webp');
                $img->setOption('webp:lossless', 'true');
                $img->setImageCompressionQuality(85);
                $random_filename = generateRandomFilename('webp');
            } elseif (!$hasAlpha && Imagick::queryFormats('JPEG')) {
                $img->setImageFormat('jpeg');
                $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality(75);
                $random_filename = generateRandomFilename('jpg');
            } else {
                throw new Exception('Required format not supported by Imagick.');
            }
    
            $full_path = $upload_directory . $random_filename;
    
            $img->stripImage();
            $img->writeImage($full_path);
            $img->clear();
            $img->destroy();
    
            return json_encode(['status' => true, 'file' => $random_filename]);
    
        } catch (Exception $e) {
            // ───── FALLBACK: MOVE FILE DIRECTLY ─────
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                return json_encode([
                    'status' => true,
                    'file'   => $random_filename,
                    'note'   => 'Imagick not used. File uploaded without processing.'
                ]);
            } else {
                return json_encode(['status' => false, 'message' => 'File upload failed without Imagick: ' . $e->getMessage()]);
            }
        }
    }

    function uploadSlipFile($file, $max_file_size = 10485760) {
        if (!is_dir(__DIR__.'/../../pp-media/storage')) {
            if (!mkdir(__DIR__.'/../../pp-media/storage', 0755, true)) {
                return json_encode(['status' => false, 'message' => 'Failed to create storage folder!']);
            }
        }
        $upload_directory = __DIR__ . '/../../pp-media/storage/';

        if (!isset($file) || !is_array($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return json_encode(['status' => false, 'message' => 'No file uploaded or upload failed.']);
        }

        if (($file['size'] ?? 0) > $max_file_size) {
            return json_encode(['status' => false, 'message' => 'File size exceeds maximum allowed limit (10MB).']);
        }

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        $file_info          = pathinfo($file['name'] ?? '');
        $file_extension     = strtolower($file_info['extension'] ?? '');

        if (!in_array($file_extension, $allowed_extensions)) {
            return json_encode(['status' => false, 'message' => 'Only PDF, JPG, PNG, GIF, and WEBP files are allowed.']);
        }

        $random_filename = generateRandomFilename($file_extension);
        $full_path       = $upload_directory . $random_filename;

        // If PDF file, store directly without image processing
        if ($file_extension === 'pdf') {
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                return json_encode(['status' => true, 'file' => $random_filename]);
            } else {
                return json_encode(['status' => false, 'message' => 'Failed to upload PDF file.']);
            }
        }

        // Image processing with Imagick if available
        try {
            if (!extension_loaded('imagick')) {
                throw new Exception('Imagick extension not installed.');
            }

            $img = new Imagick($file['tmp_name']);
            $hasAlpha = $img->getImageAlphaChannel();

            if ($hasAlpha && Imagick::queryFormats('WEBP')) {
                $img->setImageFormat('webp');
                $img->setOption('webp:lossless', 'true');
                $img->setImageCompressionQuality(85);
                $random_filename = generateRandomFilename('webp');
            } elseif (!$hasAlpha && Imagick::queryFormats('JPEG')) {
                $img->setImageFormat('jpeg');
                $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality(80);
                $random_filename = generateRandomFilename('jpg');
            }

            $full_path = $upload_directory . $random_filename;
            $img->stripImage();
            $img->writeImage($full_path);
            $img->clear();
            $img->destroy();

            return json_encode(['status' => true, 'file' => $random_filename]);
        } catch (Exception $e) {
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                return json_encode(['status' => true, 'file' => $random_filename]);
            } else {
                return json_encode(['status' => false, 'message' => 'File upload failed.']);
            }
        }
    }
    
    function deleteImage($file) {
        // Define the local image directory path
        $upload_directory = __DIR__ . '/../../pp-media/storage/'; // Update path if different
    
        // Sanitize the filename to prevent directory traversal attacks
        $filename = basename($file);
        $full_path = $upload_directory . $filename;
    
        // Check if the file exists
        if (!file_exists($full_path)) {
            return json_encode(["status" => false, "message" => "File not found."]);
        }
    
        // Attempt to delete the file
        if (unlink($full_path)) {
            return json_encode(["status" => true, "message" => "File deleted successfully!"]);
        } else {
            return json_encode(["status" => false, "message" => "Error deleting file."]);
        }
    }


    function deleteFolder($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? deleteFolder($path) : unlink($path);
        }
        rmdir($dir);
    }

    function copyFolder($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);

        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;

                if (is_dir($srcPath)) {
                    copyFolder($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
    }

    function zipFolder($source, $zipFile) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = str_replace('\\', '/', $file->getRealPath());
            $relativePath = substr($filePath, strlen($source) + 1);

            // Skip heavy, backup, or runtime storage directories to prevent recursion & timeouts
            if (
                str_starts_with($relativePath, '.git') ||
                str_starts_with($relativePath, 'pp-media/storage/backup') ||
                str_starts_with($relativePath, 'pp-media/storage/temp') ||
                str_starts_with($relativePath, 'pp-media/storage/updates') ||
                $relativePath === '.maintenance'
            ) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return true;
    }

    function runSql($file) {
        $pdo = connectDatabase();

        if (!file_exists($file)) {
            throw new Exception("SQL file not found");
        }

        $sql = file_get_contents($file);

        try {
            $pdo->beginTransaction();

            $queries = array_filter(array_map('trim', explode(";\n", $sql)));

            foreach ($queries as $query) {
                if ($query !== '') {
                    $pdo->exec($query);
                }
            }

            $pdo->commit();
            return true;

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Update SQL failed: ' . $e->getMessage());
            throw new Exception('Database update failed');
        }
    }

    function backupDatabasePDO($backupPath) {
        try {
            $pdo = connectDatabase();
            $pdo->exec("SET NAMES utf8mb4");

            $fh = fopen($backupPath, 'w');
            if (!$fh) return false;

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $createRes = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
                $create = $createRes[1] ?? '';
                fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n$create;\n\n");

                $stmt = $pdo->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
                foreach ($stmt as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        $vals[] = ($val === null) ? "NULL" : $pdo->quote($val);
                    }
                    fwrite($fh, "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n");
                }

                fwrite($fh, "\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);
            return true;
        } catch (Throwable $e) {
            error_log('Database backup error: ' . $e->getMessage());
            return false;
        }
    }

    function extractUpdate($zipFile, $destination) {
        if (!file_exists($zipFile)) {
            throw new Exception("Update ZIP file not found.");
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== true) {
            throw new Exception("Cannot open update ZIP file.");
        }

        // Detect top-level folder in zip (e.g., PipraPay-main/)
        $topFolder = '';
        if ($zip->numFiles > 0) {
            $firstFile = $zip->getNameIndex(0);
            $parts = explode('/', $firstFile);
            if (count($parts) > 1 && $parts[0] !== '') {
                $topFolder = $parts[0] . '/';
            }
        }

        @mkdir($destination, 0755, true);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            // Remove top folder prefix if present
            if ($topFolder !== '' && str_starts_with($entry, $topFolder)) {
                $entryNew = substr($entry, strlen($topFolder));
            } else {
                $entryNew = $entry;
            }

            if ($entryNew === '' || $entryNew === false) continue;

            // Protect sensitive files and directories
            if (
                $entryNew === 'pp-config.php' ||
                str_starts_with($entryNew, 'pp-media/storage/') ||
                str_starts_with($entryNew, '.git')
            ) {
                continue;
            }

            $targetPath = rtrim($destination, '/\\') . '/' . $entryNew;

            if (substr($entry, -1) === '/') {
                @mkdir($targetPath, 0755, true);
            } else {
                @mkdir(dirname($targetPath), 0755, true);
                $fileData = $zip->getFromIndex($i);
                if ($fileData !== false) {
                    file_put_contents($targetPath, $fileData);
                }
            }
        }

        $zip->close();
        return true;
    }

    function addQueryParams($url, $params = []) {
        // Parse existing URL
        $parsedUrl = parse_url($url);

        // Get existing query params (if any)
        $existingParams = [];
        if (!empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $existingParams);
        }

        // Merge new params
        $finalParams = array_merge($existingParams, $params);

        // Rebuild query string
        $queryString = http_build_query($finalParams);

        // Rebuild full URL
        $baseUrl =
            ($parsedUrl['scheme'] ?? '') . ($parsedUrl['scheme'] ? '://' : '') .
            ($parsedUrl['host'] ?? '') .
            ($parsedUrl['path'] ?? '');

        return $baseUrl . '?' . $queryString;
    }































    function pp_set_lang($lang){
        $_SESSION['ui_language'] = preg_replace('/[^a-z]/', '', $lang);
    }

    function pp_site_address(){
        global $site_url;

        return $site_url;
    }

    function pp_callback_url(){
        $url = pp_site_url();

        $separator = (parse_url($url, PHP_URL_QUERY) ? '&' : '?');
        $url .= $separator . 'pp_callback';

        return $url;
    }

    function pp_ipn_url($gatewayid){
        global $site_url;

        return $site_url.'ipn/'.$gatewayid;
    }

    function pp_check_transaction($ppid = ''){
        global $db_prefix;

        $params = [ ':ref' => $ppid ];

        $response_transaciton = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref','* FROM',$params),true);

        if ($response_transaciton['status'] === true) {
            return true;
        }else{
            return false;
        }
    }

    function pp_check_transaction_id($trxid = ''){
        global $db_prefix;

        $params = [ ':trx_id' => $trxid ];

        $response_transaciton = json_decode(getData($db_prefix.'transaction','WHERE trx_id = :trx_id','* FROM',$params),true);

        if ($response_transaciton['status'] === true) {
            return true;
        }else{
            return false;
        }
    }

    function pp_set_transaction_status($transactionid, $status = '', $gateway_id = '', $trxid = '', $source_info = []){
        global $db_prefix;

        $params = [ ':ref' => $transactionid, ':status' => 'initiated' ];

        $response_transaciton = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status','* FROM',$params),true);

        if ($response_transaciton['status'] === true) {
            if($status == "canceled"){
                $columns = ['status', 'updated_date'];
                $values = ['canceled', getCurrentDatetime('Y-m-d H:i:s')];
                $condition = 'id ="'.$response_transaciton['response'][0]['id'].'"'; 

                updateData($db_prefix.'transaction', $columns, $values, $condition);

                pp_dispatch_notification('payment.failed', [
                    'amount'        => money_round($response_transaciton['response'][0]['amount'] ?? 0),
                    'currency'      => $response_transaciton['response'][0]['currency'] ?? 'BDT',
                    'gateway'       => $response_transaciton['response'][0]['gateway_id'] ?? 'Payment',
                    'trx_id'        => $response_transaciton['response'][0]['ref'] ?? '',
                    'invoice_id'    => $response_transaciton['response'][0]['ref'] ?? '',
                    'customer_name' => 'Customer',
                    'reason'        => 'Transaction was marked as cancelled.'
                ]);
                                                                
                return true;
            }

            if($status == "completed"){
                $final_source_info = '--';

                if (is_array($source_info) && !empty($source_info)) {
                    $valid = true;

                    foreach ($source_info as $item) {
                        if (
                            !is_array($item) ||
                            empty($item['label']) ||
                            empty($item['value'])
                        ) {
                            $valid = false;
                            break;
                        }
                    }

                    if ($valid) {
                        $final_source_info = json_encode($source_info, JSON_UNESCAPED_UNICODE);
                    }
                }

                $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $response_transaciton['response'][0]['brand_id'] ];

                $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id  AND status = "active"', '* FROM', $params),true);
                if($response_gateway['status'] == true){
                    $currencyRates = [];

                    $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$response_gateway['response'][0]['brand_id'].'"'), true);

                    if (!empty($currencyRes['response'])) {
                        foreach ($currencyRes['response'] as $c) {
                            $currencyRates[$c['code']] = money_sanitize($c['rate']);
                        }
                    }

                    $txnAmount  = money_sanitize($response_transaciton['response'][0]['amount']);
                    $txnCurrency = $response_transaciton['response'][0]['currency'];
                    $gatewayCurrency = $response_gateway['response'][0]['currency'];

                    if ($txnCurrency === $gatewayCurrency) {
                        $convertedAmount = $txnAmount;
                    } else {
                        if (isset($currencyRates[$gatewayCurrency])) {
                            $convertedAmount = money_div($txnAmount, $currencyRates[$gatewayCurrency]);
                        } else {
                            $convertedAmount = "0";
                        }
                    }

                    $fixed_discount = money_sanitize( $response_gateway['response'][0]['fixed_discount']);
                    $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

                    $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
                    $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

                    $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
                    $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

                    $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
                    $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

                    $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

                    if ($txnCurrency !== $gatewayCurrency && isset($currencyRates[$gatewayCurrency])) {
                        $totalDiscount = money_mul($totalDiscount, $currencyRates[$gatewayCurrency]);
                        $totalProcessingFee = money_mul($totalProcessingFee, $currencyRates[$gatewayCurrency]);
                    }
                }else{
                    return false;
                }

                $columns = ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'status', 'trx_id', 'source_info', 'updated_date'];
                $values = [$totalProcessingFee, $totalDiscount, $convertedAmount, $response_gateway['response'][0]['currency'], $gateway_id, 'completed', $trxid, $final_source_info, getCurrentDatetime('Y-m-d H:i:s')];
                $condition = 'id ="'.$response_transaciton['response'][0]['id'].'"'; 

                updateData($db_prefix.'transaction', $columns, $values, $condition);

                $params = [ ':ref' => $response_transaciton['response'][0]['ref'], ':status' => 'completed' ];

                $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status ', '* FROM', $params),true);

                $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'" AND gateway_id = "'.$gateway_id.'"'),true);

                $gateway = $response_gateway['response'][0]['display'] ?? '';

                $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                $response_brand = json_decode(getData($db_prefix.'brands',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'"'),true);

                $net = money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']), $response_transaction['response'][0]['discount_amount']);
                
                $all_transactions = [];

                $all_transactions[] = [
                    "pp_id" => $response_transaction['response'][0]['ref'],
                    "full_name" => $customer_info['name'] ?? 'N/A',
                    "email_address" => $customer_info['email'] ?? 'N/A',
                    "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                    "gateway" => $gateway,
                    "amount" => money_round($response_transaction['response'][0]['amount']),
                    "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                    "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                    "total" => money_round($net),
                    "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                    "currency" => $response_transaction['response'][0]['currency'],
                    "local_currency" => $response_transaction['response'][0]['local_currency'],
                    "metadata" => $metadata, // ← AS-IS
                    "sender" => $response_transaction['response'][0]['sender'],
                    "transaction_id" => $response_transaction['response'][0]['trx_id'],
                    "status" => $response_transaction['response'][0]['status'],
                    "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                ];

                if($response_transaction['response'][0]['webhook_url'] == "" || $response_transaction['response'][0]['webhook_url'] == "--"){

                }else{
                    $ipnData = [
                        "pp_id" => $response_transaction['response'][0]['ref'],
                        "full_name" => $customer_info['name'] ?? 'N/A',
                        "email_address" => $customer_info['email'] ?? 'N/A',
                        "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                        "gateway" => $gateway,
                        "amount" => money_round($response_transaction['response'][0]['amount']),
                        "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                        "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                        "total" => money_round($net),
                        "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                        "currency" => $response_transaction['response'][0]['currency'],
                        "local_currency" => $response_transaction['response'][0]['local_currency'],
                        "metadata" => $metadata, // ← AS-IS
                        "sender" => $response_transaction['response'][0]['sender'],
                        "transaction_id" => $response_transaction['response'][0]['trx_id'],
                        "status" => $response_transaction['response'][0]['status'],
                        "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                    ];

                    $payload = json_encode($ipnData, JSON_UNESCAPED_UNICODE);

                    $jobs = [[
                        'id'      => rand(),
                        'url'     => $response_transaction['response'][0]['webhook_url'],
                        'payload' => json_decode($payload, true),
                    ]];

                    $results = sendIPNMulti($jobs);

                    foreach ($jobs as $job) {
                        $code = $results[$job['id']] ?? 0;
                        $status = ($code === 200) ? 'completed' : 'pending';

                        if($status == 'completed'){

                        }else{
                            $columns = ['ref', 'brand_id', 'payload', 'url', 'created_date', 'updated_date'];
                            $values = [rand(), $response_brand['response'][0]['brand_id'], $payload, $response_transaction['response'][0]['webhook_url'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'webhook_log', $columns, $values);
                        }
                    }
                }

                if (!empty($all_transactions)) {
                    do_action('transactions.updated', $all_transactions);
                }

                pp_dispatch_notification('payment.success', [
                    'amount'         => money_round($net),
                    'currency'       => $response_transaction['response'][0]['currency'] ?? 'BDT',
                    'gateway'        => $gateway,
                    'trx_id'         => $response_transaction['response'][0]['trx_id'] ?? '',
                    'invoice_id'     => $response_transaction['response'][0]['ref'] ?? '',
                    'customer_name'  => $customer_info['name'] ?? 'Customer',
                    'customer_email' => $customer_info['email'] ?? '',
                    'customer_phone' => $customer_info['mobile'] ?? '',
                    'brand_name'     => $response_brand['response'][0]['brand_name'] ?? ''
                ]);

                return true;
            }

        }else{
            return false;
        }
    }

    function pp_checkout_address($paymentid = ''){
        global $path_payment, $paymentID;

        if ($paymentid === '' || $paymentid === null) {
            $paymentid = $paymentID ?? '';
        }

        return pp_site_address().$path_payment.'/'.$paymentid;
    }

    function pp_hexToRgba($hex, $opacity = 1) {
        $hex = str_replace('#','',$hex);
        if(strlen($hex) == 3){
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return "rgba($r,$g,$b,$opacity)";
    }

    function pp_assets($position = ''){
        global $site_url;

        if($position == "head"){
            echo '
                <link rel="stylesheet" href="'.$site_url.'assets/css/tabler.min.css?v=1.7" />
                <link rel="stylesheet" href="'.$site_url.'assets/css/choices.min.css">

                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-flags.min.css" />
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-payments.min.css" />
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-socials.min.css" />
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-vendors.min.css" />

                <style>
                    @import url("'.$site_url.'assets/css/inter.css");
                </style>
            ';
        }else{
            echo '
                <script src="'.$site_url.'assets/js/tabler.min.js"></script>
                <script src="'.$site_url.'assets/js/jquery-3.6.4.min.js"></script>
                <script src="'.$site_url.'assets/js/custom-toast.js?v=1.2"></script>
                <script src="'.$site_url.'assets/js/choices.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/hugerte@1/hugerte.min.js"></script>
            ';
        }
    }

    if (!class_exists('FPDF')) {
        if (file_exists(__DIR__ . '/../../pp-media/sdk/fpdf/fpdf.php')) {
            require_once __DIR__ . '/../../pp-media/sdk/fpdf/fpdf.php';
        }
    }

    if (!class_exists('PipraPayReceiptPDF') && class_exists('FPDF')) {
        class PipraPayReceiptPDF extends FPDF
        {
            public function RoundedRect($x, $y, $w, $h, $r, $style = '', $corners = '1234')
            {
                $k = $this->k;
                $hp = $this->h;
                if($style=='F')
                    $op='f';
                elseif($style=='FD' || $style=='DF')
                    $op='B';
                else
                    $op='S';
                $MyArc = 4/3 * (sqrt(2) - 1);
                $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));

                $xc = $x+$w-$r;
                $yc = $y+$r;
                $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));
                if (strpos($corners, '2')===false)
                    $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k,($hp-$y)*$k ));
                else
                    $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);

                $xc = $x+$w-$r;
                $yc = $y+$h-$r;
                $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
                if (strpos($corners, '3')===false)
                    $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-($y+$h))*$k));
                else
                    $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);

                $xc = $x+$r;
                $yc = $y+$h-$r;
                $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
                if (strpos($corners, '4')===false)
                    $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-($y+$h))*$k));
                else
                    $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);

                $xc = $x+$r;
                $yc = $y+$r;
                $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
                if (strpos($corners, '1')===false) {
                    $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$y)*$k ));
                    $this->_out(sprintf('%.2F %.2F l',($x+$r)*$k,($hp-$y)*$k ));
                } else
                    $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
                $this->_out($op);
            }

            public function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
            {
                $h = $this->h;
                $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
                    $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
            }

            public static function cleanStr($str)
            {
                $str = (string)($str ?? '');
                if (function_exists('iconv')) {
                    $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $str);
                    if ($converted !== false) {
                        return $converted;
                    }
                }
                return utf8_decode($str);
            }
        }
    }

    function pp_downloadReceiptPDF($data = []){

        if (!$data || empty($data['transaction'])) {
            die('Invalid transaction');
        }

        $tx = $data['transaction'];
        if (($tx['status'] ?? '') !== 'completed') {
            die('Receipt is only available for completed transactions.');
        }

        $brand = $data['brand'];

        $amountPaid = money_add(money_sub($tx['amount'] ?? 0, $tx['discount_amount'] ?? 0), $tx['processing_fee'] ?? 0);

        $pdf = new PipraPayReceiptPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 12, 15);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);

        // Brand colors
        $primaryHex = !empty($data['options']['primary_color']) && $data['options']['primary_color'] !== '--' ? $data['options']['primary_color'] : '#4f46e5';
        $cleanHex = ltrim($primaryHex, '#');
        if (strlen($cleanHex) == 3) {
            $pR = hexdec($cleanHex[0].$cleanHex[0]);
            $pG = hexdec($cleanHex[1].$cleanHex[1]);
            $pB = hexdec($cleanHex[2].$cleanHex[2]);
        } elseif (strlen($cleanHex) == 6) {
            $pR = hexdec(substr($cleanHex, 0, 2));
            $pG = hexdec(substr($cleanHex, 2, 2));
            $pB = hexdec(substr($cleanHex, 4, 2));
        } else {
            $pR = 79; $pG = 70; $pB = 229;
        }

        // 1. Top Decorative Brand Accent Bar
        $pdf->SetFillColor($pR, $pG, $pB);
        $pdf->Rect(15, 12, 180, 2.5, 'F');

        // 2. Header Section
        $yHeader = 18;
        $logoRendered = false;

        if (!empty($brand['logo'])) {
            $logoPath = $brand['logo'];
            if (preg_match('/^https?:\/\//i', $logoPath)) {
                if (function_exists('curl_init')) {
                    $tmp = tempnam(sys_get_temp_dir(), 'logo') . '.img';
                    $ch = curl_init($logoPath);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_BINARYTRANSFER => true,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_USERAGENT      => 'Mozilla/5.0',
                    ]);
                    $imgData = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($imgData !== false && $httpCode >= 200 && $httpCode < 300) {
                        file_put_contents($tmp, $imgData);
                        $logoPath = $tmp;
                    }
                }
            }

            try {
                if (file_exists($logoPath)) {
                    $pdf->Image($logoPath, 15, $yHeader, 0, 13);
                    $logoRendered = true;
                }
            } catch (Exception $e) {}

            if (!empty($tmp) && file_exists($tmp)) {
                @unlink($tmp);
            }
        }

        if (!$logoRendered) {
            $pdf->SetFont('Arial', 'B', 15);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetXY(15, $yHeader);
            $pdf->Cell(95, 7, PipraPayReceiptPDF::cleanStr($brand['name'] ?? 'Merchant'), 0, 1);
        }

        $brandAddress = trim(($brand['city_town'] ?? '') . (!empty($brand['city_town']) && !empty($brand['country']) ? ', ' : '') . ($brand['country'] ?? ''));
        if (!empty($brandAddress) && $brandAddress !== ',') {
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetXY(15, $logoRendered ? $yHeader + 14 : $yHeader + 7);
            $pdf->Cell(95, 4.5, PipraPayReceiptPDF::cleanStr($brandAddress), 0, 0);
        }

        // Header Right: RECEIPT Title & Details
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetXY(110, $yHeader);
        $pdf->Cell(85, 7, 'PAYMENT RECEIPT', 0, 1, 'R');

        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY(110, $yHeader + 7);
        $pdf->Cell(85, 4.5, PipraPayReceiptPDF::cleanStr('Receipt #: ' . $tx['ref']), 0, 1, 'R');

        $createdDate = convertUTCtoUserTZ($tx['created_date'], (empty($brand['timezone']) || $brand['timezone'] === '--') ? 'Asia/Dhaka' : $brand['timezone'], "M d, Y h:i A");
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(110, $yHeader + 11.5);
        $pdf->Cell(85, 4.5, PipraPayReceiptPDF::cleanStr('Date: ' . $createdDate), 0, 1, 'R');

        // Divider
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(15, 43, 195, 43);

        // 3. Status & Hero Amount Box
        $status = strtoupper($tx['status'] ?? 'INITIATED');
        $statusConfig = [
            'COMPLETED' => [
                'bg'     => [220, 252, 231],
                'text'   => [22, 101, 52],
                'border' => [187, 247, 208],
                'label'  => 'COMPLETED'
            ],
            'PENDING' => [
                'bg'     => [254, 243, 199],
                'text'   => [146, 64, 14],
                'border' => [253, 230, 138],
                'label'  => 'PENDING'
            ],
            'REFUNDED' => [
                'bg'     => [224, 242, 254],
                'text'   => [7, 89, 133],
                'border' => [186, 230, 253],
                'label'  => 'REFUNDED'
            ],
            'CANCELED' => [
                'bg'     => [254, 226, 226],
                'text'   => [153, 27, 27],
                'border' => [254, 202, 202],
                'label'  => 'CANCELED'
            ],
        ];
        $stCfg = $statusConfig[$status] ?? [
            'bg'     => [241, 245, 249],
            'text'   => [71, 85, 105],
            'border' => [203, 213, 225],
            'label'  => $status
        ];

        // Hero Card Background
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(15, 47, 180, 26, 3, 'DF');

        // Hero Card Left: Status
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(22, 51.5);
        $pdf->Cell(50, 4, 'PAYMENT STATUS', 0, 1);

        $pdf->SetFillColor($stCfg['bg'][0], $stCfg['bg'][1], $stCfg['bg'][2]);
        $pdf->SetDrawColor($stCfg['border'][0], $stCfg['border'][1], $stCfg['border'][2]);
        $pdf->RoundedRect(22, 56.5, 34, 7.5, 2, 'DF');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor($stCfg['text'][0], $stCfg['text'][1], $stCfg['text'][2]);
        $pdf->SetXY(22, 56.5);
        $pdf->Cell(34, 7.5, $stCfg['label'], 0, 0, 'C');

        // Hero Card Right: Amount
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(100, 50.5);
        $pdf->Cell(90, 4, 'TOTAL AMOUNT PAID', 0, 1, 'R');

        $pdf->SetFont('Arial', 'B', 19);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetXY(100, 54.5);
        $pdf->Cell(90, 9, money_round($amountPaid, 2) . ' ' . PipraPayReceiptPDF::cleanStr($tx['currency']), 0, 1, 'R');

        if (!empty($tx['local_net_amount']) && (!empty($tx['local_currency']) && $tx['local_currency'] != $tx['currency'])) {
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetXY(100, 63.5);
            $pdf->Cell(90, 4, PipraPayReceiptPDF::cleanStr('Local Equivalent: ' . money_round($tx['local_net_amount'], 2) . ' ' . $tx['local_currency']), 0, 1, 'R');
        }

        // 4. Two Info Cards (Paid By & Payment Info)
        $yCards = 78;
        $wCard  = 87;
        $hCard  = 38;

        // Card 1: Paid By (Left)
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(15, $yCards, $wCard, $hCard, 2.5, 'DF');

        // Header Tab
        $pdf->SetFillColor(241, 245, 249);
        $pdf->RoundedRect(15, $yCards, $wCard, 8, 2.5, 'DF', '12');
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY(19, $yCards + 2);
        $pdf->Cell(79, 4, 'CUSTOMER DETAILS', 0, 0);

        // Content
        $custName   = !empty($tx['customer']['name']) ? pp_dedupeName($tx['customer']['name']) : 'N/A';
        $custEmail  = !empty($tx['customer']['email']) ? $tx['customer']['email'] : 'N/A';
        $custMobile = !empty($tx['customer']['mobile']) ? $tx['customer']['mobile'] : 'N/A';

        $pdf->SetXY(19, $yCards + 11);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(15, 5, 'Name:', 0, 0);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(64, 5, PipraPayReceiptPDF::cleanStr($custName), 0, 1);

        $pdf->SetXY(19, $yCards + 17.5);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(15, 5, 'Email:', 0, 0);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(64, 5, PipraPayReceiptPDF::cleanStr($custEmail), 0, 1);

        $pdf->SetXY(19, $yCards + 24);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(15, 5, 'Mobile:', 0, 0);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(64, 5, PipraPayReceiptPDF::cleanStr($custMobile), 0, 1);

        // Card 2: Payment Details (Right)
        $xRight = 108;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect($xRight, $yCards, $wCard, $hCard, 2.5, 'DF');

        // Header Tab
        $pdf->SetFillColor(241, 245, 249);
        $pdf->RoundedRect($xRight, $yCards, $wCard, 8, 2.5, 'DF', '12');
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY($xRight + 4, $yCards + 2);
        $pdf->Cell(79, 4, 'TRANSACTION INFO', 0, 0);

        // Content
        $payMethod = !empty($tx['payment_method']) ? $tx['payment_method'] : 'Online Gateway';

        $pdf->SetXY($xRight + 4, $yCards + 11);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(18, 5, 'Method:', 0, 0);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(61, 5, PipraPayReceiptPDF::cleanStr($payMethod), 0, 1);

        $pdf->SetXY($xRight + 4, $yCards + 17.5);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(18, 5, 'Ref ID:', 0, 0);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(61, 5, PipraPayReceiptPDF::cleanStr($tx['ref']), 0, 1);

        $pdf->SetXY($xRight + 4, $yCards + 24);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(18, 5, 'Date/Time:', 0, 0);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(61, 5, PipraPayReceiptPDF::cleanStr($createdDate), 0, 1);

        // 5. Payment Breakdown Table
        $yTable = 122;
        $pdf->SetFillColor(15, 23, 42);
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->RoundedRect(15, $yTable, 180, 8.5, 2, 'DF');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(20, $yTable + 2);
        $pdf->Cell(115, 4.5, 'DESCRIPTION', 0, 0);
        $pdf->SetXY(135, $yTable + 2);
        $pdf->Cell(55, 4.5, 'AMOUNT', 0, 1, 'R');

        $currY = $yTable + 9.5;

        // Base Amount Row
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(15, $currY + 8.5, 195, $currY + 8.5);

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetXY(20, $currY + 2);
        $pdf->Cell(115, 4.5, 'Base Transaction Amount', 0, 0);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetXY(135, $currY + 2);
        $pdf->Cell(55, 4.5, money_round($tx['amount'] ?? 0, 2) . ' ' . PipraPayReceiptPDF::cleanStr($tx['currency']), 0, 1, 'R');

        $currY += 8.5;

        // Discount Row
        if (!empty($tx['discount_amount']) && floatval($tx['discount_amount']) > 0) {
            $pdf->Line(15, $currY + 8.5, 195, $currY + 8.5);
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(22, 101, 52);
            $pdf->SetXY(20, $currY + 2);
            $pdf->Cell(115, 4.5, 'Promotional / Special Discount', 0, 0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetXY(135, $currY + 2);
            $pdf->Cell(55, 4.5, '-' . money_round($tx['discount_amount'], 2) . ' ' . PipraPayReceiptPDF::cleanStr($tx['currency']), 0, 1, 'R');
            $currY += 8.5;
        }

        // Processing Fee Row
        if (!empty($tx['processing_fee']) && floatval($tx['processing_fee']) > 0) {
            $pdf->Line(15, $currY + 8.5, 195, $currY + 8.5);
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->SetXY(20, $currY + 2);
            $pdf->Cell(115, 4.5, 'Payment Gateway Processing Fee', 0, 0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetXY(135, $currY + 2);
            $pdf->Cell(55, 4.5, '+' . money_round($tx['processing_fee'], 2) . ' ' . PipraPayReceiptPDF::cleanStr($tx['currency']), 0, 1, 'R');
            $currY += 8.5;
        }

        // Grand Total Row
        $currY += 2;
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->RoundedRect(15, $currY, 180, 10, 2, 'DF');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetXY(20, $currY + 2.5);
        $pdf->Cell(115, 5, 'TOTAL AMOUNT PAID', 0, 0);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor($pR, $pG, $pB);
        $pdf->SetXY(135, $currY + 2.5);
        $pdf->Cell(55, 5, money_round($amountPaid, 2) . ' ' . PipraPayReceiptPDF::cleanStr($tx['currency']), 0, 1, 'R');

        // 6. Security Notice Box
        $yNotice = $currY + 16;
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(15, $yNotice, 180, 16, 2.5, 'DF');

        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(15, $yNotice + 3.5);
        $pdf->Cell(180, 4.5, 'This is a computer-generated official receipt. No physical signature or stamp is required.', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetXY(15, $yNotice + 8.5);
        $pdf->Cell(180, 4, 'For any questions or support, please contact the merchant referencing the transaction ID.', 0, 1, 'C');

        // 7. Footer Watermark
        $watermark = !empty($data['options']['watermark_text']) ? $data['options']['watermark_text'] : 'Secured by PipraPay';
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetXY(15, 280);
        $pdf->Cell(180, 4, PipraPayReceiptPDF::cleanStr($watermark . ' • Fast, Safe & Secure Digital Payments'), 0, 1, 'C');

        $pdf->Output('D', 'Receipt-' . $tx['ref'] . '.pdf');
    }

    function pp_dedupeName($name)
    {
        $name = trim((string) ($name ?? ''));
        $tokens = preg_split('/\s+/', $name);
        $count = count($tokens);

        if ($count >= 2 && $count % 2 === 0) {
            $half = intval($count / 2);
            $first  = array_slice($tokens, 0, $half);
            $second = array_slice($tokens, $half);

            if ($first === $second) {
                return implode(' ', $first);
            }
        }

        return $name;
    }

    function resolveModuleLanguage($brandLanguage, array $supportedLanguages)
    {
        if (!empty($_SESSION['ui_language'])) {
            $sessionLang = $_SESSION['ui_language'];
            if (isset($supportedLanguages[$sessionLang])) {
                return $sessionLang;
            }
        }

        if (isset($supportedLanguages[$brandLanguage])) {
            return $brandLanguage;
        }

        return array_key_first($supportedLanguages);
    }

    function buildLangArray(array $langText, ?string $language = 'en') {
        $lang = [];

        foreach ($langText as $key => $translations) {
            $lang[$key] = $translations[$language]
                ?? reset($translations);
        }

        return $lang;
    }

    function pp_gateways($tab = '', $data = []){
        global $db_prefix;

        $params = [ ':tab' => $tab, ':brand_id' => $data['brand']['id'] ];

        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE tab = :tab AND brand_id = :brand_id AND status = "active"','* FROM',$params),true);

        $gatewayList = [];

        if ($response_gateway['status'] === true) {
            $currencyRates = [];

            $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$data['brand']['id'].'"'), true);

            if (!empty($currencyRes['response'])) {
                foreach ($currencyRes['response'] as $c) {
                    $currencyRates[$c['code']] =$c['rate'];
                }
            }

            foreach ($response_gateway['response'] as $row) {
                $txnAmount  = money_sanitize($data['transaction']['amount']);
                $txnCurrency = $data['transaction']['currency'];

                if ($txnCurrency === $row['currency']) {
                    $convertedAmount = $txnAmount;
                } else {
                    if (isset($currencyRates[$row['currency']])) {
                        $convertedAmount = money_div($txnAmount, $currencyRates[$row['currency']]);
                    } else {
                        $convertedAmount = "0";
                    }
                }

                $fixed_discount = money_sanitize( $response_gateway['response'][0]['fixed_discount']);
                $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

                $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
                $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

                $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
                $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

                $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
                $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

                $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

                $min = money_sanitize($row['min_allow']);
                $max = money_sanitize($row['max_allow']);

                $hasNoMax = bccomp($max, '0', 2) <= 0 || $max === '' || $max === '--';

                $isAboveMin = bccomp(money_round($convertedAmount), $min, 2) >= 0;
                $isBelowMax = $hasNoMax ? true : (bccomp(money_round($convertedAmount), $max, 2) <= 0);

                if ($isAboveMin && $isBelowMax) {
                    $gatewayList[] = [
                        'gateway_id'           => $row['gateway_id'],
                        'slug'                 => $row['slug'],
                        'name'                 => $row['name'],
                        'display'              => $row['display'],
                        'logo'                 => $row['logo'],
                        'currency'             => $row['currency'],
                        'min_allow'            => money_round($row['min_allow']),
                        'max_allow'            => money_round($row['max_allow']),
                        'fixed_discount'       => money_round($row['fixed_discount']),
                        'percentage_discount'  => money_round($row['percentage_discount']),
                        'fixed_charge'         => money_round($row['fixed_charge']),
                        'percentage_charge'    => money_round($row['percentage_charge']),
                        'primary_color'        => $row['primary_color'],
                        'text_color'           => $row['text_color'],
                        'btn_color'            => $row['btn_color'],
                        'btn_text_color'       => $row['btn_text_color'],
                    ];
                }
            }

            return [
                'status'   => true,
                'gateway'  => $gatewayList
            ];
        }

        return [
            'status'  => false,
            'gateway' => []
        ];
    }

    function pp_gateway_info($gateway_id = '', $data = []){
        global $db_prefix;

        $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $data['brand']['id'] ];

        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id AND status = "active"','* FROM',$params),true);

        if ($response_gateway['status'] === true) {
            $row = $response_gateway['response'][0];
            $currencyRates = [];

            $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$data['brand']['id'].'"'), true);

            if (!empty($currencyRes['response'])) {
                foreach ($currencyRes['response'] as $c) {
                    $currencyRates[$c['code']] =$c['rate'];
                }
            }

            $txnAmount  = money_sanitize($data['transaction']['amount']);
            $txnCurrency = $data['transaction']['currency'];

            if ($txnCurrency === $row['currency']) {
                $convertedAmount = $txnAmount;
            } else {
                if (isset($currencyRates[$row['currency']])) {
                    $convertedAmount = money_div($txnAmount, $currencyRates[$row['currency']]);
                } else {
                    $convertedAmount = "0";
                }
            }

            $fixed_discount = money_sanitize( $response_gateway['response'][0]['fixed_discount']);
            $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

            $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
            $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

            $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
            $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

            $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
            $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

            $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

            $min = money_sanitize($row['min_allow']);
            $max = money_sanitize($row['max_allow']);

            $hasNoMax = bccomp($max, '0', 2) <= 0 || $max === '' || $max === '--';

            $isAboveMin = bccomp(money_round($convertedAmount), $min, 2) >= 0;
            $isBelowMax = $hasNoMax ? true : (bccomp(money_round($convertedAmount), $max, 2) <= 0);

            if ($isAboveMin && $isBelowMax) {
                if(file_exists(__DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php')){
                    require_once __DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php';

                    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_gateway['response'][0]['slug']))) . 'Gateway';

                    $gateway = new $class();

                    $gateway_info = $gateway->info();

                    if (method_exists($gateway, 'supported_languages')) {
                        $supported_languages = $gateway->supported_languages();
                    }else{
                        $supported_languages = [];
                    }
                }else{
                    if($response_gateway['response'][0]['tab'] == 'bank'){
                        $supported_languages = [
                            'en' => 'English',
                            'bn' => 'বাংলা',
                            'hi' => 'हिन्दी',
                            'ur' => 'اردو',
                            'ar' => 'العربية',
                        ];
                    }else{
                        $supported_languages = [];
                    }
                }

                $gatewayList = [
                    'gateway_id'           => $row['gateway_id'],
                    'slug'                 => $row['slug'],
                    'name'                 => $row['name'],
                    'display'              => $row['display'],
                    'logo'                 => $row['logo'],
                    'currency'             => $row['currency'],
                    'min_allow'            => money_round($row['min_allow']),
                    'max_allow'            => money_round($row['max_allow']),
                    'fixed_discount'       => money_round($row['fixed_discount']),
                    'percentage_discount'  => money_round($row['percentage_discount']),
                    'fixed_charge'         => money_round($row['fixed_charge']),
                    'percentage_charge'    => money_round($row['percentage_charge']),
                    'primary_color'        => $row['primary_color'],
                    'text_color'           => $row['text_color'],
                    'btn_color'            => $row['btn_color'],
                    'btn_text_color'       => $row['btn_text_color'],
                ];

                return [
                    'status'   => true,
                    'gateway'  => $gatewayList,
                    'supported_languages'  => $supported_languages
                ];
            }else{
                return [
                    'status'   => false,
                    'gateway'  => []
                ];
            }
        }

        return [
            'status'  => false,
            'gateway' => []
        ];
    }

    /* =====================================================================
     *  Automatic-gateway checkout flow (number first, then pay + auto verify)
     * ===================================================================== */
    function pp_auto_flow_lang(){
        return [
            'ppx_enter_number' => [
                'en' => 'Enter the {gateway} number you will pay from.',
                'bn' => 'আপনি যে {gateway} নম্বর থেকে পেমেন্ট করবেন সেটি লিখুন।',
            ],
            'ppx_enter_number_any' => [
                'en' => 'Enter the mobile number you will pay from.',
                'bn' => 'আপনি যে মোবাইল নম্বর থেকে পেমেন্ট করবেন সেটি লিখুন।',
            ],
            'ppx_scan_note_any' => [
                'en' => 'Scan the QR with any bank or mobile banking app, then pay exactly {amount}.',
                'bn' => 'যেকোনো ব্যাংক বা মোবাইল ব্যাংকিং অ্যাপ দিয়ে QR স্ক্যান করুন, তারপর ঠিক {amount} পেমেন্ট করুন।',
            ],
            'ppx_bq_tab_shot' => ['en' => 'Screenshot', 'bn' => 'স্ক্রিনশট'],
            'ppx_bq_tab_trx' => ['en' => 'Transaction ID', 'bn' => 'ট্রানজ্যাকশন আইডি'],
            'ppx_bq_shot_hint' => ['en' => 'Upload a screenshot of the successful payment from your app. The Transaction ID in your app can look different from ours, so a screenshot is the best proof. We will check it shortly.', 'bn' => 'আপনার অ্যাপের সফল পেমেন্টের স্ক্রিনশট আপলোড করুন। আপনার অ্যাপের ট্রানজ্যাকশন আইডি আমাদের থেকে আলাদা দেখাতে পারে, তাই স্ক্রিনশটই সেরা প্রমাণ। আমরা শীঘ্রই যাচাই করব।'],
            'ppx_bq_choose' => ['en' => 'Tap to choose a screenshot', 'bn' => 'স্ক্রিনশট বেছে নিতে ট্যাপ করুন'],
            'ppx_bq_submit_shot' => ['en' => 'Submit screenshot', 'bn' => 'স্ক্রিনশট জমা দিন'],
            'ppx_bq_shot_type' => ['en' => 'Please choose an image file (JPG, PNG or WEBP).', 'bn' => 'অনুগ্রহ করে একটি ছবি (JPG, PNG বা WEBP) বেছে নিন।'],
            'ppx_bq_shot_big' => ['en' => 'This file is too large. Please choose an image under 10 MB.', 'bn' => 'ফাইলটি অনেক বড়। ১০ MB-এর কম ছবি বেছে নিন।'],
            'ppx_bq_title' => ['en' => 'Pay easily from any bank or MFS app', 'bn' => 'যেকোনো ব্যাংক বা MFS অ্যাপ থেকে সহজে পেমেন্ট করুন'],
            'ppx_bq_open' => ['en' => 'Open App', 'bn' => 'অ্যাপ খুলুন'],
            'ppx_bq_scan' => ['en' => 'Scan QR', 'bn' => 'QR স্ক্যান'],
            'ppx_bq_confirm' => ['en' => 'Confirm', 'bn' => 'কনফার্ম'],
            'ppx_bq_proof' => ['en' => 'Submit your transaction proof', 'bn' => 'ট্রানজ্যাকশন প্রুফ জমা দিন'],
            'ppx_bq_trx_hint' => ['en' => 'Enter the Transaction ID shown in your payment app or SMS.', 'bn' => 'আপনার পেমেন্ট অ্যাপ বা SMS-এ দেখানো ট্রানজ্যাকশন আইডি দিন।'],
            'ppx_bq_supported' => ['en' => 'Supported Apps:', 'bn' => 'সাপোর্টেড অ্যাপ:'],
            'ppx_bq_more' => ['en' => '& more', 'bn' => 'এবং আরও'],
            'ppx_bq_static_note' => ['en' => 'Scan the QR and pay exactly {amount}. The amount is not locked in this QR, so type it carefully.', 'bn' => 'QR স্ক্যান করে ঠিক {amount} পেমেন্ট করুন। এই QR-এ অ্যামাউন্ট লক করা নেই, তাই সাবধানে লিখুন।'],
            'ppx_bq_noqr' => ['en' => 'This payment method is not ready yet. Please choose another method or contact the merchant.', 'bn' => 'এই পেমেন্ট মেথড এখনো প্রস্তুত নয়। অন্য মেথড বেছে নিন বা মার্চেন্টের সাথে যোগাযোগ করুন।'],
            'ppx_updating' => ['en' => 'Payment status updated. Refreshing...', 'bn' => 'পেমেন্টের স্ট্যাটাস আপডেট হয়েছে। রিফ্রেশ হচ্ছে...'],
            'ppx_cancel' => ['en' => 'Cancel', 'bn' => 'বাতিল'],
            'ppx_invalid_number' => [
                'en' => 'Please enter a valid mobile number (e.g. 01XXXXXXXXX).',
                'bn' => 'সঠিক মোবাইল নম্বর লিখুন (যেমন 01XXXXXXXXX)।',
            ],
            'ppx_scan_note' => [
                'en' => 'Scan the QR with your {gateway} app, then send exactly {amount}.',
                'bn' => 'আপনার {gateway} অ্যাপ দিয়ে QR স্ক্যান করুন, তারপর ঠিক {amount} পাঠান।',
            ],
            'ppx_send_note' => [
                'en' => 'Open your {gateway} app and send exactly {amount}.',
                'bn' => 'আপনার {gateway} অ্যাপ খুলে ঠিক {amount} পাঠান।',
            ],
            'ppx_or' => ['en' => 'OR', 'bn' => 'অথবা'],
            'ppx_to_number_personal' => [
                'en' => 'Send Money to this number',
                'bn' => 'এই নম্বরে সেন্ড মানি করুন',
            ],
            'ppx_to_number_agent' => [
                'en' => 'Cash Out to this number',
                'bn' => 'এই নম্বরে ক্যাশ আউট করুন',
            ],
            'ppx_to_number_merchant' => [
                'en' => 'Pay to this number',
                'bn' => 'এই নম্বরে পেমেন্ট করুন',
            ],
            'ppx_send_exactly' => ['en' => 'Send exactly', 'bn' => 'ঠিক এই পরিমাণ পাঠান'],
            'ppx_copy' => ['en' => 'Copy', 'bn' => 'কপি'],
            'ppx_waiting' => ['en' => 'Waiting for your payment...', 'bn' => 'আপনার পেমেন্টের অপেক্ষায়...'],
            'ppx_left' => ['en' => 'left', 'bn' => 'বাকি'],
            'ppx_paid' => ['en' => 'Payment received! Redirecting...', 'bn' => 'পেমেন্ট পাওয়া গেছে! রিডাইরেক্ট হচ্ছে...'],
            'ppx_expired' => [
                'en' => 'Time is over. Please submit the Transaction ID to verify.',
                'bn' => 'সময় শেষ। যাচাই করতে ট্রানজ্যাকশন আইডি দিন।',
            ],
            'ppx_wait_note' => [
                'en' => 'Please wait. Your payment will be verified automatically.',
                'bn' => 'অনুগ্রহ করে অপেক্ষা করুন। আপনার পেমেন্ট স্বয়ংক্রিয়ভাবে যাচাই হবে।',
            ],
            'ppx_taking_long' => ['en' => 'Taking too long to verify?', 'bn' => 'যাচাই হতে দেরি হচ্ছে?'],
            'ppx_submit_trx' => ['en' => 'Submit {gateway} Transaction ID', 'bn' => '{gateway} ট্রানজ্যাকশন আইডি দিন'],
            'ppx_change_number' => ['en' => 'Change number', 'bn' => 'নম্বর পরিবর্তন করুন'],
            'ppx_download_qr' => ['en' => 'Download QR', 'bn' => 'QR ডাউনলোড'],
        ];
    }

    function ppx_hex($hex, $fallback){
        return (is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) ? $hex : $fallback;
    }

    /* Darken very light brand colours (e.g. yellow) so text stays readable on white */
    function ppx_accent($hex){
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        if ($lum > 0.62) {
            $r = (int)($r * 0.5);
            $g = (int)($g * 0.5);
            $b = (int)($b * 0.5);
        }
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    function pp_render_auto_flow($data, $gateway_info){
        if (!empty($gateway_info['direct_qr'])) { return pp_render_bangla_qr_flow($data, $gateway_info); }
        $L = $data['lang'] ?? [];
        $t = function($key) use ($L){
            return htmlspecialchars($L[$key] ?? '', ENT_QUOTES, 'UTF-8');
        };

        $brandNames = [
            'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'upay' => 'Upay',
            'pathaopay' => 'Pathao Pay', 'cellfin' => 'Cellfin', 'ipay' => 'iPay', 'mcash' => 'mCash',
            'okwallet' => 'OK Wallet', 'tap' => 'Tap', 'telecash' => 'Telecash',
        ];
        $senderKey = strtolower((string)($gateway_info['sender_key'] ?? ''));
        $brand     = $brandNames[$senderKey] ?? ucfirst($senderKey);
        if (!empty($gateway_info['display_name'])) { $brand = (string)$gateway_info['display_name']; }
        $anyApp    = !empty($gateway_info['any_app']);
        $brandH    = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');

        $type = strtolower((string)($gateway_info['sender_type'] ?? 'personal'));
        if (!in_array($type, ['personal', 'agent', 'merchant'], true)) {
            $type = 'personal';
        }

        $primary = ppx_hex($data['gateway']['primary_color'] ?? '', '#2563eb');
        $textCol = ppx_hex($data['gateway']['text_color'] ?? '', '#ffffff');
        $btnCol  = ppx_hex($data['gateway']['btn_color'] ?? '', $primary);
        $btnText = ppx_hex($data['gateway']['btn_text_color'] ?? '', '#ffffff');
        $accent  = ppx_accent($primary);

        $amountNum = (float)($data['transaction']['local_net_amount'] ?? 0);
        $amountTxt = rtrim(rtrim(number_format($amountNum, 2, '.', ''), '0'), '.');
        $currency  = (string)($data['transaction']['local_currency'] ?? 'BDT');
        $amountFull = $amountTxt . ' ' . $currency;

        $number = trim((string)($data['options']['mobile_number'] ?? ''));
        $qr     = trim((string)($data['options']['qr_code'] ?? ''));
        if ($number === '--') { $number = ''; }
        if ($qr === '--')     { $qr = ''; }

        // Pre-fill the payer number with the customer's phone when we have it
        $prefill = '';
        $ci = $data['transaction']['customer_info'] ?? null;
        if (is_string($ci)) { $ci = json_decode($ci, true); }
        if (is_array($ci) && !empty($ci['mobile']) && $ci['mobile'] !== '--') {
            $prefill = preg_replace('/[^0-9+]/', '', (string)$ci['mobile']);
        }

        $fill = function($key, $extra = []) use ($L, $brandH, $amountFull){
            $s = htmlspecialchars($L[$key] ?? '', ENT_QUOTES, 'UTF-8');
            $map = array_merge(['{gateway}' => $brandH, '{amount}' => '<b class="ppx-acc">' . htmlspecialchars($amountFull, ENT_QUOTES, 'UTF-8') . '</b>'], $extra);
            return str_replace(array_keys($map), array_values($map), $s);
        };

        $cfg = [
            'ref'       => (string)($data['transaction']['ref'] ?? ''),
            'gatewayId' => (string)($data['gateway']['gateway_id'] ?? ''),
            'window'    => 900,
            'poll'      => 5000,
            't' => [
                'invalid' => $L['ppx_invalid_number'] ?? '',
                'left'    => $L['ppx_left'] ?? '',
                'expired' => $L['ppx_expired'] ?? '',
                'paid'    => $L['ppx_paid'] ?? '',
                'waiting' => $L['ppx_waiting'] ?? '',
                'updating' => $L['ppx_updating'] ?? '',
            ],
        ];
        $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $toNumberKey = 'ppx_to_number_' . $type;
        $noteKey     = ($qr !== '') ? ($anyApp ? 'ppx_scan_note_any' : 'ppx_scan_note') : 'ppx_send_note';
        $enterKey    = $anyApp ? 'ppx_enter_number_any' : 'ppx_enter_number';

        ob_start();
?>
<div class="ppx" id="ppx" style="--ppx-primary:<?php echo $primary; ?>;--ppx-text:<?php echo $textCol; ?>;--ppx-accent:<?php echo $accent; ?>;--ppx-btn:<?php echo $btnCol; ?>;--ppx-btn-text:<?php echo $btnText; ?>;">
<style>
    .ppx{font-family:inherit;color:#0f172a;margin-top:6px}
    .ppx *{box-sizing:border-box}
    .ppx-acc{color:var(--ppx-accent)}
    .ppx-panel{background:var(--ppx-primary);color:var(--ppx-text);border-radius:16px;padding:30px 18px;text-align:center}
    .ppx-panel p{margin:0 0 16px;font-weight:700;font-size:15px;line-height:1.4}
    .ppx-input{width:100%;height:56px;border-radius:16px;border:3px solid rgba(255,255,255,.55);background:#fff;color:#0f172a;text-align:center;font-size:20px;font-weight:600;outline:none;letter-spacing:.5px}
    .ppx-input:focus{border-color:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.12)}
    .ppx-err{min-height:18px;margin-top:10px;font-size:13px;font-weight:600;color:var(--ppx-text)}
    .ppx-actions{display:flex;gap:12px;margin-top:16px}
    .ppx-btn{flex:1;height:52px;border-radius:14px;border:1px solid transparent;font-weight:700;font-size:16px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}
    .ppx-btn-cancel{background:#eef1f5;color:#1e293b;border-color:#d9dee6}
    .ppx-btn-go{background:var(--ppx-btn);color:var(--ppx-btn-text)}
    .ppx-btn:disabled{opacity:.6;cursor:wait}
    .ppx-note{background:#fff8f2;border:1px solid #f1e3d6;border-radius:12px;padding:12px 14px;font-size:14px;line-height:1.5}
    .ppx-qr{position:relative;width:min(280px,100%);margin:18px auto 0;text-align:center}
    .ppx-qr img{width:100%;display:block;border-radius:12px;background:#fff}
    .ppx-dl{position:absolute;right:-14px;bottom:-14px;width:36px;height:36px;border-radius:50%;background:var(--ppx-btn);color:var(--ppx-btn-text);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,.2)}
    .ppx-dl svg{width:18px;height:18px}
    .ppx-or{text-align:center;color:#64748b;font-size:13px;margin:22px 0 12px}
    .ppx-card{border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;background:#fff}
    .ppx-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px}
    .ppx-row+.ppx-row{border-top:1px solid #e2e8f0}
    .ppx-k{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748b;font-weight:600}
    .ppx-v{font-size:19px;font-weight:700;letter-spacing:.04em;margin-top:2px;word-break:break-all}
    .ppx-copy{border:1px solid #e2e8f0;background:#fff;color:var(--ppx-accent);font-weight:700;font-size:13px;border-radius:8px;padding:6px 14px;cursor:pointer}
    .ppx-wait{display:flex;align-items:center;gap:10px;margin-top:14px;padding:12px 14px;border-radius:12px;background:#f3f6ff;border:1px solid #dbe4ff;color:var(--ppx-accent);font-weight:600;font-size:14px}
    .ppx-wait .ppx-time{margin-left:auto;color:#334155;font-weight:600}
    .ppx-dot{width:12px;height:12px;border-radius:50%;background:var(--ppx-accent);box-shadow:0 0 0 0 var(--ppx-accent);animation:ppxPulse 1.6s infinite;flex:0 0 12px}
    .ppx-wait.ppx-ok{background:#ecfdf3;border-color:#bbf7d0;color:#15803d}
    .ppx-wait.ppx-ok .ppx-dot{background:#16a34a;animation:none}
    .ppx-wait.ppx-off{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
    .ppx-wait.ppx-off .ppx-dot{background:#ea580c;animation:none}
    @keyframes ppxPulse{0%{box-shadow:0 0 0 0 rgba(37,99,235,.35)}70%{box-shadow:0 0 0 9px rgba(37,99,235,0)}100%{box-shadow:0 0 0 0 rgba(37,99,235,0)}}
    .ppx-help{text-align:center;color:#64748b;font-size:12.5px;line-height:1.7;margin:12px 0 0}
    .ppx-help a,.ppx-link{color:var(--ppx-accent);font-weight:600;text-decoration:underline;cursor:pointer}
    .ppx-trx{margin-top:14px}
    .ppx-trx .ppx-input{height:48px;font-size:16px;border:1px solid #cbd5e1;text-align:left;padding:0 14px;letter-spacing:0}
    .ppx-trx .ppx-btn{width:100%;margin-top:10px;height:48px}
    .ppx-change{display:block;text-align:center;margin-top:10px;font-size:12.5px}
</style>

    <div class="ppx-step" id="ppx-step1">
        <div class="ppx-panel">
            <p><?php echo $fill($enterKey); ?></p>
            <input class="ppx-input" id="ppx-number" type="tel" inputmode="numeric" autocomplete="tel" maxlength="16" placeholder="01XXXXXXXXX" value="<?php echo htmlspecialchars($prefill, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="ppx-err" id="ppx-err"></div>
        </div>
        <div class="ppx-actions">
            <a class="ppx-btn ppx-btn-cancel" href="<?php echo htmlspecialchars(pp_checkout_address(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $t('ppx_cancel'); ?></a>
            <button type="button" class="ppx-btn ppx-btn-go" id="ppx-go"><?php echo $t('submit'); ?></button>
        </div>
    </div>

    <div class="ppx-step" id="ppx-step2" style="display:none">
        <div class="ppx-note"><?php echo $fill($noteKey); ?></div>

        <?php if ($qr !== ''): ?>
        <div class="ppx-qr">
            <img src="<?php echo htmlspecialchars($qr, ENT_QUOTES, 'UTF-8'); ?>" alt="QR">
            <a class="ppx-dl" href="<?php echo htmlspecialchars($qr, ENT_QUOTES, 'UTF-8'); ?>" download title="<?php echo $t('ppx_download_qr'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>
            </a>
        </div>
        <?php if ($number !== ''): ?><div class="ppx-or"><?php echo $t('ppx_or'); ?></div><?php endif; ?>
        <?php endif; ?>

        <div class="ppx-card" style="<?php echo ($qr === '' ? 'margin-top:14px' : ''); ?>">
            <?php if ($number !== ''): ?>
            <div class="ppx-row">
                <div>
                    <div class="ppx-k"><?php echo $t($toNumberKey); ?></div>
                    <div class="ppx-v"><?php echo htmlspecialchars($number, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <button type="button" class="ppx-copy" data-copy="<?php echo htmlspecialchars($number, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $t('ppx_copy'); ?></button>
            </div>
            <?php endif; ?>
            <div class="ppx-row">
                <div>
                    <div class="ppx-k"><?php echo $t('ppx_send_exactly'); ?></div>
                    <div class="ppx-v ppx-acc"><?php echo htmlspecialchars($amountFull, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <button type="button" class="ppx-copy" data-copy="<?php echo htmlspecialchars($amountTxt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $t('ppx_copy'); ?></button>
            </div>
        </div>

        <div class="ppx-wait" id="ppx-wait">
            <span class="ppx-dot"></span>
            <span id="ppx-wait-text"><?php echo $t('ppx_waiting'); ?></span>
            <span class="ppx-time" id="ppx-time"></span>
        </div>

        <p class="ppx-help">
            <?php echo $t('ppx_wait_note'); ?><br>
            <?php echo $t('ppx_taking_long'); ?> <a id="ppx-showtrx"><?php echo $fill('ppx_submit_trx'); ?></a>
        </p>

        <div class="ppx-trx" id="ppx-trx" style="display:none">
            <form id="ppx-trx-form" autocomplete="off">
                <input type="hidden" name="action-v2" value="transaction-verify">
                <input type="hidden" name="gateway-id" value="<?php echo htmlspecialchars((string)($data['gateway']['gateway_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="transaction-id" value="<?php echo htmlspecialchars((string)($data['transaction']['ref'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="mobile_number" id="ppx-trx-num" value="">
                <input class="ppx-input" type="text" name="trxid" required placeholder="<?php echo $t('enter_transaction_id'); ?>">
                <button class="ppx-btn ppx-btn-go" type="submit" id="ppx-trx-btn"><?php echo $t('verify'); ?></button>
            </form>
        </div>

        <a class="ppx-link ppx-change" id="ppx-change"><?php echo $t('ppx_change_number'); ?></a>
    </div>

<script data-cfasync="false">
(function(){
    var C = <?php echo $cfgJson; ?>;
    var $ = function(id){ return document.getElementById(id); };
    var step1 = $('ppx-step1'), step2 = $('ppx-step2'), input = $('ppx-number'), err = $('ppx-err');
    var key = 'ppx:' + C.ref, state = null, tick = null, poller = null, busy = false, done = false;

    function load(){ try { var v = sessionStorage.getItem(key); return v ? JSON.parse(v) : null; } catch(e){ return null; } }
    function save(v){ try { sessionStorage.setItem(key, JSON.stringify(v)); } catch(e){} }
    function wipe(){ try { sessionStorage.removeItem(key); } catch(e){} }
    function local(n){ return (n || '').replace(/[\s\-]/g, '').replace(/^\+?88/, ''); }
    function valid(n){ return /^01[3-9]\d{8}$/.test(local(n)); }
    function fmt(s){ var m = Math.floor(s / 60), r = s % 60; return m + 'm ' + (r < 10 ? '0' : '') + r + 's'; }
    function stopAll(){ clearInterval(tick); clearInterval(poller); tick = poller = null; }
    function setWait(cls, text){
        var w = $('ppx-wait'); w.className = 'ppx-wait' + (cls ? ' ' + cls : '');
        $('ppx-wait-text').textContent = text;
    }
    function showTrx(){ $('ppx-trx').style.display = 'block'; }

    function remaining(){ return C.window - Math.floor((Date.now() - state.start) / 1000); }

    function expire(){
        stopAll();
        setWait('ppx-off', C.t.expired);
        $('ppx-time').textContent = '';
        showTrx();
    }

    function poll(){
        if (busy || done || !state) return;
        busy = true;
        var fd = new FormData();
        fd.append('action-v2', 'transaction-verify');
        fd.append('gateway-id', C.gatewayId);
        fd.append('transaction-id', C.ref);
        fd.append('auto', '1');
        fd.append('mobile_number', state.number);
        fetch('', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                busy = false;
                if (d.status === 'true') {
                    done = true; stopAll();
                    setWait('ppx-ok', C.t.paid);
                    $('ppx-time').textContent = '';
                    if (typeof success === 'function') { success(d); } else { location.reload(); }
                } else if (d.status === 'state') {
                    /* the order was approved / cancelled meanwhile: show the real result */
                    done = true; stopAll();
                    setWait(d.state === 'completed' ? 'ppx-ok' : 'ppx-off', d.state === 'completed' ? C.t.paid : C.t.updating);
                    $('ppx-time').textContent = '';
                    setTimeout(function(){ location.reload(); }, 700);
                } else if (d.status === 'false') {
                    stopAll();
                    setWait('ppx-off', d.title || C.t.expired);
                    $('ppx-time').textContent = '';
                    if (typeof failed === 'function') { failed(d.title, d.message); }
                    showTrx();
                }
                /* status "pending" -> keep waiting */
            })
            .catch(function(){ busy = false; });
    }

    function showStep2(){
        step1.style.display = 'none';
        step2.style.display = 'block';
        $('ppx-trx-num').value = state.number;
        done = false;
        setWait('', C.t.waiting);
        var left = remaining();
        if (left <= 0) { expire(); return; }
        $('ppx-time').textContent = fmt(left) + ' ' + C.t.left;
        tick = setInterval(function(){
            var r = remaining();
            if (r <= 0) { expire(); return; }
            $('ppx-time').textContent = fmt(r) + ' ' + C.t.left;
        }, 1000);
        poller = setInterval(poll, C.poll);
        poll();
    }

    $('ppx-go').addEventListener('click', function(){
        var n = input.value;
        if (!valid(n)) { err.textContent = C.t.invalid; input.focus(); return; }
        err.textContent = '';
        state = { number: local(n), start: Date.now() };
        save(state);
        showStep2();
    });
    input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { $('ppx-go').click(); } });

    $('ppx-change').addEventListener('click', function(){
        stopAll(); wipe(); state = null;
        $('ppx-trx').style.display = 'none';
        step2.style.display = 'none';
        step1.style.display = 'block';
    });

    $('ppx-showtrx').addEventListener('click', function(e){ e.preventDefault(); showTrx(); });

    var copyBtns = document.querySelectorAll('#ppx [data-copy]');
    for (var i = 0; i < copyBtns.length; i++) {
        copyBtns[i].addEventListener('click', function(){
            var v = this.getAttribute('data-copy');
            if (typeof copy_value === 'function') { copy_value(v); }
            else if (navigator.clipboard) { navigator.clipboard.writeText(v); }
        });
    }

    $('ppx-trx-form').addEventListener('submit', function(e){
        e.preventDefault();
        var btn = $('ppx-trx-btn'), old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        fetch('', { method: 'POST', body: new FormData(this), credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false; btn.innerHTML = old;
                if (d.status === 'true') { if (typeof success === 'function') { success(d); } else { location.reload(); } }
                else if (d.status === 'state') { location.reload(); }
                else if (typeof failed === 'function') { failed(d.title || 'Failed', d.message || ''); }
            })
            .catch(function(){
                btn.disabled = false; btn.innerHTML = old;
                if (typeof failed === 'function') { failed('Request Error', 'Something went wrong. Please try again.'); }
            });
    });

    state = load();
    if (state && state.number && state.start) { showStep2(); }
})();
</script>
</div>
<?php
        return ob_get_clean();
    }

    /* =====================================================================
     *  Bangla QR (locked amount) - EMVCo helpers + QR-first checkout page
     * ===================================================================== */
    function pp_bqr_crc16($str){
        $crc = 0xFFFF;
        $len = strlen($str);
        for ($c = 0; $c < $len; $c++) {
            $crc ^= (ord($str[$c]) << 8);
            for ($i = 0; $i < 8; $i++) {
                $crc = ($crc & 0x8000) ? ((($crc << 1) ^ 0x1021) & 0xFFFF) : (($crc << 1) & 0xFFFF);
            }
        }
        return sprintf('%04X', $crc);
    }

    /* Splits an EMVCo QR string into [tag, value] pairs; returns null when it is malformed */
    function pp_bqr_parse($payload){
        $s = (string)$payload;
        $n = strlen($s);
        $i = 0;
        $out = [];
        while ($i < $n) {
            if ($i + 4 > $n) { return null; }
            $tag = substr($s, $i, 2);
            $len = substr($s, $i + 2, 2);
            if (!ctype_digit($tag) || !ctype_digit($len)) { return null; }
            $len = (int)$len;
            if ($i + 4 + $len > $n) { return null; }
            $out[] = [$tag, substr($s, $i + 4, $len)];
            $i += 4 + $len;
        }
        return $out;
    }

    /* Takes the merchant's static Bangla QR text and returns the same QR with the amount locked in */
    function pp_bqr_build_locked($payload, $amount){
        $payload = trim((string)$payload);
        $amount  = trim((string)$amount);

        if (strlen($payload) < 30 || strlen($payload) > 900) { return null; }
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $amount) || (float)$amount <= 0) { return null; }

        $tags = pp_bqr_parse($payload);
        if (empty($tags) || $tags[0][0] !== '00') { return null; }

        $last = $tags[count($tags) - 1];
        if ($last[0] !== '63' || strlen($last[1]) !== 4) { return null; }
        if (strcasecmp(pp_bqr_crc16(substr($payload, 0, -4)), $last[1]) !== 0) { return null; }   // reject a damaged / mistyped QR text

        $items = [];
        foreach ($tags as $t) {
            if (in_array($t[0], ['01', '53', '54', '63'], true)) { continue; }
            if (strlen($t[1]) > 99) { return null; }
            $items[$t[0]] = $t[1];
        }
        $items['01'] = '12';        // dynamic QR
        $items['53'] = '050';       // BDT
        $items['54'] = number_format((float)$amount, 2, '.', '');
        ksort($items, SORT_STRING);

        $out = '';
        foreach ($items as $tag => $val) {
            $out .= $tag . sprintf('%02d', strlen($val)) . $val;
        }
        $out .= '6304';
        return $out . pp_bqr_crc16($out);
    }

    /* QR Code Generator library (MIT) built in, so the page never depends on a separate file */
    function pp_bqr_qrlib(){
        return <<<'PPQRJS'
/* QR Code Generator (c) 2009 Kazuhiko Arase, MIT license - http://www.d-project.com/ */
var qrcode=function(){var t=function(t,r){var e=t,n=g[r],o=null,i=0,a=null,u=[],f={},c=function(t,r){o=function(t){for(var r=new Array(t),e=0;e<t;e+=1){r[e]=new Array(t);for(var n=0;n<t;n+=1)r[e][n]=null}return r}(i=4*e+17),l(0,0),l(i-7,0),l(0,i-7),s(),h(),d(t,r),e>=7&&v(t),null==a&&(a=p(e,n,u)),w(a,r)},l=function(t,r){for(var e=-1;e<=7;e+=1)if(!(t+e<=-1||i<=t+e))for(var n=-1;n<=7;n+=1)r+n<=-1||i<=r+n||(o[t+e][r+n]=0<=e&&e<=6&&(0==n||6==n)||0<=n&&n<=6&&(0==e||6==e)||2<=e&&e<=4&&2<=n&&n<=4)},h=function(){for(var t=8;t<i-8;t+=1)null==o[t][6]&&(o[t][6]=t%2==0);for(var r=8;r<i-8;r+=1)null==o[6][r]&&(o[6][r]=r%2==0)},s=function(){for(var t=B.getPatternPosition(e),r=0;r<t.length;r+=1)for(var n=0;n<t.length;n+=1){var i=t[r],a=t[n];if(null==o[i][a])for(var u=-2;u<=2;u+=1)for(var f=-2;f<=2;f+=1)o[i+u][a+f]=-2==u||2==u||-2==f||2==f||0==u&&0==f}},v=function(t){for(var r=B.getBCHTypeNumber(e),n=0;n<18;n+=1){var a=!t&&1==(r>>n&1);o[Math.floor(n/3)][n%3+i-8-3]=a}for(n=0;n<18;n+=1){a=!t&&1==(r>>n&1);o[n%3+i-8-3][Math.floor(n/3)]=a}},d=function(t,r){for(var e=n<<3|r,a=B.getBCHTypeInfo(e),u=0;u<15;u+=1){var f=!t&&1==(a>>u&1);u<6?o[u][8]=f:u<8?o[u+1][8]=f:o[i-15+u][8]=f}for(u=0;u<15;u+=1){f=!t&&1==(a>>u&1);u<8?o[8][i-u-1]=f:u<9?o[8][15-u-1+1]=f:o[8][15-u-1]=f}o[i-8][8]=!t},w=function(t,r){for(var e=-1,n=i-1,a=7,u=0,f=B.getMaskFunction(r),c=i-1;c>0;c-=2)for(6==c&&(c-=1);;){for(var g=0;g<2;g+=1)if(null==o[n][c-g]){var l=!1;u<t.length&&(l=1==(t[u]>>>a&1)),f(n,c-g)&&(l=!l),o[n][c-g]=l,-1==(a-=1)&&(u+=1,a=7)}if((n+=e)<0||i<=n){n-=e,e=-e;break}}},p=function(t,r,e){for(var n=A.getRSBlocks(t,r),o=b(),i=0;i<e.length;i+=1){var a=e[i];o.put(a.getMode(),4),o.put(a.getLength(),B.getLengthInBits(a.getMode(),t)),a.write(o)}var u=0;for(i=0;i<n.length;i+=1)u+=n[i].dataCount;if(o.getLengthInBits()>8*u)throw"code length overflow. ("+o.getLengthInBits()+">"+8*u+")";for(o.getLengthInBits()+4<=8*u&&o.put(0,4);o.getLengthInBits()%8!=0;)o.putBit(!1);for(;!(o.getLengthInBits()>=8*u||(o.put(236,8),o.getLengthInBits()>=8*u));)o.put(17,8);return function(t,r){for(var e=0,n=0,o=0,i=new Array(r.length),a=new Array(r.length),u=0;u<r.length;u+=1){var f=r[u].dataCount,c=r[u].totalCount-f;n=Math.max(n,f),o=Math.max(o,c),i[u]=new Array(f);for(var g=0;g<i[u].length;g+=1)i[u][g]=255&t.getBuffer()[g+e];e+=f;var l=B.getErrorCorrectPolynomial(c),h=k(i[u],l.getLength()-1).mod(l);for(a[u]=new Array(l.getLength()-1),g=0;g<a[u].length;g+=1){var s=g+h.getLength()-a[u].length;a[u][g]=s>=0?h.getAt(s):0}}var v=0;for(g=0;g<r.length;g+=1)v+=r[g].totalCount;var d=new Array(v),w=0;for(g=0;g<n;g+=1)for(u=0;u<r.length;u+=1)g<i[u].length&&(d[w]=i[u][g],w+=1);for(g=0;g<o;g+=1)for(u=0;u<r.length;u+=1)g<a[u].length&&(d[w]=a[u][g],w+=1);return d}(o,n)};f.addData=function(t,r){var e=null;switch(r=r||"Byte"){case"Numeric":e=M(t);break;case"Alphanumeric":e=x(t);break;case"Byte":e=m(t);break;case"Kanji":e=L(t);break;default:throw"mode:"+r}u.push(e),a=null},f.isDark=function(t,r){if(t<0||i<=t||r<0||i<=r)throw t+","+r;return o[t][r]},f.getModuleCount=function(){return i},f.make=function(){if(e<1){for(var t=1;t<40;t++){for(var r=A.getRSBlocks(t,n),o=b(),i=0;i<u.length;i++){var a=u[i];o.put(a.getMode(),4),o.put(a.getLength(),B.getLengthInBits(a.getMode(),t)),a.write(o)}var g=0;for(i=0;i<r.length;i++)g+=r[i].dataCount;if(o.getLengthInBits()<=8*g)break}e=t}c(!1,function(){for(var t=0,r=0,e=0;e<8;e+=1){c(!0,e);var n=B.getLostPoint(f);(0==e||t>n)&&(t=n,r=e)}return r}())},f.createTableTag=function(t,r){t=t||2;var e="";e+='<table style="',e+=" border-width: 0px; border-style: none;",e+=" border-collapse: collapse;",e+=" padding: 0px; margin: "+(r=void 0===r?4*t:r)+"px;",e+='">',e+="<tbody>";for(var n=0;n<f.getModuleCount();n+=1){e+="<tr>";for(var o=0;o<f.getModuleCount();o+=1)e+='<td style="',e+=" border-width: 0px; border-style: none;",e+=" border-collapse: collapse;",e+=" padding: 0px; margin: 0px;",e+=" width: "+t+"px;",e+=" height: "+t+"px;",e+=" background-color: ",e+=f.isDark(n,o)?"#000000":"#ffffff",e+=";",e+='"/>';e+="</tr>"}return e+="</tbody>",e+="</table>"},f.createSvgTag=function(t,r,e,n){var o={};"object"==typeof arguments[0]&&(t=(o=arguments[0]).cellSize,r=o.margin,e=o.alt,n=o.title),t=t||2,r=void 0===r?4*t:r,(e="string"==typeof e?{text:e}:e||{}).text=e.text||null,e.id=e.text?e.id||"qrcode-description":null,(n="string"==typeof n?{text:n}:n||{}).text=n.text||null,n.id=n.text?n.id||"qrcode-title":null;var i,a,u,c,g=f.getModuleCount()*t+2*r,l="";for(c="l"+t+",0 0,"+t+" -"+t+",0 0,-"+t+"z ",l+='<svg version="1.1" xmlns="http://www.w3.org/2000/svg"',l+=o.scalable?"":' width="'+g+'px" height="'+g+'px"',l+=' viewBox="0 0 '+g+" "+g+'" ',l+=' preserveAspectRatio="xMinYMin meet"',l+=n.text||e.text?' role="img" aria-labelledby="'+y([n.id,e.id].join(" ").trim())+'"':"",l+=">",l+=n.text?'<title id="'+y(n.id)+'">'+y(n.text)+"</title>":"",l+=e.text?'<description id="'+y(e.id)+'">'+y(e.text)+"</description>":"",l+='<rect width="100%" height="100%" fill="white" cx="0" cy="0"/>',l+='<path d="',a=0;a<f.getModuleCount();a+=1)for(u=a*t+r,i=0;i<f.getModuleCount();i+=1)f.isDark(a,i)&&(l+="M"+(i*t+r)+","+u+c);return l+='" stroke="transparent" fill="black"/>',l+="</svg>"},f.createDataURL=function(t,r){t=t||2,r=void 0===r?4*t:r;var e=f.getModuleCount()*t+2*r,n=r,o=e-r;return I(e,e,function(r,e){if(n<=r&&r<o&&n<=e&&e<o){var i=Math.floor((r-n)/t),a=Math.floor((e-n)/t);return f.isDark(a,i)?0:1}return 1})},f.createImgTag=function(t,r,e){t=t||2,r=void 0===r?4*t:r;var n=f.getModuleCount()*t+2*r,o="";return o+="<img",o+=' src="',o+=f.createDataURL(t,r),o+='"',o+=' width="',o+=n,o+='"',o+=' height="',o+=n,o+='"',e&&(o+=' alt="',o+=y(e),o+='"'),o+="/>"};var y=function(t){for(var r="",e=0;e<t.length;e+=1){var n=t.charAt(e);switch(n){case"<":r+="&lt;";break;case">":r+="&gt;";break;case"&":r+="&amp;";break;case'"':r+="&quot;";break;default:r+=n}}return r};return f.createASCII=function(t,r){if((t=t||1)<2)return function(t){t=void 0===t?2:t;var r,e,n,o,i,a=1*f.getModuleCount()+2*t,u=t,c=a-t,g={"██":"█","█ ":"▀"," █":"▄","  ":" "},l={"██":"▀","█ ":"▀"," █":" ","  ":" "},h="";for(r=0;r<a;r+=2){for(n=Math.floor((r-u)/1),o=Math.floor((r+1-u)/1),e=0;e<a;e+=1)i="█",u<=e&&e<c&&u<=r&&r<c&&f.isDark(n,Math.floor((e-u)/1))&&(i=" "),u<=e&&e<c&&u<=r+1&&r+1<c&&f.isDark(o,Math.floor((e-u)/1))?i+=" ":i+="█",h+=t<1&&r+1>=c?l[i]:g[i];h+="\n"}return a%2&&t>0?h.substring(0,h.length-a-1)+Array(a+1).join("▀"):h.substring(0,h.length-1)}(r);t-=1,r=void 0===r?2*t:r;var e,n,o,i,a=f.getModuleCount()*t+2*r,u=r,c=a-r,g=Array(t+1).join("██"),l=Array(t+1).join("  "),h="",s="";for(e=0;e<a;e+=1){for(o=Math.floor((e-u)/t),s="",n=0;n<a;n+=1)i=1,u<=n&&n<c&&u<=e&&e<c&&f.isDark(o,Math.floor((n-u)/t))&&(i=0),s+=i?g:l;for(o=0;o<t;o+=1)h+=s+"\n"}return h.substring(0,h.length-1)},f.renderTo2dContext=function(t,r){r=r||2;for(var e=f.getModuleCount(),n=0;n<e;n++)for(var o=0;o<e;o++)t.fillStyle=f.isDark(n,o)?"black":"white",t.fillRect(o*r,n*r,r,r)},f};t.stringToBytes=(t.stringToBytesFuncs={default:function(t){for(var r=[],e=0;e<t.length;e+=1){var n=t.charCodeAt(e);r.push(255&n)}return r}}).default,t.createStringToBytes=function(t,r){var e=function(){for(var e=S(t),n=function(){var t=e.read();if(-1==t)throw"eof";return t},o=0,i={};;){var a=e.read();if(-1==a)break;var u=n(),f=n()<<8|n();i[String.fromCharCode(a<<8|u)]=f,o+=1}if(o!=r)throw o+" != "+r;return i}(),n="?".charCodeAt(0);return function(t){for(var r=[],o=0;o<t.length;o+=1){var i=t.charCodeAt(o);if(i<128)r.push(i);else{var a=e[t.charAt(o)];"number"==typeof a?(255&a)==a?r.push(a):(r.push(a>>>8),r.push(255&a)):r.push(n)}}return r}};var r,e,n,o,i,a=1,u=2,f=4,c=8,g={L:1,M:0,Q:3,H:2},l=0,h=1,s=2,v=3,d=4,w=5,p=6,y=7,B=(r=[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,50],[6,30,54],[6,32,58],[6,34,62],[6,26,46,66],[6,26,48,70],[6,26,50,74],[6,30,54,78],[6,30,56,82],[6,30,58,86],[6,34,62,90],[6,28,50,72,94],[6,26,50,74,98],[6,30,54,78,102],[6,28,54,80,106],[6,32,58,84,110],[6,30,58,86,114],[6,34,62,90,118],[6,26,50,74,98,122],[6,30,54,78,102,126],[6,26,52,78,104,130],[6,30,56,82,108,134],[6,34,60,86,112,138],[6,30,58,86,114,142],[6,34,62,90,118,146],[6,30,54,78,102,126,150],[6,24,50,76,102,128,154],[6,28,54,80,106,132,158],[6,32,58,84,110,136,162],[6,26,54,82,110,138,166],[6,30,58,86,114,142,170]],e=1335,n=7973,i=function(t){for(var r=0;0!=t;)r+=1,t>>>=1;return r},(o={}).getBCHTypeInfo=function(t){for(var r=t<<10;i(r)-i(e)>=0;)r^=e<<i(r)-i(e);return 21522^(t<<10|r)},o.getBCHTypeNumber=function(t){for(var r=t<<12;i(r)-i(n)>=0;)r^=n<<i(r)-i(n);return t<<12|r},o.getPatternPosition=function(t){return r[t-1]},o.getMaskFunction=function(t){switch(t){case l:return function(t,r){return(t+r)%2==0};case h:return function(t,r){return t%2==0};case s:return function(t,r){return r%3==0};case v:return function(t,r){return(t+r)%3==0};case d:return function(t,r){return(Math.floor(t/2)+Math.floor(r/3))%2==0};case w:return function(t,r){return t*r%2+t*r%3==0};case p:return function(t,r){return(t*r%2+t*r%3)%2==0};case y:return function(t,r){return(t*r%3+(t+r)%2)%2==0};default:throw"bad maskPattern:"+t}},o.getErrorCorrectPolynomial=function(t){for(var r=k([1],0),e=0;e<t;e+=1)r=r.multiply(k([1,C.gexp(e)],0));return r},o.getLengthInBits=function(t,r){if(1<=r&&r<10)switch(t){case a:return 10;case u:return 9;case f:case c:return 8;default:throw"mode:"+t}else if(r<27)switch(t){case a:return 12;case u:return 11;case f:return 16;case c:return 10;default:throw"mode:"+t}else{if(!(r<41))throw"type:"+r;switch(t){case a:return 14;case u:return 13;case f:return 16;case c:return 12;default:throw"mode:"+t}}},o.getLostPoint=function(t){for(var r=t.getModuleCount(),e=0,n=0;n<r;n+=1)for(var o=0;o<r;o+=1){for(var i=0,a=t.isDark(n,o),u=-1;u<=1;u+=1)if(!(n+u<0||r<=n+u))for(var f=-1;f<=1;f+=1)o+f<0||r<=o+f||0==u&&0==f||a==t.isDark(n+u,o+f)&&(i+=1);i>5&&(e+=3+i-5)}for(n=0;n<r-1;n+=1)for(o=0;o<r-1;o+=1){var c=0;t.isDark(n,o)&&(c+=1),t.isDark(n+1,o)&&(c+=1),t.isDark(n,o+1)&&(c+=1),t.isDark(n+1,o+1)&&(c+=1),0!=c&&4!=c||(e+=3)}for(n=0;n<r;n+=1)for(o=0;o<r-6;o+=1)t.isDark(n,o)&&!t.isDark(n,o+1)&&t.isDark(n,o+2)&&t.isDark(n,o+3)&&t.isDark(n,o+4)&&!t.isDark(n,o+5)&&t.isDark(n,o+6)&&(e+=40);for(o=0;o<r;o+=1)for(n=0;n<r-6;n+=1)t.isDark(n,o)&&!t.isDark(n+1,o)&&t.isDark(n+2,o)&&t.isDark(n+3,o)&&t.isDark(n+4,o)&&!t.isDark(n+5,o)&&t.isDark(n+6,o)&&(e+=40);var g=0;for(o=0;o<r;o+=1)for(n=0;n<r;n+=1)t.isDark(n,o)&&(g+=1);return e+=Math.abs(100*g/r/r-50)/5*10},o),C=function(){for(var t=new Array(256),r=new Array(256),e=0;e<8;e+=1)t[e]=1<<e;for(e=8;e<256;e+=1)t[e]=t[e-4]^t[e-5]^t[e-6]^t[e-8];for(e=0;e<255;e+=1)r[t[e]]=e;var n={glog:function(t){if(t<1)throw"glog("+t+")";return r[t]},gexp:function(r){for(;r<0;)r+=255;for(;r>=256;)r-=255;return t[r]}};return n}();function k(t,r){if(void 0===t.length)throw t.length+"/"+r;var e=function(){for(var e=0;e<t.length&&0==t[e];)e+=1;for(var n=new Array(t.length-e+r),o=0;o<t.length-e;o+=1)n[o]=t[o+e];return n}(),n={getAt:function(t){return e[t]},getLength:function(){return e.length},multiply:function(t){for(var r=new Array(n.getLength()+t.getLength()-1),e=0;e<n.getLength();e+=1)for(var o=0;o<t.getLength();o+=1)r[e+o]^=C.gexp(C.glog(n.getAt(e))+C.glog(t.getAt(o)));return k(r,0)},mod:function(t){if(n.getLength()-t.getLength()<0)return n;for(var r=C.glog(n.getAt(0))-C.glog(t.getAt(0)),e=new Array(n.getLength()),o=0;o<n.getLength();o+=1)e[o]=n.getAt(o);for(o=0;o<t.getLength();o+=1)e[o]^=C.gexp(C.glog(t.getAt(o))+r);return k(e,0).mod(t)}};return n}var A=function(){var t=[[1,26,19],[1,26,16],[1,26,13],[1,26,9],[1,44,34],[1,44,28],[1,44,22],[1,44,16],[1,70,55],[1,70,44],[2,35,17],[2,35,13],[1,100,80],[2,50,32],[2,50,24],[4,25,9],[1,134,108],[2,67,43],[2,33,15,2,34,16],[2,33,11,2,34,12],[2,86,68],[4,43,27],[4,43,19],[4,43,15],[2,98,78],[4,49,31],[2,32,14,4,33,15],[4,39,13,1,40,14],[2,121,97],[2,60,38,2,61,39],[4,40,18,2,41,19],[4,40,14,2,41,15],[2,146,116],[3,58,36,2,59,37],[4,36,16,4,37,17],[4,36,12,4,37,13],[2,86,68,2,87,69],[4,69,43,1,70,44],[6,43,19,2,44,20],[6,43,15,2,44,16],[4,101,81],[1,80,50,4,81,51],[4,50,22,4,51,23],[3,36,12,8,37,13],[2,116,92,2,117,93],[6,58,36,2,59,37],[4,46,20,6,47,21],[7,42,14,4,43,15],[4,133,107],[8,59,37,1,60,38],[8,44,20,4,45,21],[12,33,11,4,34,12],[3,145,115,1,146,116],[4,64,40,5,65,41],[11,36,16,5,37,17],[11,36,12,5,37,13],[5,109,87,1,110,88],[5,65,41,5,66,42],[5,54,24,7,55,25],[11,36,12,7,37,13],[5,122,98,1,123,99],[7,73,45,3,74,46],[15,43,19,2,44,20],[3,45,15,13,46,16],[1,135,107,5,136,108],[10,74,46,1,75,47],[1,50,22,15,51,23],[2,42,14,17,43,15],[5,150,120,1,151,121],[9,69,43,4,70,44],[17,50,22,1,51,23],[2,42,14,19,43,15],[3,141,113,4,142,114],[3,70,44,11,71,45],[17,47,21,4,48,22],[9,39,13,16,40,14],[3,135,107,5,136,108],[3,67,41,13,68,42],[15,54,24,5,55,25],[15,43,15,10,44,16],[4,144,116,4,145,117],[17,68,42],[17,50,22,6,51,23],[19,46,16,6,47,17],[2,139,111,7,140,112],[17,74,46],[7,54,24,16,55,25],[34,37,13],[4,151,121,5,152,122],[4,75,47,14,76,48],[11,54,24,14,55,25],[16,45,15,14,46,16],[6,147,117,4,148,118],[6,73,45,14,74,46],[11,54,24,16,55,25],[30,46,16,2,47,17],[8,132,106,4,133,107],[8,75,47,13,76,48],[7,54,24,22,55,25],[22,45,15,13,46,16],[10,142,114,2,143,115],[19,74,46,4,75,47],[28,50,22,6,51,23],[33,46,16,4,47,17],[8,152,122,4,153,123],[22,73,45,3,74,46],[8,53,23,26,54,24],[12,45,15,28,46,16],[3,147,117,10,148,118],[3,73,45,23,74,46],[4,54,24,31,55,25],[11,45,15,31,46,16],[7,146,116,7,147,117],[21,73,45,7,74,46],[1,53,23,37,54,24],[19,45,15,26,46,16],[5,145,115,10,146,116],[19,75,47,10,76,48],[15,54,24,25,55,25],[23,45,15,25,46,16],[13,145,115,3,146,116],[2,74,46,29,75,47],[42,54,24,1,55,25],[23,45,15,28,46,16],[17,145,115],[10,74,46,23,75,47],[10,54,24,35,55,25],[19,45,15,35,46,16],[17,145,115,1,146,116],[14,74,46,21,75,47],[29,54,24,19,55,25],[11,45,15,46,46,16],[13,145,115,6,146,116],[14,74,46,23,75,47],[44,54,24,7,55,25],[59,46,16,1,47,17],[12,151,121,7,152,122],[12,75,47,26,76,48],[39,54,24,14,55,25],[22,45,15,41,46,16],[6,151,121,14,152,122],[6,75,47,34,76,48],[46,54,24,10,55,25],[2,45,15,64,46,16],[17,152,122,4,153,123],[29,74,46,14,75,47],[49,54,24,10,55,25],[24,45,15,46,46,16],[4,152,122,18,153,123],[13,74,46,32,75,47],[48,54,24,14,55,25],[42,45,15,32,46,16],[20,147,117,4,148,118],[40,75,47,7,76,48],[43,54,24,22,55,25],[10,45,15,67,46,16],[19,148,118,6,149,119],[18,75,47,31,76,48],[34,54,24,34,55,25],[20,45,15,61,46,16]],r=function(t,r){var e={};return e.totalCount=t,e.dataCount=r,e},e={};return e.getRSBlocks=function(e,n){var o=function(r,e){switch(e){case g.L:return t[4*(r-1)+0];case g.M:return t[4*(r-1)+1];case g.Q:return t[4*(r-1)+2];case g.H:return t[4*(r-1)+3];default:return}}(e,n);if(void 0===o)throw"bad rs block @ typeNumber:"+e+"/errorCorrectionLevel:"+n;for(var i=o.length/3,a=[],u=0;u<i;u+=1)for(var f=o[3*u+0],c=o[3*u+1],l=o[3*u+2],h=0;h<f;h+=1)a.push(r(c,l));return a},e}(),b=function(){var t=[],r=0,e={getBuffer:function(){return t},getAt:function(r){var e=Math.floor(r/8);return 1==(t[e]>>>7-r%8&1)},put:function(t,r){for(var n=0;n<r;n+=1)e.putBit(1==(t>>>r-n-1&1))},getLengthInBits:function(){return r},putBit:function(e){var n=Math.floor(r/8);t.length<=n&&t.push(0),e&&(t[n]|=128>>>r%8),r+=1}};return e},M=function(t){var r=a,e=t,n={getMode:function(){return r},getLength:function(t){return e.length},write:function(t){for(var r=e,n=0;n+2<r.length;)t.put(o(r.substring(n,n+3)),10),n+=3;n<r.length&&(r.length-n==1?t.put(o(r.substring(n,n+1)),4):r.length-n==2&&t.put(o(r.substring(n,n+2)),7))}},o=function(t){for(var r=0,e=0;e<t.length;e+=1)r=10*r+i(t.charAt(e));return r},i=function(t){if("0"<=t&&t<="9")return t.charCodeAt(0)-"0".charCodeAt(0);throw"illegal char :"+t};return n},x=function(t){var r=u,e=t,n={getMode:function(){return r},getLength:function(t){return e.length},write:function(t){for(var r=e,n=0;n+1<r.length;)t.put(45*o(r.charAt(n))+o(r.charAt(n+1)),11),n+=2;n<r.length&&t.put(o(r.charAt(n)),6)}},o=function(t){if("0"<=t&&t<="9")return t.charCodeAt(0)-"0".charCodeAt(0);if("A"<=t&&t<="Z")return t.charCodeAt(0)-"A".charCodeAt(0)+10;switch(t){case" ":return 36;case"$":return 37;case"%":return 38;case"*":return 39;case"+":return 40;case"-":return 41;case".":return 42;case"/":return 43;case":":return 44;default:throw"illegal char :"+t}};return n},m=function(r){var e=f,n=t.stringToBytes(r),o={getMode:function(){return e},getLength:function(t){return n.length},write:function(t){for(var r=0;r<n.length;r+=1)t.put(n[r],8)}};return o},L=function(r){var e=c,n=t.stringToBytesFuncs.SJIS;if(!n)throw"sjis not supported.";!function(){var t=n("友");if(2!=t.length||38726!=(t[0]<<8|t[1]))throw"sjis not supported."}();var o=n(r),i={getMode:function(){return e},getLength:function(t){return~~(o.length/2)},write:function(t){for(var r=o,e=0;e+1<r.length;){var n=(255&r[e])<<8|255&r[e+1];if(33088<=n&&n<=40956)n-=33088;else{if(!(57408<=n&&n<=60351))throw"illegal char at "+(e+1)+"/"+n;n-=49472}n=192*(n>>>8&255)+(255&n),t.put(n,13),e+=2}if(e<r.length)throw"illegal char at "+(e+1)}};return i},D=function(){var t=[],r={writeByte:function(r){t.push(255&r)},writeShort:function(t){r.writeByte(t),r.writeByte(t>>>8)},writeBytes:function(t,e,n){e=e||0,n=n||t.length;for(var o=0;o<n;o+=1)r.writeByte(t[o+e])},writeString:function(t){for(var e=0;e<t.length;e+=1)r.writeByte(t.charCodeAt(e))},toByteArray:function(){return t},toString:function(){var r="";r+="[";for(var e=0;e<t.length;e+=1)e>0&&(r+=","),r+=t[e];return r+="]"}};return r},S=function(t){var r=t,e=0,n=0,o=0,i={read:function(){for(;o<8;){if(e>=r.length){if(0==o)return-1;throw"unexpected end of file./"+o}var t=r.charAt(e);if(e+=1,"="==t)return o=0,-1;t.match(/^\s$/)||(n=n<<6|a(t.charCodeAt(0)),o+=6)}var i=n>>>o-8&255;return o-=8,i}},a=function(t){if(65<=t&&t<=90)return t-65;if(97<=t&&t<=122)return t-97+26;if(48<=t&&t<=57)return t-48+52;if(43==t)return 62;if(47==t)return 63;throw"c:"+t};return i},I=function(t,r,e){for(var n=function(t,r){var e=t,n=r,o=new Array(t*r),i={setPixel:function(t,r,n){o[r*e+t]=n},write:function(t){t.writeString("GIF87a"),t.writeShort(e),t.writeShort(n),t.writeByte(128),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(255),t.writeByte(255),t.writeByte(255),t.writeString(","),t.writeShort(0),t.writeShort(0),t.writeShort(e),t.writeShort(n),t.writeByte(0);var r=a(2);t.writeByte(2);for(var o=0;r.length-o>255;)t.writeByte(255),t.writeBytes(r,o,255),o+=255;t.writeByte(r.length-o),t.writeBytes(r,o,r.length-o),t.writeByte(0),t.writeString(";")}},a=function(t){for(var r=1<<t,e=1+(1<<t),n=t+1,i=u(),a=0;a<r;a+=1)i.add(String.fromCharCode(a));i.add(String.fromCharCode(r)),i.add(String.fromCharCode(e));var f,c,g,l=D(),h=(f=l,c=0,g=0,{write:function(t,r){if(t>>>r!=0)throw"length over";for(;c+r>=8;)f.writeByte(255&(t<<c|g)),r-=8-c,t>>>=8-c,g=0,c=0;g|=t<<c,c+=r},flush:function(){c>0&&f.writeByte(g)}});h.write(r,n);var s=0,v=String.fromCharCode(o[s]);for(s+=1;s<o.length;){var d=String.fromCharCode(o[s]);s+=1,i.contains(v+d)?v+=d:(h.write(i.indexOf(v),n),i.size()<4095&&(i.size()==1<<n&&(n+=1),i.add(v+d)),v=d)}return h.write(i.indexOf(v),n),h.write(e,n),h.flush(),l.toByteArray()},u=function(){var t={},r=0,e={add:function(n){if(e.contains(n))throw"dup key:"+n;t[n]=r,r+=1},size:function(){return r},indexOf:function(r){return t[r]},contains:function(r){return void 0!==t[r]}};return e};return i}(t,r),o=0;o<r;o+=1)for(var i=0;i<t;i+=1)n.setPixel(i,o,e(i,o));var a=D();n.write(a);for(var u=function(){var t=0,r=0,e=0,n="",o={},i=function(t){n+=String.fromCharCode(a(63&t))},a=function(t){if(t<0);else{if(t<26)return 65+t;if(t<52)return t-26+97;if(t<62)return t-52+48;if(62==t)return 43;if(63==t)return 47}throw"n:"+t};return o.writeByte=function(n){for(t=t<<8|255&n,r+=8,e+=1;r>=6;)i(t>>>r-6),r-=6},o.flush=function(){if(r>0&&(i(t<<6-r),t=0,r=0),e%3!=0)for(var o=3-e%3,a=0;a<o;a+=1)n+="="},o.toString=function(){return n},o}(),f=a.toByteArray(),c=0;c<f.length;c+=1)u.writeByte(f[c]);return u.flush(),"data:image/gif;base64,"+u};return t}();qrcode.stringToBytesFuncs["UTF-8"]=function(t){return function(t){for(var r=[],e=0;e<t.length;e++){var n=t.charCodeAt(e);n<128?r.push(n):n<2048?r.push(192|n>>6,128|63&n):n<55296||n>=57344?r.push(224|n>>12,128|n>>6&63,128|63&n):(e++,n=65536+((1023&n)<<10|1023&t.charCodeAt(e)),r.push(240|n>>18,128|n>>12&63,128|n>>6&63,128|63&n))}return r}(t)},function(t){"function"==typeof define&&define.amd?define([],t):"object"==typeof exports&&(module.exports=t())}(function(){return qrcode});
PPQRJS;
    }

    /* "Supported Apps" strip (bKash, Rocket, EBL Sky, BRAC Astha, Islami Wallet, DBBL, City Touch, ... & more) - built in as an inline image */
    function pp_bqr_apps_img(){
        return 'data:image/png;base64,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';
    }

    function pp_render_bangla_qr_flow($data, $gateway_info){
        $L = $data['lang'] ?? [];
        $t = function($key) use ($L){
            return htmlspecialchars($L[$key] ?? '', ENT_QUOTES, 'UTF-8');
        };

        $primary = ppx_hex($data['gateway']['primary_color'] ?? '', '#006A4E');
        $accent  = ppx_accent('#2563eb');
        $btnCol  = '#2563eb';

        $amountNum  = (float)($data['transaction']['local_net_amount'] ?? 0);
        $amountPlain = number_format($amountNum, 2, '.', '');
        $amountTxt  = rtrim(rtrim($amountPlain, '0'), '.');
        $currency   = (string)($data['transaction']['local_currency'] ?? 'BDT');
        $amountFull = $amountTxt . ' ' . $currency;

        $payload = trim((string)($data['options']['qr_payload'] ?? ''));
        $static  = trim((string)($data['options']['qr_code'] ?? ''));
        if ($static === '--') { $static = ''; }

        $locked = ($payload !== '' && $payload !== '--') ? pp_bqr_build_locked($payload, $amountPlain) : null;

        $logo   = (string)($data['gateway']['logo'] ?? '');

        $mode = 'none';
        if ($locked !== null) { $mode = 'locked'; }
        elseif ($static !== '')                  { $mode = 'static'; }

        $cfg = [
            'ref'       => (string)($data['transaction']['ref'] ?? ''),
            'gatewayId' => (string)($data['gateway']['gateway_id'] ?? ''),
            'mode'      => $mode,
            'slip'      => !empty($gateway_info['allow_slip']),
            'payload'   => ($mode === 'locked') ? $locked : '',
            'logo'      => $logo,
            'file'      => 'bangla-qr-' . $amountTxt . '.png',
            'window'    => 900,
            'poll'      => 5000,
            't' => [
                'left'    => $L['ppx_left'] ?? '',
                'expired' => $L['ppx_expired'] ?? '',
                'paid'    => $L['ppx_paid'] ?? '',
                'waiting' => $L['ppx_waiting'] ?? '',
                'updating' => $L['ppx_updating'] ?? '',
                'shotType' => $L['ppx_bq_shot_type'] ?? '',
                'shotBig'  => $L['ppx_bq_shot_big'] ?? '',
                'choose'   => $L['ppx_bq_choose'] ?? '',
            ],
        ];
        $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);


        ob_start();
?>
<div class="bqr" id="bqr" style="--bqr-primary:<?php echo $primary; ?>;--bqr-blue:<?php echo $btnCol; ?>;--bqr-accent:<?php echo $accent; ?>;">
<style>
    .bqr{font-family:inherit;color:#0f172a;text-align:center;padding:6px 4px 2px}
    .bqr *{box-sizing:border-box}
    .bqr-title{font-size:18px;font-weight:700;margin:6px 0 14px;letter-spacing:-.01em}
    .bqr-steps{display:flex;align-items:center;justify-content:center;gap:9px;font-size:13px;color:#64748b;margin-bottom:16px;flex-wrap:wrap}
    .bqr-chev{width:20px;height:20px;border-radius:50%;background:var(--bqr-blue);color:#fff;display:inline-flex;align-items:center;justify-content:center}
    .bqr-chev svg{width:11px;height:11px}
    .bqr-qrbox{position:relative;width:min(300px,100%);margin:0 auto;border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:6px}
    .bqr-qrbox canvas,.bqr-qrbox img{display:block;width:100%;height:auto;border-radius:10px}
    .bqr-dl{position:absolute;right:-14px;bottom:-14px;width:36px;height:36px;border-radius:50%;background:var(--bqr-blue);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(37,99,235,.35);cursor:pointer;border:0;padding:0}
    .bqr-dl svg{width:18px;height:18px}
    .bqr-note{margin:14px auto 0;max-width:300px;font-size:12.5px;color:#64748b;line-height:1.5}
    .bqr-none{padding:26px 10px;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;font-size:14px}
    .bqr-wait{display:flex;align-items:center;gap:10px;margin-top:20px;padding:12px 14px;border-radius:12px;background:#f3f6ff;border:1px solid #dbe4ff;color:var(--bqr-blue);font-weight:600;font-size:14px;text-align:left}
    .bqr-wait .bqr-time{margin-left:auto;color:#334155;font-weight:600;white-space:nowrap}
    .bqr-dot{width:10px;height:10px;border-radius:50%;background:var(--bqr-blue);animation:bqrPulse 1.6s infinite;flex:0 0 10px}
    .bqr-wait.bqr-ok{background:#ecfdf3;border-color:#bbf7d0;color:#15803d}
    .bqr-wait.bqr-ok .bqr-dot{background:#16a34a;animation:none}
    .bqr-wait.bqr-off{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
    .bqr-wait.bqr-off .bqr-dot{background:#ea580c;animation:none}
    @keyframes bqrPulse{0%{box-shadow:0 0 0 0 rgba(37,99,235,.4)}70%{box-shadow:0 0 0 9px rgba(37,99,235,0)}100%{box-shadow:0 0 0 0 rgba(37,99,235,0)}}
    .bqr-help{color:#64748b;font-size:12.5px;line-height:1.7;margin:12px 0 0}
    .bqr-help a{color:var(--bqr-blue);text-decoration:underline;cursor:pointer}
    .bqr-proof{display:none;margin-top:14px;text-align:left}
    .bqr-proof input{width:100%;height:46px;border-radius:12px;border:1px solid #cbd5e1;padding:0 14px;font-size:15px;outline:none}
    .bqr-proof input:focus{border-color:var(--bqr-blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
    .bqr-proof button{width:100%;height:46px;margin-top:10px;border:0;border-radius:12px;background:var(--bqr-blue);color:#fff;font-weight:700;font-size:15px;cursor:pointer}
    .bqr-proof button:disabled{opacity:.6;cursor:wait}
    .bqr-proof small{display:block;margin-top:6px;color:#64748b;font-size:12px}
    .bqr-seg{display:flex;background:#f1f5f9;border-radius:12px;padding:4px;gap:4px;margin-bottom:12px}
    .bqr-seg button{flex:1;height:38px;border:0;border-radius:9px;background:transparent;color:#475569;font-weight:600;font-size:14px;cursor:pointer;padding:0;margin:0;width:auto}
    .bqr-seg button.active{background:#fff;color:var(--bqr-blue);box-shadow:0 1px 3px rgba(15,23,42,.12)}
    .bqr-hint{font-size:12.5px;color:#64748b;line-height:1.55;margin:0 0 10px}
    .bqr-drop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;min-height:92px;border:2px dashed #93b4f5;border-radius:12px;background:#f6f9ff;color:#2563eb;font-weight:600;font-size:14px;cursor:pointer;padding:12px;text-align:center;word-break:break-all}
    .bqr-drop img{max-height:150px;max-width:100%;border-radius:8px;border:1px solid #e2e8f0}
    .bqr-apps{margin-top:22px;font-size:12.5px;color:#475569}
    .bqr-strip{margin-top:10px;display:flex;justify-content:center}
    .bqr-strip img{display:block;width:340px;max-width:100%;height:auto}
</style>

    <div class="bqr-title"><?php echo $t('ppx_bq_title'); ?></div>

    <div class="bqr-steps">
        <span><?php echo $t('ppx_bq_open'); ?></span>
        <span class="bqr-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
        <span><?php echo $t('ppx_bq_scan'); ?></span>
        <span class="bqr-chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
        <span><?php echo $t('ppx_bq_confirm'); ?></span>
    </div>

    <?php if ($mode === 'none'): ?>
        <div class="bqr-none"><?php echo $t('ppx_bq_noqr'); ?></div>
    <?php else: ?>
        <div class="bqr-qrbox">
            <?php if ($mode === 'locked'): ?>
                <canvas id="bqr-canvas" width="600" height="600"></canvas>
            <?php else: ?>
                <img id="bqr-static" src="<?php echo htmlspecialchars($static, ENT_QUOTES, 'UTF-8'); ?>" alt="QR">
            <?php endif; ?>
            <button type="button" class="bqr-dl" id="bqr-dl" title="<?php echo $t('ppx_download_qr'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>
            </button>
        </div>
        <?php if ($mode === 'static'): ?>
            <div class="bqr-note"><?php echo str_replace('{amount}', '<b>' . htmlspecialchars($amountFull, ENT_QUOTES, 'UTF-8') . '</b>', $t('ppx_bq_static_note')); ?></div>
        <?php endif; ?>

        <div class="bqr-wait" id="bqr-wait">
            <span class="bqr-dot"></span>
            <span id="bqr-wait-text"><?php echo $t('ppx_waiting'); ?></span>
            <span class="bqr-time" id="bqr-time"></span>
        </div>

        <p class="bqr-help">
            <?php echo $t('ppx_wait_note'); ?><br>
            <?php echo $t('ppx_taking_long'); ?> <a id="bqr-showproof"><?php echo $t('ppx_bq_proof'); ?></a>
        </p>

        <div class="bqr-proof" id="bqr-proof">
            <?php if (!empty($gateway_info['allow_slip'])): ?>
            <div class="bqr-seg">
                <button type="button" class="active" data-pane="shot"><?php echo $t('ppx_bq_tab_shot'); ?></button>
                <button type="button" data-pane="trx"><?php echo $t('ppx_bq_tab_trx'); ?></button>
            </div>
            <div id="bqr-pane-shot">
                <p class="bqr-hint"><?php echo $t('ppx_bq_shot_hint'); ?></p>
                <label class="bqr-drop" for="bqr-file">
                    <span id="bqr-drop-text"><?php echo $t('ppx_bq_choose'); ?></span>
                    <img id="bqr-thumb" alt="" hidden>
                </label>
                <input type="file" id="bqr-file" accept="image/*" hidden>
                <button type="button" id="bqr-shot-btn" disabled><?php echo $t('ppx_bq_submit_shot'); ?></button>
            </div>
            <?php endif; ?>
            <div id="bqr-pane-trx"<?php echo !empty($gateway_info['allow_slip']) ? ' style="display:none"' : ''; ?>>
                <form id="bqr-proof-form" autocomplete="off">
                    <input type="hidden" name="action-v2" value="transaction-verify">
                    <input type="hidden" name="gateway-id" value="<?php echo htmlspecialchars((string)($data['gateway']['gateway_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="transaction-id" value="<?php echo htmlspecialchars((string)($data['transaction']['ref'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" name="trxid" required placeholder="<?php echo $t('enter_transaction_id'); ?>">
                    <small><?php echo $t('ppx_bq_trx_hint'); ?></small>
                    <button type="submit" id="bqr-proof-btn"><?php echo $t('verify'); ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="bqr-apps">
        <?php echo $t('ppx_bq_supported'); ?>
        <div class="bqr-strip"><img src="<?php echo pp_bqr_apps_img(); ?>" alt="bKash, Rocket, EBL Skybanking, BRAC Bank Astha, Islami Wallet, DBBL, City Touch and more" width="340" height="29"></div>
    </div>

<?php if ($mode === 'locked'): ?>
<script data-cfasync="false">
(function(window){
var define, exports, module;
<?php echo pp_bqr_qrlib(); ?>

window.ppQRCode = qrcode;
})(window);
</script>
<?php endif; ?>
<script data-cfasync="false">
(function(){
    var C = <?php echo $cfgJson; ?>;
    var $ = function(id){ return document.getElementById(id); };
    if (C.mode === 'none') { return; }

    var key = 'bqr:' + C.ref, start = 0, tick = null, poller = null, busy = false, done = false;
    var canvas = $('bqr-canvas');

    /* ---- draw the locked-amount QR in the browser (no third-party service) ---- */
    var model = null;
    function paintModules(ctx, S){
        var n = model.getModuleCount(), quiet = 2;
        var cell = Math.floor(S / (n + quiet * 2)), off = Math.floor((S - cell * n) / 2);
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, S, S);
        ctx.fillStyle = '#1e293b';
        for (var r = 0; r < n; r++) {
            for (var c = 0; c < n; c++) {
                if (model.isDark(r, c)) { ctx.fillRect(off + c * cell, off + r * cell, cell, cell); }
            }
        }
        return n * cell;
    }
    function drawQR(){
        if (C.mode !== 'locked' || !canvas) { return; }
        var lib = window.ppQRCode || null;
        if (!lib) { fallbackNoLib(); return; }
        if (lib.stringToBytesFuncs && lib.stringToBytesFuncs['UTF-8']) { lib.stringToBytes = lib.stringToBytesFuncs['UTF-8']; }  /* QR text may contain non-English letters */
        try { model = lib(0, 'Q'); model.addData(C.payload, 'Byte'); model.make(); }
        catch (e) { fallbackNoLib(); return; }
        var S = canvas.width, ctx = canvas.getContext('2d');
        var size = paintModules(ctx, S);
        if (C.logo) {
            var img = new Image();
            img.onload = function(){
                var box = Math.floor(size * 0.21), x = Math.floor((S - box) / 2), y = x;
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(x - 6, y - 6, box + 12, box + 12);
                var ar = img.width / img.height, w = box, h = box;
                if (ar > 1) { h = box / ar; } else { w = box * ar; }
                ctx.drawImage(img, x + (box - w) / 2, y + (box - h) / 2, w, h);
            };
            img.src = C.logo;
        }
    }
    function fallbackNoLib(){
        var b = canvas.parentNode;
        var p = document.createElement('div'); p.className = 'bqr-none'; p.textContent = 'QR could not be drawn. Please use the Transaction ID option below.';
        b.parentNode.insertBefore(p, b); b.style.display = 'none';
        $('bqr-proof').style.display = 'block';
    }
    function exportPNG(){
        try { return canvas.toDataURL('image/png'); }
        catch (e) {   /* logo from another domain blocks export: save the QR without the logo */
            var c = document.createElement('canvas'); c.width = c.height = canvas.width;
            paintModules(c.getContext('2d'), c.width);
            return c.toDataURL('image/png');
        }
    }

    $('bqr-dl').addEventListener('click', function(){
        var a = document.createElement('a');
        if (C.mode === 'locked' && canvas) {
            a.href = exportPNG(); a.download = C.file;
        } else {
            var im = $('bqr-static'); if (!im) { return; }
            a.href = im.src; a.download = 'bangla-qr.png';
        }
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    });

    /* ---- countdown + automatic payment check ---- */
    try { start = parseInt(sessionStorage.getItem(key) || '0', 10) || 0; } catch(e){}
    if (!start) { start = Date.now(); try { sessionStorage.setItem(key, String(start)); } catch(e){} }

    function fmt(s){ var m = Math.floor(s / 60), r = s % 60; return m + 'm ' + (r < 10 ? '0' : '') + r + 's'; }
    function setWait(cls, text){ $('bqr-wait').className = 'bqr-wait' + (cls ? ' ' + cls : ''); $('bqr-wait-text').textContent = text; }
    function stopAll(){ clearInterval(tick); clearInterval(poller); tick = poller = null; }
    function showProof(){ $('bqr-proof').style.display = 'block'; }
    function left(){ return C.window - Math.floor((Date.now() - start) / 1000); }

    function expire(){ stopAll(); setWait('bqr-off', C.t.expired); $('bqr-time').textContent = ''; showProof(); }

    function poll(){
        if (busy || done) { return; }
        busy = true;
        var fd = new FormData();
        fd.append('action-v2', 'transaction-verify');
        fd.append('gateway-id', C.gatewayId);
        fd.append('transaction-id', C.ref);
        fd.append('auto', '1');
        fetch('', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                busy = false;
                if (d.status === 'true') {
                    done = true; stopAll(); setWait('bqr-ok', C.t.paid); $('bqr-time').textContent = '';
                    if (typeof success === 'function') { success(d); } else { location.reload(); }
                } else if (d.status === 'state') {
                    done = true; stopAll();
                    setWait(d.state === 'completed' ? 'bqr-ok' : 'bqr-off', d.state === 'completed' ? C.t.paid : C.t.updating);
                    $('bqr-time').textContent = '';
                    setTimeout(function(){ location.reload(); }, 700);
                } else if (d.status === 'false') {
                    stopAll(); setWait('bqr-off', d.title || C.t.expired); $('bqr-time').textContent = '';
                    if (typeof failed === 'function') { failed(d.title, d.message); }
                    showProof();
                }
            })
            .catch(function(){ busy = false; });
    }

    function run(){
        var l = left();
        if (l <= 0) { expire(); return; }
        $('bqr-time').textContent = fmt(l) + ' ' + C.t.left;
        tick = setInterval(function(){
            var r = left();
            if (r <= 0) { expire(); return; }
            $('bqr-time').textContent = fmt(r) + ' ' + C.t.left;
        }, 1000);
        poller = setInterval(poll, C.poll);
        poll();
    }

    $('bqr-showproof').addEventListener('click', function(e){ e.preventDefault(); showProof(); });

    $('bqr-proof-form').addEventListener('submit', function(e){
        e.preventDefault();
        var btn = $('bqr-proof-btn'), old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        fetch('', { method: 'POST', body: new FormData(this), credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false; btn.innerHTML = old;
                if (d.status === 'true') { if (typeof success === 'function') { success(d); } else { location.reload(); } }
                else if (d.status === 'state') { location.reload(); }
                else if (typeof failed === 'function') { failed(d.title || 'Failed', d.message || ''); }
            })
            .catch(function(){
                btn.disabled = false; btn.innerHTML = old;
                if (typeof failed === 'function') { failed('Request Error', 'Something went wrong. Please try again.'); }
            });
    });

    /* ---- screenshot proof ---- */
    var fileInp = $('bqr-file');
    if (C.slip && fileInp) {
        var picked = null, pickedName = '', shotBtn = $('bqr-shot-btn');
        var segBtns = document.querySelectorAll('.bqr-seg button');
        for (var q = 0; q < segBtns.length; q++) {
            segBtns[q].addEventListener('click', function(){
                for (var z = 0; z < segBtns.length; z++) { segBtns[z].className = ''; }
                this.className = 'active';
                var shot = this.getAttribute('data-pane') === 'shot';
                $('bqr-pane-shot').style.display = shot ? '' : 'none';
                $('bqr-pane-trx').style.display = shot ? 'none' : '';
            });
        }
        var shrink = function(f, cb){   /* phone screenshots can be large: resize to max 1600px JPEG before upload */
            var img = new Image(), url = URL.createObjectURL(f);
            img.onload = function(){
                try {
                    var k = Math.min(1, 1600 / Math.max(img.width, img.height));
                    var c = document.createElement('canvas'); c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
                    var x = c.getContext('2d'); x.fillStyle = '#ffffff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0, c.width, c.height);
                    c.toBlob(function(b){ URL.revokeObjectURL(url); if (b && b.size < f.size) { cb(b, 'payment-proof.jpg'); } else { cb(f, f.name); } }, 'image/jpeg', 0.85);
                } catch (e) { URL.revokeObjectURL(url); cb(f, f.name); }
            };
            img.onerror = function(){ URL.revokeObjectURL(url); cb(f, f.name); };
            img.src = url;
        };
        fileInp.addEventListener('change', function(){
            var f = this.files[0]; picked = null; shotBtn.disabled = true; $('bqr-thumb').hidden = true;
            $('bqr-drop-text').textContent = C.t.choose;
            if (!f) { return; }
            if (!/^image\//.test(f.type)) { if (typeof failed === 'function') { failed('Invalid file', C.t.shotType); } return; }
            if (f.size > 10 * 1024 * 1024) { if (typeof failed === 'function') { failed('File too large', C.t.shotBig); } return; }
            shrink(f, function(blob, name){
                picked = blob; pickedName = name;
                $('bqr-thumb').src = URL.createObjectURL(blob); $('bqr-thumb').hidden = false;
                $('bqr-drop-text').textContent = f.name;
                shotBtn.disabled = false;
            });
        });
        shotBtn.addEventListener('click', function(){
            if (!picked || busy || done) { return; }
            busy = true; shotBtn.disabled = true;
            var old = shotBtn.innerHTML;
            shotBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            var fd = new FormData();
            fd.append('action-v2', 'transaction-verify');
            fd.append('gateway-id', C.gatewayId);
            fd.append('transaction-id', C.ref);
            fd.append('slip', picked, pickedName);
            fetch('', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    busy = false; shotBtn.innerHTML = old;
                    if (d.status === 'true') { done = true; stopAll(); if (typeof success === 'function') { success(d); } else { location.reload(); } }
                    else if (d.status === 'state') { done = true; stopAll(); location.reload(); }
                    else { shotBtn.disabled = false; if (typeof failed === 'function') { failed(d.title || 'Failed', d.message || ''); } }
                })
                .catch(function(){
                    busy = false; shotBtn.disabled = false; shotBtn.innerHTML = old;
                    if (typeof failed === 'function') { failed('Request Error', 'Something went wrong. Please try again.'); }
                });
        });
    }

    drawQR();
    run();
})();
</script>
</div>
<?php
        return ob_get_clean();
    }

    function pp_gateway_render($gateway_id = '', $data = []){
        global $db_prefix;

        unset($data['options'], $data['lang']);

        $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $data['brand']['id'] ];

        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id  AND status = "active"', '* FROM', $params),true);
        if($response_gateway['status'] == true){

            $options = [];

            $params = [ ':gateway_id' => $gateway_id ];
            $response_gateways_parameter = json_decode(getData($db_prefix.'gateways_parameter','WHERE gateway_id = :gateway_id', '* FROM', $params),true);
            foreach($response_gateways_parameter['response'] as $field){
                $value = $field['value'];

                if(!empty($field['multiple']) && !empty($value)){
                    $value = is_array($value) ? $value : json_decode($value, true);
                }

                $options[$field['option_name']] = $value;
            }

            $data['options'] = $options;

            $gatewayInfo = [
                'gateway_id'     => $response_gateway['response'][0]['gateway_id'],
                'slug'     => $response_gateway['response'][0]['slug'],
                'name'     => $response_gateway['response'][0]['name'],
                'display'     => $response_gateway['response'][0]['display'],
                'logo'     => $response_gateway['response'][0]['logo'],
                'currency'     => $response_gateway['response'][0]['currency'],
                'min_allow'     => money_round($response_gateway['response'][0]['min_allow']),
                'max_allow'     => money_round($response_gateway['response'][0]['max_allow']),

                'fixed_discount'     => money_round($response_gateway['response'][0]['fixed_discount']),
                'percentage_discount'     => money_round($response_gateway['response'][0]['percentage_discount']),
                'fixed_charge'     => money_round($response_gateway['response'][0]['fixed_charge']),
                'percentage_charge'     => money_round($response_gateway['response'][0]['percentage_charge']),

                'primary_color'     => $response_gateway['response'][0]['primary_color'],
                'text_color'     => $response_gateway['response'][0]['text_color'],
                'btn_color'     => $response_gateway['response'][0]['btn_color'],
                'btn_text_color'     => $response_gateway['response'][0]['btn_text_color'],
            ];

            $data['gateway'] = $gatewayInfo;

            $currencyRates = [];

            $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$response_gateway['response'][0]['brand_id'].'"'), true);

            if (!empty($currencyRes['response'])) {
                foreach ($currencyRes['response'] as $c) {
                    $currencyRates[$c['code']] =$c['rate'];
                }
            }

            $txnAmount  = money_sanitize($data['transaction']['amount']);
            $txnCurrency = $data['transaction']['currency'];
            $gatewayCurrency = $response_gateway['response'][0]['currency'];

            if ($txnCurrency === $gatewayCurrency) {
                $convertedAmount = $txnAmount;
            } else {
                if (isset($currencyRates[$gatewayCurrency])) {
                    $convertedAmount = money_div($txnAmount, $currencyRates[$gatewayCurrency]);
                } else {
                    $convertedAmount = "0";
                }
            }

            $fixed_discount = money_sanitize($response_gateway['response'][0]['fixed_discount']);
            $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

            $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
            $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

            $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
            $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

            $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
            $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

            $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

            if ($txnCurrency !== $gatewayCurrency && isset($currencyRates[$gatewayCurrency])) {
                $totalDiscount = money_mul($totalDiscount, $currencyRates[$gatewayCurrency], 8);
                $totalProcessingFee = money_mul($totalProcessingFee, $currencyRates[$gatewayCurrency], 8);
            }

            $data['transaction']['amount'] = money_round($txnAmount, 2);
            $data['transaction']['processing_fee'] = money_round($totalProcessingFee, 2);
            $data['transaction']['discount_amount'] = money_round($totalDiscount, 2);
            $data['transaction']['local_net_amount'] = money_round($convertedAmount, 2);
            $data['transaction']['local_currency'] = $gatewayCurrency;

            if(file_exists(__DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php')){
                require_once __DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php';

                $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_gateway['response'][0]['slug']))) . 'Gateway';

                $gateway = new $class();

                $gateway_info = $gateway->info();
                if (method_exists($gateway, 'resolve')) { $gateway_info = $gateway->resolve($gateway_info, $options); }

                if (method_exists($gateway, 'supported_languages')) {
                    $supported_languages = $gateway->supported_languages();
                }else{
                    $supported_languages = [];
                }

                if (method_exists($gateway, 'lang_text')) {
                     $lang_text = $gateway->lang_text();
                }else{
                    $lang_text = [];
                }
            }else{
                if($response_gateway['response'][0]['tab'] == 'bank'){
                    $gateway = '';

                    $supported_languages = [
                        'en' => 'English',
                        'bn' => 'বাংলা',
                        'hi' => 'हिन्दी',
                        'ur' => 'اردو',
                        'ar' => 'العربية',
                    ];

                    $lang_text = [
                        'bank_step_bank_name' => [
                            'en' => 'Bank Name: {bank_name}',
                            'bn' => 'ব্যাংকের নাম: {bank_name}',
                            'hi' => 'बैंक का नाम: {bank_name}',
                            'ur' => 'بینک کا نام: {bank_name}',
                            'ar' => 'اسم البنك: {bank_name}',
                        ],

                        'bank_step_account_name' => [
                            'en' => 'Account Name: {account_holder_name}',
                            'bn' => 'অ্যাকাউন্টের নাম: {account_holder_name}',
                            'hi' => 'खाते का नाम: {account_holder_name}',
                            'ur' => 'اکاؤنٹ کا نام: {account_holder_name}',
                            'ar' => 'اسم الحساب: {account_holder_name}',
                        ],

                        'bank_step_account_number' => [
                            'en' => 'Account Number: {account_number}',
                            'bn' => 'অ্যাকাউন্ট নম্বর: {account_number}',
                            'hi' => 'खाता संख्या: {account_number}',
                            'ur' => 'اکاؤنٹ نمبر: {account_number}',
                            'ar' => 'رقم الحساب: {account_number}',
                        ],

                        'bank_step_branch_name' => [
                            'en' => 'Branch Name: {branch_name}',
                            'bn' => 'শাখার নাম: {branch_name}',
                            'hi' => 'शाखा का नाम: {branch_name}',
                            'ur' => 'برانچ کا نام: {branch_name}',
                            'ar' => 'اسم الفرع: {branch_name}',
                        ],

                        'bank_step_routing_number' => [
                            'en' => 'Routing Number: {routing_number}',
                            'bn' => 'রাউটিং নম্বর: {routing_number}',
                            'hi' => 'रूटिंग नंबर: {routing_number}',
                            'ur' => 'روٹنگ نمبر: {routing_number}',
                            'ar' => 'رقم التوجيه: {routing_number}',
                        ],

                        'bank_step_swift_code' => [
                            'en' => 'Swift Code: {swift_code}',
                            'bn' => 'সুইফট কোড: {swift_code}',
                            'hi' => 'स्विफ्ट कोड: {swift_code}',
                            'ur' => 'سوئفٹ کوڈ: {swift_code}',
                            'ar' => 'رمز السويفت: {swift_code}',
                        ],

                        'bank_step_amount' => [
                            'en' => 'Amount: {amount} {currency}',
                            'bn' => 'পরিমাণ: {amount} {currency}',
                            'hi' => 'राशि: {amount} {currency}',
                            'ur' => 'رقم: {amount} {currency}',
                            'ar' => 'المبلغ: {amount} {currency}',
                        ],

                        'bank_step_slip' => [
                            'en' => 'Upload the Payment Slip in the box below and press Submit',
                            'bn' => 'নিচের বক্সে পেমেন্ট স্লিপ আপলোড করুন এবং জমা দিন চাপুন।',
                            'hi' => 'नीचे दिए गए बॉक्स में भुगतान रसीद अपलोड करें और "सबमिट" दबाएँ।',
                            'ur' => 'نیچے دیے گئے باکس میں ادائیگی کی رسید اپ لوڈ کریں اور "Submit" دبائیں۔',
                            'ar' => 'قم برفع إيصال الدفع في المربع أدناه ثم اضغط على "إرسال".',
                        ],
                    ];

                    $instructions = [
                        [
                            'icon' => '',
                            'text' => 'bank_step_bank_name',
                            'copy' => true,
                            'value' => $data['options']['bank_name'],
                            'vars' => [
                                '{bank_name}' => $data['options']['bank_name']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_account_name',
                            'copy' => true,
                            'value' => $data['options']['account_holder_name'],
                            'vars' => [
                                '{account_holder_name}' => $data['options']['account_holder_name']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_account_number',
                            'copy' => true,
                            'value' => $data['options']['account_number'],
                            'vars' => [
                                '{account_number}' => $data['options']['account_number']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_branch_name',
                            'copy' => true,
                            'value' => $data['options']['branch_name'],
                            'vars' => [
                                '{branch_name}' => $data['options']['branch_name']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_routing_number',
                            'copy' => true,
                            'value' => $data['options']['routing_number'],
                            'vars' => [
                                '{routing_number}' => $data['options']['routing_number']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_swift_code',
                            'copy' => true,
                            'value' => $data['options']['swift_code'],
                            'vars' => [
                                '{swift_code}' => $data['options']['swift_code']
                            ]
                        ],                        
                        [
                            'icon' => '',
                            'text' => 'bank_step_amount',
                            'copy' => true,
                            'value' => $data['transaction']['local_net_amount'],
                            'vars' => [
                                '{amount}' => number_format((float)$data['transaction']['local_net_amount'], 2),
                                '{currency}' => $data['transaction']['local_currency']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_slip',
                            'copy' => false,
                        ],
                    ];

                    $gateway_info = [
                        'gateway_type'        => 'manual',
                        'verify_by'        => 'slip',
                    ];
                }else{
                    return false;
                }
            }

            $lang_text['verify'] = [
                'en' => 'Verify',
                'bn' => 'যাচাই করুন',
                'hi' => 'सत्यापित करें',
                'ur' => 'تصدیق کریں',
                'ar' => 'تحقق',
            ];
            
            $lang_text['transaction_id'] = [
                'en' => 'Transaction ID',
                'bn' => 'ট্রানজ্যাকশন আইডি',
                'hi' => 'लेन-देन आईडी',
                'ur' => 'لین دین آئی ڈی',
                'ar' => 'معرّف المعاملة',
            ];

            $lang_text['enter_transaction_id'] = [
                'en' => 'Enter transaction ID',
                'bn' => 'ট্রানজ্যাকশন আইডি লিখুন',
                'hi' => 'लेन-देन आईडी दर्ज करें',
                'ur' => 'لین دین آئی ڈی درج کریں',
                'ar' => 'أدخل معرّف المعاملة',
            ];
            
            $lang_text['upload_slip'] = [
                'en' => 'Upload Payment Slip',
                'bn' => 'পেমেন্ট স্লিপ আপলোড করুন',
                'hi' => 'भुगतान स्लिप अपलोड करें',
                'ur' => 'ادائیگی سلپ اپ لوڈ کریں',
                'ar' => 'ارفع إيصال الدفع',
            ];            
            
            $lang_text['mobile_number'] = [
                'en' => 'Mobile Number',
                'bn' => 'মোবাইল নম্বর',
                'hi' => 'मोबाइल नंबर',
                'ur' => 'موبائل نمبر',
                'ar' => 'رقم الجوال',
            ];

            $lang_text['submit'] = [
                'en' => 'Submit',
                'bn' => 'জমা দিন',
                'hi' => 'जमा करें',
                'ur' => 'جمع کریں',
                'ar' => 'إرسال',
            ];
            
            $lang_text = array_merge((array)$lang_text, pp_auto_flow_lang());
            $language = resolveModuleLanguage($data['brand']['locale']['language'],$supported_languages);

            // Build $lang array for developer
            $lang = buildLangArray($lang_text, $language);

            $data['lang']    = $lang; // or whatever new value

            // If you also want to keep discount in sync (optional)
            //$data['transaction']['discount_amount'] = number_format((float)$data['transaction']['discount_amount'],2,'.','');

            if (is_callable([$gateway, 'instructions'])) {
                $instructions = $gateway->instructions($data);
            }

            if(isset($instructions) && !(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "automation")){
                echo '<ol class="payment-instructions">';

                $rowli = 0;

                foreach ($instructions as $step) {
                    $rowli = $rowli+1;

                    // Resolve language directly
                    $text = $lang[$step['text']] ?? $step['text'];

                    // Replace variables
                    if (!empty($step['vars'])) {
                        foreach ($step['vars'] as $k => $v) {
                            $text = str_replace($k, '<span class="dynamic-value">'.$v.'</span>', $text);
                        }
                    }

                    echo '<li class="li-'.$rowli.'">';
                    echo ($step['icon'] == "") ? '<div class="dot"></div>' : $step['icon'];

                    echo '<p>';
                    echo $text;

                    /* Copy button */
                    if (!empty($step['copy']) && isset($step['value'])) {
                        echo ' <span class="button-icon"
                            onclick="copy_value(\'' . htmlspecialchars($step['value'], ENT_QUOTES) . '\')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666"/>
                                <path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/>
                            </svg>
                        </span>';
                    }

                    /* Action button */
                    if (!empty($step['action'])) {
                        $action = $step['action'];

                        if ($action['type'] === 'image' && !empty($action['value'])) {
                            echo ' <span class="button-icon"
                                onclick="pp_show_image(\''.htmlspecialchars($action['value'], ENT_QUOTES).'\')">
                                '.$action['label'].'
                            </span>';
                        }else{
                            echo '<style>.li-'.$rowli.'{display: none !important;}</style>';
                        }
                    }

                    echo '</p>';
                    echo '</li>';

                }

                echo '</ol>';

                echo '
                    <div id="pp-image-modal" class="pp-modal" style="display:none;">
                        <div class="pp-modal-content">
                            <span class="pp-close" onclick="pp_close_image()">&times;</span>
                            <div class="pp-model-image-b"><img id="pp-modal-image" src="" alt="Preview"></div>
                        </div>
                    </div>

                    <script data-cfasync="false">
                        function pp_show_image(src) {
                            const modal = document.getElementById("pp-image-modal");
                            const img = document.getElementById("pp-modal-image");

                            img.src = src;
                            modal.style.display = "flex";
                        }

                        function pp_close_image() {
                            document.getElementById("pp-image-modal").style.display = "none";
                        }
                    </script>
                ';
            }

            if(isset($gateway_info)){
                if(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "automation"){
                    echo pp_render_auto_flow($data, $gateway_info);
                }
                if(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "manual"){
                    if(isset($gateway_info['verify_by']) && $gateway_info['verify_by'] == "trxid"){
                        echo '
                            <form class="payment-form-submit" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action-v2" value="transaction-verify">
                                <input type="hidden" name="gateway-id" value="'.$data['gateway']['gateway_id'].'">
                                <input type="hidden" name="transaction-id" value="'.$data['transaction']['ref'].'">

                                <div class="form-group  mt-3">
                                    <label class="form-label">'.$data['lang']['transaction_id'].'</label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" name="trxid" placeholder="'.$data['lang']['enter_transaction_id'].'" required=""> 
                                    </div>
                                </div>

                                <button class="btn btn-primary w-100 payment-form-btn mt-3" type="submit">'.$data['lang']['submit'].'</button>
                            </form>

                            <script data-cfasync="false">
                                document.addEventListener("DOMContentLoaded", function() {
                                    const form = document.querySelector(".payment-form-submit");
                                    const mobileWrapper = form.querySelector(`.form-group[style*="display: none"]`);
                                    const submitBtn = form.querySelector(".payment-form-btn");

                                    form.addEventListener("submit", function(e) {
                                        e.preventDefault();

                                        const formData = new FormData(form);

                                        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

                                        fetch("", { // replace "" with your PHP AJAX URL if needed
                                            method: "POST",
                                            body: formData
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            submitBtn.innerHTML = `'.$data['lang']['verify'].'`;

                                            if(data.status === "true") {
                                                // Verified successfully
                                                success(data); // pass data if needed
                                            } else if(data.status === "false") {
                                                // Call failed handler with title & message
                                                failed(data.title, data.message);
                                            } else {
                                                // Unexpected response
                                                failed("Unexpected Response", "Please try again later.");
                                            }
                                        })
                                        .catch(err => {
                                            submitBtn.innerHTML = `'.$data['lang']['verify'].'`;
                                            console.error(err);
                                            failed("Request Error", "Something went wrong. Please try again.");
                                        });
                                    });
                                });

                            </script>
                        ';
                    }else{
                        if(isset($gateway_info['verify_by']) && $gateway_info['verify_by'] == "slip"){
                            echo '
                                <form class="payment-form-submit" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action-v2" value="transaction-verify">
                                    <input type="hidden" name="gateway-id" value="'.$data['gateway']['gateway_id'].'">
                                    <input type="hidden" name="transaction-id" value="'.$data['transaction']['ref'].'">

                                    <div class="form-group  mt-3">
                                        <label class="form-label">'.$data['lang']['upload_slip'].'</label>
                                        <div class="form-control-wrap">
                                            <input type="file" class="form-control" name="slip" accept="image/*,application/pdf,.pdf" placeholder="'.$data['lang']['upload_slip'].'" required=""> 
                                        </div>
                                    </div>

                                    <button class="btn btn-primary w-100 payment-form-btn mt-3" type="submit">'.$data['lang']['submit'].'</button>
                                </form>

                                <script data-cfasync="false">
                                    document.addEventListener("DOMContentLoaded", function() {
                                        const form = document.querySelector(".payment-form-submit");
                                        const mobileWrapper = form.querySelector(`.form-group[style*="display: none"]`);
                                        const submitBtn = form.querySelector(".payment-form-btn");

                                        form.addEventListener("submit", function(e) {
                                            e.preventDefault();

                                            const formData = new FormData(form);

                                            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

                                            fetch("", { // replace "" with your PHP AJAX URL if needed
                                                method: "POST",
                                                body: formData
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                submitBtn.innerHTML = `'.$data['lang']['verify'].'`;

                                                if(data.status === "true") {
                                                    // Verified successfully
                                                    success(data); // pass data if needed
                                                } else if(data.status === "false") {
                                                    // Call failed handler with title & message
                                                    failed(data.title, data.message);
                                                } else {
                                                    // Unexpected response
                                                    failed("Unexpected Response", "Please try again later.");
                                                }
                                            })
                                            .catch(err => {
                                                submitBtn.innerHTML = `'.$data['lang']['verify'].'`;
                                                console.error(err);
                                                failed("Request Error", "Something went wrong. Please try again.");
                                            });
                                        });
                                    });

                                </script>
                            ';
                        }
                    }
                }
            }

            if(isset($_GET['pp_callback'])){
                if (is_callable([$gateway, 'callback'])) {
                    $gateway->callback($data);
                }
            }else{
                if (is_callable([$gateway, 'process_payment'])) {
                    $gateway->process_payment($data);
                }
            }
        }else{
            return false;
        }
    }

    function pp_renderFormFields(string $type = '', array $data = [])
    {
        if($type == "payment-link"){
            $paymentLinkID = $data['paymentLink']['pid'] ?? '';
            $fields = $data['paymentLink']['fields'] ?? '';

            echo "<input type='hidden' name='action-v2' value='payment-link'>";
            echo "<input type='hidden' name='itemid' value='".$paymentLinkID."'>";

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='full-name'>".$data['lang']['full_name']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='full-name' id='full-name' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='email-address'>".$data['lang']['email_address']." <span class='text-danger'>*</span></label>";
                echo "<input type='email' name='email-address' id='email-address' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['mobile_number']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='mobile-number' id='mobile-number' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['amount']." <span class='text-danger'>*</span></label>";
                echo '
                    <div class="input-group mb-2">
                        <span class="input-group-text"> '.$data['paymentLink']['currency'].' </span>
                        <input type="text" class="form-control" placeholder="Amount" value="'.money_round($data['paymentLink']['total'], 2).'" autocomplete="off" readonly>
                    </div>';
            echo '</div>';

            foreach ($fields as $field) {
                $name     = htmlspecialchars($field['name']);
                $label    = htmlspecialchars($field['label']);
                $type     = $field['type'];
                $required = (!empty($field['required']) && $field['required'] !== 'false') ? 'required' : '';

                echo '<div class="mb-3">';

                // Show label for all except checkbox (we put label inside input for checkbox)
                if ($type == 'checkbox') {
                    echo "<label class='form-label' for='{$name}'>{$label}";
                    if ($required) echo ' <span class="text-danger">*</span>';
                    echo "</label>";
                }

                switch ($type) {

                    case 'text':
                        echo "<input type='text' name='{$name}' id='{$name}' class='form-control' {$required}>";
                        break;

                    case 'textarea':
                        echo "<textarea name='{$name}' id='{$name}' class='form-control' {$required}></textarea>";
                        break;

                    case 'select':
                        echo "<select name='{$name}' id='{$name}' class='form-control' {$required}>";
                        if (!empty($field['options'])) {
                            foreach ($field['options'] as $opt) {
                                echo "<option value='".htmlspecialchars($opt)."'>".htmlspecialchars($opt)."</option>";
                            }
                        }
                        echo "</select>";
                        break;

                    case 'checkbox':
                        if (!empty($field['options'])) {
                            foreach ($field['options'] as $opt) {

                                $optValue = htmlspecialchars($opt);
                                $optId    = $name . '_' . preg_replace('/\s+/', '_', strtolower($opt));

                                echo "<div class='form-check'>";
                                echo "<input 
                                        type='checkbox'
                                        name='{$name}[]'
                                        id='{$optId}'
                                        class='form-check-input'
                                        value='{$optValue}'
                                        {$required}
                                    >";
                                echo "<label class='form-check-label' for='{$optId}'>{$optValue}</label>";
                                echo "</div>";
                            }
                        }
                        break;

                    case 'radio':
                        if (!empty($field['options'])) {
                            foreach ($field['options'] as $opt) {
                                $radioId = $name.'_'.preg_replace('/\s+/', '_', strtolower($opt));
                                echo "<div class='form-check'>";
                                echo "<input type='radio' name='{$name}' id='{$radioId}' class='form-check-input' value='".htmlspecialchars($opt)."' {$required}>";
                                echo "<label class='form-check-label' for='{$radioId}'>".htmlspecialchars($opt)."</label>";
                                echo "</div>";
                            }
                        }
                        break;

                    case 'file':
                        $accept = '';
                        if (!empty($field['options'])) {
                            $exts = array_map(function($e){ return ".".$e; }, $field['options']);
                            $accept = 'accept="'.implode(',', $exts).'"';
                        }
                        echo "<input type='file' name='{$name}' id='{$name}' class='form-control' {$accept} {$required}>";
                        break;

                    default:
                        echo "<input type='text' name='{$name}' id='{$name}' class='form-control' {$required}>";
                        break;
                }

                // Optional context hint (for invoice, payment_link, etc.)
                if (!empty($field['hint'])) {
                    echo "<small class='form-text text-muted'>{$field['hint']}</small>";
                }

                echo '</div>';
            }
        }

        if($type == "payment-link-default"){
            $paymentLinkID = $data['paymentLink']['pid'] ?? '';
            $currency = $data['paymentLink']['currency'] ?? '';

            echo "<input type='hidden' name='action-v2' value='payment-link-default'>";
            echo "<input type='hidden' name='itemid' value='".$paymentLinkID."'>";

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='full-name'>".$data['lang']['full_name']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='full-name' id='full-name' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='email-address'>".$data['lang']['email_address']." <span class='text-danger'>*</span></label>";
                echo "<input type='email' name='email-address' id='email-address' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['mobile_number']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='mobile-number' id='mobile-number' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['amount']." <span class='text-danger'>*</span></label>";
                echo '
                    <div class="input-group mb-2">
                        <span class="input-group-text"> '.$data['paymentLink']['currency'].' </span>
                        <input type="number" name="amount" class="form-control" placeholder="Amount" value="0" autocomplete="off">
                    </div>';
            echo '</div>';
        }

        if($type == "invoice"){
            $invoiceID = $data['invoice']['iid'] ?? '';

            echo "<input type='hidden' name='action-v2' value='invoice'>";
            echo "<input type='hidden' name='itemid' value='".$invoiceID."'>";
        }
    }

    $GLOBALS['__actions'] = [];
    $GLOBALS['__filters'] = [];

    function add_action(string $hook, callable $callback, int $priority = 10)
    {
        $GLOBALS['__actions'][$hook][$priority][] = $callback;
    }

    function do_action(string $hook, ...$args)
    {
        if (empty($GLOBALS['__actions'][$hook])) {
            return;
        }

        ksort($GLOBALS['__actions'][$hook]);

        foreach ($GLOBALS['__actions'][$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    call_user_func_array($callback, $args);
                } catch (Throwable $e) {
                    // prevent plugin crash
                    error_log('Action error ['.$hook.']: '.$e->getMessage());
                }
            }
        }
    }

    function add_filter(string $hook, callable $callback, int $priority = 10)
    {
        $GLOBALS['__filters'][$hook][$priority][] = $callback;
    }

    function apply_filters(string $hook, $value, ...$args)
    {
        if (empty($GLOBALS['__filters'][$hook])) {
            return $value;
        }

        ksort($GLOBALS['__filters'][$hook]);

        foreach ($GLOBALS['__filters'][$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $value = call_user_func($callback, $value, ...$args);
                } catch (Throwable $e) {
                    error_log('Filter error ['.$hook.']: '.$e->getMessage());
                }
            }
        }

        return $value;
    }

    /*
    add_filter('invoice.total', function ($total, $invoice) {
        return $total + 10;
    });
    add_action('invoice.updated', function ($invoice) {
        error_log('Wallet credited for invoice '.$invoice['id']);
    });
    */

    class DB
    {
        protected static ?PDO $pdo = null;

        protected static function pdo(): PDO
        {
            if (!self::$pdo) {
                self::$pdo = connectDatabase(); // your existing function
            }
            return self::$pdo;
        }

        public static function table(string $table): QueryBuilder
        {
            global $db_prefix;
            return new QueryBuilder($db_prefix . $table, self::pdo());
        }

        /* ========================
        TRANSACTIONS
        ======================== */

        public static function beginTransaction(): void
        {
            self::pdo()->beginTransaction();
        }

        public static function commit(): void
        {
            self::pdo()->commit();
        }

        public static function rollBack(): void
        {
            self::pdo()->rollBack();
        }
    }
    class QueryBuilder
    {
        protected PDO $pdo;
        protected string $table;

        protected array $wheres = [];
        protected array $bindings = [];
        protected array $orders = [];

        protected ?int $limit = null;
        protected ?int $offset = null;

        public function __construct(string $table, PDO $pdo)
        {
            $this->table = $table;
            $this->pdo   = $pdo;
        }

        /* ========================
        WHERE
        ======================== */

        public function where(string $column, $operator, $value = null): self
        {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }

            $this->wheres[] = ['AND', "$column $operator ?"];
            $this->bindings[] = $value;

            return $this;
        }

        public function orWhere(string $column, $operator, $value = null): self
        {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }

            $this->wheres[] = ['OR', "$column $operator ?"];
            $this->bindings[] = $value;

            return $this;
        }

        public function whereIn(string $column, array $values): self
        {
            $placeholders = implode(',', array_fill(0, count($values), '?'));

            $this->wheres[] = ['AND', "$column IN ($placeholders)"];
            $this->bindings = array_merge($this->bindings, $values);

            return $this;
        }

        /* ========================
        ORDER / LIMIT
        ======================== */

        public function orderBy(string $column, string $direction = 'ASC'): self
        {
            $this->orders[] = "$column " . strtoupper($direction);
            return $this;
        }

        public function limit(int $limit): self
        {
            $this->limit = $limit;
            return $this;
        }

        public function offset(int $offset): self
        {
            $this->offset = $offset;
            return $this;
        }

        /* ========================
        READ
        ======================== */

        public function get(): array
        {
            $sql = $this->buildSelect();
            $rows = $this->run($sql, true, true);
            $this->reset();

            return $rows;
        }

        public function first(): ?object
        {
            $this->limit = 1;
            $sql = $this->buildSelect();

            $row = $this->run($sql, true, false);
            $this->reset();

            return $row ?: null;
        }

        public function count(): int
        {
            $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
            $sql .= $this->compileWhere();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $this->reset();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        }

        public function exists(): bool
        {
            return $this->count() > 0;
        }

        /* ========================
        INSERT
        ======================== */

        public function insert(array $data): bool
        {
            $columns = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));

            $sql = "INSERT INTO {$this->table} (" .
                implode(',', $columns) .
                ") VALUES ($placeholders)";

            return $this->run($sql, false, false, array_values($data));
        }

        /* ========================
        UPDATE
        ======================== */

        public function update(array $data): bool
        {
            $sets = [];

            foreach ($data as $col => $val) {
                $sets[] = "$col = ?";
                $this->bindings[] = $val;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
            $sql .= $this->compileWhere();

            $result = $this->run($sql, false, false);
            $this->reset();

            return $result;
        }

        /* ========================
        DELETE
        ======================== */

        public function delete(): bool
        {
            $sql = "DELETE FROM {$this->table}";
            $sql .= $this->compileWhere();

            $result = $this->run($sql, false, false);
            $this->reset();

            return $result;
        }

        /* ========================
        INTERNAL
        ======================== */

        protected function buildSelect(): string
        {
            $sql = "SELECT * FROM {$this->table}";
            $sql .= $this->compileWhere();

            if ($this->orders) {
                $sql .= ' ORDER BY ' . implode(', ', $this->orders);
            }

            if ($this->limit !== null) {
                $sql .= " LIMIT {$this->limit}";
            }

            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }

            return $sql;
        }

        protected function compileWhere(): string
        {
            if (!$this->wheres) {
                return '';
            }

            $sql = ' WHERE ';
            foreach ($this->wheres as $i => [$type, $condition]) {
                $sql .= ($i === 0 ? '' : " $type ") . $condition;
            }

            return $sql;
        }

        protected function run(
            string $sql,
            bool $fetch,
            bool $fetchAll,
            array $bindings = []
        ) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings ?: $this->bindings);

            if (!$fetch) {
                return true;
            }

            return $fetchAll
                ? $stmt->fetchAll(PDO::FETCH_OBJ)
                : $stmt->fetch(PDO::FETCH_OBJ);
        }

        protected function reset(): void
        {
            $this->wheres = [];
            $this->bindings = [];
            $this->orders = [];
            $this->limit = null;
            $this->offset = null;
        }
    }

    function pp_fetch_update_manifest() {
        // 1. Try GitHub Contents API (Instant & Uncached)
        $apiUrls = [
            'https://api.github.com/repos/samsusiyam/PipraPay/contents/manifest.json',
            'https://api.github.com/repos/PipraPay/PipraPay/contents/manifest.json'
        ];

        foreach ($apiUrls as $apiUrl) {
            if (function_exists('curl_init')) {
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PipraPay-Updater/3.0');
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && !empty($response)) {
                    $apiData = json_decode($response, true);
                    if (!empty($apiData['content'])) {
                        $rawJson = base64_decode($apiData['content']);
                        $decoded = json_decode($rawJson, true);
                        if (is_array($decoded) && isset($decoded['channels'])) {
                            return $decoded;
                        }
                    }
                }
            }
        }

        // 2. Fallback to Raw GitHub URL
        $ts = time();
        $urls = [
            'https://raw.githubusercontent.com/samsusiyam/PipraPay/main/manifest.json?t=' . $ts,
            'https://raw.githubusercontent.com/PipraPay/PipraPay/main/manifest.json?t=' . $ts
        ];

        foreach ($urls as $url) {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PipraPay-Updater/3.0');
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && !empty($response)) {
                    $decoded = json_decode($response, true);
                    if (is_array($decoded) && isset($decoded['channels'])) {
                        return $decoded;
                    }
                }
            }
        }

        // 3. Fallback to local manifest if available
        $localPath = __DIR__ . '/../../manifest.json';
        if (file_exists($localPath)) {
            $localContent = file_get_contents($localPath);
            $decoded = json_decode($localContent, true);
            if (is_array($decoded) && isset($decoded['channels'])) {
                return $decoded;
            }
        }

        return null;
    }

    /* =========================================================================
     * PIPRAPAY UNIFIED NOTIFICATION & ALERT ENGINE (Telegram, Discord, WhatsApp, Email, SMS)
     * ========================================================================= */

    function pp_send_telegram($text, $chatId = null, $botToken = null, $topicId = null) {
        $token = !empty($botToken) ? $botToken : get_env('notification_telegram_token');
        $chat = !empty($chatId) ? $chatId : get_env('notification_telegram_chat_id');
        $topic = !empty($topicId) ? $topicId : get_env('notification_telegram_topic_id');

        if (empty($token) || $token === '--' || empty($chat) || $chat === '--') {
            return ['status' => false, 'message' => 'Telegram bot token or chat ID is not configured.'];
        }

        $url = "https://api.telegram.org/bot" . trim($token) . "/sendMessage";
        $payload = [
            'chat_id' => trim($chat),
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false
        ];

        if (!empty($topic) && $topic !== '--') {
            $payload['message_thread_id'] = trim($topic);
        }

        if (!function_exists('curl_init')) {
            return ['status' => false, 'message' => 'PHP cURL extension is required for Telegram notifications.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'PipraPay-Notification/3.0'
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($httpCode === 200 && isset($json['ok']) && $json['ok'] == true) {
            return ['status' => true, 'message' => 'Telegram message sent successfully.'];
        }
        return ['status' => false, 'message' => $json['description'] ?? ($err ?: "Telegram API returned HTTP $httpCode: $res")];
    }

    function pp_send_discord($title, $description, $fields = [], $color = 3066993, $webhookUrl = null, $extra = []) {
        $url = !empty($webhookUrl) ? $webhookUrl : get_env('notification_discord_webhook');
        if (empty($url) || $url === '--') {
            return ['status' => false, 'message' => 'Discord webhook URL is not configured.'];
        }

        $botName = get_env('notification_discord_bot_name') ?: 'PipraPay Alerts';
        if ($botName === '--') $botName = 'PipraPay Alerts';

        $embed = [
            'title' => $title,
            'description' => $description,
            'color' => $color,
            'fields' => $fields,
            'timestamp' => date('c'),
            'footer' => [
                'text' => 'PipraPay Notification Engine'
            ]
        ];

        if (!empty($extra['image'])) { $embed['image'] = ['url' => (string)$extra['image']]; }

        $payload = [
            'username' => $botName,
            'embeds' => [$embed]
        ];

        if (!empty($extra['components'])) {
            $payload['components'] = $extra['components'];
            if (stripos($url, 'with_components=') === false) { $url .= (strpos($url, '?') === false ? '?' : '&') . 'with_components=true'; }
        }

        if (!function_exists('curl_init')) {
            return ['status' => false, 'message' => 'PHP cURL extension is required for Discord webhook.'];
        }

        $ch = curl_init(trim($url));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'PipraPay-Notification/3.0'
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($httpCode == 400 && !empty($extra['components'])) {
            // this webhook does not accept buttons: send the same message with plain links only
            $extra2 = $extra; unset($extra2['components']);
            return pp_send_discord($title, $description, $fields, $color, preg_replace('/[?&]with_components=true/', '', trim($url)), $extra2);
        }
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['status' => true, 'message' => 'Discord notification sent successfully.'];
        }
        $json = json_decode($res, true);
        return ['status' => false, 'message' => $json['message'] ?? ($err ?: "Discord returned HTTP $httpCode: $res")];
    }

    function pp_send_whatsapp($phone, $message, $config = []) {
        $apiUrl = !empty($config['api_url']) ? $config['api_url'] : get_env('notification_whatsapp_api_url');
        $apiKey = !empty($config['api_key']) ? $config['api_key'] : get_env('notification_whatsapp_api_key');
        $sender = !empty($config['sender_id']) ? $config['sender_id'] : get_env('notification_whatsapp_sender');

        if (empty($apiUrl) || $apiUrl === '--') {
            return ['status' => false, 'message' => 'WhatsApp API URL is not configured.'];
        }

        if (empty($phone) || $phone === '--') {
            return ['status' => false, 'message' => 'WhatsApp target recipient phone number is missing.'];
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $url = str_replace(
            ['{phone}', '{to}', '{number}', '{message}', '{api_key}'],
            [urlencode($cleanPhone), urlencode($cleanPhone), urlencode($cleanPhone), urlencode($message), urlencode($apiKey)],
            $apiUrl
        );

        if (!function_exists('curl_init')) {
            return ['status' => false, 'message' => 'PHP cURL extension is required for WhatsApp API.'];
        }

        $ch = curl_init();
        if (strpos($apiUrl, '{phone}') !== false || strpos($apiUrl, '{to}') !== false || strpos($apiUrl, '{message}') !== false) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        } else {
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            $postData = [
                'to' => $cleanPhone,
                'phone' => $cleanPhone,
                'number' => $cleanPhone,
                'message' => $message,
                'text' => $message,
                'api_key' => $apiKey,
                'token' => $apiKey,
                'sender' => $sender
            ];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PipraPay-Notification/3.0');
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['status' => true, 'message' => 'WhatsApp message sent successfully.'];
        }
        return ['status' => false, 'message' => $err ?: "WhatsApp API returned HTTP $httpCode: $res"];
    }

    function pp_send_email($to, $subject, $htmlBody, $config = []) {
        $fromName = !empty($config['from_name']) ? $config['from_name'] : (get_env('notification_email_from_name') ?: 'PipraPay');
        $fromEmail = !empty($config['from_email']) ? $config['from_email'] : (get_env('notification_email_from') ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'piprapay.com'));
        if ($fromName === '--') $fromName = 'PipraPay';
        if ($fromEmail === '--') $fromEmail = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'piprapay.com');

        $smtpHost = !empty($config['smtp_host']) ? $config['smtp_host'] : get_env('notification_email_smtp_host');
        $smtpPort = !empty($config['smtp_port']) ? $config['smtp_port'] : get_env('notification_email_smtp_port');
        $smtpUser = !empty($config['smtp_user']) ? $config['smtp_user'] : get_env('notification_email_smtp_user');
        $smtpPass = !empty($config['smtp_pass']) ? $config['smtp_pass'] : get_env('notification_email_smtp_pass');
        $smtpEnc  = !empty($config['smtp_enc']) ? $config['smtp_enc'] : get_env('notification_email_smtp_enc'); // ssl or tls

        // If SMTP credentials provided, try direct SMTP connection
        if (!empty($smtpHost) && $smtpHost !== '--') {
            try {
                $port = (!empty($smtpPort) && $smtpPort !== '--') ? (int)$smtpPort : 587;
                $scheme = ($smtpEnc === 'ssl' || $port === 465) ? 'ssl://' : '';
                $socket = @fsockopen($scheme . $smtpHost, $port, $errno, $errstr, 8);
                if (!$socket) {
                    return ['status' => false, 'message' => "Cannot connect to SMTP server ({$smtpHost}:{$port}). Error: {$errstr} ({$errno})"];
                }

                $read = function($sock) {
                    $s = '';
                    while ($line = fgets($sock, 515)) {
                        $s .= $line;
                        if (substr($line, 3, 1) === ' ') break;
                    }
                    return $s;
                };
                $write = function($sock, $cmd) {
                    fputs($sock, $cmd . "\r\n");
                };

                $read($socket);
                $write($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $read($socket);

                if ($smtpEnc === 'tls' || $port === 587) {
                    $write($socket, "STARTTLS");
                    $tlsRes = $read($socket);
                    if (strpos($tlsRes, '220') !== false) {
                        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                        $write($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                        $read($socket);
                    }
                }

                if (!empty($smtpUser) && $smtpUser !== '--') {
                    $write($socket, "AUTH LOGIN");
                    $read($socket);
                    $write($socket, base64_encode($smtpUser));
                    $read($socket);
                    $write($socket, base64_encode($smtpPass));
                    $authRes = $read($socket);

                    if (strpos($authRes, '235') === false) {
                        fclose($socket);
                        return ['status' => false, 'message' => "SMTP Authentication failed: " . trim($authRes)];
                    }
                }

                $write($socket, "MAIL FROM: <$fromEmail>");
                $fromRes = $read($socket);
                $write($socket, "RCPT TO: <$to>");
                $rcptRes = $read($socket);
                $write($socket, "DATA");
                $dataPrompt = $read($socket);

                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: $fromName <$fromEmail>\r\n";
                $headers .= "To: <$to>\r\n";
                $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
                $headers .= "Date: " . date('r') . "\r\n";

                $write($socket, $headers . "\r\n" . $htmlBody . "\r\n.");
                $dataRes = $read($socket);
                $write($socket, "QUIT");
                fclose($socket);

                if (strpos($dataRes, '250') !== false) {
                    return ['status' => true, 'message' => 'Email sent successfully via SMTP.'];
                }
                return ['status' => false, 'message' => "SMTP Error delivering message: " . trim($dataRes)];
            } catch (Throwable $e) {
                return ['status' => false, 'message' => "SMTP Exception: " . $e->getMessage()];
            }
        }

        // Fallback to PHP native mail() only if explicitly desired
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $sent = @mail($to, $subject, $htmlBody, $headers);
        if ($sent) {
            return ['status' => true, 'message' => 'Email sent successfully via server mail().'];
        }
        return ['status' => false, 'message' => 'No SMTP configured and PHP mail() is unavailable. Please configure SMTP host & credentials.'];
    }

    function pp_send_sms($to, $message, $config = []) {
        $gatewayUrl = !empty($config['api_url']) ? $config['api_url'] : get_env('notification_sms_api_url');
        $apiKey     = !empty($config['api_key']) ? $config['api_key'] : get_env('notification_sms_api_key');
        $senderId   = !empty($config['sender_id']) ? $config['sender_id'] : get_env('notification_sms_sender_id');

        if (empty($gatewayUrl) || $gatewayUrl === '--') {
            return ['status' => false, 'message' => 'SMS Gateway API URL is not configured.'];
        }

        if (empty($to) || $to === '--') {
            return ['status' => false, 'message' => 'Target phone number is missing.'];
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $to);
        $url = str_replace(
            ['{to}', '{phone}', '{number}', '{message}', '{api_key}', '{sender_id}'],
            [urlencode($cleanPhone), urlencode($cleanPhone), urlencode($cleanPhone), urlencode($message), urlencode($apiKey), urlencode($senderId)],
            $gatewayUrl
        );

        if (!function_exists('curl_init')) {
            return ['status' => false, 'message' => 'PHP cURL extension is required for SMS Gateway.'];
        }

        $ch = curl_init();
        if (strpos($gatewayUrl, '{to}') !== false || strpos($gatewayUrl, '{phone}') !== false || strpos($gatewayUrl, '{message}') !== false) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        } else {
            curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            $postData = [
                'api_key'   => $apiKey,
                'to'        => $cleanPhone,
                'number'    => $cleanPhone,
                'message'   => $message,
                'sender_id' => $senderId
            ];
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PipraPay-Notification/3.0');
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['status' => true, 'message' => 'SMS dispatched successfully.'];
        }
        return ['status' => false, 'message' => $err ?: "SMS API returned HTTP $httpCode: $res"];
    }

    function pp_dispatch_notification($event, $data = []) {
        $currency = $data['currency'] ?? 'BDT';
        $amount = $data['amount'] ?? '0.00';
        $trxId = $data['trx_id'] ?? ($data['transaction_id'] ?? 'N/A');
        $gateway = $data['gateway'] ?? ($data['payment_method'] ?? 'PipraPay');
        $customerName = $data['customer_name'] ?? ($data['name'] ?? 'Customer');
        $customerEmail = $data['customer_email'] ?? ($data['email'] ?? '');
        $customerPhone = $data['customer_phone'] ?? ($data['phone'] ?? '');
        $invoiceId = $data['invoice_id'] ?? ($data['pp_id'] ?? 'N/A');
        $siteName = get_env('brand-brand_name') ?: 'PipraPay';
        if ($siteName === '--') $siteName = 'PipraPay';

        // 1. Payment Success Event
        if ($event === 'payment.success') {
            // Admin Alerts - Telegram
            $tgToken = get_env('notification_telegram_token');
            $tgChat = get_env('notification_telegram_chat_id');
            $tgEvent = get_env('notification_event_admin_payment_success_telegram');
            if (!empty($tgToken) && $tgToken !== '--' && !empty($tgChat) && $tgChat !== '--' && $tgEvent !== 'no') {
                $tgMsg = "🎉 <b>Payment Received!</b>\n\n"
                       . "💰 <b>Amount:</b> {$amount} {$currency}\n"
                       . "💳 <b>Gateway:</b> {$gateway}\n"
                       . "🆔 <b>TrxID:</b> <code>{$trxId}</code>\n"
                       . "👤 <b>Customer:</b> {$customerName}\n"
                       . "📄 <b>Invoice:</b> #{$invoiceId}\n"
                       . "⏰ <b>Time:</b> " . date('d M Y, h:i A') . "\n\n"
                       . "🌐 <i>{$siteName} Payment Engine</i>";
                pp_send_telegram($tgMsg);
            }

            // Admin Alerts - Discord
            $dcWebhook = get_env('notification_discord_webhook');
            $dcEvent = get_env('notification_event_admin_payment_success_discord');
            if (!empty($dcWebhook) && $dcWebhook !== '--' && $dcEvent !== 'no') {
                $fields = [
                    ['name' => 'Amount', 'value' => "**{$amount} {$currency}**", 'inline' => true],
                    ['name' => 'Gateway', 'value' => (string)$gateway, 'inline' => true],
                    ['name' => 'TrxID', 'value' => "`{$trxId}`", 'inline' => true],
                    ['name' => 'Customer', 'value' => (string)$customerName, 'inline' => true],
                    ['name' => 'Invoice ID', 'value' => "#{$invoiceId}", 'inline' => true]
                ];
                pp_send_discord("🎉 Payment Received - {$amount} {$currency}", "A new payment has been completed successfully on {$siteName}.", $fields, 3066993);
            }

            // Admin Alerts - WhatsApp
            $waUrl = get_env('notification_whatsapp_api_url');
            $waEvent = get_env('notification_event_admin_payment_success_whatsapp');
            $adminPhone = get_env('notification_whatsapp_target_phone');
            if (!empty($waUrl) && $waUrl !== '--' && !empty($adminPhone) && $adminPhone !== '--' && $waEvent !== 'no') {
                $waMsg = "🎉 *Payment Received!*\n\n"
                       . "Amount: {$amount} {$currency}\n"
                       . "Gateway: {$gateway}\n"
                       . "TrxID: {$trxId}\n"
                       . "Customer: {$customerName}\n"
                       . "Invoice: #{$invoiceId}\n"
                       . "Time: " . date('d M Y, h:i A');
                pp_send_whatsapp($adminPhone, $waMsg);
            }

            // Admin Alerts - Email (SMTP)
            $smtpHost = get_env('notification_email_smtp_host');
            $emEvent = get_env('notification_event_admin_payment_success_email');
            if (!empty($smtpHost) && $smtpHost !== '--' && $emEvent !== 'no') {
                $adminEmail = get_env('notification_email_admin_recipients');
                if (empty($adminEmail) || $adminEmail === '--') {
                    $adminEmail = get_env('notification_email_smtp_user');
                }
                if (!empty($adminEmail) && $adminEmail !== '--') {
                    $emails = array_map('trim', explode(',', $adminEmail));
                    $subject = "🎉 Payment Received: {$amount} {$currency} (TrxID: {$trxId})";
                    $html = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                        <h2 style='color: #2fb344; margin-top: 0;'>🎉 Payment Received Successfully</h2>
                        <p>A new payment has been processed on <strong>{$siteName}</strong>.</p>
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                            <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; color: #666;'>Amount:</td><td style='padding: 8px 0; font-weight: bold; font-size: 16px;'>{$amount} {$currency}</td></tr>
                            <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; color: #666;'>Payment Method:</td><td style='padding: 8px 0;'>{$gateway}</td></tr>
                            <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; color: #666;'>Transaction ID:</td><td style='padding: 8px 0; font-family: monospace;'>{$trxId}</td></tr>
                            <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; color: #666;'>Customer Name:</td><td style='padding: 8px 0;'>{$customerName}</td></tr>
                            <tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px 0; color: #666;'>Invoice Number:</td><td style='padding: 8px 0;'>#{$invoiceId}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666;'>Date:</td><td style='padding: 8px 0;'>" . date('d M Y, h:i A') . "</td></tr>
                        </table>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='color: #888; font-size: 12px; margin-bottom: 0;'>This is an automated notification from {$siteName}.</p>
                    </div>";
                    foreach ($emails as $em) {
                        if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
                            pp_send_email($em, $subject, $html);
                        }
                    }
                }
            }

            // Customer Alerts - Email
            if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) && get_env('notification_event_customer_payment_success_email') !== 'no') {
                $subject = "Payment Receipt: {$amount} {$currency} - {$siteName}";
                $html = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #16a34a; margin: 0;'>Payment Receipt</h2>
                        <p style='color: #6b7280; font-size: 14px; margin-top: 5px;'>Thank you for your payment!</p>
                    </div>
                    <div style='background: #f9fafb; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;'>
                        <div style='font-size: 28px; font-weight: bold; color: #111827; text-align: center;'>{$amount} {$currency}</div>
                        <div style='text-align: center; color: #16a34a; font-size: 14px; font-weight: 500; margin-top: 4px;'>✓ Completed</div>
                    </div>
                    <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                        <tr style='border-bottom: 1px solid #f3f4f6;'><td style='padding: 10px 0; color: #6b7280;'>Transaction ID</td><td style='padding: 10px 0; text-align: right; font-family: monospace; font-weight: bold;'>{$trxId}</td></tr>
                        <tr style='border-bottom: 1px solid #f3f4f6;'><td style='padding: 10px 0; color: #6b7280;'>Payment Method</td><td style='padding: 10px 0; text-align: right;'>{$gateway}</td></tr>
                        <tr style='border-bottom: 1px solid #f3f4f6;'><td style='padding: 10px 0; color: #6b7280;'>Invoice No.</td><td style='padding: 10px 0; text-align: right;'>#{$invoiceId}</td></tr>
                        <tr><td style='padding: 10px 0; color: #6b7280;'>Date & Time</td><td style='padding: 10px 0; text-align: right;'>" . date('d M Y, h:i A') . "</td></tr>
                    </table>
                    <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;'>
                    <p style='color: #9ca3af; font-size: 12px; text-align: center; margin: 0;'>If you have any questions, please contact support.</p>
                </div>";
                pp_send_email($customerEmail, $subject, $html);
            }

            // Customer Alerts - SMS
            $smsUrl = get_env('notification_sms_api_url');
            if (!empty($smsUrl) && $smsUrl !== '--' && !empty($customerPhone) && get_env('notification_event_customer_payment_success_sms') !== 'no') {
                $smsMsg = "Thank you! Your payment of {$amount} {$currency} via {$gateway} (TrxID: {$trxId}) is successful. - {$siteName}";
                pp_send_sms($customerPhone, $smsMsg);
            }
        }

        // 2. Payment Failed Event
        if ($event === 'payment.failed') {
            $reason = $data['reason'] ?? 'Payment cancelled or verification failed.';

            $tgToken = get_env('notification_telegram_token');
            $tgChat = get_env('notification_telegram_chat_id');
            if (!empty($tgToken) && $tgToken !== '--' && !empty($tgChat) && $tgChat !== '--' && get_env('notification_event_admin_payment_failed_telegram') === 'yes') {
                $tgMsg = "❌ <b>Payment Failed / Cancelled</b>\n\n"
                       . "💰 <b>Amount:</b> {$amount} {$currency}\n"
                       . "💳 <b>Gateway:</b> {$gateway}\n"
                       . "👤 <b>Customer:</b> {$customerName}\n"
                       . "⚠️ <b>Reason:</b> {$reason}\n"
                       . "⏰ <b>Time:</b> " . date('d M Y, h:i A');
                pp_send_telegram($tgMsg);
            }

            $dcWebhook = get_env('notification_discord_webhook');
            if (!empty($dcWebhook) && $dcWebhook !== '--' && get_env('notification_event_admin_payment_failed_discord') === 'yes') {
                $fields = [
                    ['name' => 'Amount', 'value' => "{$amount} {$currency}", 'inline' => true],
                    ['name' => 'Gateway', 'value' => (string)$gateway, 'inline' => true],
                    ['name' => 'Customer', 'value' => (string)$customerName, 'inline' => true],
                    ['name' => 'Reason', 'value' => (string)$reason, 'inline' => false]
                ];
                pp_send_discord("❌ Payment Failed", "A payment attempt was cancelled or failed.", $fields, 15158332);
            }
        }

        // 3. Device Offline Event
        if ($event === 'device.offline') {
            $deviceName = $data['device_name'] ?? 'Companion Phone';
            $deviceId = $data['device_id'] ?? 'N/A';
            $simSlot = $data['sim_slot'] ?? 'SIM 1';
            $lastSeen = $data['last_seen'] ?? date('d M Y, h:i A');

            $tgToken = get_env('notification_telegram_token');
            $tgChat = get_env('notification_telegram_chat_id');
            if (!empty($tgToken) && $tgToken !== '--' && !empty($tgChat) && $tgChat !== '--' && get_env('notification_event_device_offline_telegram') !== 'no') {
                $tgMsg = "🚨 <b>DEVICE OFFLINE ALERT!</b>\n\n"
                       . "📱 <b>Device:</b> {$deviceName}\n"
                       . "🆔 <b>Device ID:</b> <code>{$deviceId}</code>\n"
                       . "📶 <b>SIM Slot:</b> {$simSlot}\n"
                       . "⏳ <b>Last Seen:</b> {$lastSeen}\n\n"
                       . "⚠️ <i>Please check your companion phone network, battery or app state immediately!</i>";
                pp_send_telegram($tgMsg);
            }

            $dcWebhook = get_env('notification_discord_webhook');
            if (!empty($dcWebhook) && $dcWebhook !== '--' && get_env('notification_event_device_offline_discord') !== 'no') {
                $fields = [
                    ['name' => 'Device Name', 'value' => $deviceName, 'inline' => true],
                    ['name' => 'SIM Slot', 'value' => $simSlot, 'inline' => true],
                    ['name' => 'Last Seen', 'value' => $lastSeen, 'inline' => true]
                ];
                pp_send_discord("🚨 Companion Device Offline", "Device `{$deviceId}` is no longer responding. SMS sync is paused.", $fields, 15105570);
            }

            $waUrl = get_env('notification_whatsapp_api_url');
            $adminPhone = get_env('notification_whatsapp_target_phone');
            if (!empty($waUrl) && $waUrl !== '--' && !empty($adminPhone) && $adminPhone !== '--' && get_env('notification_event_device_offline_whatsapp') !== 'no') {
                $waMsg = "🚨 *DEVICE OFFLINE ALERT!*\n\n"
                       . "Device: {$deviceName}\n"
                       . "SIM: {$simSlot}\n"
                       . "Last Seen: {$lastSeen}\n"
                       . "Please check phone app!";
                pp_send_whatsapp($adminPhone, $waMsg);
            }
        }

        // 4. Device Low Battery Event
        if ($event === 'device.low_battery') {
            $deviceName = $data['device_name'] ?? 'Companion Phone';
            $batteryLevel = $data['battery_level'] ?? '10%';

            $tgToken = get_env('notification_telegram_token');
            $tgChat = get_env('notification_telegram_chat_id');
            if (!empty($tgToken) && $tgToken !== '--' && !empty($tgChat) && $tgChat !== '--' && get_env('notification_event_device_battery_telegram') !== 'no') {
                $tgMsg = "🪫 <b>Low Battery Alert!</b>\n\n"
                       . "📱 <b>Device:</b> {$deviceName}\n"
                       . "🔋 <b>Battery Level:</b> {$batteryLevel}\n"
                       . "⚠️ Please plug in your companion phone charger to prevent payment interruptions.";
                pp_send_telegram($tgMsg);
            }

            $dcWebhook = get_env('notification_discord_webhook');
            if (!empty($dcWebhook) && $dcWebhook !== '--' && get_env('notification_event_device_battery_discord') !== 'no') {
                $fields = [
                    ['name' => 'Device', 'value' => $deviceName, 'inline' => true],
                    ['name' => 'Battery Level', 'value' => "**{$batteryLevel}**", 'inline' => true]
                ];
                pp_send_discord("🪫 Low Battery Warning", "Companion phone battery is running critically low.", $fields, 16753920);
            }
        }
    }

    /* =====================================================================
     *  PENDING PAYMENT APPROVAL REQUESTS
     *  Telegram: real Approve / Cancel buttons (one tap, works through a Telegram webhook)
     *  Discord / WhatsApp: tappable Approve / Cancel links that open a secure review page
     * ===================================================================== */

    function pp_notify_secret(){
        $s = get_env('notification_action_secret');
        if (empty($s) || $s === '--' || strlen($s) < 32) {
            $s = bin2hex(random_bytes(24));
            set_env('notification_action_secret', $s);
        }
        return (string)$s;
    }

    function pp_notify_sig($parts){
        return substr(hash_hmac('sha256', implode('|', $parts), pp_notify_secret()), 0, 16);
    }

    /* signed link to the review page (valid 3 days); $act only highlights a button, the page asks for the final choice */
    function pp_notify_review_link($ref, $act = 'approve'){
        global $site_url;
        $exp = time() + 3 * 86400;
        $sig = pp_notify_sig(['review', (string)$ref, (string)$exp]);
        return $site_url . '?pp-notify=review&a=' . rawurlencode($act) . '&r=' . rawurlencode((string)$ref) . '&e=' . $exp . '&s=' . $sig;
    }

    /* one HTTPS call (cURL). A test can replace it with $GLOBALS['pp_notify_http_mock'] */
    function pp_notify_http_json($url, $payload, $timeout = 8){
        if (isset($GLOBALS['pp_notify_http_mock']) && is_callable($GLOBALS['pp_notify_http_mock'])) {
            return call_user_func($GLOBALS['pp_notify_http_mock'], $url, $payload);
        }
        if (!function_exists('curl_init')) {
            return ['code' => 0, 'body' => '', 'error' => 'PHP cURL extension is required.', 'errno' => 0];
        }
        $do = function($verify) use ($url, $payload, $timeout){
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_USERAGENT => 'PipraPay-Notification/3.0',
            ]);
            $body = curl_exec($ch);
            $r = ['code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => (string)$body, 'error' => curl_error($ch), 'errno' => curl_errno($ch)];
            curl_close($ch);
            return $r;
        };
        $r = $do(true);
        if (!empty($r['errno']) && in_array($r['errno'], [35, 51, 58, 60, 77, 83], true)) {
            $r = $do(false);    // hosting without a CA bundle (same behaviour as the other notification senders)
        }
        return $r;
    }

    function pp_telegram_api($method, $payload, $token = null){
        $token = !empty($token) ? $token : get_env('notification_telegram_token');
        if (empty($token) || $token === '--') {
            return ['ok' => false, 'description' => 'Telegram bot token is not configured.'];
        }
        $r = pp_notify_http_json('https://api.telegram.org/bot' . trim((string)$token) . '/' . $method, $payload);
        $j = json_decode((string)$r['body'], true);
        if (is_array($j)) { return $j; }
        return ['ok' => false, 'description' => !empty($r['error']) ? $r['error'] : 'Telegram API returned HTTP ' . $r['code']];
    }

    /* tells Telegram where to send button taps (needs an https:// website) */
    function pp_telegram_connect_buttons($token = null){
        global $site_url;
        $url = $site_url . '?pp-notify=telegram';
        if (stripos($url, 'https://') !== 0) {
            set_env('notification_telegram_webhook_status', 'error - Telegram buttons need your website to use https://');
            return ['ok' => false, 'description' => 'Your website address must start with https:// for Telegram buttons.'];
        }
        $r = pp_telegram_api('setWebhook', [
            'url' => $url,
            'secret_token' => substr(pp_notify_secret(), 0, 32),
            'allowed_updates' => ['callback_query'],
            'max_connections' => 5,
        ], $token);
        set_env('notification_telegram_webhook_status', !empty($r['ok']) ? 'ok' : 'error - ' . substr((string)($r['description'] ?? 'unknown'), 0, 120));
        set_env('notification_telegram_webhook_time', (string)time());
        return $r;
    }

    /* a small note per order (key/value in settings): when the customer started waiting, whether we alerted, and the
       payment details needed if the admin approves an order that is still "initiated" */
    function pp_notify_marker_key($ref){ return 'pp_na_' . substr(md5((string)$ref), 0, 20); }

    function pp_notify_marker_get($ref){
        $v = get_env(pp_notify_marker_key($ref));
        if ($v === '' || $v === false || $v === null) { return null; }
        $p = explode('|', (string)$v);
        if (count($p) < 8) { return null; }
        return ['t0' => (int)$p[0], 'alerted' => (int)$p[1], 'gid' => $p[2], 'fee' => $p[3], 'disc' => $p[4], 'net' => $p[5], 'cur' => $p[6], 'sk' => $p[7]];
    }

    function pp_notify_marker_set($ref, $m){
        $clean = function($v){ return str_replace('|', '', (string)$v); };
        set_env(pp_notify_marker_key($ref), implode('|', [(int)$m['t0'], (int)$m['alerted'], $clean($m['gid']), $clean($m['fee']), $clean($m['disc']), $clean($m['net']), $clean($m['cur']), $clean($m['sk'])]));
    }

    function pp_notify_gateway_name($gid, $brandId){
        global $db_prefix;
        $gw = json_decode(getData($db_prefix . 'gateways', 'WHERE gateway_id = :gid AND brand_id = :bid', '* FROM', [':gid' => $gid, ':bid' => $brandId]), true);
        return $gw['response'][0]['name'] ?? 'Payment';
    }

    /* Called each time a waiting customer's page checks for the payment and finds nothing yet.
       After the chosen number of minutes (default 5) it sends ONE approval request. $live = what the page is paying with. */
    function pp_notify_slow_check($row, $live){
        $ref = (string)($row['ref'] ?? '');
        if ($ref === '' || ($row['status'] ?? '') !== 'initiated') { return; }

        $enabled = (get_env('notification_event_admin_payment_slow_telegram') !== 'no')
                || (get_env('notification_event_admin_payment_slow_discord') === 'yes')
                || (get_env('notification_event_admin_payment_slow_whatsapp') === 'yes');
        if (!$enabled) { return; }

        $minutes = (int)get_env('notification_slow_alert_minutes');
        if ($minutes < 1 || $minutes > 60) { $minutes = 5; }

        $m = pp_notify_marker_get($ref);
        if ($m === null) {                                   // first check: start the clock
            pp_notify_marker_set($ref, ['t0' => time(), 'alerted' => 0] + $live);
            return;
        }
        if (!empty($m['alerted']) || (time() - $m['t0']) < $minutes * 60) { return; }

        $m = array_merge($m, $live, ['alerted' => 1]);
        pp_notify_marker_set($ref, $m);                      // mark first, so two quick checks never send twice

        $ctx = pp_notify_ctx($row);
        $ctx['amount']  = preg_replace('/\.00$/', '', (string)money_round($live['net'])) . ' ' . $live['cur'];
        $ctx['gateway'] = pp_notify_gateway_name($live['gid'], $row['brand_id']);
        $ctx['event']   = 'slow';
        $ctx['title']   = 'Payment is taking longer than ' . $minutes . ' minutes';
        $ctx['lead']    = 'A customer on **' . (get_env('geneal-brand-settings-title') ?: 'PipraPay') . '** is still waiting and no matching payment SMS was found yet.';
        $ctx['note']    = 'The customer is still on the payment page. Check your payment SMS / app, then choose:';
        pp_notify_send_pending($ctx);
    }

    function pp_notify_find($ref){
        global $db_prefix;
        $r = json_decode(getData($db_prefix . 'transaction', 'WHERE ref = :ref', '* FROM', [':ref' => (string)$ref]), true);
        return (!empty($r['status']) && !empty($r['response'][0])) ? $r['response'][0] : null;
    }

    function pp_notify_ctx($row){
        global $db_prefix;
        $gw = json_decode(getData($db_prefix . 'gateways', 'WHERE gateway_id = :gid AND brand_id = :bid', '* FROM', [':gid' => $row['gateway_id'], ':bid' => $row['brand_id']]), true);
        $ci = json_decode((string)($row['customer_info'] ?? ''), true);
        if (!is_array($ci)) { $ci = []; }
        $clean = function($v){ $v = trim((string)$v); return ($v === '--') ? '' : $v; };
        $net = $clean($row['local_net_amount'] ?? '');
        if ($net === '' || (float)$net <= 0) { $net = (string)($row['amount'] ?? '0'); }
        $cur = $clean($row['local_currency'] ?? '');
        if ($cur === '') { $cur = $clean($row['currency'] ?? 'BDT'); }
        $gwName = $gw['response'][0]['name'] ?? 'Payment';
        if (($row['status'] ?? '') === 'initiated') {            // still waiting: use what the customer's page was paying with
            $mk = pp_notify_marker_get($row['ref']);
            if ($mk !== null && (float)$mk['net'] > 0) {
                $net = $mk['net']; $cur = $mk['cur'];
                $gwName = pp_notify_gateway_name($mk['gid'], $row['brand_id']);
            }
        }
        return [
            'ref'     => (string)$row['ref'],
            'amount'  => preg_replace('/\.00$/', '', (string)money_round($net)) . ' ' . $cur,
            'gateway' => $gwName,
            'trx'     => $clean($row['trx_id'] ?? ''),
            'slip'    => $clean($row['trx_slip'] ?? ''),
            'name'    => $clean($ci['name'] ?? ''),
            'email'   => $clean($ci['email'] ?? ''),
            'mobile'  => $clean($ci['mobile'] ?? ''),
            'sender'  => $clean($row['sender'] ?? ''),
        ];
    }

    /* Builds and sends the approval request. $only = 'telegram'|'discord'|'whatsapp'|null (all enabled).
       $cfg can carry live form values for the test button. Returns the result of every channel tried. */
    function pp_notify_send_pending($ctx, $only = null, $cfg = [], $respectToggles = true){
        $h = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $ref = (string)$ctx['ref'];
        $siteName = get_env('geneal-brand-settings-title') ?: 'PipraPay';
        if ($siteName === '--') { $siteName = 'PipraPay'; }
        $when = date('d M Y, h:i A') . ' UTC';
        $out = [];
        $ev    = (string)($ctx['event'] ?? 'pending');                 // 'pending' or 'slow'
        $title = (string)($ctx['title'] ?? 'Payment needs your approval');
        $note  = (string)($ctx['note'] ?? 'Check your payment SMS / app, then choose:');
        $lead  = (string)($ctx['lead'] ?? ('A payment on **' . $siteName . '** is waiting for your decision.'));

        /* ---- Telegram: real buttons ---- */
        $tgToken = $cfg['tg_token'] ?? get_env('notification_telegram_token');
        $tgChat  = $cfg['tg_chat']  ?? get_env('notification_telegram_chat_id');
        $tgOn    = $respectToggles ? (get_env('notification_event_admin_payment_' . $ev . '_telegram') !== 'no') : true;
        if (($only === null || $only === 'telegram') && $tgOn && !empty($tgToken) && $tgToken !== '--' && !empty($tgChat) && $tgChat !== '--') {
            $lastTry = (int)get_env('notification_telegram_webhook_time');
            if (get_env('notification_telegram_webhook_status') !== 'ok' && (time() - $lastTry) > 600) {
                pp_telegram_connect_buttons($tgToken);
            }
            $text = "⏳ <b>" . $h($title) . "</b>\n\n"
                  . "💰 <b>Amount:</b> " . $h($ctx['amount']) . "\n"
                  . "💳 <b>Gateway:</b> " . $h($ctx['gateway']) . "\n";
            if ($ctx['trx'] !== '')    { $text .= "🆔 <b>TrxID:</b> <code>" . $h($ctx['trx']) . "</code>\n"; }
            if ($ctx['sender'] !== '') { $text .= "📱 <b>Sender:</b> " . $h($ctx['sender']) . "\n"; }
            $cust = trim($ctx['name'] . ($ctx['mobile'] !== '' ? ' (' . $ctx['mobile'] . ')' : ''));
            if ($cust !== '')          { $text .= "👤 <b>Customer:</b> " . $h($cust) . "\n"; }
            $text .= "📄 <b>Order:</b> #" . $h($ref) . "\n⏰ <b>Time:</b> " . $h($when) . "\n\n"
                   . $h($note);
            $markup = ['inline_keyboard' => [[
                ['text' => '✅ Approve', 'callback_data' => 'pp:a:' . $ref . ':' . substr(pp_notify_sig(['tg', 'a', $ref]), 0, 10)],
                ['text' => '❌ Cancel',  'callback_data' => 'pp:c:' . $ref . ':' . substr(pp_notify_sig(['tg', 'c', $ref]), 0, 10)],
            ]]];
            $payload = ['chat_id' => trim((string)$tgChat), 'parse_mode' => 'HTML', 'reply_markup' => $markup];
            $topic = $cfg['tg_topic'] ?? get_env('notification_telegram_topic_id');
            if (!empty($topic) && $topic !== '--') { $payload['message_thread_id'] = trim((string)$topic); }

            $res = null;
            if ($ctx['slip'] !== '' && stripos($ctx['slip'], 'http') === 0) {       // show the screenshot right inside the chat
                $p = $payload; $p['photo'] = $ctx['slip']; $p['caption'] = mb_substr($text, 0, 1000);
                $res = pp_telegram_api('sendPhoto', $p, $tgToken);
            }
            if ($res === null || empty($res['ok'])) {
                $p = $payload; $p['text'] = $text;
                if ($ctx['slip'] !== '') { $p['text'] .= "\n🖼 <a href=\"" . $h($ctx['slip']) . "\">Open screenshot</a>"; }
                $res = pp_telegram_api('sendMessage', $p, $tgToken);
            }
            $out['telegram'] = ['status' => !empty($res['ok']), 'message' => !empty($res['ok']) ? 'Sent' : (string)($res['description'] ?? 'Telegram error')];
        }

        /* ---- Discord: link buttons ---- */
        $dcUrl = $cfg['dc_webhook'] ?? get_env('notification_discord_webhook');
        $dcOn  = $respectToggles ? (get_env('notification_event_admin_payment_' . $ev . '_discord') === 'yes') : true;
        $linkA = pp_notify_review_link($ref, 'approve');
        $linkC = pp_notify_review_link($ref, 'cancel');
        if (($only === null || $only === 'discord') && $dcOn && !empty($dcUrl) && $dcUrl !== '--') {
            $fields = [
                ['name' => 'Amount', 'value' => '**' . $ctx['amount'] . '**', 'inline' => true],
                ['name' => 'Gateway', 'value' => (string)$ctx['gateway'], 'inline' => true],
                ['name' => 'Order', 'value' => '#' . $ref, 'inline' => true],
            ];
            if ($ctx['trx'] !== '')    { $fields[] = ['name' => 'TrxID', 'value' => '`' . $ctx['trx'] . '`', 'inline' => true]; }
            if ($ctx['name'] !== '' || $ctx['mobile'] !== '') { $fields[] = ['name' => 'Customer', 'value' => trim($ctx['name'] . ' ' . $ctx['mobile']), 'inline' => true]; }
            $extra = ['components' => [[
                'type' => 1,
                'components' => [
                    ['type' => 2, 'style' => 5, 'label' => 'Approve', 'url' => $linkA],
                    ['type' => 2, 'style' => 5, 'label' => 'Cancel',  'url' => $linkC],
                ],
            ]]];
            if ($ctx['slip'] !== '' && stripos($ctx['slip'], 'http') === 0) { $extra['image'] = $ctx['slip']; }
            $desc = $lead . "\n\n[✅ Approve]({$linkA})  •  [❌ Cancel]({$linkC})";
            $res = pp_send_discord('⏳ ' . $title . ' - ' . $ctx['amount'], $desc, $fields, 16753920, !empty($cfg['dc_webhook']) ? $cfg['dc_webhook'] : null, $extra);
            $out['discord'] = ['status' => !empty($res['status']), 'message' => (string)($res['message'] ?? '')];
        }

        /* ---- WhatsApp: tappable links ---- */
        $waPhone = $cfg['wa_phone'] ?? get_env('notification_whatsapp_target_phone');
        $waUrl   = $cfg['wa_url']   ?? get_env('notification_whatsapp_api_url');
        $waOn    = $respectToggles ? (get_env('notification_event_admin_payment_' . $ev . '_whatsapp') === 'yes') : true;
        if (($only === null || $only === 'whatsapp') && $waOn && !empty($waUrl) && $waUrl !== '--' && !empty($waPhone) && $waPhone !== '--') {
            $msg = "⏳ *" . $title . "*\n" . trim(str_replace('**', '', $lead)) . "\n\n"
                 . "Amount: {$ctx['amount']}\nGateway: {$ctx['gateway']}\n";
            if ($ctx['trx'] !== '')    { $msg .= "TrxID: {$ctx['trx']}\n"; }
            if ($ctx['name'] !== '')   { $msg .= "Customer: {$ctx['name']} {$ctx['mobile']}\n"; }
            $msg .= "Order: #{$ref}\nTime: {$when}\n";
            if ($ctx['slip'] !== '')   { $msg .= "Screenshot: {$ctx['slip']}\n"; }
            $msg .= "\n✅ Approve:\n{$linkA}\n\n❌ Cancel:\n{$linkC}";
            $wcfg = [];
            if (!empty($cfg['wa_url']))    { $wcfg['api_url'] = $cfg['wa_url']; }
            if (!empty($cfg['wa_key']))    { $wcfg['api_key'] = $cfg['wa_key']; }
            if (!empty($cfg['wa_sender'])) { $wcfg['sender_id'] = $cfg['wa_sender']; }
            $res = pp_send_whatsapp($waPhone, $msg, $wcfg);
            $out['whatsapp'] = ['status' => !empty($res['status']), 'message' => (string)($res['message'] ?? '')];
        }

        return $out;
    }

    /* a real order that just became "pending": notify the enabled channels */
    function pp_notify_pending_request($trx){
        $ref = (string)($trx['pp_id'] ?? '');
        if ($ref === '') { return; }
        $row = pp_notify_find($ref);
        if (!$row || ($row['status'] ?? '') !== 'pending') { return; }
        pp_notify_send_pending(pp_notify_ctx($row));
    }

    /* the "Send test approval request" button: sends a sample request; its buttons only show "test OK" */
    function pp_notify_send_test_approval($which, $cfg = []){
        $ctx = [
            'ref' => 'TEST', 'amount' => '100 BDT', 'gateway' => 'Bangla QR (test)', 'trx' => '', 'slip' => '',
            'name' => 'Test Customer', 'email' => '', 'mobile' => '01700000000', 'sender' => '',
        ];
        if ($which === 'telegram' && !empty($cfg['tg_token'])) {
            pp_telegram_connect_buttons($cfg['tg_token']);
        }
        $res = pp_notify_send_pending($ctx, $which, $cfg, false);
        if (empty($res[$which])) {
            return ['status' => false, 'message' => 'Please fill in the settings of this channel first.'];
        }
        return $res[$which];
    }

    /* approve = completed, cancel = canceled. Same effect as the buttons on the admin transaction page. */
    function pp_notify_apply($ref, $act){
        global $db_prefix;
        $row = pp_notify_find($ref);
        if (!$row) { return ['ok' => false, 'code' => 'missing', 'message' => 'Order not found.']; }
        $from = (string)($row['status'] ?? '');
        $marker = null;
        if ($from === 'initiated') {
            $marker = pp_notify_marker_get($ref);
            if ($marker === null || empty($marker['alerted'])) {
                return ['ok' => false, 'code' => 'waiting', 'message' => 'This order is still waiting for the customer.'];
            }
        } elseif ($from !== 'pending') {
            return ['ok' => false, 'code' => 'done', 'message' => 'This order is already ' . ($from !== '' ? $from : 'processed') . '.'];
        }
        $new = ($act === 'approve') ? 'completed' : 'canceled';
        if ($marker !== null && $act === 'approve') {
            // the customer never reached "pending": fill in the payment details we saved while they were waiting
            updateData($db_prefix . 'transaction',
                ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'sender_key', 'status', 'updated_date'],
                [$marker['fee'], $marker['disc'], $marker['net'], $marker['cur'], $marker['gid'], $marker['sk'], $new, getCurrentDatetime('Y-m-d H:i:s')],
                'id = ' . (int)$row['id'] . ' AND status = \'initiated\'');
        } else {
            updateData($db_prefix . 'transaction', ['status', 'updated_date'], [$new, getCurrentDatetime('Y-m-d H:i:s')],
                'id = ' . (int)$row['id'] . ' AND status = \'' . ($from === 'initiated' ? 'initiated' : 'pending') . '\'');
        }

        $now = pp_notify_find($ref);
        if (!$now || ($now['status'] ?? '') !== $new) {
            return ['ok' => false, 'code' => 'race', 'message' => 'This order was just handled by someone else.'];
        }

        $brand = json_decode(getData($db_prefix . 'brands', 'WHERE brand_id = :bid', '* FROM', [':bid' => $now['brand_id']]), true);
        $tz = $brand['response'][0]['timezone'] ?? '--';
        if ($tz === '--' || $tz === '') { $tz = 'Asia/Dhaka'; }
        $ctx = pp_notify_ctx($now);
        $ci = json_decode((string)($now['customer_info'] ?? ''), true);
        if (!is_array($ci)) { $ci = []; }
        $meta = json_decode((string)($now['metadata'] ?? ''), true);
        if (!is_array($meta)) { $meta = []; }
        $total = money_sub(money_add($now['amount'], $now['processing_fee']), $now['discount_amount']);

        do_action('transactions.updated', [[
            'pp_id' => $now['ref'],
            'full_name' => $ci['name'] ?? 'N/A',
            'email_address' => $ci['email'] ?? 'N/A',
            'mobile_number' => $ci['mobile'] ?? 'N/A',
            'gateway' => $ctx['gateway'],
            'amount' => money_round($now['amount']),
            'fee' => money_round($now['processing_fee']),
            'discount_amount' => money_round($now['discount_amount']),
            'total' => money_round($total),
            'local_net_amount' => money_round($now['local_net_amount']),
            'currency' => $now['currency'],
            'local_currency' => $now['local_currency'],
            'metadata' => $meta,
            'sender' => $now['sender'],
            'transaction_id' => $now['trx_id'],
            'status' => $now['status'],
            'date' => convertUTCtoUserTZ($now['created_date'], $tz, 'M d, Y h:i A'),
        ]]);

        return ['ok' => true, 'code' => $new, 'message' => ($new === 'completed') ? 'Payment approved.' : 'Payment cancelled.'];
    }

    function pp_notify_route($what){
        if ($what === 'telegram') { pp_notify_route_telegram(); return; }
        if ($what === 'review')   { pp_notify_route_review(false); return; }
        if ($what === 'act')      { pp_notify_route_review(true); return; }
        http_response_code(404);
        echo 'Not found';
    }

    /* Telegram sends a button tap here. Returns what it did (used by the tests). */
    function pp_notify_tg_handle_callback($cq){
        $log = [];
        $id   = (string)($cq['id'] ?? '');
        $data = (string)($cq['data'] ?? '');
        $from = $cq['from'] ?? [];
        $msg  = $cq['message'] ?? [];
        $chat = $msg['chat'] ?? [];
        $answer = function($text, $alert = false) use ($id, &$log){
            $log[] = ['answer', $text];
            pp_telegram_api('answerCallbackQuery', ['callback_query_id' => $id, 'text' => $text, 'show_alert' => $alert]);
        };

        if ($data === 'pp:noop') { $answer('Already handled.'); return $log; }
        if (!preg_match('/^pp:([ac]):([A-Za-z0-9_-]{1,40}):([a-f0-9]{10})$/', $data, $m)) {
            if ($data !== '') { $answer('Unknown button.'); }
            return $log;
        }
        $act = $m[1]; $ref = $m[2];
        if (!hash_equals(substr(pp_notify_sig(['tg', $act, $ref]), 0, 10), $m[3])) { $answer('Invalid button.', true); return $log; }

        // only the chat that was configured may use the buttons
        $cfgChat = trim((string)get_env('notification_telegram_chat_id'));
        $chatId  = (string)($chat['id'] ?? '');
        $chatUn  = '@' . ltrim((string)($chat['username'] ?? ''), '@');
        if ($cfgChat === '' || ($cfgChat !== $chatId && strcasecmp($cfgChat, $chatUn) !== 0)) { $answer('Not allowed from this chat.', true); return $log; }

        $approvers = array_filter(array_map('trim', explode(',', (string)get_env('notification_telegram_approvers'))));
        if (!empty($approvers) && !in_array((string)($from['id'] ?? ''), $approvers, true)) { $answer('You are not allowed to approve payments.', true); return $log; }

        $who = !empty($from['username']) ? '@' . $from['username'] : trim((string)($from['first_name'] ?? 'someone'));

        if ($ref === 'TEST') { $answer('✅ Buttons are working (test message).', true); return $log; }

        $r = pp_notify_apply($ref, $act === 'a' ? 'approve' : 'cancel');
        $label = $r['ok'] ? (($act === 'a' ? '✅ Approved by ' : '❌ Cancelled by ') . $who) : ('ℹ️ ' . $r['message']);
        $answer($r['ok'] ? $r['message'] : $r['message'], !$r['ok']);

        if ($r['ok'] || $r['code'] === 'done' || $r['code'] === 'race') {
            $log[] = ['edit', $label];
            pp_telegram_api('editMessageReplyMarkup', [
                'chat_id' => $chat['id'] ?? '', 'message_id' => $msg['message_id'] ?? 0,
                'reply_markup' => ['inline_keyboard' => [[['text' => mb_substr($label, 0, 60), 'callback_data' => 'pp:noop']]]],
            ]);
        }
        return $log;
    }

    function pp_notify_route_telegram(){
        header('Content-Type: application/json');
        $hdr = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        if (!is_string($hdr) || !hash_equals(substr(pp_notify_secret(), 0, 32), $hdr)) {
            http_response_code(403);
            echo '{}';
            return;
        }
        $upd = $_POST;
        if (empty($upd)) {
            $upd = json_decode((string)file_get_contents('php://input'), true);
            if (!is_array($upd)) { $upd = []; }
        }
        if (!empty($upd['callback_query']) && is_array($upd['callback_query'])) {
            pp_notify_tg_handle_callback($upd['callback_query']);
        }
        echo '{}';
    }

    function pp_notify_page($title, $bodyHtml, $code = 200){
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
           . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title><style>'
           . 'body{margin:0;background:#eef5ff;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#0f172a;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:16px;box-sizing:border-box}'
           . '.c{width:100%;max-width:440px;background:#fff;border-radius:22px;box-shadow:0 20px 50px rgba(30,64,175,.15);padding:24px}'
           . 'h1{font-size:19px;margin:0 0 14px}.r{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #eef1f5;font-size:14px}.r span:first-child{color:#64748b}.r span:last-child{text-align:right;word-break:break-all;font-weight:600}'
           . 'img{width:100%;border-radius:12px;border:1px solid #e2e8f0;margin-top:14px}.b{display:flex;gap:10px;margin-top:18px}.b button,.b a{flex:1;height:50px;border:0;border-radius:13px;font-size:16px;font-weight:700;cursor:pointer;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}'
           . '.ok{background:#16a34a}.no{background:#dc2626}.pre{outline:3px solid rgba(37,99,235,.35)}.m{margin:0 0 10px;font-size:14px;line-height:1.55;color:#475569}.big{font-size:17px;font-weight:700}'
           . '</style></head><body><div class="c">' . $bodyHtml . '</div></body></html>';
    }

    function pp_notify_route_review($isPost){
        $src = $isPost ? $_POST : $_GET;
        $ref = (string)($src['r'] ?? '');
        $exp = (string)($src['e'] ?? '');
        $sig = (string)($src['s'] ?? '');
        $hint = ((string)($src['a'] ?? '') === 'cancel') ? 'cancel' : 'approve';
        $h = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

        if ($ref === '' || !ctype_digit($exp) || (int)$exp < time() || !hash_equals(pp_notify_sig(['review', $ref, $exp]), $sig)) {
            pp_notify_page('Link expired', '<h1>This link is not valid</h1><p class="m">It may have expired (links last 3 days) or been changed. Open the order from your admin panel instead.</p>', 403);
            return;
        }

        if ($ref === 'TEST') {
            pp_notify_page('Test OK', '<h1>✅ Test link works</h1><p class="m">Real payment requests open a page here where you can Approve or Cancel the payment.</p>');
            return;
        }

        $noLogin = (get_env('notification_pending_links_no_login') === 'yes');
        $logged  = !empty($GLOBALS['global_user_login']);
        $perm = null; $role = 'staff';
        if (!$noLogin) {
            if (!$logged) {
                global $site_url, $path_login;
                $login = $site_url . ($path_login ?? 'login');
                pp_notify_page('Login needed', '<h1>Please log in first</h1><p class="m">For safety, payments can only be approved after you log in to the admin panel. Log in, then tap the Approve / Cancel link again.</p><div class="b"><a class="ok" href="' . $h($login) . '">Log in</a></div>', 401);
                return;
            }
            $perm = json_decode($GLOBALS['global_response_permission']['response'][0]['permission'] ?? '[]', true);
            $role = $GLOBALS['global_user_response']['response'][0]['role'] ?? 'staff';
        }

        $row = pp_notify_find($ref);
        if (!$row) { pp_notify_page('Not found', '<h1>Order not found</h1>', 404); return; }
        $ctx = pp_notify_ctx($row);

        if ($isPost) {
            $act = ((string)($_POST['act'] ?? '') === 'cancel') ? 'cancel' : (((string)($_POST['act'] ?? '') === 'approve') ? 'approve' : '');
            $tok = (string)($_POST['t'] ?? '');
            if ($act === '' || empty($_SESSION['pp_notify_csrf']) || !hash_equals((string)$_SESSION['pp_notify_csrf'], $tok)) {
                pp_notify_page('Expired', '<h1>Please try again</h1><p class="m">This page was open for too long. Tap the link in your message again.</p>', 400);
                return;
            }
            if (!$noLogin && !hasPermission($perm, 'transaction', $act === 'approve' ? 'approve' : 'cancel', $role)) {
                pp_notify_page('No permission', '<h1>No permission</h1><p class="m">Your admin account is not allowed to do this.</p>', 403);
                return;
            }
            $r = pp_notify_apply($ref, $act);
            $icon = $r['ok'] ? ($act === 'approve' ? '✅' : '❌') : 'ℹ️';
            pp_notify_page('Done', '<h1>' . $icon . ' ' . $h($r['message']) . '</h1><p class="m">Order #' . $h($ref) . ' - ' . $h($ctx['amount']) . '</p>');
            return;
        }

        if (($row['status'] ?? '') === 'initiated' && (($mk = pp_notify_marker_get($ref)) === null || empty($mk['alerted']))) {
            pp_notify_page('Waiting', '<h1>⏳ Still waiting for the customer</h1><p class="m">Order #' . $h($ref) . ' has not reached the approval stage.</p>');
            return;
        }
        if (($row['status'] ?? '') !== 'pending' && ($row['status'] ?? '') !== 'initiated') {
            pp_notify_page('Already done', '<h1>ℹ️ Already ' . $h($row['status']) . '</h1><p class="m">Order #' . $h($ref) . ' - ' . $h($ctx['amount']) . '</p>');
            return;
        }

        $_SESSION['pp_notify_csrf'] = bin2hex(random_bytes(16));
        $rows = '<div class="r"><span>Amount</span><span>' . $h($ctx['amount']) . '</span></div>'
              . '<div class="r"><span>Gateway</span><span>' . $h($ctx['gateway']) . '</span></div>'
              . '<div class="r"><span>Order</span><span>#' . $h($ref) . '</span></div>';
        if ($ctx['trx'] !== '')    { $rows .= '<div class="r"><span>TrxID</span><span>' . $h($ctx['trx']) . '</span></div>'; }
        if ($ctx['name'] !== '')   { $rows .= '<div class="r"><span>Customer</span><span>' . $h($ctx['name']) . '</span></div>'; }
        if ($ctx['mobile'] !== '') { $rows .= '<div class="r"><span>Phone</span><span>' . $h($ctx['mobile']) . '</span></div>'; }
        $img = ($ctx['slip'] !== '') ? '<a href="' . $h($ctx['slip']) . '" target="_blank" rel="noopener"><img src="' . $h($ctx['slip']) . '" alt="Payment screenshot"></a>' : '';
        $form = '<form method="post" action="?pp-notify=act" onsubmit="return confirm(\'Are you sure?\')">'
              . '<input type="hidden" name="r" value="' . $h($ref) . '"><input type="hidden" name="e" value="' . $h($exp) . '"><input type="hidden" name="s" value="' . $h($sig) . '">'
              . '<input type="hidden" name="t" value="' . $h($_SESSION['pp_notify_csrf']) . '">'
              . '<div class="b"><button class="ok' . ($hint === 'approve' ? ' pre' : '') . '" name="act" value="approve">✅ Approve</button>'
              . '<button class="no' . ($hint === 'cancel' ? ' pre' : '') . '" name="act" value="cancel">❌ Cancel</button></div></form>';
        pp_notify_page('Payment approval', '<h1>⏳ Payment needs your approval</h1>' . $rows . $img
            . '<p class="m" style="margin-top:14px">Compare the amount and time with your payment SMS before you approve.</p>' . $form);
    }

    // Register Automatic Multi-Channel Notification Hook for Transaction Events
    add_action('transactions.updated', function ($transactions) {
        if (empty($transactions) || !is_array($transactions)) {
            return;
        }

        foreach ($transactions as $trx) {
            $status = strtolower($trx['status'] ?? '');
            if ($status === 'completed' || $status === 'successful' || $status === 'success') {
                pp_dispatch_notification('payment.success', [
                    'amount'         => $trx['amount'] ?? ($trx['total'] ?? '0.00'),
                    'currency'       => $trx['currency'] ?? 'BDT',
                    'gateway'        => $trx['gateway'] ?? 'Payment Gateway',
                    'trx_id'         => $trx['transaction_id'] ?? ($trx['pp_id'] ?? 'N/A'),
                    'invoice_id'     => $trx['pp_id'] ?? 'N/A',
                    'customer_name'  => $trx['full_name'] ?? 'Customer',
                    'customer_email' => $trx['email_address'] ?? '',
                    'customer_phone' => $trx['mobile_number'] ?? ($trx['sender'] ?? '')
                ]);
            } elseif ($status === 'canceled' || $status === 'cancelled' || $status === 'failed') {
                pp_dispatch_notification('payment.failed', [
                    'amount'         => $trx['amount'] ?? ($trx['total'] ?? '0.00'),
                    'currency'       => $trx['currency'] ?? 'BDT',
                    'gateway'        => $trx['gateway'] ?? 'Payment Gateway',
                    'trx_id'         => $trx['transaction_id'] ?? ($trx['pp_id'] ?? 'N/A'),
                    'invoice_id'     => $trx['pp_id'] ?? 'N/A',
                    'customer_name'  => $trx['full_name'] ?? 'Customer',
                    'customer_email' => $trx['email_address'] ?? '',
                    'customer_phone' => $trx['mobile_number'] ?? ($trx['sender'] ?? ''),
                    'reason'         => 'Transaction was ' . $status
                ]);
            } elseif ($status === 'pending') {
                try { pp_notify_pending_request($trx); } catch (\Throwable $e) { error_log('pp_notify_pending_request: ' . $e->getMessage()); }
            }
        }
    });


