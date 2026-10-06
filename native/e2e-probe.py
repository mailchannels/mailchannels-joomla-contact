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
gate=os.environ.get('JOOMLA_E2E_GATE')
if gate:
 root.joinpath('visibility-contact-events').write_text('')
 d=fields();code,html=request(d)
 check(records()==[],gate+' prevents HTTPS submission')
 check(code==(503 if gate=='no-suppression' else 200) and 'accepted for email processing' not in html,gate+' does not claim acceptance')
 print(f'JOOMLA_E2E_GATE_COMPLETE {gate} {checks} checks');sys.exit(0)
for mode,copy,expected in [('success',False,1),('success',True,2),('reject',True,1),('copy-failure',True,2)]:
 root.joinpath('visibility-contact-mode').write_text(mode);root.joinpath('visibility-contact-events').write_text('')
 d=fields()
 if copy:d['jform[contact_email_copy]']='1'
 code,html=request(d);observed=records()
 check(len(observed)==expected,f'{mode} copy={copy}: exact HTTPS request count')
 check(all(r['key_ok'] and r['from']=='operator@example.com' and r['reply_to']=='visitor@example.com' and r['path']=='/tx/v1/send' for r in observed),'native rendered requests preserve sender/reply-to and dummy authentication')
 check(observed[0]['to']==['recipient@example.com'],'primary contact destination preserved')
 if expected==2:check(observed[1]['to']==['visitor@example.com'],'copy uses separate visitor destination')
 if mode=='success':check(code==200 and 'accepted for email processing' in html,'success redirect shows API acceptance message')
 else:check(code==503 and 'accepted for email processing' not in html,'uncertain/failure response does not claim success')
root.joinpath('visibility-contact-mode').write_text('success');root.joinpath('visibility-contact-events').write_text('')
d=fields();d={k:v for k,v in d.items() if not(len(k)==32 and v=='1')};code,html=request(d)
check(records()==[],'missing CSRF prevents actual HTTPS submission')
print(f'JOOMLA_E2E_COMPLETE {checks} checks')
