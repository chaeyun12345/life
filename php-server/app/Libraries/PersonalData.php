<?php
namespace App\Libraries;
use InvalidArgumentException;
class PersonalData {
 public static function empty():array{return ['schemaVersion'=>1,'revision'=>0,'sample'=>false,'settings'=>['budget'=>0],'tasks'=>[],'goals'=>[],'assets'=>[],'transactions'=>[],'expected'=>[],'savings'=>[],'items'=>[],'habits'=>[['id'=>'water','name'=>'물 마시기'],['id'=>'exercise','name'=>'30분 운동']],'habitLog'=>(object)[],'notes'=>(object)[]];}
 public static function validate(array $d):array{
  $bad=static fn()=>throw new InvalidArgumentException('입력 데이터 형식을 확인해 주세요.');
  $str=static fn($v,$max=200)=>is_string($v)&&mb_strlen($v)>0&&mb_strlen($v)<=$max;
  $num=static fn($v,$min=0)=>(is_int($v)||is_float($v))&&is_finite((float)$v)&&$v>=$min&&$v<=1e15;
  $date=static function($v){if(!is_string($v)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v))return false;[$y,$m,$day]=array_map('intval',explode('-',$v));return checkdate($m,$day,$y);};
  if(($d['schemaVersion']??null)!==1||!is_int($d['revision']??null)||$d['revision']<0||!is_bool($d['sample']??null)||!$num($d['settings']['budget']??null))$bad();
  $fields=['tasks'=>['title'=>200,'category'=>30,'date'=>'date','time'=>'time','done'=>'bool','important'=>'bool'],'goals'=>['title'=>200,'category'=>30,'unit'=>10,'deadline'=>'date','current'=>'num','target'=>'positive'],'assets'=>['name'=>200,'category'=>30,'amount'=>'num'],'transactions'=>['title'=>200,'category'=>30,'date'=>'date','amount'=>'integer','kind'=>['income','expense','saving']],'expected'=>['title'=>200,'date'=>'date','amount'=>'integer','recurring'=>'bool','status'=>['expected','confirmed','received']],'savings'=>['title'=>200,'start'=>'date','target'=>'positive','initial'=>'num','monthly'=>'positive'],'items'=>['name'=>200,'platform'=>50,'date'=>'date','price'=>'num','actual'=>'num','status'=>['draft','selling','pending','sold']],'habits'=>['name'=>50]];
  $ids=[];
  foreach($fields as $collection=>$rules){
   if(!is_array($d[$collection]??null)||!array_is_list($d[$collection])||count($d[$collection])>10000)$bad();$ids[$collection]=[];
   foreach($d[$collection] as $item){
    if(!is_array($item)||!$str($item['id']??null,100)||isset($ids[$collection][$item['id']]))$bad();$ids[$collection][$item['id']]=true;
    foreach($rules as $key=>$rule){$v=$item[$key]??null;$ok=is_int($rule)?$str($v,$rule):(is_array($rule)?in_array($v,$rule,true):match($rule){'date'=>$date($v),'time'=>is_string($v)&&(bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$v),'bool'=>is_bool($v),'num'=>$num($v),'positive'=>$num($v,1),'integer'=>is_int($v)&&$num($v,1),default=>false});if(!$ok)$bad();}
    if($collection==='items'&&$item['status']==='sold'&&$item['actual']<1)$bad();
   }
  }
  foreach($d['transactions'] as $t)foreach(['savingId'=>'savings','sourceExpected'=>'expected','sourceItem'=>'items'] as $key=>$c)if(isset($t[$key])&&(!is_string($t[$key])||!isset($ids[$c][$t[$key]])))$bad();
  foreach(['habitLog','notes'] as $key)if(!isset($d[$key])||(!is_array($d[$key])&&!is_object($d[$key])))$bad();
  foreach($d['habitLog'] as $day=>$list){if(!$date($day)||!is_array($list)||!array_is_list($list))$bad();$seen=[];foreach($list as $id){if(!is_string($id)||!isset($ids['habits'][$id])||isset($seen[$id]))$bad();$seen[$id]=true;}}
  foreach($d['notes'] as $day=>$note)if(!$date($day)||!is_array($note)||!is_string($note['focus']??null)||mb_strlen($note['focus'])>1000||!is_string($note['memo']??null)||mb_strlen($note['memo'])>10000)$bad();
  $d['habitLog']=(object)$d['habitLog'];$d['notes']=(object)$d['notes'];
  return array_intersect_key($d,array_flip(array_merge(['schemaVersion','revision','sample','settings','habitLog','notes'],array_keys($fields))));
 }
}
