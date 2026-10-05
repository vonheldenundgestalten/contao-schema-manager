<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Dotenv\Dotenv;

/** Reads explicitly on every request, including installations with dumped dotenv. */
final class ApiKeyStore
{
    public const NAME = 'SCHEMA_AI_API_KEY';
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir) {}
    public function get(): string
    {
        $file=$this->projectDir.'/.env.local';
        if (is_file($file) && !is_link($file)) {
            $values=(new Dotenv())->parse((string) file_get_contents($file),$file);
            if (array_key_exists(self::NAME,$values)) { return trim($values[self::NAME]); }
        }
        return trim((string) ($_SERVER[self::NAME] ?? $_ENV[self::NAME] ?? getenv(self::NAME) ?: ''));
    }
    public function save(string $key): void
    {
        $key=trim($key);
        if (!preg_match('/^sk-[A-Za-z0-9_-]{20,512}$/D',$key)) { throw new \InvalidArgumentException('Enter a valid OpenAI API key.'); }
        $file=$this->projectDir.'/.env.local'; $lockPath=$this->projectDir.'/var/schema-ai-key.lock';
        if (is_link($file) || is_link($lockPath)) { throw new \RuntimeException('Refusing to write a symbolic link. Configure SCHEMA_AI_API_KEY on the server instead.'); }
        if (!is_dir(dirname($lockPath))) { mkdir(dirname($lockPath),0700,true); }
        $lock=fopen($lockPath,'c');
        if (!$lock || !flock($lock,LOCK_EX)) { throw new \RuntimeException('Cannot lock the environment file.'); }
        $temp=null;
        try {
            $old=is_file($file)?(string)file_get_contents($file):'';
            // Parse first: never rewrite a malformed dotenv file or unrelated settings.
            (new Dotenv())->parse($old,$file);
            $pattern='/^[ \t]*(?:export[ \t]+)?'.self::NAME.'[ \t]*=.*(?:\r?\n|$)/m';
            $next=preg_replace($pattern,'',$old);
            $next=rtrim($next,"\r\n")."\n".self::NAME."='".$key."'\n";
            if (((new Dotenv())->parse($next,$file)[self::NAME] ?? null)!==$key) { throw new \RuntimeException('The existing key declaration cannot be safely replaced. Configure it on the server.'); }
            $temp=tempnam($this->projectDir,'.schema-ai-');
            if (!$temp || !chmod($temp,0600) || file_put_contents($temp,$next)===false || !rename($temp,$file)) { throw new \RuntimeException('Cannot save .env.local. Configure SCHEMA_AI_API_KEY on the server instead.'); }
            $temp=null;
        } finally { if ($temp && is_file($temp)) { unlink($temp); } flock($lock,LOCK_UN); fclose($lock); }
    }
}
