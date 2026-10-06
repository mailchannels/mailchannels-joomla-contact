"""Run real cURL TLS probes on an internal Docker network, never the provider."""
from pathlib import Path
from datetime import datetime, timedelta, timezone
import json, subprocess, time, os
from cryptography import x509
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.x509.oid import NameOID
root=Path(__file__).resolve().parent
candidate=root.parent
out=root/'generated';out.mkdir(exist_ok=True)
now=datetime.now(timezone.utc)
def key():return rsa.generate_private_key(public_exponent=65537,key_size=2048)
def ca(label):
    k=key();n=x509.Name([x509.NameAttribute(NameOID.COMMON_NAME,label)])
    c=x509.CertificateBuilder().subject_name(n).issuer_name(n).public_key(k.public_key()).serial_number(x509.random_serial_number()).not_valid_before(now-timedelta(days=2)).not_valid_after(now+timedelta(days=30)).add_extension(x509.BasicConstraints(ca=True,path_length=None),True).sign(k,hashes.SHA256())
    return k,c
trusted_key,trusted_ca=ca('Isolated Joomla Fixture CA')
untrusted_key,untrusted_ca=ca('Untrusted Fixture CA')
(out/'ca.crt').write_bytes(trusted_ca.public_bytes(serialization.Encoding.PEM))
for name in ['trusted','wrong-host','expired','untrusted']:
    k=key();ca_key,ca_cert=(untrusted_key,untrusted_ca) if name=='untrusted' else (trusted_key,trusted_ca)
    host='wrong.example.com' if name=='wrong-host' else 'api.mailchannels.net'
    cert=x509.CertificateBuilder().subject_name(x509.Name([x509.NameAttribute(NameOID.COMMON_NAME,host)])).issuer_name(ca_cert.subject).public_key(k.public_key()).serial_number(x509.random_serial_number()).not_valid_before(now-timedelta(days=2)).not_valid_after(now-timedelta(days=1) if name=='expired' else now+timedelta(days=7)).add_extension(x509.SubjectAlternativeName([x509.DNSName(host)]),False).sign(ca_key,hashes.SHA256())
    (out/(name+'.crt')).write_bytes(cert.public_bytes(serialization.Encoding.PEM))
    (out/(name+'.key')).write_bytes(k.private_bytes(serialization.Encoding.PEM,serialization.PrivateFormat.PKCS8,serialization.NoEncryption()))
def run(args,**kwargs):return subprocess.run(args,text=True,capture_output=True,check=True,**kwargs)
network=os.environ.get('MAILCHANNELS_TEST_NETWORK','visibility-joomla-native')
assert run(['docker','network','inspect','--format','{{.Internal}}',network]).stdout.strip()=='true'
results=[]
for scenario in ['trusted','wrong-host','expired','untrusted','redirect','dry-run','failed','malformed','empty','oversize','wrong-index','stall']:
    records=out/(scenario+'-requests.jsonl');records.unlink(missing_ok=True)
    name=os.environ.get('MAILCHANNELS_TEST_CONTAINER_PREFIX','visibility-joomla')+'-tls'
    run(['docker','run','-d','--rm','--name',name,'--network',network,'--network-alias','api.mailchannels.net','-e','SCENARIO='+scenario,'-v',str(out)+':/fixtures','-v',str(root)+':/probe:ro','python:3.12-slim','python','/probe/server.py'])
    try:
        for attempt in range(100):
            if 'READY' in run(['docker','logs',name]).stdout:break
            time.sleep(.2)
        else:raise RuntimeError('TLS fixture did not become ready')
        result=run(['docker','run','--rm','--network',network,'-v',str(candidate)+':/candidate:ro','-v',str(out)+':/fixtures:ro','mailchannels-joomla-tests:php83','php','-d','curl.cainfo=/fixtures/ca.crt','/candidate/tls/client.php'],timeout=30)
        value=json.loads(result.stdout)
        observed=[json.loads(line) for line in records.read_text().splitlines()] if records.exists() else []
        assert value['accepted']==(scenario=='trusted'),(scenario,value)
        assert len(observed)==(0 if scenario in ['wrong-host','expired','untrusted'] else 1),(scenario,observed)
        assert all(r=={'path':'/tx/v1/send','key_matches':True,'recipient_matches':True} for r in observed)
        if scenario=='stall':assert 14<=value['seconds']<=20,value
        results.append(dict(scenario=scenario,**value,http_requests=len(observed)))
        print('PASS '+scenario,flush=True)
    finally:run(['docker','rm','-f',name])
(out/'results.json').write_text(json.dumps(results,indent=2)+'\n')
print('TLS_PROBE_COMPLETE',flush=True)
