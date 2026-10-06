"""Build a deterministic, explicitly unreleased Joomla review artifact."""
from pathlib import Path
import hashlib,json,zipfile
root=Path(__file__).resolve().parent
source=root/'candidate'
out=root/'dist'
out.mkdir(exist_ok=True,parents=True)
files=[source/'mailchannelscontact.xml',source/'LICENSE',*source.joinpath('services').rglob('*.php'),*source.joinpath('src').rglob('*.php'),*source.joinpath('language').rglob('*.ini')]
files=sorted(files,key=lambda p:p.relative_to(source).as_posix())
archive=out/'mailchannels-contact-candidate.zip'
manifest=[]
with zipfile.ZipFile(archive,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for path in files:
  if path.is_symlink() or not path.is_file():raise RuntimeError('Invalid package input')
  name=path.relative_to(source).as_posix();data=path.read_bytes()
  info=zipfile.ZipInfo(name,date_time=(1980,1,1,0,0,0));info.create_system=3;info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED
  z.writestr(info,data,compress_type=zipfile.ZIP_DEFLATED,compresslevel=9)
  manifest.append({'path':name,'bytes':len(data),'sha256':hashlib.sha256(data).hexdigest()})
result={'status':'unreleased review candidate; not approved for production or JED submission','archive':archive.name,'sha256':hashlib.sha256(archive.read_bytes()).hexdigest(),'files':manifest}
(out/'candidate-package.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps({'archive':str(archive),'sha256':result['sha256'],'files':len(files)}))
