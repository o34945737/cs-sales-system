<?php
$path = 'resources/js/pages/Complaints/Index.vue';
$content = file_get_contents($path);
// Hapus baris spesifik yang menyebabkan pergeseran (Source 12%)
$content = preg_replace('/^\s+<th class="w-\[12%\] py-3 pl-5 pr-4">Source<\/th>\r?\n/m', '', $content);
file_put_content($path, $content);
echo "Fix applied successfully.";
