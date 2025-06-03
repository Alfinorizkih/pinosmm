<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$api_key = 'a8hn)Y7(a@YEq@Sl)8(y3';
$order_log_file = __DIR__ . '/orders_' . $_SESSION['username'] . '.json';

function log_order($data, $file) {
    $existing = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $existing[] = $data;
    file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $link = $_POST['link'];
    $service = $_POST['service'];
    $quantity = $_POST['quantity'];

    $post_data = [
        'key' => $api_key,
        'action' => 'add',
        'service' => $service,
        'link' => $link,
        'quantity' => $quantity
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.smmraja.com/api/v3');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    $response = curl_exec($ch);
    curl_close($ch);

    $order_result = json_decode($response, true);
    if (isset($order_result['order'])) {
        $order_result['time'] = date('Y-m-d H:i:s');
        log_order($order_result, $order_log_file);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];

    $post_data = [
        'key' => $api_key,
        'action' => 'status',
        'order' => $order_id
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.smmraja.com/api/v3');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    $response = curl_exec($ch);
    curl_close($ch);

    $status_result = json_decode($response, true);
}

// Fetch service list for dropdown
$services = [];
$post_data = [
    'key' => $api_key,
    'action' => 'services'
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.smmraja.com/api/v3');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
$services_response = curl_exec($ch);
curl_close($ch);
$services = json_decode($services_response, true);

$post_data = [
    'key' => $api_key,
    'action' => 'balance'
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.smmraja.com/api/v3');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
$balance_response = curl_exec($ch);
curl_close($ch);
$balance_result = json_decode($balance_response, true);
$order_history = file_exists($order_log_file) ? json_decode(file_get_contents($order_log_file), true) : [];
?><!DOCTYPE html><html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Panel SMM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Selamat datang, <?php echo $_SESSION['username']; ?>!</h1>
        <nav><a href="logout.php">Logout</a></nav>
    </header><main>
    <section>
        <h2>Saldo Akun</h2>
        <?php if (isset($balance_result)) echo "<pre>" . print_r($balance_result, true) . "</pre>"; ?>
    </section>

    <section>
        <h2>Pesan Layanan SMM</h2>
        <form method="post" action="">
            <label for="link">Link:</label>
            <input type="text" id="link" name="link" required>

            <label for="service">Pilih Layanan:</label>
            <select id="service" name="service" required>
                <?php
                foreach ($services as $s) {
                    echo '<option value="' . htmlspecialchars($s['service']) . '">' . htmlspecialchars($s['name']) . ' - ' . htmlspecialchars($s['rate']) . '/1000</option>';
                }
                ?>
            </select>

            <label for="quantity">Jumlah:</label>
            <input type="number" id="quantity" name="quantity" required>

            <input type="submit" name="order" value="Kirim Pesanan">
        </form>
        <?php if (isset($order_result)) echo "<h3>Hasil Pemesanan:</h3><pre>" . print_r($order_result, true) . "</pre>"; ?>
    </section>

    <section>
        <h2>Cek Status Pesanan</h2>
        <form method="post" action="">
            <label for="order_id">ID Pesanan:</label>
            <input type="number" id="order_id" name="order_id" required>

            <input type="submit" name="status" value="Cek Status">
        </form>
        <?php if (isset($status_result)) echo "<h3>Status Pesanan:</h3><pre>" . print_r($status_result, true) . "</pre>"; ?>
    </section>

    <section>
        <h2>Histori Pesanan Anda</h2>
        <?php if (!empty($order_history)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Waktu</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($order_history as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order']); ?></td>
                        <td><?php echo htmlspecialchars($order['time']); ?></td>
                        <td><?php echo htmlspecialchars($order['quantity'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($order['status'] ?? 'Terkirim'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Belum ada histori pesanan.</p>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Panel SMM Anda.</p>
</footer>

</body>
</html>