<?php
// Auth functions — mirrors AuthService.java
require_once __DIR__ . '/db.php';

/**
 * Attempt login. Returns user array or null.
 * Mirrors AuthService::login()
 */
function auth_login(string $email, string $password): ?array {
    $db  = get_db();
    $sql = 'SELECT UID, FirstName, MiddleName, LastName FROM `User` WHERE Email = ? AND Password = ?';
    $st  = $db->prepare($sql);
    $st->execute([$email, $password]);
    $row = $st->fetch();

    if (!$row) return null;

    $uid  = $row['UID'];
    $role = detect_role($uid);
    $roleID = get_role_id($uid, $role);

    return [
        'uid'        => $uid,
        'email'      => $email,
        'firstName'  => $row['FirstName'],
        'middleName' => $row['MiddleName'],
        'lastName'   => $row['LastName'],
        'role'       => $role,
        'roleID'     => $roleID,
    ];
}

/**
 * Register a new user. Returns true on success.
 * Mirrors AuthService::register()
 */
function auth_register(
    string $email, string $password,
    string $firstName, string $middleName, string $lastName,
    string $contact, string $role,
    string $course = '', int $yearLevel = 0
): bool {
    $db  = get_db();
    $sql = 'INSERT INTO `User` (Email, Password, FirstName, MiddleName, LastName, Contact)
            VALUES (?, ?, ?, ?, ?, ?)';
    try {
        $st = $db->prepare($sql);
        $st->execute([
            $email,
            $password,
            $firstName,
            $middleName !== '' ? $middleName : null,
            $lastName,
            $contact    !== '' ? $contact    : null,
        ]);
        $uid = (int) $db->lastInsertId();

        if ($role === 'Student') {
            return insert_student($uid, $course, $yearLevel);
        } elseif ($role === 'Faculty') {
            return insert_faculty($uid);
        } elseif ($role === 'TSG Personnel') {
            return insert_faculty($uid) && insert_tsg_personnel($uid);
        }
    } catch (PDOException $e) {
        // Email already taken or other DB error
        return false;
    }
    return false;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function insert_student(int $uid, string $course, int $yearLevel): bool {
    $db = get_db();
    $st = $db->prepare('INSERT INTO `Student` (Course, YearLevel, UID) VALUES (?, ?, ?)');
    $st->execute([$course ?: null, $yearLevel ?: null, $uid]);
    return true;
}

function insert_faculty(int $uid): bool {
    $db = get_db();
    $st = $db->prepare('INSERT INTO `Faculty` (UID) VALUES (?)');
    $st->execute([$uid]);
    return true;
}

function insert_tsg_personnel(int $uid): bool {
    $db  = get_db();
    $st  = $db->prepare('SELECT FacultyID FROM `Faculty` WHERE UID = ?');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row) return false;
    $st2 = $db->prepare('INSERT INTO `TSG_Personnel` (FacultyID) VALUES (?)');
    $st2->execute([$row['FacultyID']]);
    return true;
}

/**
 * Detect user role from subtype tables.
 * Mirrors AuthService::detectRole()
 */
function detect_role(int $uid): string {
    $db = get_db();

    $st = $db->prepare('SELECT StudentID FROM `Student` WHERE UID = ?');
    $st->execute([$uid]);
    if ($st->fetch()) return 'Student';

    $st = $db->prepare('SELECT FacultyID FROM `Faculty` WHERE UID = ?');
    $st->execute([$uid]);
    $row = $st->fetch();
    if ($row) {
        $st2 = $db->prepare('SELECT PersonnelID FROM `TSG_Personnel` WHERE FacultyID = ?');
        $st2->execute([$row['FacultyID']]);
        if ($st2->fetch()) return 'TSG Personnel';
        return 'Faculty';
    }

    return 'Unknown';
}

/**
 * Get role-specific ID (StudentID or FacultyID).
 * Mirrors AuthService::getRoleID()
 */
function get_role_id(int $uid, string $role): int {
    $db = get_db();
    if ($role === 'Student') {
        $st = $db->prepare('SELECT StudentID FROM `Student` WHERE UID = ?');
        $st->execute([$uid]);
        $r = $st->fetch();
        return $r ? (int) $r['StudentID'] : -1;
    }
    if ($role === 'Faculty' || $role === 'TSG Personnel') {
        $st = $db->prepare('SELECT FacultyID FROM `Faculty` WHERE UID = ?');
        $st->execute([$uid]);
        $r = $st->fetch();
        return $r ? (int) $r['FacultyID'] : -1;
    }
    return -1;
}
