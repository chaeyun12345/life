<?php
namespace App\Controllers;
use App\Libraries\PersonalData;
use CodeIgniter\HTTP\ResponseInterface;
class Dayflow extends BaseController {
 protected $helpers=['url','form'];
 private function user():?array{
  $s=session();$id=$s->get('user_id');if(!$id)return null;$u=db_connect()->table('df_users')->where('id',$id)->get()->getRowArray();
  if(!$u||!(int)$u['active']||(int)$u['session_version']!==(int)$s->get('version')){$s->destroy();return null;}return $u;
 }
 private function reply($body,int $code=200):ResponseInterface{return $this->response->setHeader('Cache-Control','no-store, private')->setStatusCode($code)->setJSON($body);}
 private function access(bool $admin=false):array|ResponseInterface{
  $u=$this->user();if(!$u)return $this->reply(['error'=>'로그인이 필요합니다.'],401);if((int)$u['must_change_password'])return $this->reply(['error'=>'먼저 비밀번호를 변경해 주세요.'],403);if($admin&&$u['role']!=='admin')return $this->reply(['error'=>'관리자만 사용할 수 있습니다.'],403);return $u;
 }
 private function input():array{try{$v=$this->request->getJSON(true);return is_array($v)?$v:[];}catch(\Throwable){return [];}}
 public function loginPage(){if($this->user())return redirect()->to(site_url('/'));return $this->response->setHeader('Cache-Control','no-store')->setBody(view('login'));}
 public function login(){
  $v=$this->input();$email=strtolower(trim((string)($v['email']??'')));$pw=(string)($v['password']??'');$db=db_connect();$now=time();$fp=hash('sha256',$this->request->getIPAddress().'|'.$email);$ipfp=hash('sha256','ip|'.$this->request->getIPAddress());
  $db->table('df_login_attempts')->where('attempted_at <',$now-900)->delete();
  if($db->table('df_login_attempts')->where('fingerprint',$ipfp)->countAllResults()>=20||$db->table('df_login_attempts')->where('fingerprint',$fp)->countAllResults()>=5)return $this->reply(['error'=>'잠시 후 다시 시도해 주세요.'],429);
  $u=$db->table('df_users')->where('email',$email)->get()->getRowArray();$valid=password_verify($pw,$u['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
  if(!$u||!$valid||!(int)$u['active']){$db->table('df_login_attempts')->insertBatch([['fingerprint'=>$fp,'attempted_at'=>$now],['fingerprint'=>$ipfp,'attempted_at'=>$now]]);return $this->reply(['error'=>'이메일 또는 비밀번호를 확인해 주세요.'],401);}
  $db->table('df_login_attempts')->where('fingerprint',$fp)->delete();session()->regenerate(true);session()->set(['user_id'=>(int)$u['id'],'version'=>(int)$u['session_version']]);return $this->reply(['redirect'=>site_url((int)$u['must_change_password']?'password':'/')]);
 }
 public function logout(){session()->destroy();return $this->reply(['redirect'=>site_url('login')]);}
 public function passwordPage(){if(!$this->user())return redirect()->to(site_url('login'));return $this->response->setHeader('Cache-Control','no-store')->setBody(view('password'));}
 public function password(){
  $u=$this->user();if(!$u)return $this->reply(['error'=>'로그인이 필요합니다.'],401);$v=$this->input();$pw=(string)($v['password']??'');
  if(!password_verify((string)($v['current']??''),$u['password_hash'])||strlen($pw)<12||strlen($pw)>72||password_verify($pw,$u['password_hash']))return $this->reply(['error'=>'현재 비밀번호와 새 비밀번호(12~72바이트)를 확인하세요. 같은 비밀번호는 사용할 수 없습니다.'],422);
  $version=(int)$u['session_version']+1;db_connect()->table('df_users')->where('id',$u['id'])->update(['password_hash'=>password_hash($pw,PASSWORD_DEFAULT),'must_change_password'=>0,'session_version'=>$version]);session()->regenerate(true);session()->set('version',$version);return $this->reply(['redirect'=>site_url('/')]);
 }
 public function dashboard(){ $u=$this->user();if(!$u)return redirect()->to(site_url('login'));if((int)$u['must_change_password'])return redirect()->to(site_url('password'));return $this->response->setHeader('Cache-Control','no-store, private')->setBody(view('dashboard',['user'=>$u]));}
 public function data(){
  $u=$this->access();if($u instanceof ResponseInterface)return $u;$db=db_connect();$row=$db->table('df_personal_data')->where('user_id',$u['id'])->get()->getRowArray();
  if($this->request->getMethod()==='GET')return $this->reply(json_decode($row['payload'],false,512,JSON_THROW_ON_ERROR));
  if(strlen($this->request->getBody())>5000000)return $this->reply(['error'=>'데이터는 5MB 이하여야 합니다.'],413);
  try{$d=PersonalData::validate($this->input());}catch(\InvalidArgumentException $e){return $this->reply(['error'=>$e->getMessage()],422);}
  $revision=$d['revision'];$d['revision']++;$db->table('df_personal_data')->where('user_id',$u['id'])->where('revision',$revision)->update(['payload'=>json_encode($d,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'revision'=>$d['revision'],'updated_at'=>date('Y-m-d H:i:s')]);
  if($db->affectedRows()!==1)return $this->reply(['error'=>'다른 창에서 변경했습니다. 현재 내용을 백업한 뒤 새로고침해 주세요.'],409);return $this->reply($d);
 }
 public function announcements(){ $u=$this->access();if($u instanceof ResponseInterface)return $u;return $this->reply(db_connect()->table('df_announcements')->where('published',1)->orderBy('updated_at','DESC')->get()->getResultArray());}
 public function adminPage(){ $u=$this->access(true);if($u instanceof ResponseInterface)return $u;return $this->response->setHeader('Cache-Control','no-store, private')->setBody(view('admin',['user'=>$u]));}
 public function members(){
  $u=$this->access(true);if($u instanceof ResponseInterface)return $u;$db=db_connect();if($this->request->getMethod()==='GET')return $this->reply($db->table('df_users')->select('id,email,name,role,active,must_change_password,created_at')->orderBy('id','DESC')->get()->getResultArray());
  $v=$this->input();$email=strtolower(trim((string)($v['email']??'')));$name=trim((string)($v['name']??''));$pw=(string)($v['password']??'');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||mb_strlen($name)<1||mb_strlen($name)>80||strlen($pw)<12||strlen($pw)>72)return $this->reply(['error'=>'이메일·이름·임시 비밀번호(12~72바이트)를 확인하세요.'],422);
  if($db->table('df_users')->where('email',$email)->countAllResults())return $this->reply(['error'=>'이미 등록된 이메일입니다.'],409);
  $db->transStart();$db->table('df_users')->insert(['email'=>$email,'name'=>$name,'password_hash'=>password_hash($pw,PASSWORD_DEFAULT),'role'=>'member','active'=>1,'must_change_password'=>1,'session_version'=>1,'created_at'=>date('Y-m-d H:i:s')]);$id=$db->insertID();$db->table('df_personal_data')->insert(['user_id'=>$id,'revision'=>0,'payload'=>json_encode(PersonalData::empty(),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'updated_at'=>date('Y-m-d H:i:s')]);$db->transComplete();if(!$db->transStatus())return $this->reply(['error'=>'회원 생성에 실패했습니다.'],500);return $this->reply(['id'=>$id],201);
 }
 public function member(int $id){
  $u=$this->access(true);if($u instanceof ResponseInterface)return $u;$db=db_connect();$t=$db->table('df_users')->where('id',$id)->get()->getRowArray();if(!$t)return $this->reply(['error'=>'회원을 찾을 수 없습니다.'],404);if($t['role']==='admin')return $this->reply(['error'=>'관리자 계정은 이 화면에서 변경할 수 없습니다.'],403);
  $v=$this->input();$changes=[];if(array_key_exists('active',$v)){if(!is_bool($v['active']))return $this->reply(['error'=>'회원 상태를 확인하세요.'],422);$changes['active']=$v['active']?1:0;}
  if(isset($v['password'])){$pw=(string)$v['password'];if(strlen($pw)<12||strlen($pw)>72)return $this->reply(['error'=>'임시 비밀번호는 12~72바이트여야 합니다.'],422);$changes['password_hash']=password_hash($pw,PASSWORD_DEFAULT);$changes['must_change_password']=1;}
  if(!$changes)return $this->reply(['error'=>'변경할 항목이 없습니다.'],422);$changes['session_version']=(int)$t['session_version']+1;$db->table('df_users')->where('id',$id)->update($changes);return $this->reply(['ok'=>true]);
 }
 public function adminAnnouncements(?int $id=null){
  $u=$this->access(true);if($u instanceof ResponseInterface)return $u;$db=db_connect();$method=$this->request->getMethod();if($method==='GET')return $this->reply($db->table('df_announcements')->orderBy('id','DESC')->get()->getResultArray());
  if($id&&!$db->table('df_announcements')->where('id',$id)->countAllResults())return $this->reply(['error'=>'공지를 찾을 수 없습니다.'],404);
  if($method==='DELETE'){$db->table('df_announcements')->where('id',$id)->delete();return $this->reply(['ok'=>true]);}
  $v=$this->input();$title=trim((string)($v['title']??''));$body=trim((string)($v['body']??''));if(mb_strlen($title)<1||mb_strlen($title)>200||mb_strlen($body)<1||mb_strlen($body)>10000||!is_bool($v['published']??null))return $this->reply(['error'=>'공지 제목·내용·공개 상태를 확인하세요.'],422);
  $values=['title'=>$title,'body'=>$body,'published'=>$v['published']?1:0,'updated_at'=>date('Y-m-d H:i:s')];if($id)$db->table('df_announcements')->where('id',$id)->update($values);else{$db->table('df_announcements')->insert($values);$id=$db->insertID();}return $this->reply(['id'=>$id]);
 }
}
