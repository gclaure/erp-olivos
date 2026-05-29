@echo off
:: =========================================================================
:: Lanzador Automático de Instalador Laravel
:: Eleva privilegios y ejecuta el script de PowerShell
:: =========================================================================

echo Detectando privilegios de administrador...

:: 1. Comprobar si ya somos administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo.
    echo -----------------------------------------------------------------
    echo  SE REQUIEREN PERMISOS DE ADMINISTRADOR
    echo -----------------------------------------------------------------
    echo  Elevando permisos para continuar la instalacion...
    echo.
    
    :: Crear un script VBS temporal para solicitar elevación UAC
    echo Set UAC = CreateObject^("Shell.Application"^) > "%temp%\getadmin.vbs"
    echo UAC.ShellExecute "cmd.exe", "/c ""%~s0""", "", "runas", 1 >> "%temp%\getadmin.vbs"
    
    :: Ejecutar el script VBS y salir de esta instancia sin privilegios
    "%temp%\getadmin.vbs"
    del "%temp%\getadmin.vbs"
    exit /b
)

:: 2. Si llegamos aquí, YA SOMOS ADMINISTRADOR
echo Privilegios de administrador concedidos.
echo.
echo Iniciando el instalador de PowerShell (install-windows.ps1)...
echo Por favor, espere y NO cierre esta ventana.
echo.

:: Cambiar al directorio actual (donde reside el .bat)
cd /d "%~dp0"

:: 3. Ejecutar el script PowerShell saltando las políticas de restricción
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ".\install-windows.ps1"

echo.
pause
