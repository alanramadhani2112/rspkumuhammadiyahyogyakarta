<?php
// Usage: php scrape-pendaftaran-schedule.php [out.json]  (read-only crawl, 0.3s delay)
// Read-only crawl of pendaftaran.pkujogja.com jadwal -> JSON
$base = 'https://pendaftaran.pkujogja.com';
function get(string $url): string {
    usleep(300000);
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: rspku-compare/1.0\r\n"]]);
    $h = @file_get_contents($url, false, $ctx);
    return $h === false ? '' : $h;
}
function dom(string $html): DOMXPath {
    $d = new DOMDocument();
    @$d->loadHTML('<?xml encoding="utf-8"?>' . $html);
    return new DOMXPath($d);
}
$clean = fn($s) => trim(preg_replace('/\s+/u', ' ', $s));

$x = dom(get("$base/jadwal"));
$polis = [];
foreach ($x->query('//a[contains(@class,"menu-card1")]') as $a) {
    if (preg_match('#/jadwal/dokter/(P\d+)#', $a->getAttribute('href'), $m)) {
        $polis[$m[1]] = $clean($x->query('.//h5', $a)->item(0)->textContent ?? '');
    }
}
$out = [];
foreach ($polis as $pid => $pname) {
    $x = dom(get("$base/jadwal/dokter/$pid"));
    foreach ($x->query('//a[contains(@class,"menu-card1")]') as $a) {
        if (!preg_match('#/jadwal/dokter/(P\d+)/(S\d+)#', $a->getAttribute('href'), $m)) continue;
        $name = $clean($a->textContent);
        $sx = dom(get("$base/jadwal/dokter/$pid/{$m[2]}"));
        $slots = [];
        foreach ($sx->query('//div[contains(@class,"schedule-content")]') as $c) {
            $slots[] = [
                'date' => $clean($sx->query('.//div[contains(@class,"schedule-date")]', $c)->item(0)->textContent ?? ''),
                'time' => $clean($sx->query('.//div[contains(@class,"schedule-time")]', $c)->item(0)->textContent ?? ''),
            ];
        }
        $out[] = ['poli_id' => $pid, 'poli' => $pname, 'sid' => $m[2], 'name' => $name, 'slots' => $slots];
    }
    fwrite(STDERR, "$pid $pname: " . count(array_filter($out, fn($r) => $r['poli_id'] === $pid)) . "\n");
}
file_put_contents($argv[1] ?? __DIR__ . '/source.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo count($polis) . " poli, " . count($out) . " dokter-poli rows\n";
