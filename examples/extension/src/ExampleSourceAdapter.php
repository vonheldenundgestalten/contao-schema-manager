<?php
declare(strict_types=1);
namespace SchemaManagerExample;
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
/** Replace this synthetic file source with an integration's own authoritative repository. */
final class ExampleSourceAdapter implements Api\SourceAdapterInterface
{
    public function __construct(private readonly string $stateFile) {}
    public function key(): string { return 'example.inventory'; }
    public function describe(): Api\SourceDefinition { return new Api\SourceDefinition(['example.equipment'],['name','notes'],['description']); }
    private function state(): ?array
    {
        if(!is_file($this->stateFile))return null;
        return json_decode(file_get_contents($this->stateFile),true,32,JSON_THROW_ON_ERROR);
    }
    public function findCandidates(Api\CandidateQuery $query): Api\CandidatePage
    {
        $state=$this->state();
        if(!$query->context->authorized || !$state || $query->cursor!==null || ($state['site']??'')!==$query->context->site || !empty($state['forbidden']) || ($query->typeKeys && !in_array('example.equipment',$query->typeKeys,true)) || ($query->search!=='' && stripos($state['name'],$query->search)===false))return new Api\CandidatePage([]);
        return new Api\CandidatePage([new Api\Candidate(new Api\SourceReference($this->key(),$state['site'],$state['key']),$state['name'],'example.equipment',hash('sha256',json_encode($state,JSON_THROW_ON_ERROR)))]);
    }
    public function resolve(Api\SourceReference $source,Api\ReadContext $context): Api\SourceResolution
    {
        $state=$this->state();
        if(!$state || $source->adapterKey!==$this->key() || $source->subjectKey!==$state['key'])return new Api\SourceResolution('missing');
        if($source->installationScope!==$context->site || $state['site']!==$context->site || !empty($state['forbidden']))return new Api\SourceResolution('forbidden');
        $now=$context->now?:time();$start=$state['start']??null;$stop=$state['stop']??null;
        if($context->purpose!=='preview' && (empty($state['published']) || ($start!==null && $start>$now) || ($stop!==null && $stop<=$now)))return new Api\SourceResolution('unpublished',nextVisibilityChange:$start!==null && $start>$now?$start:null);
        if(!array_key_exists($context->language,$state['descriptions']))return new Api\SourceResolution('translation_missing');
        return new Api\SourceResolution('available',new Api\SourceSnapshot($source,hash('sha256',json_encode($state,JSON_THROW_ON_ERROR)),'example.equipment',['name'=>$state['name'],'notes'=>null],['description'=>$state['descriptions'][$context->language]],$state['url']??null,nextVisibilityChange:$stop??$state['expiry']??null));
    }
}
