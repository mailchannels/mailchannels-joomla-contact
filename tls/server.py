import http.server, json, os, ssl, time
from pathlib import Path
scenario=os.environ['SCENARIO']
class Handler(http.server.BaseHTTPRequestHandler):
    def log_message(self,*args):pass
    def do_POST(self):
        body=json.loads(self.rfile.read(int(self.headers['Content-Length'])))
        record={'path':self.path,'key_matches':self.headers.get('X-Api-Key')=='tls-fixture-dummy-key','recipient_matches':body['personalizations'][0]['to'][0]['email']=='recipient@example.com'}
        with open('/fixtures/'+scenario+'-requests.jsonl','a') as output:output.write(json.dumps(record)+'\n')
        if scenario=='stall':time.sleep(25)
        if scenario=='redirect':
            self.send_response(302);self.send_header('Location','https://api.mailchannels.net/redirect-target');self.end_headers();return
        result=json.dumps({'request_id':'fixture','results':[{'index':0,'status':'sent','message_id':'fixture'}]}).encode()
        if scenario=='failed':result=json.dumps({'results':[{'index':0,'status':'failed'}]}).encode()
        if scenario=='wrong-index':result=json.dumps({'results':[{'index':1,'status':'sent'}]}).encode()
        if scenario=='malformed':result=b'{invalid'
        if scenario=='empty':result=b''
        if scenario=='oversize':result=b' ' * 65537 + result
        self.send_response(200 if scenario=='dry-run' else 202);self.send_header('Content-Type','application/json');self.send_header('Content-Length',str(len(result)));self.end_headers()
        try:self.wfile.write(result)
        except (BrokenPipeError,ssl.SSLError):pass
server=http.server.HTTPServer(('0.0.0.0',443),Handler)
ctx=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
cert=scenario if scenario in ['wrong-host','expired','untrusted'] else 'trusted'
ctx.load_cert_chain('/fixtures/'+cert+'.crt','/fixtures/'+cert+'.key')
server.socket=ctx.wrap_socket(server.socket,server_side=True)
print('READY',flush=True)
server.serve_forever()
