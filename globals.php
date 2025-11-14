<?php

declare(strict_types=1);

/**
 * Global configuration constants for the Dead Man's Script
 */
final class globals
{
    /** @var string Base folder path for the application */
    public const baseFolder = '/Users/sah-comp/Sites/playground/php-dead-mans-script';
    
    /** @var string Path to the day number data file */
    public const dataFile = '/Users/sah-comp/Sites/playground/php-dead-mans-script/daynum.dat';
    
    /** @var string Path to the email footer file */
    public const footerFile = '/Users/sah-comp/Sites/playground/php-dead-mans-script/footer.txt';

    /** @var string Path to the token file */
    public const tokenFile = '/Users/sah-comp/Sites/playground/php-dead-mans-script/token.dat';

    /** @var int Number of days between check-in notifications */
    public const checkInterval = 1;
    
    /** @var int Number of days after which messages are sent */
    public const sendAfter = 4;
    
    /** @var string Base web URL for the application */
    //public const webPath = 'https://hombergs.org/stephan/dms';
	public const webPath = 'https://playground.test/php-dead-mans-script';
    
    /** @var string Owner's email address */
    public const ownerMail = 'stephan@hombergs.org';
    
    /** @var string From email address for notifications */
    public const mailFrom = 'stephan@hombergs.org';
    
    /** @var string Email subject prefix */
    public const subjectPrefix = '';
}
