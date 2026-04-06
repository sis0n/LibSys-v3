<?php

/**
 * Role View Router (Internal Router)
 * File Path: libsys/src/views/[ROLE_FOLDER]/index.php
 * Aayusin nito ang pag-load ng partials (head, sidebar, footer).
 */

// Ang $action (e.g., 'myProfile') at $id (kung meron) ay galing sa dynamic_view_loader.php
$action = $action ?? 'dashboard';
$id = $id ?? null;

// 1. I-set ang variables na kailangan ng View at Sidebar
$data = [
  "title" => ucfirst($action),
  "currentPage" => $action // Para sa sidebar highlighting
];

// 2. Gawin ang filename (e.g., bookCatalog.php)
$action_file = $action . '.php';

// 3. I-check ang file
$view_path = __DIR__ . '/' . $action_file;

if (file_exists($view_path)) {

  // I-extract ang data para maging available ang $title at $currentPage sa LAHAT ng partials
  extract($data);

  // --- ITO ANG NAWAWALA MONG LOGIC ---
  // Kailangan mong i-load ang buong layout.

  // 4. I-load ang Head (kung saan nandoon ang CSS)
  // Path: Mula sa /views/[Role]/ papuntang /views/partials/
  require_once __DIR__ . '/../partials/head.php';

  // (Simulan ang main body/wrapper dito kung meron)
  // echo '<body class="..."><div class="main-wrapper">';

  // 5. I-load ang Sidebar
  require_once __DIR__ . '/../partials/sidebar.php';

  // 6. I-load ang Header (o kung ano man ang top navigation mo)
  // (Siguro may main content wrapper ka rin dito)
  require_once __DIR__ . '/../partials/header.php';

  // 7. I-load ang actual na Content File
  // (Ang $id variable ay available na rin dito)
  require_once $view_path;

  // (Isara ang main wrapper dito)
  // echo '</div></body>';

  // 8. I-load ang Footer (kung saan nandoon ang JS)
  require_once __DIR__ . '/../partials/footer.php';
} else {
  // 404 Error
  http_response_code(404);
  echo "404 Error: The requested page (" . htmlspecialchars($action_file) . ") was not found in this area.";

  // (Pwede mo rin i-load ang 404 view mo dito)
  // extract(["title" => "404 Not Found"]);
  // require_once __DIR__ . '/../errors/404.php';
}
