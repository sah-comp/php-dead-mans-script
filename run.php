<?php

declare(strict_types=1);

require_once 'globals.php';
require_once 'functions.php';

$dayNum = getDay();

// Check if it's time to send a check-in notification
if (($dayNum % globals::checkInterval) === 0) {
    if ($dayNum < globals::sendAfter) {
        $subjectText = 'DMS Check-In Day ' . getDay() . ', Token: ' . getToken();
        $msgText = "It's time to check in with DMS.\r\n\r\n";
        $msgText .= 'Currently on day number ' . getDay() . " since last check-in.\r\n\r\n";
        $msgText .= 'Messages are configured to release after ' . globals::sendAfter . " days.\r\n\r\n";
        
        if ((globals::sendAfter - $dayNum) <= globals::checkInterval) {
            $msgText .= " ** WARNING: THIS IS YOUR FINAL NOTIFICATION! ** \r\n\r\n";
        }
        
        $msgText .= globals::webPath . '/checkin.php?token=' . getToken();
        mail(globals::ownerMail, $subjectText, $msgText, 'From: ' . globals::mailFrom);
    }
    writeDay($dayNum);
} else {
    writeDay($dayNum);
}

// Send messages if the deadline has passed
if ($dayNum >= globals::sendAfter) {
    $daysAfter = $dayNum - globals::sendAfter;
    $targetAddrList = array_filter(glob(globals::baseFolder . '/data/*'), 'is_dir');
    
    foreach ($targetAddrList as $targetAddr) {
        $numberedFolders = array_filter(glob($targetAddr . '/*'), 'is_dir');
        
        foreach ($numberedFolders as $numberedFolder) {
            if ((int)basename($numberedFolder) === $daysAfter) {
                $messageDir = $numberedFolder;
                $messageFiles = scandir($messageDir);
                
                if ($messageFiles === false) {
                    continue;
                }
                
                foreach ($messageFiles as $messageFile) {
                    $fullPath = $messageDir . '/' . $messageFile;
                    if (is_file($fullPath) && $messageFile !== '.' && $messageFile !== '..') {
                        sendMsg(basename($targetAddr), $fullPath);
                    }
                }
            }
        }
    }
}
