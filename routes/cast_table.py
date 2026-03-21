from flask import render_template, request
from db import get_db

def init_app(app):
    @app.route("/cast-table")
    def cast_table():
        include_opening = request.args.get("include_opening", "0") == "1"
        with get_db() as conn:
            numbers = conn.execute(
                """
                SELECT number_id, number_name, team, number_type
                FROM numbers
                WHERE (? = 1) OR number_type != 'intro'
                ORDER BY number_id
                """,
                (1 if include_opening else 0,),
            ).fetchall()

            members = conn.execute(
                "SELECT member_name, dance_name, generation FROM members ORDER BY generation, member_name"
            ).fetchall()

            member_numbers = conn.execute(
                "SELECT member_name, number_id FROM member_numbers"
            ).fetchall()

        member_number_set = {(r["member_name"], r["number_id"]) for r in member_numbers}
        return render_template(
            "cast_table.html",
            numbers=numbers,
            members=members,
            member_number_set=member_number_set,
            include_opening=include_opening,
        )
