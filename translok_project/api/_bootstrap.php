<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

function response_ok($data = []) {
  echo json_encode([
    'ok' => true,
    'data' => $data
  ]);
  exit;
}

function response_error($message = 'Terjadi kesalahan di server', $error = null) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => $message,
    'error' => $error
  ]);
  exit;
}

set_exception_handler(function($e){
  response_error('Terjadi kesalahan di server', $e->getMessage());
});

set_error_handler(function($severity, $message){
  response_error('Terjadi kesalahan di server', $message);
});
