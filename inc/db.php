<?php
$db = new PDO('sqlite:' . __DIR__ . '/../wishtrain.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
