# Ringkasan Modifikasi Fitur Tes & Soal MTC

Seluruh modifikasi dan integrasi data untuk Mining Training Center (MTC) telah berhasil diimplementasikan dan diverifikasi secara penuh menggunakan pengujian integrasi otomatis.

---

## Ringkasan Perubahan Utama

### 1. Perbaikan Kritis Core Framework (`executeSecure`)
* **File Terkait**: [secure_query.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/functions/secure_query.php)
* **Deskripsi**: Fungsi `executeSecure` sebelumnya mengembalikan nilai dari `mysqli_insert_id()` saat melakukan operasi `INSERT`. Namun, karena database project menggunakan primary key string format UUID v4 (bukan `AUTO_INCREMENT`), `mysqli_insert_id()` selalu menghasilkan `0` (falsy) meskipun data sukses tersimpan. Ini menyebabkan framework menampilkan pesan error "Terjadi kesalahan saat menambahkan data" pada operasi penambahan data.
* **Perbaikan**: Diubah agar mengembalikan `true` apabila query `INSERT` sukses tetapi tidak memiliki ID `AUTO_INCREMENT`.

### 2. Modifikasi Halaman Manajemen Tes & Sidebar Menu
* **File Terkait**:
  - [test.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/pages/test/test.php)
  - [sidebar.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/sidebar.php)
* **Perubahan**:
  - Tampilan list di tabel kini menampilkan **Judul Kategori asli** (bukan string UUID) dengan menggunakan `LEFT JOIN` ke `test_categorys`.
  - Kolom kategori pada modal Add & Edit diubah menjadi drop-down `<select>` yang memuat list kategori dinamis dari database.
  - Menyetel nilai default pada modal Add: **Waktu = 45 menit**, **Tipe = post test** (via dropdown), dan **Point Show = true** (via dropdown).
  - Menambahkan tombol dengan **icon mata** (`bi-eye`) di baris tindakan tabel. Jika diklik, akan mengarah ke `?hal=test_test-question&id=<test_id>`.
  - **Question Count Badge**: Menampilkan jumlah soal yang sudah ditambahkan di bawah Judul Tes dengan label badge berwarna biru (`bg-info`). Diambil secara efisien via subquery SQL: `(SELECT COUNT(*) FROM test_questions q WHERE q.test_id = t.id) AS total_questions`.
  - **Sidebar Icon Fixes**: Memperbaiki icon-icon sidebar menu di `sidebar.php` yang sebelumnya duplikat (`bi-people-fill`) atau kurang representatif:
    - **Dashboard**: `bi-file-earmark-medical-fill` -> `bi-grid-fill`
    - **Generate CRUD**: `bi-people-fill` -> `bi-code-square`
    - **Test Category**: `bi-people-fill` -> `bi-tags-fill`
    - **Test Management**: `bi-people-fill` -> `bi-clipboard-check-fill`

### 3. Modifikasi Halaman & Handler Soal (Single-Page Multiple Choice, Bulk Insertion, Text Importer & Media Uploads)
* **File Terkait**: 
  - View: [test-question.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/pages/test/test-question.php)
  - Action Handler: [test-question.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/actions/pages/test/test-question.php)
  - Upload Helper: [upload_file.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/functions/upload_file.php)
* **Perubahan**:
  - **Penyederhanaan Layout Form**: Kolom Test & Question Type dijadikan satu baris (`row g-1`), input `Material Of Question` dibuat opsional (menghapus atribut `required` baik di form Add maupun Edit).
  - **Fitur Impor Soal via Teks Mentah (Plain Text Importer)**:
    - Menambahkan panel collapse `#importTextCollapse` dengan input textarea besar di atas form modal Add.
    - JavaScript `processImportText()` mem-parse baris demi baris menggunakan regex: baris dengan awalan `*` sebagai soal, dan baris awalan `-` sebagai opsi jawaban.
    - Pola `(true : <poin>)` secara otomatis ditangkap untuk menandai kunci jawaban yang benar serta bobot nilainya.
    - Data yang di-parse otomatis digenerate menjadi Question Cards lengkap dengan baris input pilihan ganda dinamis.
  - **Bulk Questions Creation**:
    - Menggunakan sistem Question Card dinamis. Halaman modal Add Soal secara default memuat 1 kartu soal.
    - Ditambahkan tombol **[+ Add Another Question]** untuk menambah kartu soal baru secara fleksibel. Setiap kartu memiliki pengaturan soal, tipe (Multiple Choice / Form), material, input pilihan ganda, dan input upload medianya masing-masing.
    - Fungsi dynamic reindexing Javascript menjaga agar attribute `name` dari input (`questions[i]...`) dan parameter files (`questions_media_files_i[]`) selalu berurutan.
  - **Dynamic Multiple Choice**: Form Add dan Edit diubah untuk menampilkan **4 baris opsi awal** (A s/d D) secara default. Ditambahkan tombol dynamic row (`addChoiceRow`) untuk menambahkan opsi baru secara tidak terbatas (fleksibel) serta `reindexChoices` untuk menyusun ulang index attribute `name` & label huruf opsi saat ada baris yang dihapus/ditambah.
  - **Multi-Media Form**: Kedua form (Add & Edit) ditambahkan parameter `enctype="multipart/form-data"` dan area upload dinamis (`addMediaRow`) untuk melampirkan lebih dari 1 file gambar atau dokumen.
  - **Upload Terenkripsi**: Menggunakan modul helper `uploadFile()` untuk menyimpan file dengan nama unik dan aman di server, dengan nama file display di DB yang dapat dikustomisasi secara mandiri oleh user. Path file disimpan secara relatif terhadap root project (`assets/test_medias/`).
  - **Sinkronisasi Edit & Cleanup**:
    - Memungkinkan penghapusan attachment lama secara instan pada modal Edit (dengan menandai ID untuk dihapus, menghapus record di DB `test_question_medias`, dan menghapus file fisik di disk via `unlink`).
    - Mengubah nama display dari attachment media yang sudah ada langsung dari modal Edit.
    - Menghapus semua file fisik di disk pada saat soal dihapus secara permanen dari sistem.
  - **Kesesuaian Lingkungan CLI**: Modul helper `upload_file.php` dimodifikasi agar menggunakan fungsi `copy()` ketika berjalan di bawah PHP CLI (untuk unit/integration testing) dan tetap menggunakan fungsi `move_uploaded_file()` bawaan PHP ketika berjalan di web server Apache/FPM biasa.

### 4. Integrasi Logger Sistem & Redirection Terpadu
* **File Terkait**:
  - [test.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/actions/pages/test/test.php)
  - [test-question.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/actions/pages/test/test-question.php)
* **Perubahan**:
  - Menghapus kode redirection manual HTML/Script `window.location.href` dan setup session message manual.
  - Mengintegrasikan fungsi logging `createLog($con, $user, $description)` di setiap akhir proses query penulisan database (Add, Update, Delete) yang berhasil. Deskripsi log berisi informasi nama objek yang diubah secara dinamis.
  - Menggunakan fungsi terpadu `redirectWithMessage($url, $message, $type)` untuk memberikan respons popup/alert browser & redirect halaman secara konsisten.

### 5. Perbaikan File Sistem Lainnya
* **File Terkait**: [generate_uuid.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/functions/generate_uuid.php)
* **Perubahan**: Memindahkan PHP closing tag (`?>`) ke akhir file agar teks dokumentasi contoh penggunaan di bagian bawah tidak tercetak secara mentah ke output HTML/HTTP response (yang dapat merusak pemrosesan header/JSON AJAX).

---

## Laporan Pengujian Otomatis (Integration Test - Tahap 3)

Sebuah script pengujian integrasi otomatis lengkap dijalankan di [test_integration.php](file:///C:/Users/USER/.gemini/antigravity-ide/brain/84ad3d72-ac71-4107-86e0-5c15ae814ebc/scratch/test_integration.php). Script ini memvalidasi seluruh alur penambahan data (secara bulk sekaligus), multi-upload media per kartu soal, update (rename & delete file), pemanggilan JSON AJAX, dan pembersihan file fisik di disk saat soal dihapus.

### Hasil Output Pengujian Terakhir:
```text
=== START MTC FITUR INTEGRATION TEST ===
[PASS] Category created: 8beed8be-e912-44c8-a39b-4d566e050ee5
Testing Add Test...
RUNNING COMMAND: php "C:\Users\USER\.gemini\antigravity-ide\brain\84ad3d72-ac71-4107-86e0-5c15ae814ebc\scratch/runner_temp.php" ...
[PASS] Test inserted successfully. ID: f8a30224-1ddf-4df4-940a-1946275bec18
Testing Add Questions Bulk (Question 1: Multiple Choice with 5 choices & 2 media uploads; Question 2: Form with 1 media upload)...
RUNNING COMMAND: php "C:\Users\USER\.gemini\antigravity-ide\brain\84ad3d72-ac71-4107-86e0-5c15ae814ebc\scratch/runner_temp.php" ...
[PASS] Question 1 inserted successfully. ID: f29b0ef3-74a2-4c4a-af58-d9fba9b4ddb5
[PASS] Verified exactly 5 choices inserted for Question 1.
[PASS] Verified exactly 2 media records inserted for Question 1.
[PASS] Question 1 media name 'Diagram Aliran Kerja' matched in database
[PASS] Question 1 physical file exists on disk: d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/assets/test_medias/diagram_aliran_20260520210201_b9bedfc9.png
[PASS] Question 1 media name 'SOP Keselamatan Tambang' matched in database
[PASS] Question 1 physical file exists on disk: d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/assets/test_medias/petunjuk_keselamatan_20260520210201_00c56892.pdf
[PASS] Question 2 (Form) inserted successfully with empty material. ID: f711ba0d-ddc5-4da1-be2c-fa22ac5abdd6
[PASS] Question 2 has 0 choices (correct for Form type).
[PASS] Verified exactly 1 media record inserted for Question 2.
[PASS] Question 2 media name 'Catatan Tambahan' matched in database
[PASS] Question 2 physical file exists: d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/assets/test_medias/catatan_material_20260520210201_80886e81.docx
Testing AJAX get_choices endpoint...
RUNNING COMMAND: php "C:\Users\USER\.gemini\antigravity-ide\brain\84ad3d72-ac71-4107-86e0-5c15ae814ebc\scratch/runner_temp.php" ...
[PASS] AJAX get_choices returned structured object containing choices and medias.
[PASS] AJAX returned exact counts: 5 choices, 2 medias.
Testing Update Question (Rename, Delete, and Add Media)...
RUNNING COMMAND: php "C:\Users\USER\.gemini\antigravity-ide\brain\84ad3d72-ac71-4107-86e0-5c15ae814ebc\scratch/runner_temp.php" ...
[PASS] Question text updated successfully
[PASS] Verified exactly 5 choices remain in database (Jakarta Raya, Bandung, Surabaya, Medan, Makassar).
[PASS] Choice with cleared text (Semarang) was deleted successfully.
[PASS] Verified exactly 2 media records remain in database.
[PASS] Media 1 was renamed successfully in database.
[PASS] Physical file for deleted media 2 was cleaned up from disk.
[PASS] New media 3 record verified in database.
[PASS] New media 3 physical file exists on disk.
Testing Delete Question (verifying media and file cleanup)...
[PASS] Prepared mock media file exists on disk before delete: d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/assets/test_medias/final_delete_test_20260520210201_873b52ed.txt
RUNNING COMMAND: php "C:\Users\USER\.gemini\antigravity-ide\brain\84ad3d72-ac71-4107-86e0-5c15ae814ebc\scratch/runner_temp.php" ...
[PASS] Question deleted successfully from database
[PASS] Choices orphaned cleanup successfully verified
[PASS] Media records cleaned up successfully from database
[PASS] Physical file for deleted question media was cleaned up from disk.
=== INTEGRATION TEST COMPLETED ===
```

---

## Sesi Lanjutan: Modul CBT Pengerjaan Ujian Kandidat (2026-05-21)

Seluruh modifikasi pada sesi ini berfokus pada pembuatan modul **Computer Based Test (CBT)** publik untuk peserta/kandidat tes MTC, terpisah dari panel admin utama.

---

### 6. Penambahan Tombol Share / Copy Link di Admin Test (`pages/test/test.php`)
* **File Terkait**: [test.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/pages/test/test.php)
* **Perubahan**:
  - Menambahkan tombol hijau `<i class="bi bi-share">` di kolom Actions setiap baris Tes pada tabel admin.
  - Tombol meng-construct URL kandidat secara dinamis menggunakan `window.location.origin` + path direktori + `/exam/?test_id=<UUID>`.
  - Menggunakan Clipboard API (`navigator.clipboard.writeText()`) untuk menyalin link. Fallback ke `prompt()` untuk browser lama yang tidak mendukung Clipboard API.
  - Format link yang disalin: `http://localhost/armadaMixGrup-mtc/exam/?test_id=<UUID>` (atau production URL jika di server).

### 7. Migrasi Skema Database
* **Perintah SQL dijalankan ke DB `armadamix_mtc`**:
  - `ALTER TABLE test_user_sessions ADD COLUMN question_order TEXT DEFAULT NULL` — menyimpan urutan soal teracak per sesi dalam format JSON Array.
  - `ALTER TABLE test_user_sessions ADD COLUMN status VARCHAR(50) DEFAULT 'active'` — mencatat status sesi (`active` / `submitted`).
  - `ALTER TABLE test_user_answers ADD COLUMN answer_text TEXT DEFAULT NULL` — menyimpan jawaban teks untuk soal tipe Form/Esai.

### 8. Folder & Aset Terpisah untuk Modul CBT (`/exam/`)
Struktur direktori baru dibuat di root project:
```text
/exam/
  ├── index.php                 (Form registrasi & inisialisasi sesi ujian)
  ├── exam-cbt.php              (Halaman utama CBT 2 kolom)
  ├── finish.php                (Halaman selesai & tampilan skor)
  ├── assets/
  │     └── css/
  │           └── style.css     (Custom CSS premium CBT)
  └── actions/
        └── submit-exam.php     (AJAX handler autosave & submit final)
```
* Bootstrap 5 dan Bootstrap Icons dimuat via CDN (`cdn.jsdelivr.net`), **tidak** menggunakan folder `assets/` milik panel admin.

### 9. Halaman Registrasi Kandidat (`exam/index.php`)
* **File**: [index.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/exam/index.php)
* **Fitur**:
  - Menerima parameter `?test_id=<UUID>` dan memvalidasi apakah tes tersebut terdaftar di database.
  - Menampilkan informasi ringkas tes (Nama, Durasi, Tipe) di atas formulir.
  - Form input: Nama Lengkap, No. WhatsApp, Email, Role (Rekrutmen / Karyawan).
  - **Logika user**: Jika email sudah ada di `users`, record diperbarui. Jika belum ada, user baru dibuat (UUID, username dari email + angka acak, password hash acak).
  - **Logika sesi**: Jika ada sesi `active` untuk user + test yang sama dan belum kedaluwarsa, kandidat akan dilanjutkan ke sesi tersebut. Jika tidak, sesi baru dibuat dengan soal teracak (`shuffle()` PHP) dan `question_order` disimpan sebagai JSON di `test_user_sessions`.
  - Redirect ke `exam-cbt.php` dengan `$_SESSION['exam_session_id']` yang terisi.

### 10. Halaman CBT Ujian (`exam/exam-cbt.php`)
* **File**: [exam-cbt.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/exam/exam-cbt.php)
* **Fitur**:
  - Validasi PHP sisi-server: memastikan sesi ada, masih `active`, dan waktu belum habis sebelum halaman dirender.
  - **Layout 2 Kolom (responsif)**:
    - **Kiri (col-lg-8)**: Area soal aktif (nomor, materi, teks soal, media lampiran, opsi pilihan/textarea esai) + tombol navigasi Sebelumnya / Selanjutnya.
    - **Kanan (col-lg-4)**: Timer countdown interaktif + grid kotak navigasi nomor soal + tombol "Selesaikan Ujian".
  - Semua soal, pilihan, media, dan jawaban yang sudah tersimpan di-inject dari PHP ke JavaScript sebagai objek JSON pada awal rendering halaman.
  - **JavaScript `loadQuestion(index)`**: Mengganti konten panel kiri secara dinamis tanpa reload halaman. Merender ulang media (gambar/video/audio/file), teks soal, dan pilihan ganda atau textarea esai.
  - **Autosave AJAX**: Setiap klik radio button memanggil `sendAnswerAjax()`. Untuk soal esai, input di-debounce (delay 1500ms) lalu dikirim ke `actions/submit-exam.php` saat `blur` atau setelah debounce.
  - **Timer Countdown**: Dihitung berdasarkan `datetime_end` dari database. Menampilkan format `HH:MM:SS`. Warna merah berkedip saat sisa waktu < 5 menit. Auto-submit saat countdown mencapai `00:00:00`.
  - **Grid Navigasi**: Warna grid berubah otomatis saat jawaban disimpan (hijau = terjawab, outline = belum dijawab, biru = soal aktif saat ini).
  - Loading overlay glassmorphic saat halaman pertama dimuat dan saat mengumpulkan jawaban.

### 11. AJAX Handler Autosave & Submit (`exam/actions/submit-exam.php`)
* **File**: [submit-exam.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/exam/actions/submit-exam.php)
* **Endpoint**: `POST actions/submit-exam.php`
* **Aksi `save_answer`**:
  - Memvalidasi sesi dan waktu sebelum menyimpan.
  - Jika jawaban untuk `question_id` pada `user_session_id` sudah ada → `UPDATE`. Jika belum ada → `INSERT` dengan UUID baru.
  - Mendukung penyimpanan `choice_id` (pilihan ganda) dan `answer_text` (esai/form) sekaligus.
  - Jika waktu sesi sudah habis saat menyimpan, otomatis mengubah status sesi menjadi `submitted` dan mengembalikan respons `expired: true`.
* **Aksi `finish_exam`**:
  - Mengubah `status` sesi menjadi `submitted` di `test_user_sessions`.
  - Meng-unset `$_SESSION['exam_session_id']` dan menyimpan `$_SESSION['completed_session_id']` untuk digunakan di halaman `finish.php`.

### 12. Halaman Selesai & Skor (`exam/finish.php`)
* **File**: [finish.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/exam/finish.php)
* **Fitur**:
  - Menampilkan detail pengerjaan: Nama, Ujian, Email, No. WA, Role, Waktu Selesai.
  - **Jika `point_show = 'true'`**: Menghitung skor kandidat dari jawaban pilihan ganda yang dipilih (`JOIN test_question_choices`) dan membandingkan dengan total skor maksimum yang mungkin. Ditampilkan dalam bentuk lingkaran skor visual.
  - **Jika ada soal esai**: Menampilkan pemberitahuan bahwa N soal esai masih menunggu penilaian manual admin.
  - **Jika `point_show = 'false'`**: Menampilkan pesan informatif bahwa hasil akan dievaluasi internal.
  - Tombol "Mulai Ulang" untuk memulai tes kembali (kembali ke form registrasi dengan `test_id` yang sama), dan tombol "Keluar" untuk menutup tab.

### 13. Custom CSS Modul CBT (`exam/assets/css/style.css`)
* **File**: [style.css](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/exam/assets/css/style.css)
* **Desain**:
  - Font: `Outfit` (Google Fonts) untuk kesan premium dan modern.
  - **Glassmorphism** pada semua panel konten (backdrop-blur, border transparan, bayangan lembut).
  - Custom CSS Variables untuk warna (`--primary-gradient`, `--glass-bg`, dll.).
  - Animasi `timerPulse` untuk timer yang hampir habis.
  - Styling grid navigasi (`.grid-item`) dengan state: `unanswered`, `answered`, `active-item`.
  - Styling choice item (`.choice-item`) dengan efek hover geser dan state `.selected`.
  - Custom scrollbar minimal yang elegan.
  - Loading spinner CSS murni (`.cbt-loader` dengan animasi rotasi).

---

## Sesi Lanjutan: Halaman Rekapan Sesi Ujian & Manajemen Laporan Admin (2026-05-22)

Sesi ini berfokus pada pembuatan modul **Test User Session** di panel admin untuk melihat rekapan hasil pengerjaan CBT peserta, melakukan penilaian detail, serta mencetak laporan ke PDF.

---

### 14. Modul Rekapan Sesi Ujian Admin (Report & Session Management)
* **File Terkait**:
  - View Page: [test-user-session.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/pages/test/test-user-session.php)
  - Action Handler: [test-user-session.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/actions/pages/test/test-user-session.php)
  - Sidebar Menu: [sidebar.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/sidebar.php)
* **Perubahan & Fitur**:
  - **Sidebar Menu Integration**: Menambahkan menu baru "Test User Session" dengan tautan ke `?hal=test_test-user-session` dan icon `bi-journal-text` yang serasi di bawah section *Assesments*.
  - **Penamaan File Kebab-Case**: Sesuai dengan aturan routing framework, nama file menggunakan pemisah tanda hubung/dash (`-`) bukan underscore (`_`), yaitu `test-user-session.php` untuk menghindari pembacaan subdirektori tambahan oleh router.
  - **List View (Daftar Sesi Ujian)**:
    - Dilengkapi pencarian real-time (`$search`) dan paginasi data menggunakan `makePagination` & `showPagination`.
    - Menampilkan informasi kandidat (Nama, Email, Role), nama tes, durasi/waktu mulai-selesai, dan status ujian (`Sedang Mengerjakan` / `Selesai`).
    - Menghitung skor pilihan ganda secara dinamis dengan prepared statements dan memberikan info jumlah soal esai yang perlu dinilai manual.
  - **Detail View (Evaluasi Jawaban)**:
    - Menampilkan banner informasi lengkap kandidat (Nama, Email, WA, Role, Durasi Ujian).
    - Skor akhir divisualisasikan menggunakan desain premium **Score Circle** minimalis modern.
    - Menampilkan seluruh pertanyaan sesuai urutan acak yang tersimpan di `question_order` JSON sesi tersebut.
    - Pilihan jawaban kandidat di-render secara visual: hijau terang untuk jawaban benar, merah terang untuk jawaban salah, dan garis putus-putus hijau untuk opsi jawaban benar yang tidak dipilih kandidat.
    - Menampilkan jawaban esai/form di dalam kontainer bergaris biru elegan dengan pesan pemberitahuan untuk evaluasi penguji.
    - Mendukung render file lampiran media (gambar dengan preview modal fullscreen, video player, audio player, dan link download dokumen pendukung).
  - **Print & PDF Support (Premium CSS Media Print)**:
    - Menambahkan tombol "Cetak Laporan (PDF)" yang mengarah ke parameter `&print=1` atau men-trigger fungsi cetak browser.
    - Mengintegrasikan rule `@media print` CSS premium untuk secara otomatis menyembunyikan sidebar admin, header, footer, tombol print, dan burger menu sehingga menghasilkan cetakan laporan/unduhan PDF bersih dan sangat rapi.
  - **Action Deletion Handler (Keamanan & Logger)**:
    - Penanganan penghapusan rekapan sesi secara aman menggunakan `actions/pages/test/test-user-session.php`.
    - Menghapus jawaban terkait di `test_user_answers` terlebih dahulu sebelum menghapus baris sesi di `test_user_sessions` (Prepared Statements `executeSecure`).
    - Mengintegrasikan sistem logging `createLog()` untuk mencatat aktivitas penghapusan sesi kandidat oleh admin serta redirect aman menggunakan `redirectWithMessage()`.


### 15. Perbaikan Bug Kalkulasi Skor Pilihan Ganda
* **File Terkait**:
  - [test-user-session.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/pages/test/test-user-session.php) (baris 37-62 dan 530-562)
  - [finish.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/exam/finish.php) (baris 38-40)
* **Masalah**: Skor kandidat terhitung tidak nol meskipun semua jawaban salah. Penyebabnya adalah query SUM(CAST(c.point AS UNSIGNED)) tidak memfilter c.choice_true = 	rue. Karena template default soal membuat semua opsi dengan nilai point = 10, jawaban salah pun ikut terjumlah ke total skor.
* **Perbaikan**: Menambahkan kondisi AND c.choice_true = 	rue pada semua query agregasi skor di view admin test-user-session.php (List View dan Detail View) dan halaman selesai ujian kandidat finish.php. Setelah perbaikan, kandidat yang memilih semua jawaban salah mendapatkan skor 0 dengan benar.

---

## Sesi Lanjutan: Admin Dashboard Premium (2026-05-22)

Sesi ini berfokus pada pembuatan halaman Dashboard Admin yang komprehensif dan premium sebagai pusat kendali sistem MTC.

---

### 16. Admin Dashboard Premium (pages/dashboard.php)
* **File Terkait**: [dashboard.php](file:///d:/xampp/htdocs/armadaMixGrup/armadaMixGrup-mtc/pages/dashboard.php)
* **Deskripsi**: Dashboard admin dibangun dengan desain premium, real-time metrics dari database, dua grafik interaktif ApexCharts, daftar sesi terbaru, dan feed log aktivitas sistem.

#### A. Welcome Banner Dinamis
  - Kartu selamat datang dengan gradient biru (#1e3a8a ke #3b82f6) dan elemen dekorasi lingkaran semi-transparan.
  - Ucapan waktu dinamis berdasarkan jam lokal server: Selamat Pagi (< 11.00), Selamat Siang (< 15.00), Selamat Sore (< 19.00), Selamat Malam (>= 19.00).
  - Menampilkan nama admin yang sedang login dari ['admin']['fullname'].
  - Badge tanggal hari ini di pojok kanan atas.

#### B. Metric Quick-Cards (4 Kartu Statistik)
  Empat kartu metrik diambil langsung dari database menggunakan querySecure:
  - **Bank Soal** - total soal dari test_questions (icon hijau bi-database-fill).
  - **Paket Ujian** - total tes dari tests (icon biru bi-clipboard2-check-fill).
  - **Sesi Berjalan** - sesi ujian berstatus active dari test_user_sessions (icon kuning bi-activity).
  - **Kandidat** - total user dari users (icon cyan bi-people-fill).
  - Semua kartu memiliki efek hover translateY(-5px) dengan smooth cubic-bezier transition.

#### C. Grafik Interaktif ApexCharts

  **1. Tren Sesi Ujian CBT (Area Chart - lebar 8 kolom Bootstrap):**
  - Menampilkan jumlah sesi ujian baru yang dimulai selama 7 hari terakhir.
  - Data diambil secara dinamis via loop PHP for ( = 6;  >= 0; --) menggunakan querySecure dengan filter DATE(datetime_start) = ?.
  - Grafik bergaya smooth area dengan gradient fill biru transparan, grid putus-putus, dan tanpa toolbar.

  **2. Distribusi Peran Kandidat (Donut Chart - lebar 4 kolom Bootstrap):**
  - Menampilkan perbandingan jumlah peserta berperan Rekrutmen vs Karyawan.
  - Label di tengah donut menampilkan total peserta secara otomatis via formatter JavaScript.
  - Warna: Rekrutmen = #06b6d4 (cyan), Karyawan = #64748b (slate gray).

#### D. Tabel Pengerjaan Ujian Terbaru
  - Menampilkan 5 sesi ujian terbaru dengan JOIN ke tabel tests dan users.
  - Kolom: Nama Kandidat (+ badge peran), Nama Ujian, Skor / Status, Waktu Mulai, Tombol Detail.
  - Skor dihitung real-time menggunakan query bug-free (AND c.choice_true = 'true').
  - Tombol Lihat Semua mengarah ke ?hal=test_test-user-session.
  - Jika sesi masih aktif: badge Aktif biru. Jika selesai: nilai numerik dengan konteks skor maksimum.

#### E. Feed Log Aktivitas Sistem Terkini
  - Menampilkan 5 log aktivitas terbaru dari tabel logs, urutan DESC.
  - Ikon dan warna log disesuaikan otomatis berdasarkan deskripsi:
    - delete: bi-trash merah.
    - add / insert: bi-plus-circle hijau.
    - update: bi-pencil kuning.
    - Lainnya: bi-info-circle biru.
  - Menampilkan nama pengguna, deskripsi tindakan, waktu (HH:MM), dan IP address.

* **Teknis dan Keamanan**:
  - Semua query menggunakan querySecure() dengan prepared statements.
  - ApexCharts diinisialisasi via inline script di akhir dashboard.php (bukan di dashboard.js global) untuk menghindari error DOM jika elemen chart tidak ditemukan di halaman lain.
  - CSS kustom scoped dalam style tag di dalam dashboard.php (.welcome-card, .metric-card, .icon-box, .avatar-sm).
