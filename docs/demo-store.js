import {makeSeed,validateData} from './model.js';
const KEY='dayflow-demo-v1';
export function loadDemo(storage){
  const raw=storage.getItem(KEY);
  if(raw){try{return validateData(JSON.parse(raw));}catch{storage.removeItem(KEY);}}
  const initial=makeSeed();
  storage.setItem(KEY,JSON.stringify(initial));
  return initial;
}
export function saveDemo(storage,next,revision){
  validateData(next);
  const saved=structuredClone(next);
  saved.revision=revision+1;
  storage.setItem(KEY,JSON.stringify(saved));
  return saved;
}
