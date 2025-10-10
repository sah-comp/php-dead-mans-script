# Simple Dead Man's Script (SDMS)

A lightweight PHP-based dead man's switch that automatically sends scheduled emails to designated recipients if you fail to check in within a specified time period. Based on existing Dead Man's Scripts that are no longer available.

## Table of Contents
- [How It Works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Message Organization](#message-organization)
- [File Structure](#file-structure)
- [Usage](#usage)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)

---

## How It Works

SDMS operates on a simple counter-based system with token security:

### The Daily Cycle
1. **Cron Execution**: A cron job runs `run.php` once per day
2. **Counter Increment**: The script reads `daynum.dat`, increments the counter by 1, and writes it back
3. **Check-in Reminders**: Every `checkInterval` days (e.g., every 7 days), SDMS sends a reminder email to `ownerMail` with:
   - Current day number since last check-in
   - A unique check-in link with a randomized token
   - Warning if it's the final notification before messages are sent

### The Check-in Process
- The reminder email contains a link: `http://yoursite.com/dms/checkin.php?token=XXXXXXXX`
- Clicking the link validates the token and resets the counter to 0
- A new random token is generated (security measure to prevent replay attacks)
- The cycle begins again from day 0

### The Trigger Point
- If the counter reaches `sendAfter` days without a check-in, the system activates
- SDMS begins scanning the `data/` directory for messages to send
- Messages are sent based on the folder structure: `data/<recipient-email>/<days-offset>/`
- The counter continues incrementing, sending messages at different offsets on subsequent days

### Example Timeline
With `checkInterval = 7` and `sendAfter = 28`:

| Day | Action |
|-----|--------|
| 0   | Counter reset (checked in) |
| 7   | Reminder email sent to owner |
| 14  | Reminder email sent to owner |
| 21  | **FINAL WARNING** reminder sent to owner |
| 28  | Messages in `data/*/0/` folders sent to recipients |
| 29  | Messages in `data/*/1/` folders sent to recipients |
| 35  | Messages in `data/*/7/` folders sent to recipients |

---

## Requirements

- **PHP**: Version 5.3+ (no database required)
- **Web Server**: Apache with `.htaccess` support (or equivalent configuration)
- **Mail Function**: PHP's `mail()` function properly configured
- **Cron**: Ability to schedule daily cron jobs
- **File Permissions**: Write access to data files and directories

---

## Installation

### Quick Install

1. **Extract files** to your web server directory (e.g., `/home/user/www/dms/`)

2. **Run the initialization script**:
   ```bash
   bash initialize.sh
   ```
   
   Or manually initialize via SSH:
   ```bash
   echo 0 > daynum.dat
   echo 0 > token.dat
   chmod +w daynum.dat
   chmod +w token.dat
   [ -d data ] || mkdir data
   chmod 755 data
   echo "order deny,allow" > data/.htaccess
   echo "deny from all" >> data/.htaccess
   ```

3. **Rename security files**:
   ```bash
   mv htaccess .htaccess
   # Optional: Set up password protection
   htpasswd -c .htpasswd yourusername
   ```

4. **Configure globals**: Edit `globals.php` with your paths and settings (see Configuration section)

5. **Set up cron job**:
   ```bash
   crontab -e
   # Add this line:
   @daily /usr/bin/php -f /home/user/www/dms/run.php
   ```

6. **Test the system**:
   ```bash
   # Test run.php directly
   php -f run.php
   
   # Test check-in with token
   php -f checkin.php token=0
   ```

---

## Configuration

All configuration is done in `globals.php`. Edit the constants in the `globals` class:

### Path Configuration
```php
const baseFolder   = '/home/user/www/dms';
const dataFile     = '/home/user/www/dms/daynum.dat';
const footerFile   = '/home/user/www/dms/footer.txt';
const tokenFile    = '/home/user/www/dms/token.dat';
```

- **baseFolder**: Absolute path to the SDMS installation directory
- **dataFile**: Full path to the day counter file (must be writable)
- **footerFile**: Optional footer text appended to all messages
- **tokenFile**: Full path to the token file (must be writable)

### Timing Configuration
```php
const checkInterval = 7;   // Days between reminders
const sendAfter     = 28;  // Days before releasing messages
```

- **checkInterval**: How often (in days) to send reminder emails to the owner
  - Recommended: 5-7 days
  - Must be less than `sendAfter`
  
- **sendAfter**: How many days without check-in before messages are sent
  - Recommended: 3-4 times the `checkInterval` value
  - Example: If `checkInterval = 7`, set `sendAfter = 28`

### Email Configuration
```php
const webPath       = 'http://yoursite.com/dms';
const ownerMail     = 'you@example.com';
const mailFrom      = 'deadman@example.com';
const subjectPrefix = '[DMS] ';
```

- **webPath**: Full URL to your SDMS installation (no trailing slash)
- **ownerMail**: Your email address (receives check-in reminders)
- **mailFrom**: From address for all emails sent by SDMS
- **subjectPrefix**: Text prepended to all email subject lines (optional)

---

## Message Organization

Messages are stored as plain text files in a nested folder structure under `data/`.

### Folder Structure
```
data/
├── recipient1@example.com/
│   ├── 0/
│   │   ├── will-and-testament
│   │   └── account-passwords
│   ├── 7/
│   │   └── goodbye-letter
│   └── 30/
│       └── delayed-message
└── recipient2@example.com/
    └── 0/
        └── notification
```

### Hierarchy Explained
```
data/<recipient-email>/<days-after-trigger>/<message-filename>
```

1. **First Level**: Recipient's email address (folder name)
2. **Second Level**: Number of days AFTER the `sendAfter` threshold
3. **Third Level**: Message files (plain text)

### Examples

**Immediate message** (sent on day `sendAfter`):
```
data/lawyer@example.com/0/last-will
```
- Sent to: `lawyer@example.com`
- Sent on: Day 28 (if `sendAfter = 28`)
- Subject: "[DMS] last-will"

**Delayed message** (sent 7 days after trigger):
```
data/friend@example.com/7/goodbye
```
- Sent to: `friend@example.com`
- Sent on: Day 35 (28 + 7)
- Subject: "[DMS] goodbye"

**Multiple messages to one recipient**:
```
data/executor@example.com/0/will
data/executor@example.com/0/passwords
data/executor@example.com/0/instructions
```
- All three files sent on day 28 to `executor@example.com`

### Message File Format

- **Content**: Plain text only
- **Filename**: Used as the email subject line (after `subjectPrefix`)
- **Extension**: Not required (`.txt` is fine but unnecessary)
- **Footer**: The contents of `footer.txt` are automatically appended to every message

### Folder Naming Rules

- **Email folders**: Must be valid email addresses
- **Day folders**: Must be numeric only (0, 1, 7, 30, 100, etc.)
  - Leading zeros are acceptable (01, 007)
  - No letters or special characters
  - The system reads them as base-10 integers

---

## File Structure

### Core PHP Files

#### `run.php` - Main Cron Script
The workhorse of SDMS. Executed daily by cron.

**Responsibilities**:
- Increments the day counter
- Sends check-in reminders to owner (every `checkInterval` days)
- Sends final warning when approaching `sendAfter`
- Scans and sends messages to recipients after `sendAfter` threshold
- Calculates which day-offset folders to process

**Key Logic**:
```php
// Reminder emails sent when: (dayNum % checkInterval) == 0
if(($dayNum % globals::checkInterval) == 0) {
    // Send reminder to owner
}

// Messages sent when: dayNum >= sendAfter
if($dayNum >= globals::sendAfter) {
    $daysAfter = ($dayNum - globals::sendAfter);
    // Send messages from data/*/[daysAfter]/ folders
}
```

#### `checkin.php` - Check-in Endpoint
Handles the check-in process when you click the link in reminder emails.

**Responsibilities**:
- Validates the `token` GET parameter
- Compares it against the stored token in `token.dat`
- Resets the day counter to 0 on success
- Generates a new random token (1048576 to 134217728)

**Access Methods**:
- Web: `http://yoursite.com/dms/checkin.php?token=12345678`
- CLI: `php -f checkin.php token=12345678`

#### `functions.php` - Helper Functions
Utility functions used throughout SDMS.

**Functions**:
- `sendMsg($toAddress, $msgFile)`: Sends an email with message file content
  - Appends footer from `footer.txt`
  - Adds check-in link with current token
  - Sets subject from filename
  
- `getDay()`: Reads and returns current day number (+1)
- `writeDay($dayNum)`: Writes day number to `daynum.dat`
- `getToken()`: Reads and returns current token
- `randomizeToken()`: Generates new random token and saves it
- `resetDayNum()`: Writes 0 to `daynum.dat`

#### `globals.php` - Configuration
Contains all configuration constants in a static class. See [Configuration](#configuration) section.

### Data Files

#### `daynum.dat`
- **Purpose**: Stores the current day counter
- **Format**: Single integer (e.g., `17`)
- **Permissions**: Must be writable by PHP
- **Modified by**: `run.php` (increments), `checkin.php` (resets to 0)

#### `token.dat`
- **Purpose**: Stores the current security token
- **Format**: Single integer between 1048576 and 134217728
- **Permissions**: Must be writable by PHP
- **Modified by**: `checkin.php` (randomizes on each check-in)
- **Security**: Prevents unauthorized check-ins via replay attacks

#### `footer.txt` (Optional)
- **Purpose**: Standard footer appended to all outgoing messages
- **Format**: Plain text
- **Example content**:
  ```
  This message was sent automatically by Dead Man's Script.
  If you have questions, please contact the estate executor.
  ```

### Security Files

#### `.htaccess`
Restricts web access to the installation directory. Rename `htaccess` to `.htaccess` after installation.

#### `.htpasswd` (Optional)
Password file for HTTP Basic Authentication. Generate with:
```bash
htpasswd -c .htpasswd username
```

#### `data/.htaccess`
**Critical**: Prevents direct web access to message files. Contains:
```
order deny,allow
deny from all
```

---

## Usage

### Initial Setup Workflow

1. **Install and configure** (see Installation and Configuration sections)

2. **Create your first message**:
   ```bash
   mkdir -p data/recipient@example.com/0
   echo "This is my test message." > data/recipient@example.com/0/test-message
   ```

3. **Test the cron script**:
   ```bash
   php -f run.php
   # Check your email for day 0 reminder (if checkInterval allows)
   ```

4. **Verify file updates**:
   ```bash
   cat daynum.dat  # Should show 1
   cat token.dat   # Should show a random number
   ```

5. **Test check-in**:
   - Copy the token from reminder email or `token.dat`
   - Visit: `http://yoursite.com/dms/checkin.php?token=XXXXXXXX`
   - Or: `php -f checkin.php token=XXXXXXXX`
   - Verify: `cat daynum.dat` should show 0

### Ongoing Maintenance

- **Check in regularly**: Click the link in reminder emails before the `sendAfter` deadline
- **Add/update messages**: Edit files in `data/` directories anytime
- **Monitor cron logs**: Ensure daily execution is working
- **Test periodically**: Manually run `run.php` to verify email functionality

### Adding New Messages

```bash
# Add immediate message (sent on day sendAfter)
mkdir -p data/newrecipient@example.com/0
nano data/newrecipient@example.com/0/message-title

# Add delayed message (sent 14 days after trigger)
mkdir -p data/newrecipient@example.com/14
nano data/newrecipient@example.com/14/delayed-message
```

### Manual Counter Reset

If you need to manually reset the system:
```bash
echo 0 > daynum.dat
```

---

## Security Considerations

### Critical Security Measures

1. **Protect the `data/` directory**
   - Ensure `data/.htaccess` is in place and working
   - **Better**: Move `data/` outside the web root entirely
   - Update `baseFolder` in `globals.php` accordingly

2. **Token-based authentication**
   - Tokens are randomized after each check-in
   - Prevents replay attacks (old links won't work)
   - Token range: 1048576 to 134217728 (provides ~127 million possibilities)

3. **File permissions**
   - `daynum.dat`: 644 (rw-r--r--)
   - `token.dat`: 644 (rw-r--r--)
   - `data/`: 755 (rwxr-xr-x)
   - Message files: 644 (rw-r--r--)

4. **Web server protection**
   - Use `.htaccess` to restrict access to the installation directory
   - Consider HTTP Basic Auth via `.htpasswd`
   - Use HTTPS for check-in links (prevents token interception)

5. **Message content security**
   - Messages are stored as **plain text**
   - Do NOT store highly sensitive information unencrypted
   - Consider encrypting message files and providing decryption instructions
   - Filesystem-level encryption is recommended for sensitive data

### Security Recommendations

- **Use HTTPS**: Protect check-in tokens in transit
- **Restrict file access**: Use `.htaccess` or move files outside web root
- **Monitor logs**: Check for unauthorized access attempts
- **Regular testing**: Periodically verify the system works as expected
- **Backup**: Keep encrypted backups of your message files
- **Consider alternatives**: For highly sensitive data, consider PGP-encrypted emails

### Potential Vulnerabilities

1. **Plain text storage**: Messages are not encrypted at rest
2. **Token in URL**: GET parameters are logged by web servers
3. **Email security**: Emails are sent via PHP's `mail()` (typically unencrypted SMTP)
4. **No authentication**: `checkin.php` only requires token knowledge
5. **Cron dependency**: System fails silently if cron stops running

---

## Troubleshooting

### Emails Not Sending

**Check PHP mail configuration**:
```bash
php -r "mail('test@example.com', 'Test', 'Test message');"
```

**Check cron execution**:
```bash
# Add logging to crontab
@daily /usr/bin/php -f /home/user/www/dms/run.php >> /home/user/dms-cron.log 2>&1
```

**Verify email addresses**:
- Ensure `ownerMail` and `mailFrom` are valid
- Check spam/junk folders

### Counter Not Incrementing

**Permissions issue**:
```bash
ls -l daynum.dat token.dat
# Should show write permissions for PHP user
chmod 644 daynum.dat token.dat
```

**Cron not running**:
```bash
crontab -l  # Verify cron job exists
grep CRON /var/log/syslog  # Check cron execution logs
```

### Check-in Link Not Working

**Token mismatch**:
- Ensure you're using the latest token (check `token.dat`)
- Don't reuse old check-in links

**File permissions**:
```bash
chmod 644 token.dat daynum.dat
```

**Test directly**:
```bash
# Get current token
cat token.dat
# Test check-in via CLI
php -f checkin.php token=$(cat token.dat)
```

### Messages Not Sending to Recipients

**Folder structure**:
```bash
# Verify structure
ls -R data/
# Should show: data/email@example.com/0/messagefile
```

**Day calculation**:
- Verify `daynum.dat` value is >= `sendAfter`
- Calculate expected folder: `(current_day - sendAfter)`
- Example: Day 35 with `sendAfter=28` → looks for `data/*/7/` folders

**Permissions**:
```bash
chmod -R 755 data/
find data/ -type f -exec chmod 644 {} \;
```

### Debug Mode

Add debugging to `run.php`:
```php
// At the top of run.php, add:
error_log("DMS: Day number is " . $dayNum);
error_log("DMS: checkInterval=" . globals::checkInterval);
error_log("DMS: sendAfter=" . globals::sendAfter);
```

Check PHP error logs:
```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php_errors.log
```

---

## License

This project is provided as-is with no warranty. Use at your own risk.

---

## Credits

Based on earlier Dead Man's Script implementations that are no longer available online.
