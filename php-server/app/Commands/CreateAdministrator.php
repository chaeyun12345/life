<?php
namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\PersonalData;
class CreateAdministrator extends BaseCommand {
 protected $group='DAYFLOW';protected $name='dayflow:admin';protected $description='첫 관리자 생성. 기본 계정이나 웹 설치 페이지는 제공하지 않습니다.';
 public function run(array $params){
  $db=db_connect();if($db->table('df_users')->where('role','admin')->countAllResults()){CLI::error('관리자가 이미 있습니다.');return;}
  $email=strtolower(trim($params[0]??CLI::prompt('관리자 이메일')));$name=CLI::prompt('관리자 이름');CLI::write('비밀번호 입력 내용은 터미널에 표시될 수 있습니다.');$pw=CLI::prompt('12자 이상 비밀번호');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||mb_strlen($name)<1||mb_strlen($name)>80||strlen($pw)<12||strlen($pw)>72){CLI::error('입력값을 확인하세요.');return;}
  $db->transStart();$db->table('df_users')->insert(['email'=>$email,'name'=>$name,'password_hash'=>password_hash($pw,PASSWORD_DEFAULT),'role'=>'admin','active'=>1,'must_change_password'=>0,'session_version'=>1,'created_at'=>date('Y-m-d H:i:s')]);
  $id=$db->insertID();$db->table('df_personal_data')->insert(['user_id'=>$id,'revision'=>0,'payload'=>json_encode(PersonalData::empty(),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'updated_at'=>date('Y-m-d H:i:s')]);$db->transComplete();if(!$db->transStatus())throw new \RuntimeException('관리자 생성 실패');CLI::write('관리자를 생성했습니다. /login 에서 로그인하세요.','green');
 }
}
