<?php

$records = [];

function getAttendeeType($firstName, $lastName, $ticketType, $discountCode)
{
    if (stripos($ticketType, 'virtual') !== false) {
        return 'Virtual';
    }

    if (stripos($ticketType, 'organizer') !== false) {
        return "Organizer";
    }

    if (stripos($ticketType, 'volunteer') !== false) {
        return 'Volunteer';
    }

    if (stripos($ticketType, 'speaker') !== false) {
        return "Speaker";
    }

    if (stripos($ticketType, 'sponsor') !== false && stripos($discountCode, 'giveaway') === false) {
        return "Sponsor";
    }

    return "Attendee";
}

function getFirstNameStyle($firstName) // adjust styles for long names to fit
{
    if (in_array($firstName, ['Catherine', 'Samantha', 'Lawrence', 'Stephanie', 'Benjamin', 'Margaret', 'Vincent J.'])) {
        return 'style="font-size: 19.5pt"';
    }

    if ($firstName === 'Oleksandr') {
        return 'style="font-size: 18.5pt"';
    }

    if (in_array($firstName, ['Christopher'])) {
        return 'style="font-size: 16pt"';
    }

    if (in_array($firstName, ['Nabilahmed'])) {
        return 'style="font-size: 15.5pt"';
    }

    if (in_array($firstName, ['Anderson', 'Alejandro', 'Matthew'])) {
        return 'style="font-size: 20.5pt"';
    }

    return '';
}

// pull records from Ti.to CSV
foreach (file('attendees-remaining.csv') as $line) {
    $line = str_getcsv($line);

    if (!$line[5] || $line[5] === 'Ticket First Name') {
        continue; // skip tickets that are currently unclaimed
    }

    $record = [
        'firstName' => $firstName = ucwords($line[5]),
        'lastName' => $lastName = ucwords($line[6]),
        'email' => $line[7],
        'company' => $line[8],
        'attendeeType' => getAttendeeType($firstName, $lastName, $line[3], $line[23]),
        'isTutorial' => stripos($line[3], 'tutorial') !== false,
        'ticketId' => $line[14] // ticket reference
    ];

    if ($record['attendeeType'] === 'Virtual') {
        continue; // skip virtual attendees
    }

    $records[] = $record;
}

if (($_GET['sort'] ?? '') === 'tutorial') {
    usort($records, function($a, $b) {
        return $b['isTutorial'] <=> $a['isTutorial'] ?:
            strcasecmp($a['lastName'], $b['lastName']) ?:
            strcasecmp($a['firstName'], $b['firstName']);
    });
}

$limit = isset($_GET['screenshot']) ? 999 : ($_GET['limit'] ?? 15);

$page = array_map(function($record) {
    $record['id'] = str_replace([' ', '.'], ['-', ''], strtolower(iconv('utf8', 'ascii//TRANSLIT', str_replace('ń', 'n', $record['firstName'])))) . '-' .
                str_replace([' ', "'"], ['-', ''], strtolower(iconv('utf8', 'ascii//TRANSLIT', str_replace('ń', 'n', $record['lastName']))));

    return $record;
}, array_slice($records, ($_GET['page'] ?? 0) * $limit, $limit));

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Remaining <?= $_GET['page'] ?? 0 ?></title>
    <!-- <title>6793 Labels (2.625 x 2 inches)</title> -->
    <style>
        body {
            width: 9.5in;
            margin: .5in .0625in .5in .25in;
            font-family: "Open Sans", sans-serif;
        }
        .label {
            width: 2.875in; /* plus .125 inches from padding */
            height: 1.375in; /* plus .125 inches from padding */
            padding: 0 0 0 .1in;
            margin-right: .125in; /* the gutter */

            float: left;
            position: relative; /* we need this to position company properly */
            overflow: hidden;

            /* outline: 1px dotted; */ /* outline doesn't occupy space like border does */
        }
        .label .first-name {
            padding-top: .1in;
            font-size: 21.5pt;
        }
        .label .last-name {
            font-size: 14pt;
        }
        .label .attendee-type {
            padding-top: 0 /* was 0.25in */;
            height: 0 /* was 0.6in */;
            font-size: 16pt;
            font-variant: small-caps;
        }
        .label .company {
            font-size: 14pt;
            padding-top: 0.45in;
            position: absolute;
            bottom: 0.05in;
        }
        .label img.qr-code {
            float: right;
            width: 1.35in;
            margin: .025in -.025in -.075in -.075in;
        }
        .page-break {
            clear: left;
            display: block;
            page-break-after: always;
        }
    </style>
</head>
<body>
<?php if ($_GET['lists'] ?? false): ?><ol><?php

$byType = [];
foreach ($page as $attendee) {
    $byType[$attendee['attendeeType']][] = $attendee;
}

foreach ($byType as $type => $attendees): ?>
<li><?= $type ?><ol>
<?php foreach ($attendees as $attendee): ?>
<li><?= $attendee['isTutorial'] ? '<b>' : '' ?><?= $attendee['id'] ?>.png<?= $attendee['isTutorial'] ? '</b>' : ''  ?></li>
<?php endforeach; ?></ol></li>
<?php endforeach; ?></ol><?php endif; ?>

<?php if ($_GET['screenshot'] ?? false): ?>
<p>To get PNGs of badge stickers on this page:</p>
<ol>
    <li>Open this page in Firefox</li>
    <li>Open the JS console</li>
    <li>Paste each list item on its own into the console (hit Enter after each)</li>
</ol>
<p><strong>Tip:</strong> Triple-click line, Cmd-C, Cmd-~, Cmd-V, Enter</p>
<hr />
<ol>
    <?php foreach ($page as $entry): ?>
        <li>:screenshot <?= strtolower($entry['attendeeType']) ?>/<?= $entry['id'] ?>.png --dpr 3.125 --selector #<?= $entry['id'] ?></li>
    <?php endforeach; ?>
</ol><hr /><?php endif; ?>

<?php foreach ($page as $entry): ?>
    <div class="label <?= $entry['attendeeType'] ?>" data-attendee-type="<?= $entry['attendeeType'] ?>" id="<?= $entry['id'] ?>">
        <div class="first-name" <?= getFirstNameStyle($entry['firstName']) ?>><?= $entry['firstName'] ?></div>
        <div class="last-name"
            <?= in_array($entry['lastName'], ['Schwanekamp', 'Schreckengost']) ? 'style="font-size: 13pt"' : '' ?>>
            <?= $entry['lastName'] ?>
        </div>
        <div class="company"><?= $entry['company'] ?></div>
    </div>
<?php endforeach; ?>

<p id="end-of-page"></p>

<!-- <div class="page-break"></div> -->

</body>
</html>
