# Manna Kampus Recruitment — Local File Server

Aplikasi CodeIgniter 4 untuk menarik dokumen pelamar dari aplikasi recruitment di hosting, memverifikasi checksum SHA-256, dan menyimpan PDF di komputer lokal.

## Penyimpanan

- Database: `career_mannakampus_local_file`
- File PDF: `writable/documents/YYYY/MM/*.pdf`
- File tidak disimpan di `public` dan hanya dibuka melalui controller yang memerlukan login.
- Database hanya menyimpan metadata, lokasi relatif, ukuran, dan checksum.

## Menjalankan aplikasi

1. Jalankan Apache dan MySQL melalui XAMPP.
2. Buka `http://localhost/career_mannakampus_file_server_local/public/`.
3. Login dengan akun admin yang dibuat saat instalasi.
4. Ganti password sementara pada login pertama.

Migrasi untuk instalasi baru:

```powershell
php spark migrate
php spark db:seed InitialAdminSeeder
```

Seeder hanya membuat admin jika tabel `users` masih kosong. Isi `initialAdmin.password` sementara pada `.env` sebelum seeding, lalu kosongkan lagi setelah selesai.

## Konfigurasi API hosting

Tambahkan ke `.env` setelah endpoint hosting tersedia:

```ini
remoteStorage.baseUrl = 'https://recruitment.example.com/'
remoteStorage.clientId = 'manna-local-01'
remoteStorage.secret = 'SECRET_HMAC_YANG_SAMA_DENGAN_HOSTING'
remoteStorage.timeout = 30
remoteStorage.maxFileSize = 5242880
remoteStorage.syncBatchLimit = 100
```

Secret HMAC harus berbeda dari password pengguna, disimpan hanya dalam `.env`, dan nilainya harus sama dengan `storageSync.secret` pada hosting.

## Kontrak API hosting

### Daftar dokumen tertunda

`GET /api/storage/documents/pending`

Header:

```http
X-Sync-Client: manna-local-01
X-Sync-Timestamp: UNIX_TIMESTAMP
X-Sync-Nonce: NONCE_HEX_UNIK
X-Sync-Signature: HMAC_SHA256_HEX
Accept: application/json
```

Seluruh header HMAC dibuat otomatis oleh `RemoteDocumentService`; pengguna tidak perlu mengisinya melalui halaman aplikasi.

Respons:

```json
{
  "documents": [
    {
      "id": 10,
      "applicant_id": 3,
      "batch_id": 8,
      "application_number": "MK-260901-1234ABCD",
      "applicant_name": "Nama Pelamar",
      "document_type": "application_bundle",
      "original_filename": "berkas-lamaran.pdf",
      "mime_type": "application/pdf",
      "file_size": 1024000,
      "sha256_checksum": "64-karakter-hex",
      "uploaded_at": "2026-09-01 10:00:00"
    }
  ]
}
```

### Download PDF

`GET /api/storage/documents/{id}/download`

Mengembalikan body PDF, HTTP 200, dan disarankan menyertakan header:

```http
Content-Type: application/pdf
X-Checksum-SHA256: 64-karakter-hex
```

### Konfirmasi penyimpanan lokal

`POST /api/storage/documents/{id}/confirm`

Body JSON:

```json
{
  "sha256_checksum": "64-karakter-hex",
  "file_size": 1024000,
  "downloaded_at": "2026-09-01T10:05:00+07:00"
}
```

Hosting hanya boleh menandai transfer berhasil setelah checksum dan ukuran cocok. Penghapusan file hosting sebaiknya dilakukan oleh proses retensi terpisah, bukan langsung di endpoint konfirmasi.

## Pengujian

```powershell
composer test
```
