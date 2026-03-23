<?php
$members = $db->query("
    SELECT member_name, dance_name, generation
    FROM members
    ORDER BY generation, member_name
")->fetchAll(PDO::FETCH_ASSOC);

$numbers = $db->query("
    SELECT number_id, number_name, team, number_type
    FROM numbers
    ORDER BY number_id
")->fetchAll(PDO::FETCH_ASSOC);

$rehearsalMap = [];

$stmt = $db->query("
    SELECT
        r.rehearsal_id,
        r.rehearsal_date,
        r.rehearsal_type,
        r.studio,
        r.main_room,
        r.main_time,
        r.sub_room,
        r.sub_time,
        n.number_name,
        rn.status,
        rn.main_seq
    FROM rehearsals r
    LEFT JOIN rehearsal_numbers rn
      ON r.rehearsal_id = rn.rehearsal_id
    LEFT JOIN numbers n
      ON rn.number_id = n.number_id
    ORDER BY r.rehearsal_date
");

foreach ($stmt as $row) {
    $id = $row['rehearsal_id'];

    if (!isset($rehearsalMap[$id])) {
        $rehearsalMap[$id] = $row;
        $rehearsalMap[$id]['numbers'] = [];
    }

    if ($row['number_name']) {
        $rehearsalMap[$id]['numbers'][] = [
            'name' => $row['number_name'],
            'status' => $row['status'],
        ];
    }
}

/* 出演表用のナンバー（オープニング除外） */
$castNumbers = $db->query("
    SELECT number_id, number_name
    FROM numbers
    WHERE number_type != 'intro'
    ORDER BY number_id
")->fetchAll(PDO::FETCH_ASSOC);

/* 出演表用の所属セット */
$memberNumbers = $db->query("
    SELECT member_name, number_id
    FROM member_numbers
")->fetchAll(PDO::FETCH_ASSOC);

$memberNumberSet = [];

foreach ($memberNumbers as $row) {
    $key = $row['member_name'] . '||' . $row['number_id'];
    $memberNumberSet[$key] = true;
}