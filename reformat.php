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

// pull records from Ti.to CSV
foreach (file('attendees-remaining.csv') as $line) {
    $line = str_getcsv($line);

    if (!$line[5] || $line[5] === 'Ticket First Name') {
        continue; // skip tickets that are currently unclaimed
    }

    $record = [
        'firstName' => $firstName = ucwords($line[5]),
        'lastName' => $lastName = ucwords($line[6]),
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

usort($records, function($a, $b) {
    return $a['attendeeType'] <=> $b['attendeeType'] ?:
        $b['isTutorial'] <=> $a['isTutorial'] ?:
        strcasecmp($a['lastName'], $b['lastName']) ?:
            strcasecmp($a['firstName'], $b['firstName']);
});

echo implode("\t", ['First', 'Last', 'Company', 'Type', 'Ticket ID']) . "\n";

foreach ($records as $record) {
    echo implode("\t", [
        $record['firstName'], $record['lastName'], $record['company'], $record['attendeeType'], $record['ticketId']
    ]) . "\n";
}