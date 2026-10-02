import {request} from './auth.js';
const $=s=>document.querySelector(s),esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let notices=[],editing=null,resetId=null;
const message=(text,error=false)=>{$('#admin-message').textContent=text;$('#admin-message').className=error?'error':'';};
async function load(){const [members,rows]=await Promise.all([request('api/admin/members'),request('api/admin/announcements')]);notices=rows;
 $('#members').innerHTML=members.map(u=>`<tr><td><strong>${esc(u.name)}</strong><br>${esc(u.email)}</td><td>${u.role==='admin'?'관리자':Number(u.active)?'활성':'이용 중지'}${Number(u.must_change_password)?'<br><small>비밀번호 변경 필요</small>':''}</td><td>${esc(u.created_at)}</td><td>${u.role==='admin'?'본인 설정에서 비밀번호 변경':`<div class="member-controls"><button class="button secondary" data-status="${u.id}" data-active="${Number(u.active)?'false':'true'}">${Number(u.active)?'이용 중지':'다시 활성화'}</button><button class="button secondary" data-reset="${u.id}">비밀번호 재발급</button></div>`}</td></tr>`).join('');
 $('#announcements').innerHTML=rows.length?rows.map(n=>`<article><h3>${esc(n.title)} <small>${Number(n.published)?'공개':'비공개'}</small></h3><p class="announcement-text">${esc(n.body)}</p><div class="card-actions"><button class="button secondary" data-edit="${n.id}">수정</button><button class="button danger" data-delete="${n.id}">삭제</button></div></article>`).join(''):'<p class="empty">등록된 공지가 없습니다.</p>';
}
async function submit(form,fn){const b=form.querySelector('button');b.disabled=true;try{await fn();await load()}catch(e){message(e.message,true)}finally{b.disabled=false}}
$('#member-form').onsubmit=e=>{e.preventDefault();const f=e.currentTarget;submit(f,async()=>{await request('api/admin/members',{method:'POST',body:JSON.stringify(Object.fromEntries(new FormData(f)))});f.reset();message('회원을 등록했습니다. 임시 비밀번호는 별도로 전달하세요.');});};
$('#notice-form').onsubmit=e=>{e.preventDefault();const f=e.currentTarget;submit(f,async()=>{const body=Object.fromEntries(new FormData(f));body.published=f.elements.published.checked;await request('api/admin/announcements'+(editing?'/'+editing:''),{method:editing?'PUT':'POST',body:JSON.stringify(body)});resetNotice();message('공지를 저장했습니다.');});};
function resetNotice(){editing=null;$('#notice-form').reset();$('#notice-heading').textContent='공지 작성';}$('#notice-cancel').onclick=resetNotice;
$('#reset-cancel').onclick=()=>$('#reset-dialog').close();
$('#reset-form').onsubmit=async e=>{e.preventDefault();const f=e.currentTarget,b=f.querySelector('button');b.disabled=true;$('#reset-error').textContent='';try{await request('api/admin/members/'+resetId,{method:'PUT',body:JSON.stringify(Object.fromEntries(new FormData(f)))});f.reset();$('#reset-dialog').close();message('임시 비밀번호를 재발급했습니다. 기존 로그인은 만료됩니다.');await load();}catch(e){$('#reset-error').textContent=e.message}finally{b.disabled=false}};
document.addEventListener('click',async e=>{const b=e.target.closest('button');if(!b)return;try{
 if(b.dataset.status){if(!confirm('회원 이용 상태를 변경할까요? 기존 로그인은 만료됩니다.'))return;await request('api/admin/members/'+b.dataset.status,{method:'PUT',body:JSON.stringify({active:b.dataset.active==='true'})});await load();message('회원 상태를 변경했습니다.');}
 if(b.dataset.reset){resetId=b.dataset.reset;$('#reset-form').reset();$('#reset-error').textContent='';$('#reset-dialog').showModal();}
 if(b.dataset.edit){const n=notices.find(x=>String(x.id)===b.dataset.edit);editing=n.id;const f=$('#notice-form');f.elements.title.value=n.title;f.elements.body.value=n.body;f.elements.published.checked=!!Number(n.published);$('#notice-heading').textContent='공지 수정';f.scrollIntoView({behavior:'smooth',block:'center'});}
 if(b.dataset.delete&&confirm('공지를 삭제할까요?')){await request('api/admin/announcements/'+b.dataset.delete,{method:'DELETE',body:'{}'});await load();message('공지를 삭제했습니다.');}
 }catch(e){message(e.message,true)}});load().catch(e=>message(e.message,true));
