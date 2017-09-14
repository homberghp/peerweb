<?php

class VCalandar {

    function expand($organisationname, $username, $replyto, $dtstart, $dtend, $location, $description, $summary, $resources) {
        $mailuid = 'UID:'.date('Ymd').'T'.date('His').'-'.rand().'-fontysvenlo.org';
        $maildtstamp = date('Ymd').'T'.date('His');
        $stuf = "BEGIN:VCALENDAR\r\n"
                . "VERSION:2.0\r\n"
                . "PRODID:-//fontysvenlo.org//peerweb Calendar Tool//EN\r\n"
                . "METHOD:REQUEST\r\n"
                . "BEGIN:VEVENT\r\n"
                . "ORGANIZERCN=\"{$organisationname} ({$username})\":mailto:{$replyto}\r\n"
                . "UID:{$mailuid}\r\n"
                . "DTSTAMP:{$maildtstamp}\r\n"
                . "DTSTART:$dtstart\r\n"
                . "DTEND:$dtend\r\n"
                . "LOCATION:$location\r\n"
                . "SUMMARY:$summary\r\n"
                . "DESCRIPTION:{$description} - The following reservation were made: {$resources} \r\n"
                . "BEGIN:VALARM\r\n"
                . "TRIGGER:-PT15M\r\n"
                . "ACTION:DISPLAY\r\n"
                . "DESCRIPTION:Reminder\r\n"
                . "END:VALARM\r\n"
                . "END:VEVENT\r\n"
                . "END:VCALENDAR\r\n";
        return $stuf;
    }

}
$from='p.vandenhombergh@fontys.nl';
$to='p.vandenhombergh@fontys.nl';
$summary='assessment';
$organisationname = 'fontys hogescholen';
$replyto = 'no-reply@fontys.nl';
$vcalendar = new VCalandar();
$vcal= $vcalendar->expand($organisationname, 'hombergp', $replyto, '20170914T140000', '20170914T141500', 'Fontys Campus Venlo Room W1.81', 'assessment mod2', $summary, 'room');
$headers = "From: $from\r\nReply-To: $from"; 
$headers .= "\r\nMIME-version: 1.0\r\nContent-Type: text/calendar; name=calendar.ics; method=REQUEST; charset=\"iso-8859-1\"";
$headers .= "\r\nContent-Transfer-Encoding: 7bit\r\nX-Mailer: Microsoft Office Outlook 12.0"; 
 echo $vcal;
echo @mail($to, $subject . " " . $summary . " / " . $resources, $vcal, $headers);