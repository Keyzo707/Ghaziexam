<?php
// Include connection file and check session
e...

// Validate id_ujian parameter and prevent undefined index warnings
$id_ujian = null;
if (isset($_GET['id_ujian']) && $_GET['id_ujian'] !== '') {
    $id_ujian = intval($_GET['id_ujian']);
} elseif (isset($_GET['ujian']) && $_GET['ujian'] !== '') {
    $id_ujian = intval($_GET['ujian']);
} else {
    echo "<div class='alert alert-danger'>Error: ID Ujian tidak diberikan.</div>";
    exit;
}
if ($id_ujian <= 0) {
    echo "<div class='alert alert-danger'>Error: ID Ujian tidak valid.</div>";
    exit;
}

// Use $id_ujian for later code that requires the id_ujian value...