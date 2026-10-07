"""Installed candidate form-to-HTTPS validation; all messages synthetic."""
import json,os,sys
from html.parser import HTMLParser
from http.cookiejar import CookieJar
from pathlib import Path
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urlencode
origin='http://visibility-joomla-http:18379'
root=Path('/app/tmp')
contact=root.joinpath('visibility-contact-id').read_text().strip()
path=f'/index.php?option=com_contact&view=contact&id={contact}'
class Form(HTMLParser):
 def __init__(self,html):super().__init__();self.fields={};self.active=False;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='form':self.active=a.get('id')=='contact-form'
  if self.active and tag=='input' and a.get('type')=='hidden' and a.get('name'):self.fields[a['name']]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.active=False
client=build_opener(HTTPCookieProcessor(CookieJar()))
def request(data=None):
 try:
  with client.open(Request(origin+path,data=urlencode(data).encode() if data is not None else None),timeout=20) as r:return r.status,r.read().decode()
 except HTTPError as e:return e.code,e.read().decode()
def fields():
 code,html=request();assert code==200,(code,html[:100]);d=Form(html).fields
 d.update({'jform[contact_name]':'Synthetic Visitor','jform[contact_email]':'visitor@example.com','jform[contact_subject]':'Fixture','jform[contact_message]':'Synthetic fixture only','task':'contact.submit','option':'com_contact','id':contact})
 assert any(len(k)==32 and v=='1' for k,v in d.items()),'Missing CSRF token'
 return d
checks=0
def check(ok,label):
 global checks
 assert ok,label
 checks+=1;print('PASS '+label)
def records():
 return [json.loads(line) for line in root.joinpath('visibility-contact-events').read_text().splitlines()]
name='jform[com_fields][visibility-required-team]'
code,html=request()
check(code==200 and name in html,'native contact form renders required custom field')
for value in [None,'']:
 root.joinpath('visibility-contact-events').write_text('')
 d=fields()
 if value is not None:d[name]=value
 code,html=request(d)
 check(records()==[],f'required custom field {value!r} prevents HTTPS submission')
 check(code==200 and 'accepted for email processing' not in html and 'Required team' in html,'invalid field returns form without acceptance')
root.joinpath('visibility-contact-events').write_text('')
d=fields();d[name]='Équipe 日本';d['jform[com_fields][unconfigured-field]']='UNCONFIGURED_SENTINEL'
code,html=request(d);observed=records()
check(code==200 and 'accepted for email processing' in html and len(observed)==1,'valid required field reaches isolated provider once')
content=' '.join(part['value'] for part in observed[0]['content'])
check('Équipe 日本' in content,'validated Unicode custom field reaches native email body')
check('UNCONFIGURED_SENTINEL' not in content,'unconfigured posted field excluded from email')
print(f'JOOMLA_CUSTOM_FIELD_HTTP_COMPLETE {checks} checks')
