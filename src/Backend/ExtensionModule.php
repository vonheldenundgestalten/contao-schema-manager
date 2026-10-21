<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Backend;
use Contao\BackendUser;
use Contao\Input;
use Doctrine\DBAL\Connection;
use VHUG\SchemaManagerBundle\Extension\Internal\{Registry,Store};
use VHUG\SchemaManagerBundle\Extension\V1 as Api;
final class ExtensionModule
{
    public function __construct(private readonly Connection $connection,private readonly Registry $registry,private readonly Store $store,private readonly Api\EntityEditorInterface $editor,private readonly Api\BindingManagerInterface $bindings,private readonly \Symfony\Component\HttpFoundation\RequestStack $requests,private readonly \VHUG\SchemaManagerBundle\Extension\Internal\SiteScope $scopes) {}
    private ?string $sourceRevision=null;
    private function e(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    private function form(string $action,int $entity,string $site,string $language): string
    {
        return '<form method="post" data-turbo="false"><input type="hidden" name="FORM_SUBMIT" value="schema_extension"><input type="hidden" name="REQUEST_TOKEN" value="'.$this->e(\Contao\System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()).'"><input type="hidden" name="sourceRevision" value="'.$this->e($this->sourceRevision).'"><input type="hidden" name="schemaExtensionAction" value="'.$action.'"><input type="hidden" name="entity" value="'.$entity.'"><input type="hidden" name="site" value="'.$this->e($site).'"><input type="hidden" name="language" value="'.$this->e($language).'">';
    }
    public function generate(): string
    {
        $user=BackendUser::getInstance();
        if(!$user->isAdmin)throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Extension configuration requires an administrator.');
        $request=$this->requests->getCurrentRequest();$post=$request->isMethod('POST');
        $entity=(int)($post?Input::post('entity'):Input::get('entity'));
        $row=$entity?$this->store->record($entity):null;
        $source=$row?$this->store->binding($entity):null;
        $roots=$this->connection->fetchAllAssociative("SELECT id,title,language,dns FROM tl_page WHERE type='root' ORDER BY id");
        $site=(string)($post?Input::post('site'):(Input::get('site')?:($roots[0]['id']??'')));
        $root=null;foreach($roots as $candidate)if((string)$candidate['id']===$site)$root=$candidate;
        if(!$root)return '<div class="tl_listing_container"><p>No configured site root.</p></div>';
        $language=(string)($post?Input::post('language'):(Input::get('language')?:$root['language']));
        if(!preg_match('/^[a-z]{2,3}(?:[-_][a-z0-9]{2,8})*$/iD',$language))throw new \InvalidArgumentException('Invalid language.');
        $origin=$row['identityBase']??('https://'.($root['dns']?:$request->getHost()));
        $read=$this->scopes->read(new Api\ReadContext('preview',$site,$language,(int)$user->id,true,time()));
        $write=new Api\WriteContext($read,$origin,$post?(string)Input::post('sourceRevision'):null);
        $html='<div class="tl_listing_container"><h2>'.$this->label('schemaExtensionTitle','Extension data').'</h2>';
        $html.='<p>'.$this->label('schemaExtensionHelp','Manage additional facts and source bindings. Source-owned values are edited at the original record.').'</p>';
        $html.='<form method="get"><input type="hidden" name="do" value="schema_manager"><input type="hidden" name="key" value="extensions"><input type="hidden" name="entity" value="'.$entity.'"><label>Site <select name="site">';
        foreach($roots as $candidate)$html.='<option value="'.(int)$candidate['id'].'"'.((string)$candidate['id']===$site?' selected':'').'>'.$this->e($candidate['title']).'</option>';
        $html.='</select></label> <label>Language <input name="language" value="'.$this->e($language).'"></label> <button>Show</button></form>';
        try {
            if($post && Input::post('schemaExtensionAction')==='bind') {
                $ref=new Api\SourceReference((string)Input::post('adapter'),$site,(string)Input::post('subject'));
                $bound=$this->bindings->bind(new Api\BindRequest($ref,(string)Input::post('revision'),(string)Input::post('type')),$write);
                $html.='<p class="tl_confirm">Created or reused draft entity #'.$bound->id.'.</p>';
            }
            if($post && $row && Input::post('schemaExtensionAction')==='field') {
                $type=$this->registry->type($row['entityType']);$field=null;
                foreach($type?->fields??[] as $candidate)if($candidate->key===Input::post('field'))$field=$candidate;
                if(!$field)throw new \InvalidArgumentException('Field unavailable.');
                $value=$request->request->all()['value']??null;
                $this->editor->saveFields(new Api\EntityReference($entity),[$field->key=>$this->parse($field,$value)],$field->localized,(int)Input::post('revision'),$write);
                $html.='<p class="tl_confirm">Saved.</p>';
            }
            if($post && $row && Input::post('schemaExtensionAction')==='relationship') {
                $key=(string)Input::post('relation');$definition=$this->registry->relationship($key);$values=[];
                foreach((array)($request->request->all()['targets']??[]) as $id)if((int)$id)$values[]=new Api\RelationshipValue(new Api\EntityReference((int)$id),$definition?->localized?$language:null);
                $this->editor->saveRelationship(new Api\EntityReference($entity),$key,$values,(int)Input::post('revision'),$write);
                $html.='<p class="tl_confirm">Saved.</p>';
            }
        } catch(\InvalidArgumentException $e) { $html.='<p class="tl_error">'.$this->e($e->getMessage()).'</p>'; }
        if($row) {
            $row=$this->store->record($entity);$type=$this->registry->type($row['entityType']);
            $html.='<h3>'.$this->e($row['name']).'</h3>';
            $translation=$this->store->translation($entity,$language);
            $description=$source?$this->registry->adapter($source->adapterKey)?->describe():null;
            $snapshot=$source?$this->registry->adapter($source->adapterKey)?->resolve($source,$read)->snapshot:null;
            $this->sourceRevision=$snapshot?->revision;
            if($source && !$snapshot)$html.='<p class="tl_error">Source unavailable. Supplementary editing is disabled.</p>';
            if($snapshot?->editUrl && preg_match('~^(?:/contao(?:/|\?)|\?do=)~',$snapshot->editUrl))$html.='<p><a href="'.$this->e($snapshot->editUrl).'">Edit original record</a></p>';
            foreach($type?->fields??[] as $field) {
                $owned=in_array($field->key,$field->localized?($description?->ownedLocalizedFields??[]):($description?->ownedGlobalFields??[]),true);
                $stored=Store::decode(($field->localized?$translation:$row)['schemaExtensionData']??null);
                if(!$field->localized && $field->key==='name')$stored['name']=$row['name'];
                $value=$owned?(($field->localized?$snapshot?->localizedFields:$snapshot?->globalFields)[$field->key]??null):($stored[$field->key]??$field->default);
                $html.='<fieldset><legend>'.$this->e($field->key).($owned?' (from source)':'').'</legend>';
                if($owned || ($source && !$snapshot))$html.='<p>'.$this->e(is_scalar($value)?$value:json_encode($value)).'</p>';
                elseif($field->localized && !$translation)$html.='<p>Create a language record first.</p>';
                else $html.=$this->form('field',$entity,$site,$language).'<input type="hidden" name="field" value="'.$this->e($field->key).'"><input type="hidden" name="revision" value="'.(int)$row['schemaExtensionRevision'].'">'.$this->widget($field,$value,'value').'<button>Save</button></form>';
                $html.='</fieldset>';
            }
            foreach($this->registry->relationships() as $key=>$definition) {
                if(!in_array($row['entityType'],$definition->sourceTypes,true))continue;
                $html.='<fieldset><legend>'.$this->e($definition->label).($definition->property===null?' (editorial only)':'').'</legend>';
                if(in_array($key,$description?->ownedRelationships??[],true) || ($source && !$snapshot)) { $html.='<p>From source</p></fieldset>';continue; }
                $selected=[];foreach($this->store->relations($entity,$language)[$key]??[] as $value)if($value->target instanceof Api\EntityReference)$selected[]=$value->target->id;
                $html.=$this->form('relationship',$entity,$site,$language).'<input type="hidden" name="relation" value="'.$this->e($key).'"><input type="hidden" name="revision" value="'.(int)$row['schemaExtensionRevision'].'"><select name="targets[]"'.($definition->multiple?' multiple':'').'><option value="0">—</option>';
                foreach($this->connection->fetchAllAssociative('SELECT id,name,entityType FROM tl_schema_entity ORDER BY name,id') as $target)if(in_array($target['entityType'],$definition->targetTypes,true))$html.='<option value="'.(int)$target['id'].'"'.(in_array((int)$target['id'],$selected,true)?' selected':'').'>'.$this->e($target['name']).'</option>';
                $html.='</select><button>Save</button></form></fieldset>';
            }
            $inspection=$this->editor->inspect(new Api\EntityReference($entity),$read);
            $html.='<h3>Preview</h3><pre style="white-space:pre-wrap">'.$this->e(json_encode($inspection,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</pre>';
        } else {
            foreach($this->registry->adapters() as $key=>$adapter) {
                $html.='<h3>'.$this->e($key).'</h3>';
                $query=new Api\CandidateQuery(new Api\ReadContext('discovery',$site,$language,(int)$user->id,true,time()),search:(string)Input::get('search'),cursor:Input::get('cursor'));
                $page=$this->editor->discover($key,$query);
                foreach($page->candidates as $candidate) {
                    $bound=$this->bindings->resolve($candidate->source);
                    $html.='<p>'.$this->e($candidate->label);
                    if($bound)$html.=' <a href="'.$this->e(html_entity_decode(\Contao\Backend::addToUrl('entity='.$bound->id),ENT_QUOTES)).'">Edit</a>';
                    else $html.=$this->form('bind',0,$site,$language).'<input type="hidden" name="adapter" value="'.$this->e($key).'"><input type="hidden" name="subject" value="'.$this->e($candidate->source->subjectKey).'"><input type="hidden" name="type" value="'.$this->e($candidate->typeKey).'"><input type="hidden" name="revision" value="'.$this->e($candidate->revision).'"><button>Create draft binding</button></form>';
                    $html.='</p>';
                }
                if($page->nextCursor!==null)$html.='<a href="'.$this->e(html_entity_decode(\Contao\Backend::addToUrl('cursor='.rawurlencode($page->nextCursor)),ENT_QUOTES)).'">Next</a>';
            }
            if(!$this->registry->adapters())$html.='<p>No source adapters registered.</p>';
        }
        return $html.'</div>';
    }
    private function label(string $key,string $fallback): string { return $this->e($GLOBALS['TL_LANG']['MSC'][$key]??$fallback); }
    private function widget(Api\FieldDefinition $field,mixed $value,string $name,int $depth=0): string
    {
        if($depth>5)return '';
        if($value instanceof Api\EntityReference)$value=$value->id;
        if($field->multiple) {
            $single=new Api\FieldDefinition($field->key,$field->type,constraints:$field->constraints);
            $html='';foreach(array_slice(array_merge(is_array($value)?$value:[],[null]),0,100) as $i=>$item)$html.='<div>'.$this->widget($single,$item,$name.'['.$i.']',$depth+1).'</div>';
            return $html;
        }
        if($field->type==='measurement') {
            $html='';foreach(['value','unit','basis'] as $key)$html.='<label>'.$key.' <input name="'.$this->e($name.'['.$key.']').'" value="'.$this->e($value[$key]??'').'"></label> ';
            return $html;
        }
        if($field->type==='structure') {
            $html='';foreach($field->constraints['fields']??[] as $child)$html.='<label>'.$this->e($child->key).$this->widget($child,$value[$child->key]??null,$name.'['.$child->key.']',$depth+1).'</label>';
            return $html;
        }
        if($field->type==='enum' || $field->type==='boolean') {
            $options=$field->type==='boolean'?['0','1']:array_merge([''],$field->constraints['choices']??[]);$html='<select name="'.$this->e($name).'">';
            foreach($options as $option)$html.='<option value="'.$this->e($option).'"'.((string)$value===(string)$option?' selected':'').'>'.$this->e($option).'</option>';
            return $html.'</select>';
        }
        if($field->widget==='textarea')return '<textarea name="'.$this->e($name).'">'.$this->e($value).'</textarea>';
        return '<input name="'.$this->e($name).'" value="'.$this->e($value).'">';
    }
    private function parse(Api\FieldDefinition $field,mixed $value,int $depth=0): mixed
    {
        if($depth>5)throw new \InvalidArgumentException('Field nesting exceeded.');
        if($field->multiple) {
            if(!is_array($value) || count($value)>100)throw new \InvalidArgumentException('Invalid field list.');
            $single=new Api\FieldDefinition($field->key,$field->type,constraints:$field->constraints);$items=[];
            foreach($value as $item){$parsed=$this->parse($single,$item,$depth+1);if($parsed!==null && $parsed!=='')$items[]=$parsed;}
            return $items;
        }
        if($field->type==='measurement' || $field->type==='structure') {
            if(!is_array($value))throw new \InvalidArgumentException('Invalid structured field.');
            if(!array_filter($value,static fn($v)=>$v!=='' && $v!==null))return null;
            if($field->type==='measurement')return $value;
            $result=[];foreach($field->constraints['fields']??[] as $child)$result[$child->key]=$this->parse($child,$value[$child->key]??null,$depth+1);
            return $result;
        }
        if(is_array($value))throw new \InvalidArgumentException('Scalar expected.');
        if($value==='' || $value===null)return null;
        return match($field->type){'integer'=>filter_var($value,FILTER_VALIDATE_INT)!==false?(int)$value:throw new \InvalidArgumentException('Integer expected.'),'boolean'=>$value==='1','reference'=>new Api\EntityReference((int)$value),default=>(string)$value};
    }
}
