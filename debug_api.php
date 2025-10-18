<?php
// Endpoint de diagnostic rapide pour aider à trouver pourquoi get_documents.php échoue
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$response = ['success' => false, 'checks' => []];

// Include DB
try {
    require_once 'db_connect.php';
    $response['checks']['db_connect'] = 'ok';
} catch (Exception $e) {
    $response['checks']['db_connect'] = 'failed: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}

// Session info
$response['session'] = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'company_id' => $_SESSION['company_id'] ?? $_SESSION['entreprise_id'] ?? null,
    'all_session' => $_SESSION
];

// Test simple query: check if table documents exists
try {
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    $all = $stmt->fetchAll(PDO::FETCH_NUM);
    foreach ($all as $t) $tables[] = $t[0];
    $response['checks']['tables'] = $tables;

    if (in_array('documents', $tables)) {
        $response['checks']['documents_table'] = 'exists';
        // show columns
        $cols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM documents");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['checks']['documents_columns'] = $cols;

        // try a small select
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS c FROM documents");
            $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['checks']['documents_count'] = $cnt['c'];
        } catch (Exception $e) {
            $response['checks']['documents_count_error'] = $e->getMessage();
        }
    } else {
        $response['checks']['documents_table'] = 'missing';
    }

} catch (Exception $e) {
    $response['checks']['tables_error'] = $e->getMessage();
}

$response['success'] = true;

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>