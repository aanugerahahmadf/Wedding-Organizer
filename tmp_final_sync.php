<?php

// Comprehensive Language Sync Script v6 (Nuclear Cleanup)
// Removing all corrupted entries from the JSON files.

$basePath = 'd:/Weeding-Organizer-CBIR/AdminPanel_Mobile_Application';
$langPath = "$basePath/lang";

$homeTranslations = [
    'ar' => 'الرئيسية',
    'bg' => 'Начало',
    'bn' => 'হোম',
    'bs' => 'Početna',
    'ca' => 'Inici',
    'cs' => 'Domů',
    'cy' => 'Hafan',
    'da' => 'Hjem',
    'de' => 'Startseite',
    'el' => 'Αρχική',
    'en' => 'Home',
    'en_US' => 'Home',
    'es' => 'Inicio',
    'et' => 'Avaleht',
    'fa' => 'خانه',
    'fi' => 'Koti',
    'fil' => 'Home',
    'fr' => 'Accueil',
    'he' => 'בית',
    'hi' => 'होम',
    'hr' => 'Početna',
    'hu' => 'Kezdőlap',
    'hy' => 'Գլխավոր',
    'id' => 'Beranda',
    'is' => 'Heim',
    'it' => 'Home',
    'ja' => 'ホーム',
    'ka' => 'მთავარი',
    'km' => 'ទំព័រដើម',
    'ko' => '홈',
    'ku' => 'Destpêk',
    'lt' => 'Pradžia',
    'lv' => 'Sākums',
    'mk' => 'Почетна',
    'mn' => 'Нүүр',
    'ms' => 'Utama',
    'my' => 'ပင်มစာမျက်หนา',
    'nb' => 'Hjem',
    'nl' => 'Home',
    'nn' => 'Heim',
    'pl' => 'Główna',
    'pt_BR' => 'Início',
    'pt_PT' => 'Início',
    'ro' => 'Acasă',
    'ru' => 'Главная',
    'sk' => 'Domov',
    'sl' => 'Domov',
    'sq' => 'Ballina',
    'sr' => 'Почетна',
    'sv' => 'Hem',
    'sw' => 'Nyumbani',
    'th' => 'หน้าแรก',
    'tr' => 'Anasayfa',
    'uk' => 'Головна',
    'ur' => 'ہوم',
    'uz' => 'Bosh sahifa',
    'vi' => 'Trang chủ',
    'zh_CN' => '首页',
    'zh_TW' => '首頁',
    'zh' => '首页',
];

$vendorPath = "$basePath/vendor/filament";
$packages = [];
if (is_dir($vendorPath)) {
    foreach (scandir($vendorPath) as $pkg) {
        if ($pkg === '.' || $pkg === '..') continue;
        $pkgPath = "$vendorPath/$pkg";
        $langDir = "$pkgPath/resources/lang";
        
        if ($pkg === 'filament') {
            $namespaces = ['filament-panels', 'panels'];
        } elseif ($pkg === 'support') {
             $namespaces = ['filament', 'support'];
        } else {
            $namespaces = ["filament-$pkg", $pkg];
        }
        
        if (is_dir($langDir)) {
            $packages[] = [
                'namespaces' => $namespaces,
                'path' => realpath($langDir)
            ];
        }
    }
}

function flattenArray($array, $prefix = '') {
    $result = [];
    if (!is_array($array)) return $result;
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $prefix . $key . '.'));
        } else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}

function getPhpFilesRecursive($dir) {
    $files = [];
    if (!is_dir($dir)) return $files;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = realpath($file->getPathname());
        }
    }
    return $files;
}

foreach (glob("$langPath/*.json") as $jsonFile) {
    $locale = basename($jsonFile, '.json');
    echo "Processing locale: $locale\n";
    
    $jsonData = json_decode(file_get_contents($jsonFile), true) ?: [];
    
    // NUCLEAR CLEANUP: Remove ANY key that looks like a vendor key or has corrupted parts
    foreach ($jsonData as $key => $val) {
        if (strpos($key, '::') !== false || is_array($val) || preg_match('/\.[0-9]+$/', $key)) {
             unset($jsonData[$key]);
        }
    }

    // Core Translations for Home/Beranda (Top Priority)
    if (isset($homeTranslations[$locale])) {
        $translation = $homeTranslations[$locale];
        $jsonData['Home'] = $translation;
        $jsonData['Beranda'] = $translation;
        $jsonData['Access Admin Home'] = "Access Admin $translation";
        $jsonData['Access Admin Beranda'] = "Access Admin $translation";
        $jsonData['Dashboard'] = $translation;
    }
    
    foreach ($packages as $pkgInfo) {
        $enDir = $pkgInfo['path'] . DIRECTORY_SEPARATOR . 'en';
        if (!is_dir($enDir)) continue;
        
        $enFiles = getPhpFilesRecursive($enDir);
        foreach ($enFiles as $enFilePath) {
            $relPath = str_replace([$enDir . DIRECTORY_SEPARATOR, '.php'], ['', ''], $enFilePath);
            $relPath = str_replace('\\', '/', $relPath);
            
            $enData = include $enFilePath;
            if (!is_array($enData)) $enData = [];
            
            $targetFilePath = $pkgInfo['path'] . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath) . '.php';
            $targetData = file_exists($targetFilePath) ? (include $targetFilePath) : [];
            if (!is_array($targetData)) $targetData = [];
            
            $mergedData = array_replace_recursive($enData, $targetData);
            $flatData = flattenArray($mergedData);
            
            foreach ($pkgInfo['namespaces'] as $ns) {
                foreach ($flatData as $key => $val) {
                    $jsonKey = "$ns::$relPath.$key";
                    $jsonData[$jsonKey] = $val;
                }
            }
        }
    }
    
    // Manual overrides for persistent issues
    if (str_replace(['id', 'id_new'], '', $locale) !== $locale) {
        $jsonData['filament::components/pagination.fields.records_per_page.options.all'] = 'Semua';
        $jsonData['Welcome To Admin Panel Devi Make Up'] = 'Selamat Datang Di Panel Admin Devi Make Up';
        $jsonData['Manage your wedding organizer business efficiently with our comprehensive management system.'] = 'Kelola bisnis wedding organizer Anda secara efisien dengan sistem manajemen kami yang komprehensif.';
        $jsonData['Manage Packages & Content'] = 'Kelola Paket & Konten';
        $jsonData['Track Orders & Customer Details'] = 'Lacak Pesanan & Detail Pelanggan';
    }
    
    ksort($jsonData);
    file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

echo "Done! Synchronization complete.\n";
