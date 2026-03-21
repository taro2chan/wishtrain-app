from flask import abort, render_template
from db import get_db
from routes.common import STATUS_LABELS, number_type_label

def init_app(app):
    @app.route("/rehearsals")
    def rehearsals():
        with get_db() as conn:
            rows = conn.execute(
                """
                SELECT rehearsal_id, rehearsal_date, rehearsal_type, studio,
                       main_room, main_time, sub_room, sub_time
                FROM rehearsals
                ORDER BY rehearsal_date
                """
            ).fetchall()
        return render_template("rehearsals.html", rows=rows)

    @app.route("/rehearsals/<int:rehearsal_id>")
    def rehearsal_detail(rehearsal_id: int):
        with get_db() as conn:
            rehearsal = conn.execute(
                "SELECT * FROM rehearsals WHERE rehearsal_id = ?",
                (rehearsal_id,),
            ).fetchone()
            if not rehearsal:
                abort(404)

            targets = conn.execute(
                """
                SELECT n.number_id, n.number_name, n.team, n.number_type,
                       rn.status, rn.main_seq, rn.remark
                FROM rehearsal_numbers rn
                JOIN numbers n ON rn.number_id = n.number_id
                WHERE rn.rehearsal_id = ?
                ORDER BY CASE rn.status
                    WHEN 'all' THEN 0
                    WHEN 'main' THEN 1
                    WHEN 'sub' THEN 2
                    ELSE 3 END,
                    n.number_id
                """,
                (rehearsal_id,),
            ).fetchall()

        return render_template(
            "rehearsal_detail.html",
            rehearsal=rehearsal,
            targets=targets,
            status_labels=STATUS_LABELS,
            number_type_label=number_type_label,
        )
