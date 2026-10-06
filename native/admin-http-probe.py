"""Isolated native administrator permission and CSRF checks. Dummy users only."""
import json
from html.parser import HTMLParser
from http.cookiejar import CookieJar
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urlencode
from pathlib import Path
origin='http://visibility-joomla-http:18379'
id=Path('/app/tmp/visibility-admin-extension-id').read_text().strip()
edit='/administrator/index.php?option=com_plugins&task=plugin.edit&extension_id='+id
class Form(HTMLParser):
 def __init__(self,html):super().__init__();self.fields={};self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='input' and a.get('name') and a.get('type') not in ['checkbox','radio','submit']:self.fields[a['name']]=a.get('value','')
def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(c,path,data=None):
 try:
  with c.open(Request(origin+path,data=urlencode(data).encode() if data is not None else None),timeout=20) as r:return r.status,r.read().decode()
 except HTTPError as e:return e.code,e.read().decode()
n=0
def check(ok,label):
 global n
 assert ok,label
 n+=1;print('PASS '+label,flush=True)
def login(c,user,password):
 _,html=request(c,'/administrator/')
 d=Form(html).fields;d.update(username=user,passwd=password,option='com_login',task='login')
 return request(c,'/administrator/index.php',d)
anon=client();code,html=request(anon,edit)
check('name="passwd"' in html and 'jform[params][contact_ids]' not in html,'anonymous edit redirects to administrator login')
code,html=request(anon,'/administrator/index.php?option=com_plugins&task=plugin.apply',{'jform[extension_id]':id,'jform[params][contact_ids]':'666'})
check('name="passwd"' in html,'anonymous save cannot pass administrator login')
registered=client();code,html=login(registered,'fixture-registered','Local-Registered-Fixture-12345!')
check('name="passwd"' in html,'Registered user denied administrator login')
code,html=request(registered,edit)
check('jform[params][contact_ids]' not in html,'Registered user cannot access plugin edit form')
admin=client();code,html=login(admin,'fixture-operator','Local-Joomla-Fixture-12345!')
check(code==200 and 'name="passwd"' not in html,'fixture administrator signs in')
code,html=request(admin,edit)
check(code==200 and 'jform[params][contact_ids]' in html and 'Enabled contact IDs' in html,'administrator sees localized contact configuration')
check(Form(html).fields.get('jform[params][contact_ids]','')=='','denied users left contact selection empty')
d=Form(html).fields;d.update({'jform[extension_id]':id,'jform[params][contact_ids]':'42,77','jform[enabled]':'0','jform[access]':'1','jform[ordering]':'0','task':'plugin.apply','option':'com_plugins','extension_id':id})
for mode in ['missing','wrong']:
 invalid={k:v for k,v in d.items() if not(len(k)==32 and v=='1')}
 if mode=='wrong':invalid['0'*32]='1'
 code,result=request(admin,'/administrator/index.php',invalid)
 check('invalid' in result.lower() and 'token' in result.lower(),mode+' CSRF token rejected')
 _,unchanged=request(admin,edit)
 check(Form(unchanged).fields.get('jform[params][contact_ids]','')=='',mode+' CSRF leaves contact selection unchanged')
code,result=request(admin,'/administrator/index.php',d)
check(code==200 and 'value="42,77"' in result,'valid administrator CSRF save persists selected contact IDs')
print(f'JOOMLA_ADMIN_HTTP_COMPLETE {n} checks')
