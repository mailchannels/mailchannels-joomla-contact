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
