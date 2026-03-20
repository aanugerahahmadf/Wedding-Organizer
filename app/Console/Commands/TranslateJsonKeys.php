<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateJsonKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:sync-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinksronisasi dan terjemahkan otomatis seluruh isi Column dan Table yang hilang dari JSON Bahasa lain menggunakan Stichoza Google Translate.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $langPath = base_path('lang');
        $idFile = $langPath . '/id.json';
        
        if (!File::exists($idFile)) {
            $this->error("File id.json tidak ditemukan.");
            return;
        }

        $this->info("Menscan source code untuk mencari text translasi baru...");
        // Scan semua __( ) dari seluruh Laravel (App, Livewire, Controllers, Views) Web + Mobile NativePHP
        $scanPaths = [
            app_path(),
            resource_path('views')
        ];
        
        $foundKeys = [];
        foreach ($scanPaths as $path) {
            $filesSrc = File::allFiles($path);
            foreach ($filesSrc as $file) {
                // Hanya periksa file php/blade
                if ($file->getExtension() === 'php') {
                    $content = $file->getContents();
                    preg_match_all("/__\(['\"](.*?)['\"]\)/", $content, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $key) {
                            $foundKeys[$key] = $key;
                        }
                    }
                }
            }
        }

        $idTranslations = json_decode(File::get($idFile), true) ?? [];
        $addedToId = false;

        foreach ($foundKeys as $k => $v) {
            if (!isset($idTranslations[$k])) {
                $idTranslations[$k] = $v;
                $addedToId = true;
                $this->line("Teks baru ditemukan: " . $k);
            }
        }

        if ($addedToId) {
            File::put($idFile, json_encode($idTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("id.json telah diupdate dengan kata kunci baru.");
        }

        $files = File::glob($langPath . '/*.json');
        
        foreach ($files as $file) {
            $fileName = basename($file);
            if ($fileName === 'id.json') continue;
            
            $targetLangCode = str_replace('.json', '', $fileName);
            $gCode = $targetLangCode;
            if ($gCode === 'zh') $gCode = 'zh-CN';
            if ($gCode === 'ko') $gCode = 'ko';
            
            $this->info("Menyinkronkan bahasa: " . strtoupper($targetLangCode) . "...");
            
            $content = File::get($file);
            $targetTranslations = json_decode($content, true) ?? [];
            
            $tr = new GoogleTranslate($gCode);
            $tr->setSource('id');
            
            $updated = false;
            
            foreach ($idTranslations as $key => $value) {
                if (!isset($targetTranslations[$key])) {
                    $this->line(" - Menerjemahkan '$key' ke $targetLangCode...");
                    try {
                        $translatedText = $tr->translate($key);
                        $targetTranslations[$key] = $translatedText;
                        $updated = true;
                    } catch (\Exception $e) {
                        $this->error("Gagal menerjemahkan '$key': " . $e->getMessage());
                    }
                    usleep(100000); 
                }
            }
            
            if ($updated) {
                File::put($file, json_encode($targetTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info("File $fileName berhasil diupdate!");
            } else {
                $this->info("File $fileName sudah sepenuhnya sinkron.");
            }
        }
        
        $this->info("Proses sinkronisasi dan Auto-Translate selesai!");
    }
}
