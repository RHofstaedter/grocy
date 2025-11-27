<?php

namespace Grocy\Services;

class ApplicationService extends BaseService
{
    private $installedVersion;

    public function getChangelog()
    {
        $changelogItems = [];
        foreach (glob(__DIR__ . '/../changelog/*.md') as $file) {
            $fileName = basename($file);
            $fileNameParts = explode('_', $fileName);

            if ($fileName == '__TEMPLATE.md') {
                continue;
            }

            $fileContent = file_get_contents($file);
            $version = $fileNameParts[1];
            $releaseDate = explode('.', $fileNameParts[2])[0];
            $releaseNumber = intval($fileNameParts[0]);

            $changelogItems[] = [
                'version' => $version,
                'release_date' => $releaseDate,
                'body' => $fileContent,
                'release_number' => $releaseNumber
            ];
        }

        // Sort changelog items to have the changelog descending by newest version
        usort($changelogItems, function ($a, $b) {
            return $b['release_number'] <=> $a['release_number'];
        });

        return [
            'changelog_items' => $changelogItems,
            'newest_release_number' => $changelogItems[0]['release_number']
        ];
    }

    public function getInstalledVersion()
    {
        if ($this->installedVersion == null) {
            $this->installedVersion = json_decode(file_get_contents(__DIR__ . '/../version.json'));
        }

        return $this->installedVersion;
    }

    public function getSystemInfo()
    {
        $pdo = new \PDO('sqlite::memory:');
        $sqliteVersion = $pdo->query('SELECT sqlite_version()')->fetch()[0];
        $pdo = null;

        return [
            'grocy_version' => $this->getInstalledVersion(),
            'php_version' => phpversion(),
            'sqlite_version' => $sqliteVersion,
            'db_version' => $this->getDatabase()->migrations()->max('migration'),
            'os' => php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('v') . ' ' . php_uname('m'),
            'client' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
    }

    private static function convertToUtc(int $timestamp): string
    {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
        $dt->setTimestamp($timestamp);
        return $dt->format('Y-m-d H:i:s');
    }

    private static function getSqliteLocaltime(int $offset): string
    {
        $pdo = new \PDO('sqlite::memory:');
        if ($offset > 0) {
            return $pdo->query('SELECT datetime(\'now\', \'+' . $offset . ' seconds\', \'localtime\');')->fetch()[0];
        } else {
            return $pdo->query('SELECT datetime(\'now\', \'' . $offset . ' seconds\', \'localtime\');')->fetch()[0];
        }
    }

    public function getSystemTime(int $offset = 0): array
    {
        $timestamp = time() + $offset;
        $timeLocal = date('Y-m-d H:i:s', $timestamp);
        $timeUTC = self::convertToUtc($timestamp);
        return [
            'timezone' => date_default_timezone_get(),
            'time_local' => $timeLocal,
            'time_local_sqlite3' => self::getSqliteLocaltime($offset),
            'time_utc' => $timeUTC,
            'timestamp' => $timestamp,
            'offset' => $offset
        ];
    }
}
