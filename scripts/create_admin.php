<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

require_once dirname(__DIR__) . '/config/bootstrap.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
}

function prompt_password(string $label): string
{
    fwrite(STDOUT, $label);
    if (PHP_OS_FAMILY === 'Windows') {
        $command = 'powershell -NoProfile -Command "$p = Read-Host -AsSecureString; $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }"';
        $value = shell_exec($command);
        if (!is_string($value)) {
            throw new RuntimeException('Unable to read the password securely. Run this script from PowerShell.');
        }
        return trim($value);
    }
    shell_exec('stty -echo');
    $value = trim((string) fgets(STDIN));
    shell_exec('stty echo');
    fwrite(STDOUT, PHP_EOL);
    return $value;
}

try {
    $username = prompt('Administrator username: ');
    $fullName = prompt('Administrator full name: ');
    $email = prompt('Administrator email: ');
    $password = prompt_password('Administrator password: ');
    $confirm = prompt_password('Confirm password: ');

    if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
        throw new InvalidArgumentException('Username must be 3 to 80 characters and use letters, numbers, dot, underscore, or hyphen.');
    }
    if ($fullName === '' || mb_strlen($fullName) > 160) {
        throw new InvalidArgumentException('Full name is required and must be 160 characters or fewer.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        throw new InvalidArgumentException('Enter a valid email address (190 characters or fewer).');
    }
    if ($password === '' || $password !== $confirm) {
        throw new InvalidArgumentException('The password must not be empty, and both entries must match.');
    }

    $pdo = db();
    $lockAcquired = (int) $pdo->query("SELECT GET_LOCK('dppms_first_admin_setup', 10)")->fetchColumn();
    if ($lockAcquired !== 1) {
        throw new RuntimeException('Could not acquire the administrator setup lock. Try again.');
    }

    try {
        $existingAdmin = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'SYSTEM ADMINISTRATOR'")->fetchColumn();
        if ($existingAdmin > 0) {
            throw new RuntimeException('A System Administrator already exists. Use User Management to add more accounts.');
        }

        $statement = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, status) VALUES (?, ?, ?, ?, 'SYSTEM ADMINISTRATOR', 'ACTIVE')");
        $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $email]);
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('dppms_first_admin_setup')");
    }
    fwrite(STDOUT, 'System administrator created.' . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Administrator setup failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
