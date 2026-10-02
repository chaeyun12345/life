export async function request(path,options={}){
 const base=document.querySelector('meta[name="app-base"]').content,token=document.querySelector('meta[name="csrf-token"]').content;
 const r=await fetch(new URL(path,base),{credentials:'same-origin',...options,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,...options.headers}});
 let value;try{value=await r.json()}catch{throw Error('연결을 확인한 뒤 다시 시도해 주세요.');}
 if(r.status===401&&path!=='api/login'){location.assign(new URL('login',base));throw Error('로그인이 만료되었습니다.');}
 if(!r.ok)throw Error(value.error||'요청을 처리하지 못했습니다. 페이지를 새로고침해 주세요.');return value;
}
document.querySelectorAll('[data-logout]').forEach(b=>b.onclick=async()=>{try{const v=await request('api/logout',{method:'POST',body:'{}'});location.assign(v.redirect)}catch(e){alert(e.message)}});
const form=document.querySelector('[data-auth-form]');
if(form)form.addEventListener('submit',async e=>{e.preventDefault();const error=document.querySelector('#auth-error'),button=form.querySelector('button[type=submit]');error.textContent='';button.disabled=true;try{const values=Object.fromEntries(new FormData(form));if(values.confirm!==undefined&&values.confirm!==values.password)throw Error('새 비밀번호가 일치하지 않습니다.');delete values.confirm;const v=await request(form.dataset.authForm,{method:'POST',body:JSON.stringify(values)});location.assign(v.redirect);}catch(e){error.textContent=e.message}finally{button.disabled=false}});
