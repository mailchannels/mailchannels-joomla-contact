"""Installed candidate form-to-HTTPS validation; all messages synthetic."""
import json,os,sys
from pathlib import Path
from contact_http import fields,request,root
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
 check(code==200 and 'accepted for email processing' not in html and (gate!='no-suppression' or 'could not confirm that your message was sent' in html),gate+' does not claim acceptance')
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
 else:
  log=Path('/app/administrator/logs/plg_contact_mailchannelscontact.php').read_text()
  failed=[k for k,v in {'status':code==200,'no acceptance':'accepted for email processing' not in html,'warning':'could not confirm that your message was sent' in html,'form kept':'Synthetic fixture only' in html,'logged':'message not accepted (HTTP 202' in log,'redacted':'tls-fixture-dummy-key' not in log and 'visitor@example.com' not in log}.items() if not v]
  check(not failed,'uncertain/failure response does not claim success and logs redacted reason'+(f' (failed: {failed}, HTTP {code})' if failed else ''))
root.joinpath('visibility-contact-mode').write_text('success');root.joinpath('visibility-contact-events').write_text('')
d=fields();d={k:v for k,v in d.items() if not(len(k)==32 and v=='1')};code,html=request(d)
check(records()==[],'missing CSRF prevents actual HTTPS submission')
print(f'JOOMLA_E2E_COMPLETE {checks} checks')
