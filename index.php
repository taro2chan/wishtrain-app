<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Tokyo');

function h(string|int|float|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function parseEndTime(?string $timeText): ?string
{
    if (!$timeText || !str_contains($timeText, '-')) {
        return null;
    }

    $parts = explode('-', $timeText);
    $end = trim((string)end($parts));

    if (!preg_match('/^\d{1,2}:\d{2}$/', $end)) {
        return null;
    }

    return $end;
}

function buildEndDateTime(array $rehearsal): ?DateTime
{
    $date = $rehearsal['rehearsal_date'] ?? null;
    if (!$date) {
        return null;
    }

    $candidates = array_filter([
        parseEndTime($rehearsal['main_time'] ?? null),
        parseEndTime($rehearsal['sub_time'] ?? null),
    ]);

    if (!$candidates) {
        return null;
    }

    $endTime = max($candidates);
    $dt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $endTime);

    return $dt ?: null;
}

function pickNextRehearsal(array $rows, DateTime $now): ?array
{
    $today = $now->format('Y-m-d');

    foreach ($rows as $row) {
        $date = $row['rehearsal_date'] ?? '';
        if ($date === '') {
            continue;
        }

        if ($date > $today) {
            return $row;
        }

        if ($date < $today) {
            continue;
        }

        $end = buildEndDateTime($row);

        if ($end === null || $now <= $end) {
            return $row;
        }
    }

    return null;
}

function statusLabel(string $status): string
{
    return match ($status) {
            'all' => '全員',
            'main' => 'メイン',
            'sub' => 'サブ',
            'absent' => '休み',
            default => $status,
        };
}

function numberTypeLabel(?string $type): string
{
    return match ($type) {
            'main' => 'メイン',
            'sub' => 'サブ',
            'intro' => 'オープニング',
            default => (string)$type,
        };
}

try {
    $db = new PDO('sqlite:' . __DIR__ . '/wishtrain.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $memberName = trim((string)($_GET['member_name'] ?? ''));

    $membersStmt = $db->query("
        SELECT member_name, dance_name, generation
        FROM members
        ORDER BY generation, member_name
    ");
    $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

    $numbersStmt = $db->query("
        SELECT number_id, number_name, team, number_type
        FROM numbers
        ORDER BY number_id
    ");
    $numbers = $numbersStmt->fetchAll(PDO::FETCH_ASSOC);

    $rehearsalsStmt = $db->query("
        SELECT
            rehearsal_id,
            rehearsal_date,
            rehearsal_type,
            studio,
            main_room,
            main_time,
            sub_room,
            sub_time
        FROM rehearsals
        ORDER BY rehearsal_date
    ");
    $rehearsals = $rehearsalsStmt->fetchAll(PDO::FETCH_ASSOC);

    $now = new DateTime();
    $nextRehearsal = pickNextRehearsal($rehearsals, $now);

    $selectedMember = null;
    if ($memberName !== '') {
        $selectedStmt = $db->prepare("
            SELECT member_name, dance_name, generation
            FROM members
            WHERE member_name = ?
        ");
        $selectedStmt->execute([$memberName]);
        $selectedMember = $selectedStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $memberRelatedNext = [];
    if ($selectedMember && $nextRehearsal) {
        $relatedStmt = $db->prepare("
            SELECT
                n.number_id,
                n.number_name,
                n.team,
                n.number_type,
                rn.status,
                rn.main_seq
            FROM member_numbers mn
            JOIN numbers n
              ON mn.number_id = n.number_id
            JOIN rehearsal_numbers rn
              ON rn.number_id = n.number_id
            WHERE mn.member_name = ?
              AND rn.rehearsal_id = ?
              AND rn.status != 'absent'
            ORDER BY
                CASE rn.status
                    WHEN 'all' THEN 0
                    WHEN 'main' THEN 1
                    WHEN 'sub' THEN 2
                    ELSE 3
                END,
                n.number_id
        ");
        $relatedStmt->execute([
            $selectedMember['member_name'],
            $nextRehearsal['rehearsal_id'],
        ]);
        $memberRelatedNext = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $castNumbersStmt = $db->query("
        SELECT number_id, number_name, team, number_type
        FROM numbers
        WHERE number_type != 'intro'
        ORDER BY number_id
    ");
    $castNumbers = $castNumbersStmt->fetchAll(PDO::FETCH_ASSOC);

    $memberNumbersStmt = $db->query("
        SELECT member_name, number_id
        FROM member_numbers
    ");
    $memberNumbers = $memberNumbersStmt->fetchAll(PDO::FETCH_ASSOC);

    $memberNumberSet = [];
    foreach ($memberNumbers as $row) {
        $key = $row['member_name'] . '||' . $row['number_id'];
        $memberNumberSet[$key] = true;
    }
}
catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>' . h($e->getMessage()) . '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WishTrain</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main-content">

        <div class="page-header">
            <div class="page-heading">
                <h1 class="page-title">WishTrain</h1>
            </div>
        </div>

        <div class="page-card">
            <h2>次回リハ</h2>

            <?php if ($nextRehearsal): ?>
            <div class="detail-list">
                <div class="detail-item">
                    <div class="detail-key">日付</div>
                    <div class="detail-value">
                        <?= h($nextRehearsal['rehearsal_date'])?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-key">種類</div>
                    <div class="detail-value">
                        <?= h($nextRehearsal['rehearsal_type'])?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-key">スタジオ</div>
                    <div class="detail-value">
                        <?= h($nextRehearsal['studio'])?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-key">メイン</div>
                    <div class="detail-value">
                        <?= h($nextRehearsal['main_room'])?>
                        <?= h($nextRehearsal['main_time'])?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-key">サブ</div>
                    <div class="detail-value">
                        <?= h($nextRehearsal['sub_room'])?>
                        <?= h($nextRehearsal['sub_time'])?>
                    </div>
                </div>
            </div>
            <?php
else: ?>
            <p class="empty-text">次回リハは見つかりませんでした。</p>
            <?php
endif; ?>
        </div>

        <div class="page-card">
            <h2>自分の関係あるナンバー</h2>

            <form method="get" class="member-select-form">
                <label for="member_name" class="form-label">メンバーを選択</label>
                <select name="member_name" id="member_name" class="select-box" onchange="this.form.submit()">
                    <option value="">選択してください</option>
                    <?php foreach ($members as $member): ?>
                    <option value="<?= h($member['member_name'])?>" <?=$memberName===$member['member_name']
                        ? 'selected' : ''?>
                        >
                        <?= h($member['generation'])?>代 /
                        <?= h($member['dance_name'])?>
                    </option>
                    <?php
endforeach; ?>
                </select>
                <noscript><button type="submit" class="button">表示</button></noscript>
            </form>

            <?php if ($selectedMember): ?>
            <div class="selected-member-card">
                <div class="selected-member-name">
                    <?= h($selectedMember['dance_name'] ?: $selectedMember['member_name'])?>
                </div>
                <div class="selected-member-meta">
                    <?= h($selectedMember['generation'])?>代 /
                    <?= h($selectedMember['member_name'])?>
                </div>
            </div>

            <?php if (!$nextRehearsal): ?>
            <p class="empty-text">次回リハが無いため表示できません。</p>
            <?php
    elseif (!$memberRelatedNext): ?>
            <p class="empty-text">関係するナンバーはありません。</p>
            <?php
    else: ?>
            <div class="table-wrap">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>ナンバー名</th>
                            <th>チーム</th>
                            <th>種別</th>
                            <th>状態</th>
                            <th>メイン回</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberRelatedNext as $row): ?>
                        <tr>
                            <td>
                                <?= h($row['number_name'])?>
                            </td>
                            <td>
                                <?= h($row['team'])?>
                            </td>
                            <td>
                                <?= h(numberTypeLabel($row['number_type']))?>
                            </td>
                            <td>
                                <?= h(statusLabel($row['status']))?>
                            </td>
                            <td>
                                <?= $row['main_seq'] !== null ? h((string)$row['main_seq']) . '回目' : ''?>
                            </td>
                        </tr>
                        <?php
        endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
    endif; ?>
            <?php
else: ?>
            <p class="empty-text">メンバーを選ぶと表示されます。</p>
            <?php
endif; ?>
        </div>

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
                        <?php foreach ($members as $row): ?>
                        <tr>
                            <td>
                                <?= h($row['generation'])?>代
                            </td>
                            <td>
                                <?= h($row['dance_name'])?>
                            </td>
                            <td>
                                <?= h($row['member_name'])?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card">
            <h2>ナンバー</h2>
            <div class="table-wrap">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>ナンバー名</th>
                            <th>チーム</th>
                            <th>種別</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($numbers as $row): ?>
                        <tr>
                            <td>
                                <?= h($row['number_name'])?>
                            </td>
                            <td>
                                <?= h($row['team'])?>
                            </td>
                            <td>
                                <?= h(numberTypeLabel($row['number_type']))?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card">
            <h2>リハーサル</h2>
            <div class="table-wrap">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>種類</th>
                            <th>スタジオ</th>
                            <th>メイン</th>
                            <th>サブ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rehearsals as $row): ?>
                        <tr>
                            <td>
                                <?= h($row['rehearsal_date'])?>
                            </td>
                            <td>
                                <?= h($row['rehearsal_type'])?>
                            </td>
                            <td>
                                <?= h($row['studio'])?>
                            </td>
                            <td>
                                <?= h($row['main_room'])?>
                                <?= h($row['main_time'])?>
                            </td>
                            <td>
                                <?= h($row['sub_room'])?>
                                <?= h($row['sub_time'])?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card">
            <h2>出演表</h2>

            <div class="cast-table-wrap">
                <table class="clean-table cast-table cast-table-mobile-fit">
                    <thead>
                        <tr>
                            <th class="gen-col">代</th>
                            <th class="member-col">なまえ</th>
                            <?php foreach ($castNumbers as $number): ?>
                            <th class="rot-col">
                                <div class="rot-label">
                                    <?= h($number['number_name'])?>
                                </div>
                            </th>
                            <?php
endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td class="gen-col">
                                <?= h($member['generation'])?>代
                            </td>
                            <td class="member-col">
                                <?= h($member['dance_name'] ?: $member['member_name'])?>
                            </td>

                            <?php foreach ($castNumbers as $number): ?>
                            <?php $key = $member['member_name'] . '||' . $number['number_id']; ?>
                            <td class="cast-cell">
                                <?= isset($memberNumberSet[$key]) ? '<span class="cast-dot">●</span>' : ''?>
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