<?php // composer.json: {"require": {"bacon/bacon-qr-code": "^1.0.3"}}
// Adapted from https://boulderinformationservices.wordpress.com/2011/08/25/print-avery-labels-using-css-and-html/
// Print at 100% in Chrome with no margins to get a match. Make sure to use the Letter paper size!

require __DIR__ . '/vendor/autoload.php';

$renderer = new \BaconQrCode\Renderer\Image\Png();
$renderer->setHeight(256);
$renderer->setWidth(256);
$qrWriter = new \BaconQrCode\Writer($renderer);

$records = [];

function getAttendeeType($firstName, $lastName, $ticketType, $discountCode)
{
    if (stripos($ticketType, 'organizer') !== false) {
        return "Organizer";
    }

    if (stripos($ticketType, 'volunteer') !== false) {
        return 'Volunteer';
    }

    if (stripos($ticketType, 'speaker') !== false) {
        return "Speaker";
    }

    if (stripos($ticketType, '+') !== false) {
        return "3-Day";
    }

    if (stripos($ticketType, 'tutorials') !== false) {
        return "Tutorials Only";
    }

    return "GA";
}

function getFirstNameStyle($firstName) // adjust styles for long names to fit
{
    if (in_array($firstName, ['Samantha', 'Lawrence', 'Stephanie', 'Benjamin', 'Margaret'])) {
        return 'style="font-size: 19.5pt"';
    }

    if (in_array($firstName, ['Catherine', 'Anderson', 'Alejandro', 'Matthew'])) {
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
        'attendeeType' => getAttendeeType($firstName, $lastName, $line[3], $line[23])
    ];

    // double-add for both sides of the badge
    $records[] = $record;
}

$page = array_map(function($record) use ($qrWriter) {
    $record['qr'] = 'data:image/png;base64,' . base64_encode($qrWriter->writeString(implode("\n", [
            "BEGIN:VCARD",
            "VERSION:3.0",
            "N:" . iconv('utf8', 'ascii//TRANSLIT', str_replace('ń', 'n', $record['lastName'])) . ';' .
                iconv('utf8', 'ascii//TRANSLIT', str_replace('ń', 'n', $record['firstName'])) . ';',
            "ORG:" . $record['company'],
            "EMAIL:" . $record['email'],
            "END:VCARD"
        ])));
    $record['id'] = str_replace(' ', '-', strtolower(iconv('utf8', 'ascii//TRANSLIT', str_replace('ń', 'n', $record['firstName'])))) . '-' .
                str_replace(' ', '-', strtolower(iconv('utf8', 'ascii//TRANSLIT', str_replace('ń', 'n', $record['lastName']))));
    return $record;
}, array_slice($records, ($_GET['page'] ?? 0) * ($_GET['limit'] ?? 15), ($_GET['limit'] ?? 15)));

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Remaining <?= $_GET['page'] ?? 0 ?></title>
    <!-- <title>6793 Labels (2.625 x 2 inches)</title> -->
    <style>
        body {
            width: 8.5in;
            margin: .5in .0625in .5in .1875in;
            font-family: "Open Sans", sans-serif;
        }
        .label {
            width: 2.5in; /* plus .125 inches from padding */
            height: 2in; /* plus .125 inches from padding */
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
        <li>:screenshot <?= $entry['id']?>.png --dpr 3.125 --selector #<?= $entry['id'] ?></li>
    <?php endforeach; ?>
</ol><hr /><?php endif; ?>

<?php foreach ($page as $entry): ?>
    <div class="label" id="<?= $entry['id'] ?>">
        <img class="qr-code" src="<?= $entry['qr'] ?>" />
        <div class="first-name" <?= getFirstNameStyle($entry['firstName']) ?>><?= $entry['firstName'] ?></div>
        <div class="last-name"
            <?= in_array($entry['lastName'], ['Schwanekamp']) ? 'style="font-size: 13.5pt"' : '' ?>>
            <?= $entry['lastName'] ?>
        </div>
        <div class="attendee-type"><!-- <?= $entry['attendeeType'] ?>--></div>
        <div class="company"><?= $entry['company'] ?></div>
    </div>
<?php endforeach; ?>

<!-- <div class="page-break"></div> -->

</body>
</html>
