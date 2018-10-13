<?php // diffs/de-dupes loosely between two sets of attendees from Ti.to

$alreadyPrintedMap = array_flip(array_map(function($line) {
    return str_getcsv($line)[7];
}, file('attendees.csv')));

$toPrint = [];

foreach (file('attendees-all.csv') as $line) {
    if (isset($alreadyPrintedMap[($parts = str_getcsv($line))[7]])) { // skip dupes
        continue;
    }

    $toPrint[] = ['name' => $parts[4], 'line' => $line];
}

usort($toPrint, function($a, $b) {
    return strnatcasecmp($a['name'], $b['name']);
});

echo implode("", array_column($toPrint, 'line'));
