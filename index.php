<?php
include("../config/db.php"); // manggil 

header('Content-Type: application/json');

// ambil input dari user
$query = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// cari buku yang judulnya mirip dengan input user dan stoknya > 0
// ambil buku yang stoknya masih ada (stok > 0)
$stmt = $koneksi->prepare("SELECT judul FROM buku WHERE judul LIKE ? AND stok > 0 LIMIT 10");

// nambahin wildcard % di kedua sisi query untuk pencarian substring
$search_term = "%" . $query . "%";
$stmt->bind_param("s", $search_term);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    // kumpulin hasilnya
    // nge-escape output untuk keamanan XSS
    $suggestions[] = htmlspecialchars($row['judul']);
}

$stmt->close();
$koneksi->close();

// Kirim hasil sebagai JSON
echo json_encode($suggestions);
?>