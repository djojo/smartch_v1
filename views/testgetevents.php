<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../calendar/externallib.php');

global $USER, $DB, $CFG;

$timestart = 1658566794;
$timeend = 1753261194;

$events = calendar_get_events($timestart, $timeend, $USER->id, null, null);

var_dump($events);
