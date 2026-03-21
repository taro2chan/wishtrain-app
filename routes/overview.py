from flask import render_template, request
from db import get_db
from routes.common import STATUS_LABELS, number_type_label
from routes.home import fetch_dashboard_data


def init_app(app):
    @app.route("/overview")
    def overview():
        selected_member_name = request.args.get("member_name", "").strip() or None

        dashboard = fetch_dashboard_data(selected_member_name)

        with get_db() as conn:
            members = conn.execute(
                """
                SELECT member_name, dance_name, generation
                FROM members
                ORDER BY generation, member_name
                """
            ).fetchall()

            numbers = conn.execute(
                """
                SELECT number_id, number_name, team, number_type
                FROM numbers
                ORDER BY number_id
                """
            ).fetchall()

            rehearsals = conn.execute(
                """
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
                """
            ).fetchall()

            cast_numbers = conn.execute(
                """
                SELECT number_id, number_name, team, number_type
                FROM numbers
                WHERE number_type != 'intro'
                ORDER BY number_id
                """
            ).fetchall()

            member_numbers = conn.execute(
                """
                SELECT member_name, number_id
                FROM member_numbers
                """
            ).fetchall()

        member_number_set = {
            (row["member_name"], row["number_id"])
            for row in member_numbers
        }

        return render_template(
            "overview.html",
            **dashboard,
            members=members,
            numbers=numbers,
            rehearsals=rehearsals,
            cast_numbers=cast_numbers,
            member_number_set=member_number_set,
            status_labels=STATUS_LABELS,
            number_type_label=number_type_label,
        )