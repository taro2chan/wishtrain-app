from flask import abort, render_template, request
from db import get_db
from routes.common import STATUS_LABELS, number_type_label

def init_app(app):
    @app.route("/members")
    def members():
        q = request.args.get("q", "").strip()
        sql = "SELECT member_name, dance_name, generation FROM members"
        params = ()
        if q:
            sql += " WHERE member_name LIKE ? OR dance_name LIKE ?"
            params = (f"%{q}%", f"%{q}%")
        sql += " ORDER BY generation, member_name"

        with get_db() as conn:
            rows = conn.execute(sql, params).fetchall()
        return render_template("members.html", rows=rows, q=q)

    @app.route("/members/<path:member_name>")
    def member_detail(member_name: str):
        with get_db() as conn:
            member = conn.execute(
                "SELECT member_name, dance_name, generation FROM members WHERE member_name = ?",
                (member_name,),
            ).fetchone()
            if not member:
                abort(404)

            numbers = conn.execute(
                """
                SELECT n.number_id, n.number_name, n.team, n.number_type
                FROM member_numbers mn
                JOIN numbers n ON mn.number_id = n.number_id
                WHERE mn.member_name = ?
                ORDER BY n.number_id
                """,
                (member_name,),
            ).fetchall()

            related_rehearsals = conn.execute(
                """
                SELECT DISTINCT r.rehearsal_id, r.rehearsal_date, r.rehearsal_type,
                                n.number_name, rn.status, rn.main_seq
                FROM member_numbers mn
                JOIN rehearsal_numbers rn ON mn.number_id = rn.number_id
                JOIN rehearsals r ON rn.rehearsal_id = r.rehearsal_id
                JOIN numbers n ON rn.number_id = n.number_id
                WHERE mn.member_name = ? AND rn.status != 'absent'
                ORDER BY r.rehearsal_date, n.number_id
                """,
                (member_name,),
            ).fetchall()

        return render_template(
            "member_detail.html",
            member=member,
            numbers=numbers,
            related_rehearsals=related_rehearsals,
            status_labels=STATUS_LABELS,
            number_type_label=number_type_label,
        )
