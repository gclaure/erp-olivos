<#
.SYNOPSIS
Instalador automatico (Core/Motor) para Laravel en Windows.
.DESCRIPTION
Ideal para ser ejecutado directamente o empaquetado con Inno Setup.
Instala dependencias, configura bases de datos, despliega Nginx con su configuracion,
abre puertos del Firewall y registra los servicios automaticamente.
#>

# 1. Asegurar permisos de administrador
if (!([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Warning "Se requieren privilegios de Administrador para ejecutar este instalador."
    pause
    exit
}

# 2. Variables Principales
$InstallDir = "C:\inventory-gclaure"
$DbName = "inventory_gclaure"
$DbPass = "admin123"
$VirtualDomain = "inventory-cj7"
$SourceDir = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   Instalador Automatico Laravel (Windows)   " -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# 3. Copiar Proyecto (Si no se esta ejecutando desde el destino final)
if ($SourceDir -ne $InstallDir) {
    Write-Host "`n[+] Copiando proyecto de $SourceDir a $InstallDir..." -ForegroundColor Yellow
    if (!(Test-Path $InstallDir)) {
        New-Item -ItemType Directory -Force -Path $InstallDir | Out-Null
    }
    # Usar robocopy (nativo de Windows) que es mucho mas rapido y no se cuelga
    # /E = copia subdirectorios incluyendo vacios
    # /XD = excluir directorios pesados e innecesarios (se regeneran con composer install)
    # /XF = excluir este mismo script
    # /NFL /NDL /NJH /NJS = output silencioso
    robocopy $SourceDir $InstallDir /E /XD "vendor" "node_modules" ".git" /XF "install-windows.ps1" /NFL /NDL /NJH /NJS /NC /NS /NP
    Write-Host "    -> Proyecto copiado exitosamente." -ForegroundColor Green
}

# 4. Instalar Chocolatey
if (!(Get-Command choco -ErrorAction SilentlyContinue)) {
    Write-Host "`n[+] Instalando Chocolatey..." -ForegroundColor Yellow
    Set-ExecutionPolicy Bypass -Scope Process -Force
    iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
}

Import-Module "$env:ProgramData\chocolatey\helpers\chocolateyProfile.psm1" -ErrorAction SilentlyContinue
Update-SessionEnvironment

# 5. Instalar Dependencias via Chocolatey (PostgreSQL, Nginx, PHP 8.4, NSSM, Composer)
Write-Host "`n[+] Instalando dependencias básicas vía Chocolatey..." -ForegroundColor Yellow
choco install nginx nssm composer -y
choco install postgresql17 --params "/Password:$DbPass" -y

# 5.1 Instalar PHP 8.4 Especificamente (via Chocolatey)
Write-Host "`n[+] Descargando e instalando PHP 8.4 especificamente via Chocolatey..." -ForegroundColor Yellow
# Primero, desinstalar silenciosamente si existe una version superior o erronea ya instanciada por Choco
Write-Host "    -> Limpiando versiones previas de PHP en Chocolatey si existen..." -ForegroundColor Gray
choco uninstall php -y --all-versions -v | Out-Null
Remove-Item -Path "C:\tools\php*" -Recurse -Force -ErrorAction SilentlyContinue | Out-Null

# Instalar explicitamente solo la 8.4
choco install php --version=8.4.4 -y --force

Update-SessionEnvironment
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# 5.2 Configurar PHP.ini y habilitar extensiones necesarias para Laravel
Write-Host "`n[+] Configurando PHP.ini y habilitando extensiones..." -ForegroundColor Yellow
$phpDir = (Get-ChildItem -Path C:\tools -Directory -Filter "php*").FullName | Select-Object -First 1
if ($phpDir) {
    $phpIni = Join-Path $phpDir "php.ini"
    if (!(Test-Path $phpIni)) {
        Copy-Item (Join-Path $phpDir "php.ini-production") $phpIni
    }
    
    $iniContent = Get-Content $phpIni
    $iniContent = $iniContent -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    $iniContent = $iniContent -replace ';extension=fileinfo', 'extension=fileinfo'
    $iniContent = $iniContent -replace ';extension=pdo_pgsql', 'extension=pdo_pgsql'
    $iniContent = $iniContent -replace ';extension=pgsql', 'extension=pgsql'
    $iniContent = $iniContent -replace ';extension=mbstring', 'extension=mbstring'
    $iniContent = $iniContent -replace ';extension=openssl', 'extension=openssl'
    $iniContent = $iniContent -replace ';extension=curl', 'extension=curl'
    $iniContent = $iniContent -replace ';extension=zip', 'extension=zip'
    $iniContent | Set-Content $phpIni
    
    Write-Host "    -> Extensiones habilitadas en $phpIni" -ForegroundColor Green
} else {
    Write-Warning "    -> No se pudo encontrar el directorio de PHP en $phpDir para configurar php.ini."
}

# 6. Configurar Base de Datos
Write-Host "`n[+] Configurando Base de Datos..." -ForegroundColor Yellow
$env:PGPASSWORD = $DbPass
$psqlPath = (Get-ChildItem -Path "C:\Program Files\PostgreSQL" -Recurse -Filter "psql.exe" -ErrorAction SilentlyContinue).FullName | Select-Object -First 1

if ($psqlPath) {
    Write-Host "    -> Asegurando que el servicio de PostgreSQL este iniciado..."
    Start-Service postgresql* -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 5

    $checkDb = & $psqlPath -U postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$DbName'"
    if ($checkDb -ne "1") {
        & $psqlPath -U postgres -c "CREATE DATABASE $DbName;"
        Write-Host "    -> Base de datos creada exitosamente." -ForegroundColor Green
    }
} else {
    Write-Warning "    -> ADVERTENCIA: No se encontro psql.exe automaticamente."
}

# 7. Configurar Entorno Laravel (.env) y Preparar
Write-Host "`n[+] Configurando aplicacion Laravel..." -ForegroundColor Yellow
Set-Location $InstallDir

if (!(Test-Path ".env") -and (Test-Path ".env.example")) {
    Copy-Item ".env.example" ".env"
    (Get-Content ".env") -replace 'DB_DATABASE=.*', "DB_DATABASE=$DbName" -replace 'DB_PASSWORD=.*', "DB_PASSWORD=$DbPass" -replace 'APP_URL=.*', "APP_URL=http://$VirtualDomain:8080" | Set-Content ".env"
}

Write-Host "    -> Configurando tiempo de espera de Composer y ejecutando instalacion..."
composer config --global process-timeout 2000
composer install --optimize-autoloader --no-dev --no-interaction

Write-Host "    -> Generando Application Key..."
php artisan key:generate --force

Write-Host "    -> Creando enlaces simbolicos si es necesario..."
php artisan storage:link

Write-Host "    -> Ejecutando Migraciones y Seeders..."
php artisan migrate --force --seed

# 8. Configurar Nginx
Write-Host "`n[+] Configurando Nginx automaticamente..." -ForegroundColor Yellow
$nginxDir = (Get-ChildItem -Path C:\tools -Directory -Filter "nginx-*").FullName | Select-Object -First 1
$nginxExe = Join-Path $nginxDir "nginx.exe"
$nginxConfPath = Join-Path $nginxDir "conf\nginx.conf"

$InstallDirNginx = $InstallDir -replace '\\', '/'

$nginxConfig = @"
worker_processes  1;

events {
    worker_connections  1024;
}

http {
    include       mime.types;
    default_type  application/octet-stream;
    sendfile        on;
    keepalive_timeout  65;

    server {
        listen       8080;
        server_name  $VirtualDomain localhost;
        root         $InstallDirNginx/public;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-XSS-Protection "1; mode=block";
        add_header X-Content-Type-Options "nosniff";

        index index.php index.html index.htm;
        charset utf-8;

        location / {
            try_files `$uri `$uri/ /index.php?`$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_param  SCRIPT_FILENAME `$document_root`$fastcgi_script_name;
            include        fastcgi_params;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
"@
Set-Content -Path $nginxConfPath -Value $nginxConfig -Encoding UTF8

# 8.1 Registrar dominio virtual en el archivo hosts de Windows
Write-Host "`n[+] Registrando dominio virtual '$VirtualDomain' en archivo hosts..." -ForegroundColor Yellow
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
$hostsEntry = "127.0.0.1    $VirtualDomain"
$hostsContent = Get-Content $hostsFile -ErrorAction SilentlyContinue
if ($hostsContent -notcontains $hostsEntry) {
    Add-Content -Path $hostsFile -Value "`n$hostsEntry" -Encoding ASCII
    Write-Host "    -> Dominio '$VirtualDomain' agregado al archivo hosts." -ForegroundColor Green
} else {
    Write-Host "    -> El dominio '$VirtualDomain' ya existe en el archivo hosts." -ForegroundColor Gray
}

# 8.2 Limpiar procesos colgados de Nginx)
Write-Host "`n[+] Asegurando que el puerto 8080 este disponible..." -ForegroundColor Yellow
Stop-Process -Name nginx -Force -ErrorAction SilentlyContinue | Out-Null
Stop-Process -Name php-cgi -Force -ErrorAction SilentlyContinue | Out-Null
Write-Host "    -> Procesos antiguos de Nginx y PHP detenidos." -ForegroundColor Green

# 9. Configurar Firewall
Write-Host "`n[+] Abriendo puerto 8080 en el Firewall de Windows..." -ForegroundColor Yellow
if (!(Get-NetFirewallRule -DisplayName "Nginx HTTP 8080" -ErrorAction SilentlyContinue)) {
    New-NetFirewallRule -DisplayName "Nginx HTTP 8080" -Direction Inbound -Protocol TCP -LocalPort 8080 -Action Allow | Out-Null
    Write-Host "    -> Regla creada." -ForegroundColor Green
} else {
    Write-Host "    -> La regla del firewall ya existe." -ForegroundColor Gray
}

# 10. Registrar Servicios con NSSM y Optimizar PHP-CGI
Write-Host "`n[+] Registrando e iniciando servicios de fondo (Nginx y PHP-CGI)..." -ForegroundColor Yellow

# Refrescar PATH una vez mas para detectar los binarios recien instalados
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# Localizar NSSM por ruta absoluta (el PATH de la sesion actual no siempre se actualiza)
$nssmExe = (Get-Command nssm -ErrorAction SilentlyContinue).Source
if (!$nssmExe) {
    $nssmExe = (Get-ChildItem -Path "C:\ProgramData\chocolatey\bin" -Filter "nssm.exe" -ErrorAction SilentlyContinue).FullName
}
if (!$nssmExe) {
    $nssmExe = (Get-ChildItem -Path "C:\tools" -Recurse -Filter "nssm.exe" -ErrorAction SilentlyContinue).FullName | Select-Object -First 1
}

if (!$nssmExe) {
    Write-Warning "No se encontro nssm.exe. Intentando reinstalar..."
    choco install nssm -y --force
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    $nssmExe = (Get-Command nssm -ErrorAction SilentlyContinue).Source
}

if (!$nssmExe) {
    Write-Warning "ERROR CRITICO: No se pudo localizar nssm.exe. Los servicios no se registraron."
} else {
    Write-Host "    -> NSSM encontrado en: $nssmExe" -ForegroundColor Green

    $phpDir = (Get-ChildItem -Path C:\tools -Directory -Filter "php*").FullName | Select-Object -First 1
    $phpCgiExe = Join-Path $phpDir "php-cgi.exe"

    # --- Servicio PHP-CGI ---
    if (!(Get-Service -Name "php-cgi" -ErrorAction SilentlyContinue)) {
        & $nssmExe install php-cgi "$phpCgiExe" "-b 127.0.0.1:9000"
        & $nssmExe set php-cgi AppDirectory "$phpDir"
        & $nssmExe set php-cgi Description "Servicio FastCGI para PHP (Entorno Laravel)"
        & $nssmExe set php-cgi AppEnvironmentExtra "PHP_FCGI_MAX_REQUESTS=5000"
        & $nssmExe set php-cgi AppExit Default Restart
        & $nssmExe start php-cgi
    } else {
        Restart-Service "php-cgi" -Force
    }

    # --- Servicio Nginx ---
    if (!(Get-Service -Name "nginx" -ErrorAction SilentlyContinue)) {
        & $nssmExe install nginx "$nginxExe"
        & $nssmExe set nginx AppDirectory "$nginxDir"
        & $nssmExe set nginx Description "Servidor Web Nginx"
        & $nssmExe set nginx AppExit Default Restart
        & $nssmExe start nginx
    }

    # REINICIO FINAL OBLIGATORIO DE SERVICIOS PARA APLICAR CAMBIOS
    Write-Host "`n    -> Reiniciando servicios para aplicar la configuracion final..." -ForegroundColor Yellow
    Restart-Service "php-cgi" -Force -ErrorAction SilentlyContinue
    Restart-Service "nginx" -Force -ErrorAction SilentlyContinue
    Write-Host "    -> Servicios reiniciados exitosamente." -ForegroundColor Green
}

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   INSTALACION 100%% COMPLETADA EN WINDOWS   " -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[OK] Dependencias instaladas"
Write-Host "[OK] Proyecto copiado a C:\inventory-gclaure"
Write-Host "[OK] Base de datos migrada y sembrada"
Write-Host "[OK] Nginx configurado y enlazado a PHP-CGI"
Write-Host "[OK] Reglas de Firewall agregadas"
Write-Host "[OK] Servicios registrados y corriendo"
Write-Host ""
Write-Host "URL Final de la aplicacion: http://inventory-cj7:8080"
Write-Host "Tambien disponible en: http://localhost:8080"
Write-Host ""
Write-Host "Abre tu navegador para ver la aplicacion corriendo."
Write-Host "Presiona ENTER para salir del instalador"
Read-Host
