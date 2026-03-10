<?php

// Script: grant MySQL access for Android Emulator
// Run: php grant_emulator_access.php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check MySQL version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "MySQL Version: $version\n";

    // MySQL 8+ syntax
    if (version_compare($version, '8.0', '>=')) {
        $pdo->exec("CREATE USER IF NOT EXISTS 'root'@'10.0.2.2' IDENTIFIED BY ''");
        $pdo->exec("GRANT ALL PRIVILEGES ON `admin_panel_cbir`.* TO 'root'@'10.0.2.2'");
        $pdo->exec("CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY ''");
        $pdo->exec("GRANT ALL PRIVILEGES ON `admin_panel_cbir`.* TO 'root'@'%'");
    } else {
        // MySQL 5.x syntax
        $pdo->exec("GRANT ALL PRIVILEGES ON `admin_panel_cbir`.* TO 'root'@'10.0.2.2' IDENTIFIED BY ''");
        $pdo->exec("GRANT ALL PRIVILEGES ON `admin_panel_cbir`.* TO 'root'@'%' IDENTIFIED BY ''");
    }

    $pdo->exec('FLUSH PRIVILEGES');
    echo "✅ Akses MySQL untuk Android Emulator BERHASIL!\n";

    // Cek superadmin
    $stmt = $pdo->query("SELECT username, email FROM admin_panel_cbir.users WHERE username = 'superadmin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "✅ Superadmin ADA: username={$user['username']}, email={$user['email']}\n";
    } else {
        echo "⚠️  Superadmin BELUM ADA. Jalankan: php artisan migrate:fresh --seed\n";
    }

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
