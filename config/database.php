<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'online_food_ordering_system');

function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        // Suppress default fatal mysqli exception to display user-friendly troubleshooting guide
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            $isApi = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false);
            if ($isApi) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $conn->connect_error
                ]);
                exit();
            }

            die('
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Database Connection Issue</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; padding: 40px; color: #1e293b; }
                    .card { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 5px solid #ef4444; }
                    h2 { margin-top: 0; color: #dc2626; font-size: 1.5rem; }
                    .error-box { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 6px; font-family: monospace; margin: 15px 0; font-size: 0.9rem; }
                    ol { line-height: 1.8; color: #475569; padding-left: 20px; }
                    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0f172a; font-weight: 600; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>⚠️ Unable to connect to MySQL Database</h2>
                    <div class="error-box"><strong>Error:</strong> ' . htmlspecialchars($conn->connect_error) . '</div>
                    <p>Quick checks to resolve this:</p>
                    <ol>
                        <li>Make sure <strong>MySQL</strong> is started in your <strong>XAMPP Control Panel</strong>.</li>
                        <li>Verify that you have imported <code>SQL.sql</code> into phpMyAdmin (creating the <code>online_food_ordering_system</code> database).</li>
                        <li>If your MySQL root account has a password, update <code>DB_PASS</code> in <code>config/database.php</code>.</li>
                    </ol>
                </div>
            </body>
            </html>
            ');
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}


