STATUS_LABELS = {
    "main": "メイン",
    "sub": "サブ",
    "all": "全員",
    "absent": "なし",
}

NUMBER_TYPE_LABELS = {
    "intro": "オープニング",
    "main": "メインナンバー",
    "all": "全員もの",
    "curtain_call": "カーテンコール",
}

def number_type_label(value: str | None) -> str:
    if not value:
        return ""
    return NUMBER_TYPE_LABELS.get(value, value)
