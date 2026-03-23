<?php
function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function statusLabel($s){
    return match ($s) {
        'all' => '全員',
        'main' => 'メイン',
        'sub' => 'サブ',
        'absent' => '休み',
        default => $s,
    };
}
