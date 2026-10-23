param([ValidateSet('start', 'stop')][string]$Action = 'start')

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$localRoot = Join-Path $projectRoot '.local'
$mysqlRoot = Join-Path $localRoot 'mysql-8.4.13-winx64'
$adminConfig = Join-Path $localRoot 'admin.cnf'
$mysqlAdmin = Join-Path $mysqlRoot 'bin/mysqladmin.exe'
$mysqlClient = Join-Path $mysqlRoot 'bin/mysql.exe'

function Test-MySqlRunning {
    $ErrorActionPreference = 'Continue'
    $version = & $mysqlClient "--defaults-file=$adminConfig" --batch --skip-column-names '--execute=SELECT VERSION()' 2>$null
    return $LASTEXITCODE -eq 0 -and $version -like '8.4.*'
}

if ($Action -eq 'stop') {
    & $mysqlAdmin "--defaults-file=$adminConfig" shutdown
    exit $LASTEXITCODE
}

if (Test-MySqlRunning) {
    Write-Output 'ENDGAME MySQL is already running on 127.0.0.1:3384.'
    exit 0
}

$normalizedRoot = $projectRoot.Replace('\', '/')
@"
[mysqld]
basedir=$normalizedRoot/.local/mysql-8.4.13-winx64
datadir=$normalizedRoot/.local/mysql-data
bind-address=127.0.0.1
port=3384
mysqlx=OFF
log-error=$normalizedRoot/.local/mysql-error.log
pid-file=$normalizedRoot/.local/mysql.pid
"@ | Set-Content -LiteralPath (Join-Path $localRoot 'my.ini') -Encoding ascii

$server = Start-Process -FilePath (Join-Path $mysqlRoot 'bin/mysqld.exe') `
    -ArgumentList "`"--defaults-file=$localRoot/my.ini`"" -WindowStyle Hidden -PassThru

for ($attempt = 0; $attempt -lt 30; $attempt++) {
    if (Test-MySqlRunning) {
        Write-Output 'ENDGAME MySQL started on 127.0.0.1:3384.'
        exit 0
    }
    if ($server.HasExited) { throw 'MySQL exited. See .local/mysql-error.log.' }
    Start-Sleep -Seconds 1
}
throw 'MySQL startup timed out. See .local/mysql-error.log.'
