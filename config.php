<?php

/**
 * ============================================================
 * KONFIGURASI APLIKASI
 * Data Mahasiswa Politeknik Negeri Lhokseumawe
 * Sumber: API PDDIKTI
 * ============================================================
 */


/**
 * Base URL API PDDIKTI.
 */
define(
    'PDDIKTI_API_BASE',
    'https://pddikti.kemdiktisaintek.go.id/api'
);


/**
 * Identitas perguruan tinggi.
 */
define(
    'PT_NAME',
    'Politeknik Negeri Lhokseumawe'
);

define(
    'PT_CODE',
    '005016'
);


/**
 * ID perguruan tinggi yang diperoleh dari
 * request frontend PDDIKTI.
 *
 * Digunakan pada endpoint:
 * /api/pt/prodi/{id_pt}/{semester}
 */
define(
    'PT_API_ID',
    'Ta2RAn7SdVYbvwx3PO1guRVIF4j8q5cQ9ceolaxaUphQ_KAYuoCBk10V5sa9eojqxGJI5w=='
);


/**
 * Semester yang digunakan untuk mengambil daftar prodi.
 *
 * Format PDDIKTI:
 * YYYY1 = Ganjil
 * YYYY2 = Genap
 */
define(
    'SEMESTER',
    '20251'
);


/**
 * Folder cache: gunakan /tmp jika di Vercel atau folder tidak writable.
 */
$isVercelEnv = getenv('VERCEL') || !empty($_ENV['VERCEL']) || (isset($_SERVER['VERCEL']) && $_SERVER['VERCEL'] == '1');
define(
    'CACHE_DIR',
    ($isVercelEnv || !is_writable(__DIR__))
        ? sys_get_temp_dir() . '/pddikti_cache'
        : __DIR__ . '/cache'
);


/**
 * Cache berlaku selama 24 jam (86400 detik).
 */
define(
    'CACHE_TTL',
    86400
);


/**
 * Maksimal mahasiswa yang diproses dalam satu hasil pencarian.
 * 0 = Tanpa batas (mengambil seluruh data mahasiswa tanpa pemotongan).
 */
define(
    'MAX_MAHASISWA',
    0
);



/**
 * Jumlah request detail mahasiswa
 * yang dijalankan bersamaan via curl_multi.
 */
define(
    'DETAIL_CONCURRENCY',
    40
);



/**
 * Timeout request API (detik).
 */
define(
    'API_TIMEOUT',
    12
);


/**
 * User-Agent.
 */
define(
    'API_USER_AGENT',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/153.0 Safari/537.36'
);


/**
 * Pastikan folder cache tersedia.
 */
if (!is_dir(CACHE_DIR)) {
    @mkdir(
        CACHE_DIR,
        0777,
        true
    );
}