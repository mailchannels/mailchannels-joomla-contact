"""Loopback provider stand-in. Records only fixture routing metadata."""
import http.server,json,ssl
from pathlib import Path
root=Path('/app/tmp')
class Handler(http.server.BaseHTTPRequestHandler):
 def log_message(self,*args):pass
 def do_POST(self):
  body=json.loads(self.rfile.read(int(self.headers['Content-Length'])))
  record={'content':body.get('content',[]),'path':self.path,'key_ok':self.headers.get('X-Api-Key')=='tls-fixture-dummy-key','from':body['from']['email'],'reply_to':body['reply_to']['email'],'to':[x['email'] for x in body['personalizations'][0]['to']]}
  with root.joinpath('visibility-contact-events').open('a') as f:f.write(json.dumps(record)+'\n')
  mode=root.joinpath('visibility-contact-mode').read_text().strip()
  failed=mode=='reject' or (mode=='copy-failure' and record['to']==['visitor@example.com'])
  result=json.dumps({'results':[{'index':0,'status':'failed' if failed else 'sent'}]}).encode()
  self.send_response(202);self.send_header('Content-Type','application/json');self.send_header('Content-Length',str(len(result)));self.end_headers();self.wfile.write(result)
server=http.server.HTTPServer(('0.0.0.0',443),Handler)
ctx=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);ctx.load_cert_chain('/fixtures/trusted.crt','/fixtures/trusted.key');server.socket=ctx.wrap_socket(server.socket,server_side=True)
print('READY',flush=True);server.serve_forever()
