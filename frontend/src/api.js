const BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
async function request(path, options={}) {
  const response = await fetch(`${BASE}${path}`, {headers:{Accept:'application/json','Content-Type':'application/json',...(options.headers||{})},...options})
  const body = await response.json().catch(()=>({message:'The server returned an unreadable response.'}))
  if (!response.ok) throw Object.assign(new Error(body.message || 'Something went wrong.'), {reason:body.reason,status:response.status,errors:body.errors})
  return body.data
}
export const api = {
  portfolio:()=>request('/portfolio'), price:()=>request('/market-price'), trades:()=>request('/trades'),
  quote:(input)=>request('/quotes',{method:'POST',body:JSON.stringify(input)}),
  confirm:(id)=>request(`/quotes/${id}/confirm`,{method:'POST',body:'{}'}),
}
