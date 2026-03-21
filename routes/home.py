from flask import render_template, request
from db import get_db
from routes.common import STATUS_LABELS, number_type_label

def fetch_dashboard_data(member_name: str | None = None) -> dict:
    with get_db() as conn:
        member_count = conn.execute("SELECT COUNT(*) FROM members").fetchone()[0]
        number_count = conn.execute("SELECT COUNT(*) FROM numbers").fetchone()[0]
        rehearsal_count = conn.execute("SELECT COUNT(*) FROM rehearsals").fetchone()[0]
        member_list = conn.execute(
            "SELECT member_name, dance_name, generation FROM members ORDER BY generation, member_name"
        ).fetchall()
        next_rehearsal = conn.execute(
            "SELECT * FROM rehearsals ORDER BY rehearsal_date LIMIT 1"
        ).fetchone()
        recent_rehearsals = conn.execute(
            "SELECT rehearsal_id, rehearsal_date, rehearsal_type, studio, main_time FROM rehearsals ORDER BY rehearsal_date LIMIT 5"
        ).fetchall()

        next_targets = []
        is_all_target = False
        member_related_next = []
        selected_member = None

        if next_rehearsal:
            next_targets = conn.execute(
                """
                SELECT n.number_id, n.number_name, n.team, n.number_type,
                       rn.status, rn.main_seq, rn.remark
                FROM rehearsal_numbers rn
                JOIN numbers n ON rn.number_id = n.number_id
                WHERE rn.rehearsal_id = ? AND rn.status != 'absent'
                ORDER BY CASE rn.status
                    WHEN 'all' THEN 0
                    WHEN 'main' THEN 1
                    WHEN 'sub' THEN 2
                    ELSE 3 END,
                    n.number_id
                """,
                (next_rehearsal["rehearsal_id"],),
            ).fetchall()

            is_all_target = any(row["status"] == "all" for row in next_targets)

            if member_name:
                selected_member = conn.execute(
                    "SELECT member_name, dance_name, generation FROM members WHERE member_name = ?",
                    (member_name,),
                ).fetchone()

                if selected_member:
                    member_related_next = conn.execute(
                        """
                        SELECT n.number_id, n.number_name, n.team, n.number_type,
                               rn.status, rn.main_seq, rn.remark
                        FROM member_numbers mn
                        JOIN numbers n ON mn.number_id = n.number_id
                        JOIN rehearsal_numbers rn ON rn.number_id = n.number_id
                        WHERE mn.member_name = ? AND rn.rehearsal_id = ? AND rn.status != 'absent'
                        ORDER BY CASE rn.status
                            WHEN 'all' THEN 0
                            WHEN 'main' THEN 1
                            WHEN 'sub' THEN 2
                            ELSE 3 END,
                            n.number_id
                        """,
                        (member_name, next_rehearsal["rehearsal_id"]),
                    ).fetchall()

    return {
        "member_count": member_count,
        "number_count": number_count,
        "rehearsal_count": rehearsal_count,
        "member_list": member_list,
        "next_rehearsal": next_rehearsal,
        "recent_rehearsals": recent_rehearsals,
        "next_targets": next_targets,
        "is_all_target": is_all_target,
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
