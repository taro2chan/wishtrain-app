from __future__ import annotations

from datetime import datetime
from flask import render_template, request

from db import get_db
from routes.common import STATUS_LABELS, number_type_label


def _parse_end_time(time_text: str | None) -> str | None:
    if not time_text:
        return None

    text = time_text.strip()
    if not text or "-" not in text:
        return None

    end_part = text.split("-")[-1].strip()
    if ":" not in end_part:
        return None

    hh, mm = end_part.split(":", 1)
    if not (hh.isdigit() and mm.isdigit()):
        return None

    return f"{int(hh):02d}:{int(mm):02d}"


def _build_rehearsal_end_dt(rehearsal_row) -> datetime | None:
    rehearsal_date = rehearsal_row["rehearsal_date"]
    if not rehearsal_date:
        return None

    candidates = [
        _parse_end_time(rehearsal_row["main_time"]),
        _parse_end_time(rehearsal_row["sub_time"]),
    ]
    candidates = [t for t in candidates if t]

    if not candidates:
        return None

    end_time = max(candidates)
    return datetime.strptime(f"{rehearsal_date} {end_time}", "%Y-%m-%d %H:%M")


def _pick_next_rehearsal(rows, now_dt: datetime):
    for row in rows:
        row_date = row["rehearsal_date"]
        if not row_date:
            continue

        row_date_dt = datetime.strptime(row_date, "%Y-%m-%d").date()

        if row_date_dt > now_dt.date():
            return row

        if row_date_dt < now_dt.date():
            continue

        # 当日の場合は終了時刻まで見る
        end_dt = _build_rehearsal_end_dt(row)

        # 時刻情報が無い場合は、その日は有効扱い
        if end_dt is None:
            return row

        if now_dt <= end_dt:
            return row

    return None


def fetch_dashboard_data(member_name: str | None = None) -> dict:
    now_dt = datetime.now()

    with get_db() as conn:
        member_list = conn.execute(
            """
            SELECT member_name, dance_name, generation
            FROM members
            ORDER BY generation, member_name
            """
        ).fetchall()

        rehearsal_rows = conn.execute(
            """
            SELECT *
            FROM rehearsals
            ORDER BY rehearsal_date
            """
        ).fetchall()

        next_rehearsal = _pick_next_rehearsal(rehearsal_rows, now_dt)

        selected_member = None
        member_related_next = []

        if member_name:
            selected_member = conn.execute(
                """
                SELECT member_name, dance_name, generation
                FROM members
                WHERE member_name = ?
                """,
                (member_name,),
            ).fetchone()

        if next_rehearsal and selected_member:
            member_related_next = conn.execute(
                """
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
                """,
                (member_name, next_rehearsal["rehearsal_id"]),
            ).fetchall()

    return {
        "member_list": member_list,
        "next_rehearsal": next_rehearsal,
        "selected_member": selected_member,
        "member_related_next": member_related_next,
    }


def init_app(app):
    @app.route("/")
    def home():
        selected_member_name = request.args.get("member_name", "").strip() or None
        data = fetch_dashboard_data(selected_member_name)

        return render_template(
            "home.html",
            **data,
            status_labels=STATUS_LABELS,
            number_type_label=number_type_label,
        )