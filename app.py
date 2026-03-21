from flask import Flask
from db import init_db
from routes.home import init_app as init_home_routes
from routes.members import init_app as init_members_routes
from routes.numbers import init_app as init_numbers_routes
from routes.rehearsals import init_app as init_rehearsals_routes
from routes.cast_table import init_app as init_cast_table_routes
from routes.overview import init_app as init_overview_routes  # ← 追加

app = Flask(__name__)

init_home_routes(app)
init_members_routes(app)
init_numbers_routes(app)
init_rehearsals_routes(app)
init_cast_table_routes(app)
init_overview_routes(app)  # ← 追加

if __name__ == "__main__":
    init_db()
    app.run(debug=True, use_reloader=False, host="0.0.0.0", port=5001)