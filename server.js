import http from 'node:http';
import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {makeSeed,validateData} from './model.js';
const root=path.dirname(fileURLToPath(import.meta.url)),dataDir=process.env.DAYFLOW_DATA_DIR||path.join(root,'data'),port=Number(process.env.DAYFLOW_PORT||4173),store=path.join(dataDir,'dayflow.json');
await fs.mkdir(dataDir,{recursive:true});let data;
try{data=validateData(JSON.parse(await fs.readFile(store,'utf8')))}catch(e){if(e.code!=='ENOENT')throw new Error('저장 데이터 파일을 확인하세요. '+e.message);data=makeSeed();await fs.writeFile(store,JSON.stringify(data,null,2));}
const publicFiles=new Set(['index.html','style.css','app.js','model.js','ui-font.ttf']);
const types={'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.ttf':'font/ttf'};
const headers={'Cache-Control':'no-store','X-Content-Type-Options':'nosniff','Content-Security-Policy':"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'"};
function json(res,status,obj){res.writeHead(status,{...headers,'Content-Type':'application/json; charset=utf-8'});res.end(JSON.stringify(obj))}
async function readBody(req){const chunks=[];let length=0;for await(const chunk of req){length+=chunk.length;if(length>5000000)throw new Error('최대 5MB까지 사용할 수 있습니다.');chunks.push(chunk)}return JSON.parse(Buffer.concat(chunks).toString('utf8'))}
let writing=false;
const server=http.createServer(async(req,res)=>{try{const host=req.headers.host;if(![`127.0.0.1:${port}`,`localhost:${port}`].includes(host)){json(res,403,{error:'이 PC에서만 접속할 수 있습니다.'});return}const u=new URL(req.url,`http://${host}`);if(req.headers.origin&&!['http://127.0.0.1:'+port,'http://localhost:'+port].includes(req.headers.origin)){json(res,403,{error:'허용되지 않은 요청입니다.'});return}
if(u.pathname==='/api/data'){if(req.method==='GET'){json(res,200,data);return}if(req.method!=='PUT'){json(res,405,{error:'허용되지 않은 요청입니다.'});return}if(!req.headers['content-type']?.startsWith('application/json')){json(res,415,{error:'JSON 형식이 필요합니다.'});return}const next=validateData(await readBody(req));if(writing||next.revision!==data.revision){json(res,409,{error:'다른 창에서 변경되었습니다. 새로고침한 뒤 다시 입력해 주세요.'});return}writing=true;try{next.revision=data.revision+1;await fs.copyFile(store,path.join(dataDir,'dayflow.previous.json'));const temp=path.join(dataDir,'dayflow.tmp');await fs.writeFile(temp,JSON.stringify(next,null,2));await fs.rename(temp,store);data=next;json(res,200,data)}finally{writing=false}return}
if(req.method!=='GET'&&req.method!=='HEAD'){json(res,405,{error:'허용되지 않은 요청입니다.'});return}const file=u.pathname==='/'?'index.html':u.pathname.slice(1);if(!publicFiles.has(file)){res.writeHead(404,headers);res.end('Not found');return}const body=await fs.readFile(path.join(root,file));res.writeHead(200,{...headers,'Content-Type':types[path.extname(file)]});res.end(req.method==='HEAD'?undefined:body)}catch(e){json(res,400,{error:e.message||'저장에 실패했습니다.'})}});
server.on('error',error=>{if(error.code==='EADDRINUSE'){console.log(`이미 실행 중입니다: http://127.0.0.1:${port}`);process.exit(0)}console.error(error);process.exit(1)});
server.listen(port,'127.0.0.1',()=>console.log(`DAYFLOW http://127.0.0.1:${port}\n데이터: ${store}\n종료: Ctrl+C`));
