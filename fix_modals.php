<?php
$files = [
    'c:\\xampp\\htdocs\\iseki_scan\\resources\\views\\users\\withdrawal\\index.blade.php',
    'c:\\xampp\\htdocs\\iseki_scan\\resources\\views\\admins\\withdrawal\\index.blade.php'
];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Find the modal blocks mapping to "Modal OK" and "Modal Masuk Rak" up to the closing @endif
    $pattern = '/^(\s*)\{\{-- Modal OK --\}\}.*?@endif\s*$/sm';
    preg_match($pattern, $content, $m1);
    if (empty($m1)) continue;
    
    $pattern2 = '/^(\s*)\{\{-- Modal Masuk Rak --\}\}.*?@endif\s*$/sm';
    preg_match($pattern2, $content, $m2);
    
    $modalOke = $m1[0];
    $modalReturn = $m2 ? $m2[0] : '';
    
    // Remove them from inside the tbody
    $content = str_replace($modalOke, '', $content);
    if ($modalReturn) $content = str_replace($modalReturn, '', $content);
    
    // Put them after </tbody></table></div></div></div> loop
    $loopModals = "\n\n@foreach(\$withdrawals as \$w)\n" . $modalOke . "\n" . $modalReturn . "\n@endforeach\n";
    $content = preg_replace('/(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*@endsection)/s', "</div>\n</div>\n</div>\n</div>\n" . $loopModals . "\n@endsection", $content, 1);
    
    file_put_contents($file, $content);
    echo "Fixed: $file\n";
}
