<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Schema;
use Doctrine\DBAL\Connection;
final class EntityIdentity
{
    public function __construct(private readonly Connection $connection) {}
    public static function validate(string $value, string $origin): string
    {
        $value = trim($value);
        $parts = parse_url($value);
        $actualOrigin = 'https://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (!filter_var($value, FILTER_VALIDATE_URL) || strlen($value) > 255 || !$parts || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || strtolower($actualOrigin) !== strtolower($origin)) {
            throw new \InvalidArgumentException('Use a complete HTTPS entity ID on the saved identity origin (maximum 255 characters).');
        }
        return $value;
    }
    public function adopt(int $id, string $expected, string $replacement, bool $apply = false): array
    {
        $row = $this->connection->fetchAssociative('SELECT entityId,identityBase FROM tl_schema_entity WHERE id=?', [$id]);
        if (!$row || !$expected || $row['entityId'] !== $expected) { throw new \InvalidArgumentException('The current entity ID does not match --expected-current-id.'); }
        $replacement = self::validate($replacement, $row['identityBase']);
        if ($this->connection->fetchOne('SELECT id FROM tl_schema_entity WHERE entityId=? AND id<>?', [$replacement,$id])) { throw new \InvalidArgumentException('Another entity already uses this ID.'); }
        if ($apply && $replacement !== $expected) {
            $changed = $this->connection->executeStatement('UPDATE tl_schema_entity SET entityId=?,tstamp=? WHERE id=? AND entityId=?', [$replacement,time(),$id,$expected]);
            if ($changed !== 1) { throw new \RuntimeException('The entity changed during adoption; no ID was replaced.'); }
        }
        return ['from'=>$expected, 'to'=>$replacement, 'applied'=>$apply];
    }
}
