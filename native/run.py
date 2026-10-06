"""Fresh Joomla native acceptance. No published ports or live API credentials."""
from pathlib import Path
import argparse,hashlib,json,os,shutil,subprocess,sys,tarfile,tempfile,time,urllib.request,uuid
ROOT=Path(__file__).resolve().parents[1]
VERSIONS={'5.4.9':'8c658d16b6e908f8cfce3556ac7280fe210c4ebd916202f406fe6bf1b5b05149','6.1.4':'9558a3a3754cbe798c8a8b3d46afa4e4a0a9284de876e4ce82c0ccaeb9f4af41'}
a=argparse.ArgumentParser();a.add_argument('version',choices=VERSIONS);a.add_argument('--archive',type=Path,help='Optional already-downloaded official package; still hash checked');args=a.parse_args()
work=ROOT/'.native-work';work.mkdir(exist_ok=True)
site=Path(tempfile.mkdtemp(prefix=args.version+'-',dir=work));prefix='mcjn-'+uuid.uuid4().hex[:10];network=prefix+'-net';db=prefix+'-db';http=prefix+'-http';tls=prefix+'-receiver';owned=[];created_network=False
image='mailchannels-joomla-native:php83';logs=[];checks=0

def run(cmd,timeout=180):
 p=subprocess.run(cmd,text=True,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,timeout=timeout)
 print(p.stdout,flush=True);logs.append(p.stdout)
 if p.returncode:raise RuntimeError(f'Command failed: {cmd[0]} (exit {p.returncode})')
 return p.stdout

def docker(*parts,**kw):return run(['docker',*map(str,parts)],**kw)
def php(script,sentinel=None,count=0,extra=()):
 global checks
 out=docker('run','--rm','--network',network,'-v',f'{site}:/app','-v',f'{ROOT}:/candidate:ro','-v',f'{ROOT}/dist:/artifacts:ro','-w','/app',image,'php','-d','disable_functions=mail',f'/candidate/native/{script}.php',*extra)
 if sentinel and sentinel not in out:raise RuntimeError('Missing PHP completion sentinel: '+sentinel)
 if count:
  assert sum(x.startswith('PASS ') for x in out.splitlines())==count,script
  checks+=count
 return out

def start(name,*parts):
 owned.append(name);docker('run','-d','--rm','--name',name,'--network',network,*parts)
def stop(name):
 if name in owned:
  docker('rm','-f',name);owned.remove(name)
def services(with_tls):
 if with_tls:
  start(tls,'--network-alias','api.mailchannels.net','-v',f'{site}:/app','-v',f'{ROOT}/native:/probe:ro','-v',f'{ROOT}/tls/generated:/fixtures:ro','python:3.12-slim','python','/probe/e2e-server.py')
  for _ in range(50):
   out=subprocess.run(['docker','logs',tls],capture_output=True,text=True)
   if 'READY' in out.stdout:break
   time.sleep(.2)
  else:raise RuntimeError('TLS server readiness timeout')
 env=['-e','MAILCHANNELS_API_KEY=tls-fixture-dummy-key','-e','MAILCHANNELS_JOOMLA_ALLOWED_SENDERS=operator@example.com'] if with_tls else []
 start(http,'--network-alias','visibility-joomla-http',*env,'-v',f'{site}:/app','-v',f'{ROOT}/tls/generated:/fixtures:ro','-w','/app',image,'php','-d','disable_functions=mail','-d','curl.cainfo=/fixtures/ca.crt','-S','0.0.0.0:18379')
 # The built-in PHP server initializes immediately; verify its actual log.
 for _ in range(50):
  out=subprocess.run(['docker','logs',http],capture_output=True,text=True)
  if 'Development Server' in out.stderr:break
  time.sleep(.2)
 else:raise RuntimeError('PHP server readiness timeout')
def client(script,sentinel,count,env=()):
 global checks
 out=docker('run','--rm','--network',network,*env,'-v',f'{site}:/app','-v',f'{ROOT}/native:/probe:ro','python:3.12-slim','python',f'/probe/{script}.py')
 assert sentinel in out,script
 assert sum(x.startswith('PASS ') for x in out.splitlines())==count,script
 checks+=count
try:
 archive=args.archive
 if archive is None:
  archive=work/f'Joomla_{args.version}.tar.gz'
  if not archive.exists():
   url=f'https://github.com/joomla/joomla-cms/releases/download/{args.version}/Joomla_{args.version}-Stable-Full_Package.tar.gz'
   with urllib.request.urlopen(url,timeout=60) as response,archive.open('wb') as output:shutil.copyfileobj(response,output)
 assert hashlib.sha256(archive.read_bytes()).hexdigest()==VERSIONS[args.version],'Official archive SHA256 mismatch'
 with tarfile.open(archive) as t:t.extractall(site,filter='data')
 docker('network','create','--internal',network);created_network=True
 env=dict(os.environ,MAILCHANNELS_TEST_NETWORK=network,MAILCHANNELS_TEST_CONTAINER_PREFIX=prefix)
 p=subprocess.run([sys.executable,str(ROOT/'tls/run.py')],env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,timeout=180)
 print(p.stdout,flush=True);logs.append(p.stdout)
 assert p.returncode==0 and 'TLS_PROBE_COMPLETE' in p.stdout,'TLS prerequisite failed'
 run([sys.executable,str(ROOT/'build_package.py')])
 start(db,'-e','MARIADB_DATABASE=joomla','-e','MARIADB_USER=joomla','-e','MARIADB_PASSWORD=local-joomla-only','-e','MARIADB_ROOT_PASSWORD=local-joomla-root-only','mariadb:11.8.9')
 for _ in range(60):
  if subprocess.run(['docker','exec',db,'healthcheck.sh','--connect','--innodb_initialized'],capture_output=True).returncode==0:break
  time.sleep(1)
 else:raise RuntimeError('DB readiness timeout')
 out=docker('run','--rm','--network',network,'-v',f'{site}:/app','-w','/app',image,'php','-d','disable_functions=mail','installation/joomla.php','install','--site-name=MailChannels isolated fixture','--admin-user=Fixture Operator','--admin-username=fixture-operator','--admin-password=Local-Joomla-Fixture-12345!','--admin-email=operator@example.com','--db-type=mysql',f'--db-host={db}','--db-user=joomla','--db-pass=local-joomla-only','--db-name=joomla','--db-prefix=fixture_','--db-encryption=0','--no-interaction')
 assert (site/'configuration.php').exists() and not (site/'installation').exists(),'Joomla installation incomplete'
 for script,sentinel,count in [('template-probe','JOOMLA_TEMPLATE',20),('mime-probe','JOOMLA_MIME',12),('delivery-probe','JOOMLA_DELIVERY',10),('plugin-probe','JOOMLA_PLUGIN_PROBE_COMPLETE 5 checks',8),('custom-template-probe','JOOMLA_CUSTOM_TEMPLATE_COMPLETE 5 checks',15),('package-probe','JOOMLA_PACKAGE',14)]:php(script,sentinel,count)
 php('e2e-setup','JOOMLA_CONTACT_FIXTURE_READY');services(True)
 client('e2e-probe','JOOMLA_E2E_COMPLETE 19 checks',19)
 for mode in ['disabled','unselected','no-suppression']:
  php('e2e-state','JOOMLA_E2E_STATE '+mode,extra=(mode,))
  client('e2e-probe','JOOMLA_E2E_GATE_COMPLETE '+mode+' 2 checks',2,('-e','JOOMLA_E2E_GATE='+mode))
 stop(http);stop(tls);php('e2e-cleanup','JOOMLA_CONTACT_FIXTURE_CLEANED')
 for setup,probe,cleanup,sentinel,count in [('custom-field-http-setup','custom-field-http-probe','custom-field-http-cleanup','JOOMLA_CUSTOM_FIELD_HTTP_COMPLETE 8 checks',8),('captcha-setup','captcha-http-probe','captcha-cleanup','JOOMLA_CAPTCHA_HTTP_COMPLETE 12 checks',12)]:
  php('e2e-setup','JOOMLA_CONTACT_FIXTURE_READY');php(setup,'READY');services(True)
  client(probe,sentinel,count);stop(http);stop(tls);php(cleanup,'CLEANED');php('e2e-cleanup','JOOMLA_CONTACT_FIXTURE_CLEANED')
 php('admin-setup','JOOMLA_ADMIN_READY');services(False)
 client('admin-http-probe','JOOMLA_ADMIN_HTTP_COMPLETE 12 checks',12)
 state=php('admin-state','ADMIN_STATE');assert '42,77' in state and '"enabled":0' in state
 stop(http);php('admin-cleanup','JOOMLA_ADMIN_CLEANED')
 assert checks==136,checks
 print(f'JOOMLA_NATIVE_COMPLETE {args.version} {checks} native checks + 12 TLS checks',flush=True)
 logs.append(f'JOOMLA_NATIVE_COMPLETE {args.version} {checks} native checks + 12 TLS checks\n')
finally:
 cleanup_errors=[]
 # Also cover a TLS child interrupted by the orchestration timeout.
 for name in [*reversed(owned),prefix+'-tls']:
  exists=subprocess.run(['docker','container','inspect',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0
  if exists and subprocess.run(['docker','rm','-f',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode!=0:cleanup_errors.append(name)
 if created_network and subprocess.run(['docker','network','rm',network],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode!=0:cleanup_errors.append(network)
 shutil.rmtree(site)
 logs.append('JOOMLA_NATIVE_CLEANUP_COMPLETE' if not cleanup_errors else 'CLEANUP_FAILED '+repr(cleanup_errors))
 (work/f'{args.version}-results.txt').write_text('\n'.join(logs))
 if cleanup_errors:raise RuntimeError('Fixture cleanup failed: '+repr(cleanup_errors))
 print('JOOMLA_NATIVE_CLEANUP_COMPLETE',flush=True)
