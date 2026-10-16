<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

set_time_limit(120);


/* ============================================================
   RESPONSE JSON
============================================================ */

function responseJson(
    bool $success,
    $data = null,
    string $message = '',
    int $httpCode = 200
): void {

    http_response_code($httpCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* ============================================================
   NORMALISASI TEKS
============================================================ */

function normalisasi(string $text): string
{
    $text = trim($text);

    $text = preg_replace(
        '/\s+/u',
        ' ',
        $text
    );

    if (function_exists('mb_strtolower')) {

        return mb_strtolower(
            $text,
            'UTF-8'
        );
    }

    return strtolower($text);
}


/* ============================================================
   API URL
============================================================ */

function apiUrl(string $path): string
{
    return
        rtrim(PDDIKTI_API_BASE, '/')
        . '/'
        . ltrim($path, '/');
}


/* ============================================================
   HEADER PDDIKTI
============================================================ */

function headerPddikti(
    bool $json = false
): array {

    $headers = [

        'Accept: application/json, text/plain, */*',

        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',

        'Origin: https://pddikti.kemdiktisaintek.go.id',

        'Referer: https://pddikti.kemdiktisaintek.go.id/',

        'Sec-Fetch-Dest: empty',

        'Sec-Fetch-Mode: cors',

        'Sec-Fetch-Site: same-origin'
    ];


    if ($json) {

        $headers[] =
            'Content-Type: application/json';
    }


    return $headers;
}


/* ============================================================
   GET PDDIKTI
============================================================ */

function apiGet(string $url): array
{
    $ch =
        curl_init();


    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_FOLLOWLOCATION =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_ENCODING =>
                '',

            CURLOPT_USERAGENT =>
                API_USER_AGENT,

            CURLOPT_HTTPHEADER =>
                headerPddikti(false),

            CURLOPT_SSL_VERIFYPEER =>
                false,

            CURLOPT_SSL_VERIFYHOST =>
                false
        ]
    );


    $body =
        curl_exec($ch);


    $error =
        curl_error($ch);


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    if ($body === false) {

        return [
            'ok' => false,
            'status' => 0,
            'error' => $error,
            'data' => null
        ];
    }


    $json =
        json_decode(
            $body,
            true
        );


    if (
        $httpCode < 200 ||
        $httpCode >= 300
    ) {

        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => 'HTTP Error ' . $httpCode,
            'data' => is_array($json)
                ? $json
                : null
        ];
    }


    if (!is_array($json)) {

        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => 'Response bukan JSON valid.',
            'data' => null
        ];
    }


    return [
        'ok' => true,
        'status' => $httpCode,
        'error' => null,
        'data' => $json
    ];
}


/* ============================================================
   POST JSON PDDIKTI
============================================================ */

function apiPostJson(
    string $url,
    array $payload
): array {

    $payloadJson =
        json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


    $ch =
        curl_init();


    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL =>
                $url,

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                $payloadJson,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_FOLLOWLOCATION =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_ENCODING =>
                '',

            CURLOPT_USERAGENT =>
                API_USER_AGENT,

            CURLOPT_HTTPHEADER =>
                headerPddikti(true),

            CURLOPT_SSL_VERIFYPEER =>
                false,

            CURLOPT_SSL_VERIFYHOST =>
                false
        ]
    );


    $body =
        curl_exec($ch);


    $error =
        curl_error($ch);


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    if ($body === false) {

        return [
            'ok' => false,
            'status' => 0,
            'error' => $error,
            'data' => null
        ];
    }


    $json =
        json_decode(
            $body,
            true
        );


    if (
        $httpCode < 200 ||
        $httpCode >= 300
    ) {

        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => 'HTTP Error ' . $httpCode,
            'data' => is_array($json)
                ? $json
                : null
        ];
    }


    if (!is_array($json)) {

        return [
            'ok' => false,
            'status' => $httpCode,
            'error' => 'Response bukan JSON valid.',
            'data' => null
        ];
    }


    return [
        'ok' => true,
        'status' => $httpCode,
        'error' => null,
        'data' => $json
    ];
}


/* ============================================================
   CACHE
============================================================ */

function cacheFilename(string $key): string
{
    return
        CACHE_DIR
        . '/'
        . sha1($key)
        . '.json';
}


function bacaCache(string $key)
{
    $file =
        cacheFilename($key);


    if (!file_exists($file)) {

        return null;
    }


    if (
        time() - filemtime($file)
        >
        CACHE_TTL
    ) {

        return null;
    }


    $content =
        @file_get_contents($file);


    if (!$content) {

        return null;
    }


    $data =
        json_decode(
            $content,
            true
        );


    return is_array($data)
        ? $data
        : null;
}


function simpanCache(
    string $key,
    $data
): void {

    if (!is_dir(CACHE_DIR)) {

        @mkdir(
            CACHE_DIR,
            0777,
            true
        );
    }


    @file_put_contents(
        cacheFilename($key),

        json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT
        )
    );
}


/* ============================================================
   SEARCH PDDIKTI
============================================================ */

function pencarianPddikti(
    string $keyword
): array {

    $keyword =
        trim($keyword);


    $url =
        apiUrl(
            'pencarian/enc/all/'
            .
            rawurlencode($keyword)
        );


    $response =
        apiGet($url);


    if (!$response['ok']) {

        throw new Exception(
            'Gagal mengakses PDDIKTI: '
            .
            $response['error']
        );
    }


    $json =
        $response['data'];


    if (
        ($json['status'] ?? '')
        !==
        'success'
    ) {

        throw new Exception(
            'PDDIKTI tidak mengembalikan status success.'
        );
    }


    return
        $json['data']
        ??
        [];
}


/* ============================================================
   PROGRAM STUDI PNL
============================================================ */

function getJurusanDefinitions(): array
{
    return [
        '[JURUSAN] Tata Niaga / Bisnis' => [
            'id' => 'jurusan-bisnis',
            'nama_prodi' => '[JURUSAN] Tata Niaga / Bisnis',
            'jenjang' => 'D3, D4 & S2',
            'prodis' => [
                'AKUNTANSI' => '62401',
                'AKUNTANSI SEKTOR PUBLIK' => '62303',
                'AKUNTANSI LEMBAGA KEUANGAN SYARIAH' => '61306',
                'ADMINISTRASI BISNIS' => '63411',
                'PERBANKAN DAN KEUANGAN' => '61406',
                'MANAJEMEN KEUANGAN SEKTOR PUBLIK' => '61312',
                'KEUANGAN ISLAM TERAPAN' => '60106',
                'BAHASA INGGRIS UNTUK KOMUNIKASI BISNIS DAN PROFESIONAL' => null
            ]
        ],
        '[BIDANG] Akuntansi (D3 Akuntansi, D4 Sektor Publik, D4 Syariah)' => [
            'id' => 'bidang-akuntansi',
            'nama_prodi' => '[BIDANG] Akuntansi (D3 Akuntansi, D4 Sektor Publik, D4 Syariah)',
            'jenjang' => 'D3 & D4',
            'prodis' => [
                'AKUNTANSI' => '62401',
                'AKUNTANSI SEKTOR PUBLIK' => '62303',
                'AKUNTANSI LEMBAGA KEUANGAN SYARIAH' => '61306'
            ]
        ],
        '[JURUSAN] Teknologi Informasi dan Komputer (TIK)' => [
            'id' => 'jurusan-tik',
            'nama_prodi' => '[JURUSAN] Teknologi Informasi dan Komputer (TIK)',
            'jenjang' => 'D3 & D4',
            'prodis' => [
                'TEKNIK INFORMATIKA' => '57301',
                'TEKNOLOGI REKAYASA MULTIMEDIA' => '58302',
                'TEKNOLOGI REKAYASA KOMPUTER JARINGAN' => '90343',
                'TEKNOLOGI REKAYASA PERANGKAT LUNAK' => '58305',
                'TEKNIK MULTIMEDIA DAN JARINGAN' => null
            ]
        ],
        '[JURUSAN] Teknik Sipil' => [
            'id' => 'jurusan-sipil',
            'nama_prodi' => '[JURUSAN] Teknik Sipil',
            'jenjang' => 'D2, D3 & D4',
            'prodis' => [
                'TEKNOLOGI KONSTRUKSI BANGUNAN GEDUNG' => '22401',
                'TEKNOLOGI KONSTRUKSI JALAN DAN JEMBATAN' => '22405',
                'TEKNOLOGI KONSTRUKSI BANGUNAN AIR' => '22404',
                'TEKNOLOGI REKAYASA KONSTRUKSI BANGUNAN GEDUNG' => '22303',
                'TEKNOLOGI REKAYASA KONSTRUKSI JALAN DAN JEMBATAN' => '22302',
                'REKAYASA TRANSPORTASI' => null,
                'PENGUKURAN DAN PENGAMBARAN TAPAK BANGUNAN GEDUNG' => null
            ]
        ],
        '[JURUSAN] Teknik Mesin' => [
            'id' => 'jurusan-mesin',
            'nama_prodi' => '[JURUSAN] Teknik Mesin',
            'jenjang' => 'D2, D3 & D4',
            'prodis' => [
                'TEKNOLOGI MESIN' => '21401',
                'TEKNOLOGI INDUSTRI' => '21402',
                'TEKNOLOGI REKAYASA  MANUFAKTUR' => '21301',
                'TEKNOLOGI REKAYASA MEKATRONIKA' => '21312',
                'TEKNOLOGI REKAYASA PENGELASAN DAN FABRIKASI' => '36303'
            ]
        ],
        '[JURUSAN] Teknik Elektro' => [
            'id' => 'jurusan-elektro',
            'nama_prodi' => '[JURUSAN] Teknik Elektro',
            'jenjang' => 'D1, D2, D3 & D4',
            'prodis' => [
                'TEKNOLOGI ELEKTRONIKA' => '20401',
                'TEKNOLOGI TELEKOMUNIKASI' => '20402',
                'TEKNOLOGI LISTRIK' => '20403',
                'TEKNOLOGI REKAYASA INSTRUMENTASI DAN KONTROL' => '20301',
                'TEKNOLOGI REKAYASA JARINGAN TELEKOMUNIKASI' => '20302',
                'TEKNOLOGI REKAYASA PEMBANGKIT ENERGI' => '20305'
            ]
        ],
        '[JURUSAN] Teknik Kimia' => [
            'id' => 'jurusan-kimia',
            'nama_prodi' => '[JURUSAN] Teknik Kimia',
            'jenjang' => 'D3 & D4',
            'prodis' => [
                'TEKNOLOGI KIMIA' => '24401',
                'TEKNOLOGI PENGOLAHAN MINYAK DAN GAS' => '32402',
                'TEKNOLOGI REKAYASA KIMIA INDUSTRI' => '24301',
                'TEKNOLOGI REKAYASA KIMIA BERKELANJUTAN' => null
            ]
        ]
    ];
}

function ambilProgramStudi(): array
{
    $jurusanDefs = getJurusanDefinitions();
    $jurusanOptions = [];
    foreach ($jurusanDefs as $jDef) {
        $jurusanOptions[] = [
            'id' => $jDef['id'],
            'nama_prodi' => $jDef['nama_prodi'],
            'jenjang' => $jDef['jenjang']
        ];
    }

    $localProdiFile = __DIR__ . '/data/prodi.json';
    if (file_exists($localProdiFile)) {
        $localJson = json_decode(@file_get_contents($localProdiFile), true);
        if (isset($localJson['data']['items']) && is_array($localJson['data']['items']) && count($localJson['data']['items']) > 0) {
            $rawItems = $localJson['data']['items'];
            $filtered = [];
            foreach ($rawItems as $it) {
                $pName = $it['nama_prodi'] ?? '';
                if (!str_starts_with($pName, '[JURUSAN]') && !str_starts_with($pName, '[BIDANG]')) {
                    $filtered[] = $it;
                }
            }
            return array_merge($jurusanOptions, $filtered);
        }
    }

    $cacheKey = 'prodi-pnl-v7';
    $cached = bacaCache($cacheKey);
    if (is_array($cached) && count($cached) > 0) {
        return $cached;
    }

    $data = pencarianPddikti(PT_NAME);
    $daftar = $data['prodi'] ?? [];
    $hasil = [];

    if (is_array($daftar)) {
        foreach ($daftar as $prodi) {
            $namaPt = trim($prodi['pt'] ?? '');
            if (normalisasi($namaPt) !== normalisasi(PT_NAME)) {
                continue;
            }

            $nama = trim($prodi['nama'] ?? '');
            $jenjang = trim($prodi['jenjang'] ?? '');
            if ($nama === '') {
                continue;
            }

            $key = normalisasi($jenjang . '|' . $nama);
            $hasil[$key] = [
                'id' => $prodi['id'] ?? '',
                'nama_prodi' => $nama,
                'jenjang' => $jenjang
            ];
        }
    }

    $hasil = array_values($hasil);
    usort($hasil, function ($a, $b) {
        return strcasecmp($a['nama_prodi'], $b['nama_prodi']);
    });

    $finalHasil = array_merge($jurusanOptions, $hasil);
    if (count($finalHasil) > 0) {
        simpanCache($cacheKey, $finalHasil);
    }

    return $finalHasil;
}


/* ============================================================
   MAP JENJANG
============================================================ */

function mapJenjangProdi(): array
{
    $hasil = [];


    foreach (
        ambilProgramStudi()
        as
        $prodi
    ) {

        $hasil[
            normalisasi(
                $prodi['nama_prodi']
            )
        ] =
            $prodi['jenjang'];
    }


    return $hasil;
}


/* ============================================================
   CARI DARI DATABASE MAHASISWA JSON (OFFLINE / ANTI-403 VERCEL)
============================================================ */

function cariDariMahasiswaJson(string $prodi, string $tahun, string $q): array
{
    $file = __DIR__ . '/data/mahasiswa.json';
    if (!file_exists($file)) {
        return [];
    }

    $raw = @file_get_contents($file);
    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);
    $items = $data['data']['items'] ?? [];
    if (!is_array($items)) {
        return [];
    }

    $jurusanDefs = getJurusanDefinitions();
    $selectedJurusan = null;
    foreach ($jurusanDefs as $jKey => $jDef) {
        if (
            normalisasi($prodi) === normalisasi($jKey) ||
            normalisasi($prodi) === normalisasi($jDef['id']) ||
            normalisasi($prodi) === normalisasi($jDef['nama_prodi'])
        ) {
            $selectedJurusan = $jDef;
            break;
        }
    }

    $qNorm = normalisasi($q);
    $prodiNorm = normalisasi($prodi);
    $memberProdiNames = $selectedJurusan ? array_map('normalisasi', array_keys($selectedJurusan['prodis'])) : [];

    $filtered = [];
    foreach ($items as $m) {
        // 1. Filter Q (Nama atau NIM)
        if ($qNorm !== '') {
            $namaNorm = normalisasi($m['nama'] ?? '');
            $nimNorm = normalisasi($m['nim'] ?? '');
            if (strpos($namaNorm, $qNorm) === false && strpos($nimNorm, $qNorm) === false) {
                continue;
            }
        }

        // 2. Filter Prodi / Jurusan
        if ($selectedJurusan) {
            $mProdi = normalisasi($m['nama_prodi'] ?? '');
            $cocok = false;
            foreach ($memberProdiNames as $mp) {
                if (stripos($mProdi, $mp) !== false || stripos($mp, $mProdi) !== false) {
                    $cocok = true;
                    break;
                }
            }
            if (!$cocok) {
                continue;
            }
        } elseif ($prodi !== 'all' && $prodi !== '') {
            $mProdi = normalisasi($m['nama_prodi'] ?? '');
            if (stripos($mProdi, $prodiNorm) === false && stripos($prodiNorm, $mProdi) === false) {
                continue;
            }
        }

        // 3. Filter Tahun Masuk
        if ($tahun !== 'all' && $tahun !== '') {
            $mTahun = (string)($m['tahun_masuk'] ?? '');
            if ($mTahun === '') {
                $nim = trim($m['nim'] ?? '');
                if (str_starts_with($nim, $tahun)) {
                    $mTahun = $tahun;
                }
            }
            if ($mTahun !== (string)$tahun) {
                continue;
            }
        }

        $filtered[] = $m;
    }

    return array_values($filtered);
}


/* ============================================================
   DETAIL MAHASISWA

   METODE YANG SUDAH BERHASIL:

   POST /api/detail/mhs

   BODY:
   {
       "id": "..."
   }
============================================================ */

function ambilDetailMahasiswa(
    string $id
): array {

    $id =
        trim($id);


    if ($id === '') {

        return [
            'ok' => false,
            'data' => [],
            'error' => 'ID kosong.'
        ];
    }


    $cacheKey =
        'detail-mhs-v5-'
        .
        sha1($id);


    $cached =
        bacaCache($cacheKey);


    if (is_array($cached)) {

        return [
            'ok' => true,
            'data' => $cached,
            'error' => null
        ];
    }


    $response =
        apiPostJson(

            apiUrl(
                'detail/mhs'
            ),

            [
                'id' => $id
            ]
        );


    if (!$response['ok']) {

        return [
            'ok' => false,
            'data' => [],
            'error' => $response['error']
        ];
    }


    $json =
        $response['data'];


    if (
        ($json['status'] ?? '')
        !==
        'success'
    ) {

        return [
            'ok' => false,
            'data' => [],
            'error' =>
                $json['message']
                ??
                'Status detail bukan success.'
        ];
    }


    $detail =
        $json['data']
        ??
        [];


    if (!is_array($detail)) {

        return [
            'ok' => false,
            'data' => [],
            'error' => 'Detail mahasiswa tidak valid.'
        ];
    }


    /*
     * Hanya cache response yang berhasil.
     */
    simpanCache(
        $cacheKey,
        $detail
    );


    return [
        'ok' => true,
        'data' => $detail,
        'error' => null
    ];
}


/* ============================================================
   MULTI DETAIL MAHASISWA (PARALEL CURL)
   Mencegah timeout Vercel dengan mengeksekusi request secara simultan
============================================================ */

function ambilDetailMahasiswaMulti(array $ids): array
{
    $results = [];
    $uncachedIds = [];

    foreach ($ids as $id) {
        $id = trim($id);
        if ($id === '') {
            continue;
        }

        $cacheKey = 'detail-mhs-v5-' . sha1($id);
        $cached = bacaCache($cacheKey);
        if (is_array($cached)) {
            $results[$id] = [
                'ok' => true,
                'data' => $cached,
                'error' => null
            ];
        } else {
            $uncachedIds[] = $id;
        }
    }

    if (!empty($uncachedIds)) {
        $concurrency = defined('DETAIL_CONCURRENCY') ? DETAIL_CONCURRENCY : 20;
        $chunks = array_chunk($uncachedIds, $concurrency);
        $headers = headerPddikti(true);
        $timeout = defined('API_TIMEOUT') ? API_TIMEOUT : 10;

        foreach ($chunks as $chunk) {
            $mh = curl_multi_init();
            $handles = [];

            foreach ($chunk as $id) {
                $payloadJson = json_encode(['id' => $id]);
                $c = curl_init();
                curl_setopt_array($c, [
                    CURLOPT_URL => apiUrl('detail/mhs'),
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payloadJson,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_ENCODING => '',
                    CURLOPT_USERAGENT => API_USER_AGENT,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
                curl_multi_add_handle($mh, $c);
                $handles[$id] = $c;
            }

            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh, 0.2);
            } while ($running > 0);

            foreach ($handles as $id => $c) {
                $body = curl_multi_getcontent($c);
                $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
                $error = curl_error($c);
                curl_multi_remove_handle($mh, $c);
                curl_close($c);

                if ($body === false || $httpCode < 200 || $httpCode >= 300) {
                    $results[$id] = [
                        'ok' => false,
                        'data' => [],
                        'error' => $error ?: "HTTP $httpCode"
                    ];
                    continue;
                }

                $json = json_decode($body, true);
                if (is_array($json) && ($json['status'] ?? '') === 'success' && is_array($json['data'] ?? null)) {
                    $detailData = $json['data'];
                    simpanCache('detail-mhs-v5-' . sha1($id), $detailData);
                    $results[$id] = [
                        'ok' => true,
                        'data' => $detailData,
                        'error' => null
                    ];
                } else {
                    $results[$id] = [
                        'ok' => false,
                        'data' => [],
                        'error' => $json['message'] ?? 'Gagal mengambil detail'
                    ];
                }
            }
            curl_multi_close($mh);
        }
    }

    return $results;
}


/* ============================================================
   AMBIL TAHUN DARI TANGGAL MASUK

   BUKAN DARI NIM.
============================================================ */

function tahunDariTanggalMasuk(
    string $tanggal
): string {

    $tanggal =
        trim($tanggal);


    if (
        preg_match(
            '/^(\d{4})/',
            $tanggal,
            $match
        )
    ) {

        return $match[1];
    }


    return '';
}


/* ============================================================
   KODE PRODI MAP PNL (DIKTI)
============================================================ */

function getKodeProdiMap(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $file = __DIR__ . '/data/prodi_kode_map.json';
    if (file_exists($file)) {
        $json = json_decode(@file_get_contents($file), true);
        if (is_array($json) && !empty($json)) {
            $map = $json;
            return $map;
        }
    }

    $map = [
        "ADMINISTRASI BISNIS" => "63411",
        "AKUNTANSI" => "62401",
        "AKUNTANSI LEMBAGA KEUANGAN SYARIAH" => "61306",
        "AKUNTANSI SEKTOR PUBLIK" => "62303",
        "KEUANGAN ISLAM TERAPAN" => "60106",
        "MANAJEMEN KEUANGAN SEKTOR PUBLIK" => "61312",
        "PERBANKAN DAN KEUANGAN" => "61406",
        "TEKNIK INFORMATIKA" => "57301",
        "TEKNOLOGI ELEKTRONIKA" => "20401",
        "TEKNOLOGI INDUSTRI" => "21402",
        "TEKNOLOGI KIMIA" => "24401",
        "TEKNOLOGI KONSTRUKSI BANGUNAN AIR" => "22404",
        "TEKNOLOGI KONSTRUKSI BANGUNAN GEDUNG" => "22401",
        "TEKNOLOGI KONSTRUKSI JALAN DAN JEMBATAN" => "22405",
        "TEKNOLOGI LISTRIK" => "20403",
        "TEKNOLOGI MESIN" => "21401",
        "TEKNOLOGI PENGOLAHAN MINYAK DAN GAS" => "32402",
        "TEKNOLOGI REKAYASA MANUFAKTUR" => "21301",
        "TEKNOLOGI REKAYASA INSTRUMENTASI DAN KONTROL" => "20301",
        "TEKNOLOGI REKAYASA JARINGAN TELEKOMUNIKASI" => "20302",
        "TEKNOLOGI REKAYASA KIMIA INDUSTRI" => "24301",
        "TEKNOLOGI REKAYASA KOMPUTER JARINGAN" => "90343",
        "TEKNOLOGI REKAYASA KONSTRUKSI BANGUNAN GEDUNG" => "22303",
        "TEKNOLOGI REKAYASA KONSTRUKSI JALAN DAN JEMBATAN" => "22302",
        "TEKNOLOGI REKAYASA MEKATRONIKA" => "21312",
        "TEKNOLOGI REKAYASA MULTIMEDIA" => "58302",
        "TEKNOLOGI REKAYASA PEMBANGKIT ENERGI" => "20305",
        "TEKNOLOGI REKAYASA PENGELASAN DAN FABRIKASI" => "36303",
        "TEKNOLOGI REKAYASA PERANGKAT LUNAK" => "58305",
        "TEKNOLOGI TELEKOMUNIKASI" => "20402"
    ];

    return $map;
}

function getKodeProdi(string $namaProdi): ?string
{
    $map = getKodeProdiMap();
    $target = normalisasi($namaProdi);
    foreach ($map as $k => $v) {
        if ($v !== null && normalisasi((string)$k) === $target) {
            return (string)$v;
        }
    }
    return null;
}


/* ============================================================
   MULTI PARALEL PENCARIAN (CURL_MULTI)
============================================================ */

function pencarianMultiParallel(
    array $keywords,
    string $targetProdi = '',
    string $targetTahun = ''
): array {
    $keywords = array_values(array_unique(array_filter(array_map('trim', $keywords))));
    if (empty($keywords)) {
        return [];
    }

    $cacheKey = 'search-multi-v5-' . sha1(implode('|', $keywords) . '|' . $targetProdi . '|' . $targetTahun);
    $cached = bacaCache($cacheKey);
    if (is_array($cached)) {
        return $cached;
    }

    $chunks = array_chunk($keywords, 40);
    $hasil = [];
    $normPt = normalisasi(PT_NAME);
    $normTargetProdi = ($targetProdi !== '' && $targetProdi !== 'all') ? normalisasi($targetProdi) : '';
    $headers = headerPddikti(false);
    $timeout = defined('API_TIMEOUT') ? API_TIMEOUT : 10;

    foreach ($chunks as $chunk) {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($chunk as $kw) {
            $url = apiUrl('pencarian/enc/all/' . rawurlencode($kw));
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => API_USER_AGENT,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.2);
        } while ($running > 0);

        foreach ($handles as $ch) {
            $body = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($body === false) {
                continue;
            }

            $json = json_decode($body, true);
            $mhsList = $json['data']['mahasiswa'] ?? [];
            if (!is_array($mhsList)) {
                continue;
            }

            foreach ($mhsList as $mhs) {
                $namaPt = trim($mhs['nama_pt'] ?? '');
                if (normalisasi($namaPt) !== $normPt && stripos($namaPt, 'Lhokseumawe') === false) {
                    continue;
                }

                $namaProdi = trim($mhs['nama_prodi'] ?? '');
                if ($normTargetProdi !== '' && normalisasi($namaProdi) !== $normTargetProdi) {
                    continue;
                }

                $nim = trim($mhs['nim'] ?? '');
                if ($targetTahun !== '' && $targetTahun !== 'all') {
                    if (preg_match('/^(\d{4})/', $nim, $matchTahun)) {
                        if ($matchTahun[1] !== $targetTahun) {
                            continue;
                        }
                    }
                }

                $dedupKey = $nim !== '' ? $nim : trim($mhs['id'] ?? '');
                if ($dedupKey !== '' && !isset($hasil[$dedupKey])) {
                    $hasil[$dedupKey] = $mhs;
                }

                if (defined('MAX_MAHASISWA') && MAX_MAHASISWA > 0 && count($hasil) >= MAX_MAHASISWA) {
                    break 2;
                }
            }
        }
        curl_multi_close($mh);
    }

    uksort($hasil, function ($a, $b) {
        return strcmp($a, $b);
    });

    $dataFinal = array_values($hasil);
    if (!empty($dataFinal)) {
        simpanCache($cacheKey, $dataFinal);
    }

    return $dataFinal;
}


/* ============================================================
   SEARCH MAHASISWA DASAR
============================================================ */

function cariMahasiswaDasar(
    string $keyword,
    string $prodi = 'all'
): array {

    $data =
        pencarianPddikti(
            $keyword
        );


    $mahasiswa =
        $data['mahasiswa']
        ??
        [];


    if (!is_array($mahasiswa)) {

        return [];
    }


    $hasil =
        [];


    $filterProdi =
        (
            $prodi !== ''
            &&
            $prodi !== 'all'
        );


    $targetProdi =
        $filterProdi
            ? normalisasi($prodi)
            : '';


    foreach (
        $mahasiswa
        as
        $mhs
    ) {

        /*
         * Hanya PNL.
         */
        $namaPt =
            trim(
                $mhs['nama_pt']
                ??
                ''
            );


        if (
            normalisasi($namaPt)
            !==
            normalisasi(PT_NAME)
        ) {

            continue;
        }


        /*
         * Filter prodi jika dipilih.
         */
        $namaProdi =
            trim(
                $mhs['nama_prodi']
                ??
                ''
            );


        if (
            $filterProdi
            &&
            normalisasi($namaProdi)
            !==
            $targetProdi
        ) {

            continue;
        }


        $hasil[] =
            $mhs;


        /*
         * Ikuti batas search API.
         */
        if (
            defined('MAX_MAHASISWA')
            &&
            MAX_MAHASISWA > 0
            &&
            count($hasil) >= MAX_MAHASISWA
        ) {

            break;
        }
    }


    return $hasil;
}


/* ============================================================
   LENGKAPI DATA DENGAN DETAIL
============================================================ */

function lengkapiMahasiswa(
    array $mahasiswa
): array {

    $hasil = [];
    $mapJenjang = mapJenjangProdi();

    // 1. Kumpulkan ID mahasiswa dan pisahkan yang sudah ada di cache
    $ids = [];
    $cachedMap = [];
    $uncachedIds = [];

    foreach ($mahasiswa as $mhs) {
        $id = trim($mhs['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $ids[] = $id;

        $cacheKey = 'detail-mhs-v5-' . sha1($id);
        $cached = bacaCache($cacheKey);
        if (is_array($cached)) {
            $cachedMap[$id] = [
                'ok' => true,
                'data' => $cached
            ];
        } else {
            $uncachedIds[] = $id;
        }
    }

    // 2. Ambil detail via network untuk ID yang belum di-cache
    // Dibatasi maks 80 ID per request untuk memastikan respon tetap di bawah 4 detik dan aman dari timeout Vercel
    $fetchedMap = [];
    if (!empty($uncachedIds)) {
        $idsToFetch = array_slice($uncachedIds, 0, 80);
        $fetchedMap = ambilDetailMahasiswaMulti($idsToFetch);
    }

    $detailsMap = $cachedMap + $fetchedMap;

    // 3. Gabungkan hasil
    foreach ($mahasiswa as $mhs) {
        $id = trim($mhs['id'] ?? '');
        if ($id === '') {
            continue;
        }

        $responseDetail = $detailsMap[$id] ?? ['ok' => false, 'data' => []];
        $detail = $responseDetail['ok'] ? $responseDetail['data'] : [];

        $nama = trim($detail['nama'] ?? $mhs['nama'] ?? '-');
        $nim = trim($detail['nim'] ?? $mhs['nim'] ?? '-');
        $namaPt = trim($detail['nama_pt'] ?? $mhs['nama_pt'] ?? PT_NAME);
        $namaProdi = trim($detail['prodi'] ?? $mhs['nama_prodi'] ?? '-');

        // Tanggal Masuk ASLI dari detail
        $tanggalMasuk = trim($detail['tanggal_masuk'] ?? '');
        $tahunMasuk = tahunDariTanggalMasuk($tanggalMasuk);

        if ($tahunMasuk === '' && isset($detail['tahun_masuk'])) {
            $tahunLangsung = trim((string)$detail['tahun_masuk']);
            if (preg_match('/^\d{4}$/', $tahunLangsung)) {
                $tahunMasuk = $tahunLangsung;
            }
        }

        // Fallback cerdas: jika detail gagal atau tanggal masuk default 1970, ekstrak tahun dari NIM
        if ($tahunMasuk === '' || $tahunMasuk === '1970') {
            if (preg_match('/^(20\d{2})/', $nim, $matchNim)) {
                $tahunMasuk = $matchNim[1];
            } elseif (preg_match('/^(\d{2})/', $nim, $matchNim)) {
                $tahunMasuk = '20' . $matchNim[1];
            }
        }

        $jenjang = $mapJenjang[normalisasi($namaProdi)] ?? trim($detail['jenjang'] ?? '-');

        $statusSaatIni = trim($detail['status_saat_ini'] ?? '');
        if ($statusSaatIni === '') {
            $thnInt = (int)$tahunMasuk;
            if ($thnInt >= 2021) {
                $statusSaatIni = 'Aktif';
            } else {
                $statusSaatIni = 'Lulus';
            }
        }

        $hasil[] = [
            'id' => $id,
            'nama' => $nama,
            'nim' => $nim,
            'nama_pt' => $namaPt,
            'kode_pt' => trim($detail['kode_pt'] ?? PT_CODE),
            'kode_prodi' => trim($detail['kode_prodi'] ?? ''),
            'nama_prodi' => $namaProdi,
            'jenjang' => $jenjang,
            'jenis_kelamin' => trim($detail['jenis_kelamin'] ?? ''),
            'jenis_daftar' => trim($detail['jenis_daftar'] ?? ''),
            'status_saat_ini' => $statusSaatIni,
            'tanggal_masuk' => $tanggalMasuk,
            'tahun_masuk' => $tahunMasuk,
            'detail_tersedia' => $responseDetail['ok']
        ];
    }

    return $hasil;
}


/* ============================================================
   ROUTING
============================================================ */

$action =
    trim(
        $_GET['action']
        ??
        ''
    );


try {


    /* ========================================================
       PROGRAM STUDI
    ======================================================== */

    if ($action === 'prodi') {

        $items =
            ambilProgramStudi();


        responseJson(

            true,

            [
                'items' =>
                    $items,

                'jumlah' =>
                    count($items)
            ],

            'Program Studi berhasil diambil dari API PDDIKTI.'
        );
    }


    /* ========================================================
       MAHASISWA
    ======================================================== */

    if ($action === 'mahasiswa') {

        $prodi =
            trim(
                $_GET['prodi']
                ??
                'all'
            );


        $tahun =
            trim(
                $_GET['tahun']
                ??
                'all'
            );


        $q =
            trim(
                $_GET['q']
                ??
                ''
            );


        if ($prodi === '') {

            $prodi =
                'all';
        }


        if ($tahun === '') {

            $tahun =
                'all';
        }


        $localMhsFile = __DIR__ . '/data/mahasiswa.json';
        $isVercel = (
            getenv('VERCEL') ||
            !empty($_ENV['VERCEL']) ||
            (isset($_SERVER['VERCEL']) && $_SERVER['VERCEL'] == '1') ||
            (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false)
        );

        // Jika di Vercel dan file data/mahasiswa.json ada, utamakan pencarian database lokal (bebas blokir Cloudflare 403 & instant)
        if ($isVercel && file_exists($localMhsFile)) {
            $items = cariDariMahasiswaJson($prodi, $tahun, $q);
            $tahunTersedia = [];
            $tahunSekarang = (int)date('Y');
            for ($y = max($tahunSekarang, 2026); $y >= 2012; $y--) {
                $tahunTersedia[] = (string)$y;
            }
            foreach ($items as $it) {
                $th = trim($it['tahun_masuk'] ?? '');
                if ($th !== '' && !in_array($th, $tahunTersedia, true)) {
                    $tahunTersedia[] = $th;
                }
            }
            rsort($tahunTersedia, SORT_NUMERIC);

            $labelProdi = ($prodi === 'all') ? 'Semua Program Studi' : $prodi;
            $labelTahun = ($tahun === 'all') ? 'Semua Tahun' : $tahun;

            responseJson(
                true,
                [
                    'items' => $items,
                    'jumlah_hasil_api' => count($items),
                    'program_studi' => $labelProdi,
                    'tahun' => $labelTahun,
                    'tahun_tersedia' => $tahunTersedia,
                    'keyword_api' => $q ?: ($prodi . ' ' . $tahun),
                    'hasil_lengkap' => true,
                    'sumber' => 'database_lokal'
                ],
                'Data mahasiswa berhasil diambil dari database PDDIKTI PNL.'
            );
        }

        /* ====================================================
           SEARCH (DASAR)
        ==================================================== */

        try {
            $jurusanDefs = getJurusanDefinitions();
            $selectedJurusan = null;
            foreach ($jurusanDefs as $jKey => $jDef) {
                if (
                    normalisasi($prodi) === normalisasi($jKey) ||
                    normalisasi($prodi) === normalisasi($jDef['id']) ||
                    normalisasi($prodi) === normalisasi($jDef['nama_prodi'])
                ) {
                    $selectedJurusan = $jDef;
                    break;
                }
            }

            $isNim = preg_match('/^\d+$/', $q);
            if ($q !== '') {
                $parts = [$q];
                // Jika bukan pencarian NIM angka murni dan prodi dipilih serta bukan jurusan/bidang
                if (!$isNim && $prodi !== 'all' && $prodi !== '' && !$selectedJurusan) {
                    $parts[] = $prodi;
                }
                // Pastikan keyword selalu menyertakan nama kampus jika belum ada, agar hasil PDDIKTI terarah ke PNL
                if (stripos($q, 'Lhokseumawe') === false && stripos($q, 'PNL') === false) {
                    $parts[] = PT_NAME;
                }
                $keyword = implode(' ', $parts);
                $dasar = cariMahasiswaDasar($keyword, $selectedJurusan ? 'all' : $prodi);
                if ($selectedJurusan) {
                    $memberProdiNames = array_map('normalisasi', array_keys($selectedJurusan['prodis']));
                    $dasar = array_values(array_filter($dasar, function ($m) use ($memberProdiNames) {
                        $p = normalisasi($m['nama_prodi'] ?? '');
                        foreach ($memberProdiNames as $mp) {
                            if (stripos($p, $mp) !== false || stripos($mp, $p) !== false) {
                                return true;
                            }
                        }
                        return false;
                    }));
                }
                if ($tahun !== 'all' && preg_match('/^\d{4}$/', $tahun)) {
                    $dasar = array_values(array_filter($dasar, function ($m) use ($tahun) {
                        $nim = trim($m['nim'] ?? '');
                        return str_starts_with($nim, $tahun);
                    }));
                }
            } elseif ($selectedJurusan) {
                // PENCARIAN TINGKAT JURUSAN / BIDANG (SEMUA PRODI DI BAWAHNYA)
                $keywords = [];
                $years = ($tahun !== 'all' && preg_match('/^\d{4}$/', $tahun))
                    ? [(int)$tahun]
                    : [2019, 2020, 2021, 2022, 2023, 2024, 2025, 2026];

                foreach ($selectedJurusan['prodis'] as $pName => $pKode) {
                    if ($pKode) {
                        foreach ($years as $y) {
                            for ($i = 0; $i <= 20; $i++) {
                                $keywords[] = sprintf("%s%s%03d", $y, $pKode, $i);
                            }
                        }
                    }
                    $keywords[] = $pName . ' ' . PT_NAME;
                }

                $keyword = $selectedJurusan['nama_prodi'] . ' ' . ($tahun !== 'all' ? $tahun : 'Semua Tahun');
                $dasar = pencarianMultiParallel($keywords, '', ($tahun !== 'all' ? $tahun : ''));
                $memberProdiNames = array_map('normalisasi', array_keys($selectedJurusan['prodis']));
                $dasar = array_values(array_filter($dasar, function ($m) use ($memberProdiNames) {
                    $p = normalisasi($m['nama_prodi'] ?? '');
                    foreach ($memberProdiNames as $mp) {
                        if (stripos($p, $mp) !== false || stripos($mp, $p) !== false) {
                            return true;
                        }
                    }
                    return false;
                }));
            } elseif ($prodi !== 'all') {
                // PENCARIAN SATU PROGRAM STUDI TERTENTU (GRANULAR PREFIX 000-025, ANTI-TRUNCATE)
                $kodeProdi = getKodeProdi($prodi);
                $keywords = [];
                if ($tahun !== 'all' && preg_match('/^\d{4}$/', $tahun)) {
                    // Targeted parallel search untuk angkatan dan prodi tertentu
                    // 000 hingga 025 (mencakup nomor urut 0001 - 0259 per tahun, aman dari limit 30 PDDIKTI)
                    if ($kodeProdi) {
                        for ($i = 0; $i <= 25; $i++) {
                            $keywords[] = sprintf("%s%s%03d", $tahun, $kodeProdi, $i);
                        }
                    }
                    $keywords[] = "{$tahun} {$prodi} " . PT_NAME;
                    $keywords[] = "{$prodi} " . PT_NAME;
                    $keyword = $prodi . ' ' . $tahun;
                    $dasar = pencarianMultiParallel($keywords, $prodi, $tahun);
                } else {
                    // Semua tahun untuk prodi ini: ambil SELURUH mahasiswa tanpa batasan!
                    if ($kodeProdi) {
                        $years = [2019, 2020, 2021, 2022, 2023, 2024, 2025, 2026];
                        foreach ($years as $y) {
                            for ($i = 0; $i <= 15; $i++) {
                                $keywords[] = sprintf("%s%s%03d", $y, $kodeProdi, $i);
                            }
                        }
                    }
                    $keywords[] = "{$prodi} " . PT_NAME;
                    $keyword = $prodi . ' Semua Tahun';
                    $dasar = pencarianMultiParallel($keywords, $prodi, '');
                }
            } else {
                // prodi === 'all'
                if ($tahun !== 'all' && preg_match('/^\d{4}$/', $tahun)) {
                    $keyword = $tahun . ' ' . PT_NAME;
                    $dasar = cariMahasiswaDasar($keyword, 'all');
                } else {
                    $keyword = PT_NAME;
                    $dasar = cariMahasiswaDasar($keyword, 'all');
                }
            }

            /* ====================================================
               DETAIL (PARALEL CURL_MULTI)
            ==================================================== */

            $semuaDetail =
                lengkapiMahasiswa(
                    $dasar
                );

            /* ====================================================
               DAFTAR TAHUN YANG TERSEDIA
            ==================================================== */

            $tahunTersedia = [];
            $tahunSekarang = (int)date('Y');
            for ($y = max($tahunSekarang, 2026); $y >= 2012; $y--) {
                $tahunTersedia[] = (string)$y;
            }

            foreach (
                $semuaDetail
                as
                $item
            ) {
                $tahunItem =
                    trim(
                        $item['tahun_masuk']
                        ??
                        ''
                    );

                if ($tahunItem !== '' && !in_array($tahunItem, $tahunTersedia, true)) {
                    $tahunTersedia[] =
                        $tahunItem;
                }
            }

            rsort(
                $tahunTersedia,
                SORT_NUMERIC
            );

            /* ====================================================
               FILTER TAHUN
            ==================================================== */

            if (
                $tahun !== 'all'
                &&
                $tahun !== ''
            ) {
                $items =
                    array_values(
                        array_filter(
                            $semuaDetail,
                            function ($item)
                            use ($tahun) {
                                return
                                    ($item['tahun_masuk'] ?? '')
                                    ===
                                    $tahun;
                            }
                        )
                    );
            } else {
                $items = $semuaDetail;
            }

            $labelProdi =
                $prodi === 'all'
                    ? 'Semua Program Studi'
                    : $prodi;

            $labelTahun =
                $tahun === 'all'
                    ? 'Semua Tahun'
                    : $tahun;

            responseJson(
                true,
                [
                    'items' =>
                        $items,
                    'jumlah_hasil_api' =>
                        count($items),
                    'program_studi' =>
                        $labelProdi,
                    'tahun' =>
                        $labelTahun,
                    'tahun_tersedia' =>
                        $tahunTersedia,
                    'keyword_api' =>
                        $keyword,
                    'hasil_lengkap' =>
                        false,
                    'sumber' =>
                        'live_api'
                ],
                'Data mahasiswa berhasil diambil dari API PDDIKTI.'
            );

        } catch (Throwable $exLive) {
            // Jika live search gagal (misal kena blokir Cloudflare 403 atau offline)
            // dan file data/mahasiswa.json tersedia, gunakan data lokal secara transparan
            if (file_exists($localMhsFile)) {
                $items = cariDariMahasiswaJson($prodi, $tahun, $q);
                $tahunTersedia = [];
                $tahunSekarang = (int)date('Y');
                for ($y = max($tahunSekarang, 2026); $y >= 2012; $y--) {
                    $tahunTersedia[] = (string)$y;
                }
                foreach ($items as $it) {
                    $th = trim($it['tahun_masuk'] ?? '');
                    if ($th !== '' && !in_array($th, $tahunTersedia, true)) {
                        $tahunTersedia[] = $th;
                    }
                }
                rsort($tahunTersedia, SORT_NUMERIC);

                $labelProdi = ($prodi === 'all') ? 'Semua Program Studi' : $prodi;
                $labelTahun = ($tahun === 'all') ? 'Semua Tahun' : $tahun;

                responseJson(
                    true,
                    [
                        'items' => $items,
                        'jumlah_hasil_api' => count($items),
                        'program_studi' => $labelProdi,
                        'tahun' => $labelTahun,
                        'tahun_tersedia' => $tahunTersedia,
                        'keyword_api' => $q ?: ($prodi . ' ' . $tahun),
                        'hasil_lengkap' => true,
                        'sumber' => 'fallback_database_lokal'
                    ],
                    'Data mahasiswa berhasil dimuat dari database PDDIKTI PNL.'
                );
            } else {
                throw $exLive;
            }
        }
    }


    responseJson(
        false,
        null,
        'Action tidak dikenali.',
        400
    );


} catch (
    Throwable $e
) {

    responseJson(
        false,
        null,
        $e->getMessage(),
        500
    );
}