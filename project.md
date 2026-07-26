# Rancangan: Web Analytics Tool (Laravel + Vue + Inertia)

**Status:** Draft v1 — locked decisions ditandai 🔒, masih terbuka ditandai ❓
**Project Name:** `lumina`

---

## 1. Kenapa proyek ini ada

Tidak ada tools analytics kelas Umami/Plausible/Matomo yang dibangun di atas Laravel. Yang ada cuma package tracking (`andreaselia/laravel-analytics`, dll) — bukan produk berdiri sendiri dengan dashboard sendiri. Ini niche kosong di ekosistem Laravel.

**Bukan tujuan:** mengalahkan PostHog/Matomo secara fitur. Tujuannya adalah analytics ringan, self-hosted-mindset (dalam arti data dan kontrol penuh ada di tangan sendiri), cocok untuk developer yang stack-nya sudah Laravel, dideploy di Laravel Cloud tanpa nambah bahasa/runtime baru (Node, Elixir, Python).

**Risiko yang perlu diakui dari awal:** PHP-FPM model request-response tidak senatural Node/Elixir untuk ingest event volume tinggi. Ini bukan alasan untuk tidak jalan, tapi alasan untuk **tidak over-promise skala** di v1. Dirancang untuk skala small-to-medium (blog, SaaS kecil, agency client sites) — bukan untuk situs jutaan pageview/hari.

---

## 2. Locked decisions 🔒

| Area | Keputusan | Alasan |
|---|---|---|
| Backend | Laravel 13 | Sudah stack utama, minim context-switching |
| Starter kit | **Official Vue starter kit, Laravel 13.x** (`laravel new` → pilih Vue) — Inertia 3, Vue 3 Composition API, TypeScript, Tailwind, shadcn-vue, auth via Fortify. Ref: [laravel.com/docs/13.x/starter-kits#vue-customization](https://laravel.com/docs/13.x/starter-kits#vue-customization) | Starter kit resmi terkini (bukan Breeze — sudah tidak jadi starter kit utama sejak docs 13.x) |
| Arsitektur | **Fullstack monolith, satu repo, satu Laravel app** — bukan BE-FE terpisah | Inertia by design mengharuskan ini; split BE-FE membatalkan alasan pakai Inertia sama sekali dan nambah kompleksitas (CORS, token auth, dua pipeline deploy) tanpa manfaat konkret di tahap MVP |
| TypeScript | Ikut default starter kit (dipakai) | Menolak TS berarti harus strip semua `.ts`/type annotation dari scaffold resmi — kerja ekstra tanpa manfaat jelas. Terima default |
| Frontend | Vue 3 + Inertia.js (dari starter kit) | Konsisten dengan proyek lain (Uktubuu, dll) |
| Styling | Tailwind CSS + shadcn-vue (dari starter kit) | Standar di semua proyek eksisting + bawaan starter kit |
| Storage (v1) | PostgreSQL (managed, Laravel Cloud) | Cukup untuk MVP, hindari nambah dependency ClickHouse/Timescale sebelum ada bukti butuh. **Catatan risiko:** Postgres di Laravel Cloud serverless (Neon) dan hibernate saat idle — cek cold-start latency di load test (lihat §5) sebelum asumsikan endpoint collect selalu cepat |
| Deployment | **Laravel Cloud sepenuhnya** | Keputusan eksplisit dari kamu — bukan lagi VPS/Coolify seperti draft sebelumnya |
| Tracking script | Vanilla JS, no dependency, target <2KB | Ini fitur jual utama tools sejenis (Plausible <1KB, Umami ringan) |
| Auth dashboard | Fortify (bawaan starter kit resmi) | Jangan reinvent, ikut default resmi |
| Teams (starter kit) | **Tidak diaktifkan** — model data tetap `owner_id` per user, bukan `team_id` | Belum ada validasi kebutuhan multi-user per akun. Biaya migrasi ke teams nanti kalau tervalidasi = refactor yang jelas bentuknya (`owner_id` → `team_id`); biaya mengaktifkan sekarang tanpa bukti = pajak kompleksitas permanen (routing `/{team}/...`, scoping di semua query) untuk fitur yang mungkin tidak pernah dipakai |
| Queue | Laravel queue, **database driver dulu** di v1, worker jalan sebagai persistent process di Laravel Cloud | Laravel Cloud bikin Redis/Valkey managed satu-klik, jadi upgrade ke Redis nanti murah — tapi belum ada alasan konkret v1 butuh Redis, jadi jangan tambah dependency di awal |

**Yang sengaja DIABAIKAN dulu (defer sampai ada validasi user nyata):**
- Real-time live dashboard (websocket/Reverb) — v1 cukup refresh manual/polling
- Multi-tenant SaaS billing — v1 asumsikan self-host single-owner
- Mobile SDK, session replay, feature flags, A/B testing — di luar scope analytics dasar
- ClickHouse/columnar storage — baru dipertimbangkan kalau Postgres terbukti jadi bottleneck di beban nyata

---

## 3. Arsitektur v1

```
[Website pengunjung]
      │  (script.js <2KB, async, no cookie)
      ▼
[POST /api/collect]  ──► validasi + normalisasi ──► [queue: job InsertEvent]
                                                            │
                                                            ▼
                                                   [Postgres: table events]
                                                            │
[Dashboard Vue/Inertia] ◄── query agregasi (cached) ───────┘
```

### 3.1 Tracking script
- Satu file JS, di-embed via `<script>` tag
- Kirim: URL halaman, referrer, screen width (untuk device bucket), timestamp
- **Tidak** kirim: cookie, fingerprint, IP disimpan mentah (hash + salt harian untuk unique visitor, ala Plausible/Umami — bukan simpan IP)
- Event kustom opsional: `window.lumina('event_name', {props})`

### 3.2 Ingest endpoint
- `POST /api/collect` — endpoint publik, rate-limited per IP
- Validasi payload minimal, tolak jika domain tidak terdaftar di tabel `sites`
- Push ke queue job, **jangan** insert langsung di request cycle (supaya endpoint tetap cepat merespon script)
- Job `InsertEvent` yang insert ke Postgres

### 3.3 Storage — skema kasar
```
sites          (id, domain, owner_id, created_at)
events         (id, site_id, path, referrer, visitor_hash, device_type, country, created_at)
```
- Partisi tabel `events` per bulan kalau volume mulai terasa (Postgres native partitioning) — **defer sampai ada bukti perlu**, jangan bangun di v1

### 3.4 Dashboard query
- Agregasi dihitung on-read dengan query SQL + cache (Laravel cache, TTL pendek, mis. 60 detik) untuk endpoint yang sering diakses (grafik pageview harian, top pages, top referrer)
- **Tidak** bikin materialized view / pre-aggregation table di v1 — itu optimasi prematur sebelum tahu pola akses nyata

---

## 4. MVP scope (yang harus jalan sebelum "selesai")

- [ ] Registrasi 1 site, dapat snippet tracking
- [ ] Script terpasang di halaman, event masuk ke `events`
- [ ] Dashboard: total pageview, unique visitor (per hash harian), top pages, top referrer, grafik per hari (30 hari terakhir)
- [ ] Filter tanggal (7 hari / 30 hari / custom range)
- [ ] Multi-site di satu akun (list site, switch)

**Bukan MVP** (jangan mulai kerjakan sebelum di atas selesai dan diverifikasi jalan di produksi nyata):
- Custom event tracking dashboard UI
- Export data
- Public/shareable dashboard link
- Goal/conversion tracking

---

## 5. Verifikasi (bukti konkret, bukan "kayaknya sudah beres")

Setiap item MVP di atas baru dianggap selesai kalau ada bukti berikut:
1. **Script terpasang di situs nyata** (bukan localhost) dan event benar-benar masuk ke database — screenshot query `SELECT count(*) FROM events WHERE site_id = X` dengan angka > 0
2. **Dashboard menampilkan angka yang cocok** dengan hitung manual dari tabel `events` untuk rentang tanggal yang sama
3. **Load test ringan**: endpoint `/api/collect` di-hit dengan beban simulasi (mis. `hey` atau `k6`, 50 req/s selama 1 menit) — catat response time p95, ini jadi baseline referensi kalau nanti dianggap perlu pindah ke ClickHouse
4. **Deploy end-to-end di Laravel Cloud** (environment production sungguhan, bukan dev environment) — termasuk verifikasi queue worker benar-benar jalan sebagai persistent process (bukan cuma terdaftar di config), dan catat cold-start latency pertama kali Postgres serverless bangun dari hibernasi

---

## 6. Pertanyaan terbuka ❓

- Nama final proyek — belum ditentukan
- Skema hashing visitor: hash harian (IP+UserAgent+salt, ganti tiap hari ala Plausible) atau simpan cookie-less session ID di localStorage? Pilih salah satu sebelum mulai coding — dua-duanya defensible, tapi campur keduanya nambah kompleksitas tanpa manfaat jelas
- Apakah perlu dukung MySQL juga (banyak shared hosting Indonesia default MySQL) atau cukup Postgres saja? Ini mempengaruhi kompatibilitas Eloquent tapi nambah testing burden — rekomendasi: Postgres-only dulu, generalisasi nanti kalau ada demand nyata
- Rate limiting endpoint `/api/collect`: per IP saja, atau per site juga? Perlu diputuskan sebelum endpoint publik dibuka

---

## 7. Risiko yang perlu dipantau (bukan alasan berhenti, tapi jangan diabaikan)

- **PHP request-response overhead saat traffic naik** — mitigasi: queue job untuk insert, bukan insert sinkron. Kalau p95 response time endpoint collect > 200ms di load test, ini sinyal serius untuk investigasi lebih lanjut, bukan sekadar catatan.
- **Postgres sebagai analytics store** — relasional DB tidak dioptimasi untuk agregasi kolom besar. MVP oke untuk skala kecil-menengah; kalau `events` table tembus puluhan juta baris dan query dashboard mulai lambat, itu titik keputusan pindah ke ClickHouse/Timescale — bukan sebelum itu.
- **Postgres serverless hibernation di Laravel Cloud** — kalau database "tidur" saat idle lalu harus cold-start ketika event pertama masuk setelah jeda, ini bisa bikin request `/api/collect` pertama lambat atau timeout kalau tidak ditangani (mis. dengan queue yang toleran retry). Perlu diverifikasi langsung di §5 poin 4, jangan diasumsikan aman.
- **Privacy/GDPR-style claim** — kalau nanti dipromosikan sebagai "privacy-first no cookie", pastikan implementasi hash visitor benar-benar tidak reversible ke individu. Ini klaim yang harus dibuktikan lewat kode, bukan sekadar disebut di marketing.

---

## 8. Kenapa monolith Inertia, bukan monorepo BE-FE terpisah

Laravel Cloud punya fitur monorepo (attach 1 repo, buat aplikasi Cloud terpisah per direktori — cocok untuk kasus frontend SPA + backend API sebagai dua resource independen). Ini **secara teknis tersedia** tapi **sengaja tidak dipakai** untuk v1:

- Kalaupun dipisah jadi "backend app" + "frontend app" di Laravel Cloud, backend app tetap satu Laravel app yang isinya semua route — termasuk `/api/collect` dan endpoint dashboard. **Ini tidak menyelesaikan concern scaling ingest endpoint** yang jadi risiko utama proyek ini (lihat §7) — itu masalah yang perlu diselesaikan di level arsitektur queue/insert, bukan di level split repo.
- Split BE-FE berarti buang Inertia sepenuhnya (ganti jadi SPA + REST API + token auth), yang berarti buang starter kit resmi yang sudah dikunci di §2 dan nambah kompleksitas (CORS, dual deploy, version drift antara BE-FE) tanpa manfaat konkret di tahap MVP.
- Ini melanggar prinsip GSD: avoid premature abstraction sampai ada validasi kebutuhan nyata. Belum ada use case konkret (mis. mobile app terpisah yang perlu consume API yang sama) yang membenarkan split ini.

**Kapan dipertimbangkan ulang:** kalau nanti ada kebutuhan nyata untuk frontend terpisah (mobile app, widget embed terpisah, dsb) yang butuh API standalone — itu titik keputusan yang tepat, bukan sekarang.
