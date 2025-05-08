<?php
// Restrict access to localhost only for security
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403);
    exit('Access denied.');
}
?>
<!DOCTYPE html>
<html>
  <head><title>Test</title></head>
  <body>
    <h1>If you see this, Apache is working!</h1>
  </body>
</html>