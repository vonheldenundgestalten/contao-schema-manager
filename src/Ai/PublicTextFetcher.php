<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Ai;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
/** Only inventory-generated public URLs. Never follow redirects or send credentials. */
final class PublicTextFetcher
{
    public function enrich(array $source): array
    {
        $url=$source['url'];$parts=parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '',['https','http'],true) || isset($parts['user']) || isset($parts['pass']) || !empty($parts['fragment'])) { return $source; }
        try {
            // A separate client prevents project-level default authentication from leaking.
            $http=new NoPrivateNetworkHttpClient(HttpClient::create(['max_redirects'=>0,'timeout'=>4,'max_duration'=>6,'headers'=>['User-Agent'=>'Contao-Schema-Manager/AI-source-reader','Accept'=>'text/html']]));
            $response=$http->request('GET',$url);
            if ($response->getStatusCode()!==200 || !str_contains(strtolower($response->getHeaders(false)['content-type'][0] ?? ''),'text/html')) { $response->cancel();return $source; }
            $html='';foreach($http->stream($response) as $chunk){$html.=$chunk->getContent();if(strlen($html)>1000000){$response->cancel();return $source;}}
            // Attribute order and header directives must not bypass noindex exclusions.
            $robots=implode(',', $response->getHeaders(false)['x-robots-tag'] ?? []);
            $dom=new \DOMDocument();$before=libxml_use_internal_errors(true);
            try {$dom->loadHTML($html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);foreach($dom->getElementsByTagName('meta') as $meta){if(in_array(strtolower($meta->getAttribute('name')),['robots','googlebot'],true))$robots.=','.$meta->getAttribute('content');}}
            finally {libxml_clear_errors();libxml_use_internal_errors($before);}
            if (preg_match('/(?:^|[,\s])noindex(?:$|[,\s])/i',$robots)) { $source['text']='';$source['coverage']='Rendered page is noindex; skipped.';return $source; }
            $text=SiteInventory::text($html);
            if(mb_strlen($text)>80){$source['text']=mb_substr($text,0,24000);$source['coverage']='Public rendered HTML (without navigation, header, footer, scripts or styles).';}
        } catch (\Throwable) { /* Basic-auth staging, private hosts and unavailable pages use local public text only. */ }
        return $source;
    }
}
