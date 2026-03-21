from flask import abort, render_template
from db import get_db
from routes.common import STATUS_LABELS, number_type_label

def init_app(app):
    @app.route("/numbers")
    def numbers():
        with get_db() as conn:
            rows = conn.execute(
                "SELECT number_id, number_name, team, number_type FROM numbers ORDER BY number_id"
            ).fetchall()
        return render_template("numbers.html", rows=rows, number_type_label=number_type_label)

    @app.route("/numbers/<int:number_id>")
    def number_detail(number_id: int):
        with get_db() as conn:
            number = conn.execute(
                "SELECT number_id, number_name, team, number_type FROM numbers WHERE number_id = ?",
                (number_id,),
            ).fetchone()
            if not number:
                abort(404)

            members_rows = conn.execute(
                """
                SELECT m.member_name, m.dance_name, m.generation
                FROM member_numbers mn
                JOIN members m ON mn.member_name = m.member_name
                WHERE mn.number_id = ?
                ORDER BY m.generation, m.member_name
                """,
                (number_id,),
            ).fetchall()

            rehearsal_rows = conn.execute(
                """
                SELECT r.rehearsal_id, r.rehearsal_date, r.rehearsal_type,
                       rn.status, rn.main_seq, rn.remark
                FROM rehearsal_numbers rn
                JOIN rehearsals r ON rn.rehearsal_id = r.rehearsal_id
                WHERE rn.number_id = ? AND rn.status != 'absent'
                ORDER BY r.rehearsal_date
                """,
                (number_id,),
            ).fetchall()

        return render_template(
            "number_detail.html",
            number=number,
            members_rows=members_rows,
            rehearsal_rows=rehearsal_rows,
            status_labels=STATUS_LABELS,
            number_type_label=number_type_label,
        )
