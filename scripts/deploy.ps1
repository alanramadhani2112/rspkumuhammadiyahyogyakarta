param(
    [switch]$DryRun,
    [switch]$SelfTest
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$HostName = '103.150.92.230'
$UserName = 'pkujogja'
$Fingerprint = 'SHA256:skK51bVxxGszH7+wX0lhkXsPWeWVCMaOCT0XJVDOSDg'
$RemoteRoot = '/home/dev-rspkujogja/htdocs/dev-rspkujogja.com'
$BackupRoot = '/home/pkujogja/deploy-backups'
$Branch = 'origin/audit-review-fixes'
$SmokeUrl = 'https://dev-web.rspkujogja.com'
$Theme = 'rspku-theme'
$Plugins = @('rspku-core', 'rspku-cpt', 'rspku-schema', 'rspku-settings')
$DefaultKey = 'D:\Dev Ops\RSPKU Muhammadiyah Yogyakarta\rsa-key-20260804.ppk'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

function Fail($Message) { throw "DEPLOY REFUSED: $Message" }
function Q($Value) { "'" + ($Value -replace "'", "'\''") + "'" }
function Have($Name) { [bool](Get-Command $Name -ErrorAction SilentlyContinue) }
function Run($Exe, [string[]]$ArgList, $Cwd = $RepoRoot) {
    Push-Location $Cwd
    try {
        & $Exe @ArgList
        if ($LASTEXITCODE -ne 0) { Fail "$Exe $($ArgList -join ' ') exited $LASTEXITCODE" }
    } finally { Pop-Location }
}
function Out($Exe, [string[]]$ArgList, $Cwd = $RepoRoot) {
    Push-Location $Cwd
    try {
        $Text = & $Exe @ArgList
        if ($LASTEXITCODE -ne 0) { Fail "$Exe $($ArgList -join ' ') exited $LASTEXITCODE" }
        ($Text | Out-String).Trim()
    } finally { Pop-Location }
}
function KeyPath {
    if ($env:RSPKU_DEPLOY_PPK) { return $env:RSPKU_DEPLOY_PPK }
    $DefaultKey
}
function PlinkArgs($RemoteCommand) {
    @('-batch', '-ssh', '-l', $UserName, '-i', (KeyPath), '-hostkey', $Fingerprint, $HostName, $RemoteCommand)
}
function PscpArgs($Local, $Remote) {
    @('-batch', '-scp', '-i', (KeyPath), '-hostkey', $Fingerprint, $Local, "${UserName}@${HostName}:$Remote")
}
function NeedPrereqs {
    foreach ($Cmd in @('git', 'npm', 'plink', 'pscp', 'tar')) { if (-not (Have $Cmd)) { Fail "missing command: $Cmd" } }
    $Key = KeyPath
    if (-not (Test-Path -LiteralPath $Key -PathType Leaf)) { Fail "missing PuTTY private key: $Key" }
    $Top = Out git @('rev-parse', '--show-toplevel')
    if ((Resolve-Path $Top).Path -ne $RepoRoot) { Fail "run from repository rooted at $RepoRoot" }
    Out git @('rev-parse', '--verify', "$Branch^{commit}") | Out-Null
    foreach ($Path in @("wp-content/themes/$Theme") + ($Plugins | ForEach-Object { "wp-content/plugins/$_" })) {
        Out git @('cat-file', '-e', "$Branch`:$Path") | Out-Null
    }
    $RemoteCheck = "set -euo pipefail; ROOT=$(Q $RemoteRoot); test -d `"`$ROOT/wp-content/themes/$Theme`"; command -v wp >/dev/null; command -v tar >/dev/null; command -v rsync >/dev/null; command -v curl >/dev/null; command -v composer >/dev/null; command -v install >/dev/null; command -v runuser >/dev/null"
    foreach ($Plugin in $Plugins) { $RemoteCheck += "; test -d `"`$ROOT/wp-content/plugins/$Plugin`"" }
    Run plink (PlinkArgs "sudo -n bash -c $(Q $RemoteCheck)")
}
function Plan($Stamp) {
    $RemotePkg = "/tmp/rspku-deploy-$Stamp.tgz"
    @(
        "deploy ref: $Branch",
        "server: ${UserName}@${HostName}",
        "hostkey: $Fingerprint",
        "root: $RemoteRoot",
        "key: $(KeyPath)",
        "preflight: validate git ref, key, plink hostkey auth, sudo, wp, tar, rsync, curl, composer, install, runuser, deployed custom dirs",
        "workspace: dirty tree is ignored; package source is $Branch only",
        "local: git archive $Branch custom theme/plugins only",
        "local: npm ci; npm run build in temp staged wp-content/themes/$Theme",
        "local: verify wp-content/themes/$Theme/public/build/.vite/manifest.json",
        "local: tar staged wp-content into package including ignored public/build",
        "upload: pscp package to $RemotePkg",
        "remote: backup root $BackupRoot/$Stamp outside web root via install -d -m 700",
        "remote: runuser wp db export into owned private temp dir; install -m 600 to $(Q "$BackupRoot/$Stamp/db.sql")",
        "remote: backup deployed wp-content/themes/$Theme and each custom plugin directory",
        "remote: validate uploaded package owner and test ! -L, move into root mktemp -d dir, trap cleanup",
        "remote: extract stage under root mktemp and runuser composer install --no-dev as site owner",
        "remote: install staged custom theme/plugins only; do not touch uploads, core, wp-config.php",
        "remote: preserve ownership from $RemoteRoot/wp-content/themes/$Theme",
        "remote: verify manifest exists after install",
        "remote: HTTP smoke $SmokeUrl and $SmokeUrl/wp-json/",
        "rollback: plink -batch -ssh -l $UserName -i `"$(KeyPath)`" -hostkey $Fingerprint $HostName `"sudo -n bash $BackupRoot/$Stamp/rollback.sh`""
    ) -join [Environment]::NewLine
}
function RemoteScript($Stamp, $RemotePkg) {
@"
set -euo pipefail
ROOT=$(Q $RemoteRoot)
UPLOAD=$(Q $RemotePkg)
STAMP=$(Q $Stamp)
THEME=$(Q $Theme)
BACKUP=$(Q "$BackupRoot/$Stamp")
test -f "`$UPLOAD"
test ! -L "`$UPLOAD"
test "`$(stat -c '%U' "`$UPLOAD")" = $(Q $UserName)
TMP=`$(mktemp -d /tmp/rspku-deploy.XXXXXXXX)
trap 'rm -rf "`$TMP" "`$UPLOAD"' EXIT
PKG="`$TMP/package.tgz"
STAGE="`$TMP/stage"
mv -- "`$UPLOAD" "`$PKG"
test -f "`$PKG"
test ! -L "`$PKG"
chown root:root "`$PKG"
chmod 600 "`$PKG"
chmod 711 "`$TMP"
install -d -m 700 "`$BACKUP"
mkdir -p "`$STAGE"
cd "`$ROOT"
OWNER=`$(stat -c '%U:%G' "wp-content/themes/`$THEME")
SITE_USER=`$(stat -c '%U' "wp-content/themes/`$THEME")
SITE_GROUP=`$(stat -c '%G' "wp-content/themes/`$THEME")
test -n "`$SITE_USER"
test "`$SITE_USER" != root
test -d "wp-content/themes/`$THEME"
for p in $($Plugins -join ' '); do test -d "wp-content/plugins/`$p"; done
install -d -m 700 -o "`$SITE_USER" -g "`$SITE_GROUP" "`$TMP/db-export"
runuser -u "`$SITE_USER" -- wp db export "`$TMP/db-export/db.sql" --path="`$ROOT"
test -s "`$TMP/db-export/db.sql"
install -m 600 "`$TMP/db-export/db.sql" "`$BACKUP/db.sql"
mkdir -p "`$BACKUP/wp-content/themes" "`$BACKUP/wp-content/plugins"
cp -a "wp-content/themes/`$THEME" "`$BACKUP/wp-content/themes/"
for p in $($Plugins -join ' '); do cp -a "wp-content/plugins/`$p" "`$BACKUP/wp-content/plugins/"; done
cat > "`$BACKUP/rollback.sh" <<'ROLLBACK'
set -euo pipefail
ROOT=$(Q $RemoteRoot)
BACKUP=$(Q "$BackupRoot/$Stamp")
OWNER=`$(stat -c '%U:%G' "`$ROOT/wp-content/themes/$Theme")
SITE_USER=`$(stat -c '%U' "`$ROOT/wp-content/themes/$Theme")
test -n "`$SITE_USER"
test "`$SITE_USER" != root
test -d "`$BACKUP/wp-content/themes/$Theme"
for p in $($Plugins -join ' '); do test -d "`$BACKUP/wp-content/plugins/`$p"; done
TARGETS=("`$ROOT/wp-content/themes/$Theme" $($Plugins | ForEach-Object { '"`$ROOT/wp-content/plugins/' + $_ + '"' } | Join-String -Separator ' '))
rsync -a --delete "`$BACKUP/wp-content/themes/$Theme/" "`$ROOT/wp-content/themes/$Theme/"
for p in $($Plugins -join ' '); do rsync -a --delete "`$BACKUP/wp-content/plugins/`$p/" "`$ROOT/wp-content/plugins/`$p/"; done
chmod -R u=rwX,go=rX "`${TARGETS[@]}"
chown -R "`$OWNER" "`${TARGETS[@]}"
ROLLBACK
chmod 700 "`$BACKUP/rollback.sh"
tar -xzf "`$PKG" -C "`$STAGE"
chmod 755 "`$TMP" "`$STAGE" "`$STAGE/wp-content" "`$STAGE/wp-content/themes"
chown -R "`$OWNER" "`$STAGE/wp-content/themes/`$THEME"
runuser -u "`$SITE_USER" -- composer install --working-dir="`$STAGE/wp-content/themes/`$THEME" --no-dev --prefer-dist --no-interaction --optimize-autoloader
rsync -a --delete "`$STAGE/wp-content/themes/`$THEME/" "wp-content/themes/`$THEME/"
for p in $($Plugins -join ' '); do rsync -a --delete "`$STAGE/wp-content/plugins/`$p/" "wp-content/plugins/`$p/"; done
TARGETS=("wp-content/themes/`$THEME" $($Plugins | ForEach-Object { '"wp-content/plugins/' + $_ + '"' } | Join-String -Separator ' '))
chmod -R u=rwX,go=rX "`${TARGETS[@]}"
chown -R "`$OWNER" "`${TARGETS[@]}"
test -s "wp-content/themes/`$THEME/public/build/.vite/manifest.json"
curl -fsSIL --max-time 20 $(Q $SmokeUrl) >/dev/null
curl -fsS --max-time 20 $(Q "$SmokeUrl/wp-json/") >/dev/null
echo "rollback: sudo -n bash `$BACKUP/rollback.sh"
"@
}
function SelfTestRun {
    if ((Q "a'b") -ne "'a'\''b'") { Fail 'quote helper failed' }
    $P = Plan '20000101-000000'
    foreach ($Needle in @($HostName, $Fingerprint, $RemoteRoot, $BackupRoot, $Branch, 'rollback.sh', 'npm ci', 'composer install', 'install -d -m 700', 'mktemp -d', 'trap', 'runuser', 'test ! -L', 'pscp')) {
        if (-not $P.Contains($Needle)) { Fail "plan missing $Needle" }
    }
    $R = RemoteScript '20000101-000000' '/tmp/rspku-deploy-test.tgz'
    foreach ($Needle in @('test -n "$SITE_USER"', 'test "$SITE_USER" != root')) {
        if (-not $R.Contains($Needle)) { Fail "root refusal missing $Needle" }
    }
    foreach ($Needle in @("stat -c '%U:%G' `"wp-content/themes/`$THEME`"", "stat -c '%U' `"wp-content/themes/`$THEME`"", "stat -c '%U:%G' `"`$ROOT/wp-content/themes/$Theme`"", "stat -c '%U' `"`$ROOT/wp-content/themes/$Theme`"")) {
        if (-not $R.Contains($Needle)) { Fail "theme owner source missing $Needle" }
    }
    foreach ($Needle in @("stat -c '%U:%G' wp-content)", "stat -c '%U' wp-content)", "stat -c '%U:%G' `"`$ROOT/wp-content`"") ) {
        if ($R.Contains($Needle)) { Fail "broad owner source present $Needle" }
    }
    foreach ($Needle in @('install -d -m 700 -o "$SITE_USER" -g "$SITE_GROUP" "$TMP/db-export"', 'runuser -u "$SITE_USER" -- wp db export "$TMP/db-export/db.sql" --path="$ROOT"', 'install -m 600 "$TMP/db-export/db.sql" "$BACKUP/db.sql"')) {
        if (-not $R.Contains($Needle)) { Fail "non-root DB backup missing $Needle" }
    }
    $TmpChmod = $R.IndexOf('chmod 711 "$TMP"')
    $WpExport = $R.IndexOf('runuser -u "$SITE_USER" -- wp db export "$TMP/db-export/db.sql" --path="$ROOT"')
    if ($TmpChmod -lt 0 -or $WpExport -lt 0 -or $TmpChmod -gt $WpExport) { Fail 'chmod 711 TMP must precede WP export' }
    foreach ($Line in ($R -split "`n")) {
        if ($Line -match '^wp db export ') { Fail "bare wp db export present" }
    }
    if (([regex]::Matches($R, [regex]::Escape('chmod -R u=rwX,go=rX'))).Count -lt 2) { Fail 'permission normalization missing' }
    foreach ($Needle in @('TARGETS=("$ROOT/wp-content/themes/rspku-theme"', 'TARGETS=("wp-content/themes/$THEME"', 'chown -R "$OWNER" "${TARGETS[@]}"')) {
        if (-not $R.Contains($Needle)) { Fail "permission target assertion missing $Needle" }
    }
    'SelfTest OK'
}

if ($SelfTest) { SelfTestRun; exit 0 }

NeedPrereqs
$Stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
Write-Output (Plan $Stamp)
if ($DryRun) { exit 0 }

$Work = Join-Path ([IO.Path]::GetTempPath()) "rspku-deploy-$Stamp"
$Archive = Join-Path $Work 'source.tar'
$Package = Join-Path $Work "rspku-deploy-$Stamp.tgz"
$Stage = Join-Path $Work 'stage'
New-Item -ItemType Directory -Path $Stage -Force | Out-Null
Run git @('archive', '--format=tar', '-o', $Archive, $Branch, 'wp-content/themes/rspku-theme', 'wp-content/plugins/rspku-core', 'wp-content/plugins/rspku-cpt', 'wp-content/plugins/rspku-schema', 'wp-content/plugins/rspku-settings')
Run tar @('-xf', $Archive, '-C', $Stage)
Run npm @('ci') (Join-Path $Stage 'wp-content/themes/rspku-theme')
Run npm @('run', 'build') (Join-Path $Stage 'wp-content/themes/rspku-theme')
$Manifest = Join-Path $Stage 'wp-content/themes/rspku-theme/public/build/.vite/manifest.json'
if (-not (Test-Path -LiteralPath $Manifest -PathType Leaf)) { Fail "missing manifest: $Manifest" }
Run tar @('-czf', $Package, '-C', $Stage, 'wp-content')
$RemotePkg = "/tmp/rspku-deploy-$Stamp.tgz"
Run pscp (PscpArgs $Package $RemotePkg)
Run plink (PlinkArgs "sudo -n bash -c $(Q (RemoteScript $Stamp $RemotePkg))")
Write-Output "Deploy OK. Rollback: plink -batch -ssh -l $UserName -i `"$(KeyPath)`" -hostkey $Fingerprint $HostName `"sudo -n bash $BackupRoot/$Stamp/rollback.sh`""
