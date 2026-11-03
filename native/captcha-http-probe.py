"""Installed candidate form-to-HTTPS validation; all messages synthetic."""
import json
from contact_http import fields,request,root
checks=0
def check(ok,label):
 global checks
 assert ok,label
 checks+=1;print('PASS '+label)
def records():
 return [json.loads(line) for line in root.joinpath('visibility-contact-events').read_text().splitlines()]
code,html=request()
check(code==200 and 'data-visibility-captcha="test-only"' in html,'native contact form renders configured CAPTCHA provider')
for answer in [None,'fixture-fail','fixture-exception','fixture-pass']:
 root.joinpath('visibility-contact-events').write_text('');root.joinpath('visibility-captcha-checks').write_text('')
 d=fields()
 if answer is not None:d['jform[captcha]']=answer
 code,html=request(d)
 check(root.joinpath('visibility-captcha-checks').read_text()=='checked\n',f'{answer!r}: native CAPTCHA provider called once')
 if answer=='fixture-pass':
  check(len(records())==1 and code==200 and 'accepted for email processing' in html,'successful fixture CAPTCHA permits one isolated HTTPS request')
 else:
  check(records()==[],f'{answer!r}: no HTTPS submission on failed CAPTCHA')
  check(code==200 and 'accepted for email processing' not in html,'failed CAPTCHA returns form without email acceptance')
print(f'JOOMLA_CAPTCHA_HTTP_COMPLETE {checks} checks')
