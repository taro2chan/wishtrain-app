<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WishTrain2026</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="main-content">

        <h1>WishTrain2026</h1>

        <!-- メンバー -->
        <div class="page-card">
            <h2>メンバー</h2>

            <div class="table-wrap">
                <table class="clean-table">

                    <thead>
                        <tr>
                            <th>代</th>
                            <th>なまえ</th>
                            <th>氏名</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <?= h($m['generation'])?>代
                            </td>
                            <td>
                                <?= h($m['dance_name'])?>
                            </td>
                            <td>
                                <?= h($m['member_name'])?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- ナンバー -->
        <div class="page-card">
            <h2>ナンバー</h2>

            <div class="table-wrap">
                <table class="clean-table">

                    <thead>
                        <tr>
                            <th>ナンバー名</th>
                            <th>チーム</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($numbers as $n): ?>
                        <tr>
                            <td>
                                <?= h($n['number_name'])?>
                            </td>
                            <td>
                                <?= h($n['team'])?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- リハーサル -->
        <div class="page-card">
            <h2>リハーサル</h2>

            <div class="table-wrap">
                <table class="clean-table rehearsal-table">

                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>種類</th>
                            <th>スタジオ</th>
                            <th>メイン</th>
                            <th>サブ</th>
                            <th>曲</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rehearsalMap as $r): ?>
                        <tr>
                            <td>
                                <?= h($r['rehearsal_date'])?>
                            </td>

                            <td>
                                <?php
    $typeMap = [
        '通常WS' => '通常WS',
        '上島JAZZ WS形式' => 'WS形式',
        '7/4イベントリハーサル' => 'リハ',
        '全体練習' => '全体練習',
        '7/4イベント当日' => '当日',
    ];
    $type = $r['rehearsal_type'];
    $displayType = $typeMap[$type] ?? $type;
?>
                                <?= h($displayType)?>
                            </td>

                            <td>
                                <?= h($r['studio'])?>
                            </td>
                            <td>
                                <?= h($r['main_room'])?>
                                <?= h($r['main_time'])?>
                            </td>
                            <td>
                                <?= h($r['sub_room'])?>
                                <?= h($r['sub_time'])?>
                            </td>

                            <td>
                                <?php
    $mainNumbers = [];

    if (!empty($r['numbers'])) {
        foreach ($r['numbers'] as $n) {
            if (($n['status'] ?? '') === 'main' || ($n['status'] ?? '') === 'all') {
                $mainNumbers[] = $n['name'];
            }
        }
    }
?>
                                <?php foreach ($mainNumbers as $name): ?>
                                <div>
                                    <?= h($name)?>
                                </div>
                                <?php
    endforeach; ?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- 出演表 -->
        <div class="page-card">
            <h2>出演表</h2>

            <div class="table-wrap">
                <table class="clean-table cast-table">

                    <thead>
                        <tr>
                            <th>代</th>
                            <th>なまえ</th>
                            <?php foreach ($castNumbers as $n): ?>
                            <th><span class="vertical-head">
                                    <?= h($n['number_name'])?>
                                </span></th>
                            <?php
endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <?= h($m['generation'])?>代
                            </td>
                            <td>
                                <?= h($m['dance_name'])?>
                            </td>

                            <?php foreach ($castNumbers as $n): ?>
                            <?php $key = $m['member_name'] . '||' . $n['number_id']; ?>
                            <td class="center-cell">
                                <?= isset($memberNumberSet[$key]) ? '●' : ''?>
                            </td>
                            <?php
    endforeach; ?>

                        </tr>
                        <?php
endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

    </div>

</body>

</html>