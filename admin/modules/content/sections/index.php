<?php
require_once INCLUDES_PATH . '/init.php';
require_once __DIR__ . '/SectionController.php';

$db = Database::getInstance();
$controller = new SectionController($db);

$action = $_GET['action'] ?? 'list';
$pageId = $_GET['page_id'] ?? null;

header('Content-Type: application/json; charset=utf-8');

if ($action === 'list' && $pageId) {
    $response = $controller->getSectionsForPage($pageId);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = $controller->addSection(
        $data['page_id'] ?? null,
        $data['type'] ?? null,
        $data['data'] ?? []
    );
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
elseif ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = $controller->updateSection(
        $data['id'] ?? null,
        $data['data'] ?? []
    );
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = $controller->deleteSection($data['id'] ?? null);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
elseif ($action === 'reorder') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = $controller->reorderSections($data['orders'] ?? []);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
else {
    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}
?>