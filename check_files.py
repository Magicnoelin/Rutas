import os, datetime
files = [
    'c:/Users/olgam/Documents/Proyectos/Rutas/Rutas/script.js',
    'c:/Users/olgam/Documents/Proyectos/Rutas/Rutas/styles.css',
    'c:/Users/olgam/Documents/Proyectos/Rutas/Rutas/login.html',
    'c:/Users/olgam/Documents/Proyectos/Rutas/Rutas/.htaccess',
    'c:/Users/olgam/Documents/Proyectos/Rutas/Rutas/api/antonio_log.php',
    'c:/Users/olgam/Documents/Proyectos/Rutas/Rutas/admin_tablas/antonio_dashboard.php',
]
for f in files:
    if os.path.exists(f):
        t = os.path.getmtime(f)
        dt = datetime.datetime.fromtimestamp(t)
        size = os.path.getsize(f)
        name = f.split('/')[-1]
        print('OK  ' + dt.strftime('%H:%M:%S') + '  ' + str(size).rjust(8) + ' bytes  ' + name)
    else:
        print('MISSING: ' + f)
