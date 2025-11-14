<?php

declare(strict_types=1);

require_once 'globals.php';

/**
 * Send a message via email to the specified address
 *
 * @param string $toAddress Email address to send to
 * @param string $msgFile Path to the message file
 * @return bool True if mail was sent successfully
 */
function sendMsg(string $toAddress, string $msgFile): bool
{
    if (!file_exists($msgFile)) {
        return false;
    }
    
    $msgText = file_get_contents($msgFile) . "\r\n\r\n";
    $msgText .= "= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =" . "\r\n\r\n";
    
    if (file_exists(globals::footerFile)) {
        $msgText .= file_get_contents(globals::footerFile) . "\r\n\r\n";
    }
    
    $msgText .= globals::webPath . '/checkin.php?token=' . getToken() . "\r\n\r\n";
    $subject = globals::subjectPrefix . basename($msgFile);
    $headers = 'From: ' . globals::mailFrom;
    
    return mail($toAddress, $subject, $msgText, $headers);
}

/**
 * Get the current day number (incremented by 1)
 *
 * @return int Current day number
 */
function getDay(): int
{
    if (file_exists(globals::dataFile)) {
        $dataFile = file(globals::dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $dayNum = $dataFile[0] ?? 0;
    } else {
        $dayNum = 0;
    }
    
    return (int)$dayNum + 1;
}

/**
 * Write the day number to the data file
 *
 * @param int $dayNum Day number to write
 * @return bool True if successful, false otherwise
 */
function writeDay(int $dayNum): bool
{
    $fp = fopen(globals::dataFile, 'w');
    if (!$fp) {
        return false;
    }
    
    $result = fwrite($fp, (string)$dayNum) !== false;
    fclose($fp);
    
    return $result;
}

/**
 * Get the current token from the token file
 *
 * @return int Current token value
 */
function getToken(): int
{
    if (file_exists(globals::tokenFile)) {
        $token = file(globals::tokenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return (int)($token[0] ?? 0);
    }
    
    return 0;
}

/**
 * Generate and save a new random token
 *
 * @return bool True if successful, false otherwise
 */
function randomizeToken(): bool
{
    $fp = fopen(globals::tokenFile, 'w');
    if (!$fp) {
        return false;
    }
    
    $token = random_int(1048576, 134217728);
    $result = fwrite($fp, (string)$token) !== false;
    fclose($fp);
    
    return $result;
}

/**
 * Reset the day number to 0
 *
 * @return bool True if successful, false otherwise
 */
function resetDayNum(): bool
{
    $fp = fopen(globals::dataFile, 'w');
    if (!$fp) {
        return false;
    }
    
    $result = fwrite($fp, '0') !== false;
    fclose($fp);
    
    return $result;
}
