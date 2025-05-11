<?php
// Copyright (c) 2025 Peter Olszowka. All rights reserved. See copyright document for more details.
// Created by Peter Olszowka on 2025-01-18
$report = [];
$report['name'] = 'Pocket Program 3';
$report['description'] = 'Export CSV file of public schedule for generating Balticon pocket program; with floor, tags, and kids';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 79,
    'Publication Reports' => 45
);
$report['csv_output'] = true;
$report['group_concat_expand'] = true;
$report['queries'] = [];
$report['queries']['master'] = <<<'EOD'
WITH ST AS
  (SELECT S.sessionid,
          GROUP_CONCAT(TA.tagname SEPARATOR ", ") AS "taglist"
   FROM Schedule SCH
   JOIN Sessions S USING (sessionid)
   LEFT JOIN SessionHasTag USING (sessionid)
   LEFT JOIN Tags TA USING (tagid)
   GROUP BY S.sessionid),
     SP AS
  (SELECT SCH.sessionid,
          GROUP_CONCAT(' ', P.pubsname, IF (POS.moderator=1, ' (m)', '')) AS "partlist"
   FROM Schedule SCH
   LEFT JOIN ParticipantOnSession POS USING (sessionid)
   LEFT JOIN Participants P USING (badgeid)
   GROUP BY SCH.sessionid)
SELECT S.sessionid,
       DATE_FORMAT(ADDTIME('$ConStartDatim$', SCH.starttime), '%a') AS DAY,
       DATE_FORMAT(ADDTIME('$ConStartDatim$', SCH.starttime), '%l:%i %p') AS 'Time',
       S.duration,
       R.floor,
       R.roomname,
       ST.taglist AS TAGS,
       TY.typename AS TYPE,
       K.kidscatname,
       S.title,
       S.progguiddesc AS "Long Text",
       SP.partlist AS "PARTIC"
FROM Sessions S
JOIN Schedule SCH USING (sessionid)
JOIN Rooms R USING (roomid)
JOIN Tracks T USING (trackid)
JOIN Types TY USING (typeid)
JOIN KidsCategories K USING (kidscatid)
LEFT JOIN ST USING (sessionid)
LEFT JOIN SP USING (sessionid)
WHERE S.pubstatusid = 2 /* Public */
ORDER BY SCH.starttime, R.floor, R.roomname;
EOD;

$report['output_filename'] = 'pocketprogram3.csv';
$report['column_headings'] = 'sessionid,day,time,duration,floor,room,tags,type,kids,title,description,participants';
$report['map_functions'][7] = function ($inp): string {
    return (trim($inp));
};
$report['map_functions'][8] = function ($inp): string {
    while (mb_ereg("[\n\r\x{00a0}]", $inp)) {
        $inp = mb_ereg_replace("[\n\r\x{00a0} ]+", " ", $inp);
    }
    while (mb_ereg("  +", $inp)) {
        $inp = mb_ereg_replace("  +", " ", $inp);
    }
    return trim($inp);
};
