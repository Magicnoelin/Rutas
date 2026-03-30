content = open('index.html','r',encoding='utf-8').read()
content = content.replace('script.js?v=20260114', 'script.js?v=20260319')
content = content.replace('styles.css?v=20260114', 'styles.css?v=20260319')
open('index.html','w',encoding='utf-8').write(content)
print('index.html updated')

# Also check other pages that might load script.js with old version
import os, glob
for f in glob.glob('*.html'):
    if f == 'index.html':
        continue
    c = open(f,'r',encoding='utf-8',errors='ignore').read()
    if 'script.js?v=20260114' in c or 'styles.css?v=20260114' in c:
        c = c.replace('script.js?v=20260114', 'script.js?v=20260319')
        c = c.replace('styles.css?v=20260114', 'styles.css?v=20260319')
        open(f,'w',encoding='utf-8').write(c)
        print('Updated:', f)
