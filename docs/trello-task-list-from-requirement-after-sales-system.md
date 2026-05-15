# Trello Task List

Sumber requirement:

- `c:\Users\Yeheskhiel.Rakordana\Downloads\Requirement_After_Sales_System(1).pdf`

Tujuan:

- Menurunkan requirement PDF menjadi task list Trello yang lengkap dan siap dipakai untuk implementasi.

## Struktur Board Trello yang Disarankan

- `Backlog`
- `Need Clarification`
- `Sprint 0 - Foundation`
- `Sprint 1 - Complaint`
- `Sprint 2 - Bad Review`
- `Sprint 3 - Order Tracking`
- `Sprint 4 - OOS`
- `Sprint 5 - Dashboard`
- `Sprint 6 - Agent Productivity`
- `QA / UAT`
- `Ready for Release`
- `Done`

## Label Trello yang Disarankan

- `Backend`
- `Frontend`
- `Database`
- `Automation`
- `Integration`
- `Dashboard`
- `Security`
- `QA`
- `High`
- `Medium`
- `Low`
- `Blocked`

## List: Need Clarification

### Card: Finalisasi definisi source Complaint

Labels: Backend, Frontend, High

Checklist:

- Pastikan daftar final `Source` complaint
- Samakan istilah PDF dengan kebutuhan bisnis aktif
- Tetapkan apakah `KAE` dan `Brand` masuk source atau assignment

### Card: Finalisasi master data SKU untuk autofill Complaint

Labels: Backend, Database, High

Checklist:

- Tentukan sumber data SKU
- Tentukan field master SKU: `sku`, `product_name`, `qty`, `brand`, `status_qty`, `value`
- Putuskan apakah autofill hanya dari DB internal atau file upload admin
- Sepakati aturan fallback jika SKU tidak ditemukan

### Card: Finalisasi daftar nilai Cause/By

Labels: Backend, Frontend, Medium

Checklist:

- Samakan option PDF dengan option final di UI
- Pastikan entri `GOJEK`, `GRAB`, `GOJEK/GRAB`, `Chat++`, `WH`, `PART` konsisten
- Pastikan nilai yang auto-filled tetap tidak bentrok dengan input manual

### Card: Definisikan kategori Last Step external vs internal

Labels: Backend, Dashboard, High

Checklist:

- Tandai last step yang masuk kategori external
- Tandai last step yang masuk kategori internal
- Sepakati mapping untuk dashboard

### Card: Klarifikasi integrasi RGO untuk Order Tracking

Labels: Integration, High, Blocked

Checklist:

- Tentukan sumber data list RGO
- Tentukan format pencarian berdasarkan `order_id`
- Sepakati response saat data ditemukan
- Sepakati frekuensi sinkronisasi

### Card: Klarifikasi integrasi Jet Track untuk Order Tracking

Labels: Integration, High, Blocked

Checklist:

- Tentukan akses aplikasi/API Jet Track
- Tentukan pencarian berdasarkan `awb`
- Tentukan field `kondisi barang` yang perlu diambil
- Sepakati error handling jika API gagal

### Card: Klarifikasi definisi D-Days dan D-1 pada OOS

Labels: Dashboard, Medium

Checklist:

- Definisikan arti `D-Days`
- Definisikan arti `-1`
- Samakan timezone perhitungan

### Card: Klarifikasi requirement Controller Brand Real Time

Labels: Dashboard, High, Blocked

Checklist:

- Definisikan fungsi modul/controller ini
- Tentukan data apa yang harus realtime
- Tentukan role yang dapat melihatnya

## List: Sprint 0 - Foundation

### Card: Setup struktur modul berdasarkan requirement PDF

Labels: Backend, Frontend, High

Checklist:

- Pastikan route untuk `Complaint`, `Bad Review`, `Order Tracking`, `OOS`, dan `Dashboard` tersedia
- Rapikan penamaan route dan halaman agar konsisten
- Pastikan navigasi sidebar mengarah ke seluruh modul

### Card: Rancang database dan review skema tabel utama

Labels: Database, Backend, High

Checklist:

- Review tabel `complaints`
- Review tabel `bad_reviews`
- Review tabel `order_trackings`
- Review tabel `oos`
- Tambahkan field yang belum ada dari PDF jika masih kurang
- Tambahkan index untuk `order_id`, `username`, `cs_name`, `platform`, `status`, `month`

### Card: Implement role dan permission dasar

Labels: Security, Backend, High

Checklist:

- Siapkan role `Super Admin`
- Siapkan role `CS`
- Siapkan role `Finance`
- Siapkan role `WH`
- Siapkan role `KAE`
- Siapkan role `After Sales`
- Siapkan role `Brand`
- Batasi akses route sesuai role
- Batasi aksi create, update, delete sesuai role

### Card: Siapkan upload file dan storage policy

Labels: Backend, High

Checklist:

- Siapkan upload `video_unboxing` complaint
- Siapkan upload `video_unboxing_wh` order tracking
- Siapkan upload `bap_wh` order tracking
- Tentukan validasi ukuran dan mime type
- Tentukan strategi preview/download file

### Card: Buat seed data dan data dummy untuk UAT

Labels: Database, QA, Medium

Checklist:

- Buat dummy data complaint
- Buat dummy data bad review
- Buat dummy data order tracking
- Buat dummy data OOS
- Buat user per role untuk UAT

## List: Sprint 1 - Complaint

### Card: Implement form input Complaint sesuai PDF

Labels: Frontend, Backend, High

Checklist:

- Field `source`
- Field `tanggal complaint`
- Field `tanggal order`
- Field `jam customer complaint`
- Field `brand`
- Field `platform`
- Field `order id`
- Field `username`
- Field `resi`
- Field `sku`
- Field `value of product`
- Field `product name`
- Field `sub case`
- Field `cause/by`
- Field `update long text`
- Field `part of bad`
- Field `cs name`
- Field `last step`
- Field `step CS selesai`
- Field `level customer`
- Field `tanggal step CS selesai`
- Field `tanggal update`
- Field `video unboxing`
- Field `proof`
- Field `reason whitelist`
- Field `reason late respons`

### Card: Implement validasi Complaint

Labels: Backend, High

Checklist:

- Validasi required field utama
- Validasi tipe tanggal dan waktu
- Validasi enum `step CS selesai`
- Validasi conditional `tanggal step CS selesai`
- Validasi conditional `reason whitelist`
- Validasi conditional `reason late respons`
- Validasi upload video

### Card: Implement automation Cycle pada Complaint

Labels: Automation, Backend, Medium

Checklist:

- Jika jam `>= 21:00:00` maka `Cycle 1`
- Jika jam `<= 15:00:00` maka `Cycle 1`
- Jika jam antara `15:01` sampai `20:59` maka `Cycle 2`
- Pastikan hasil ditampilkan di UI

### Card: Implement automation SKU autofill pada Complaint

Labels: Automation, Backend, Frontend, High

Checklist:

- Auto fill `product_name`
- Auto fill `qty`
- Tentukan apakah `value_of_product` ikut terisi otomatis
- Lock atau allow edit setelah autofill sesuai keputusan bisnis

### Card: Implement automation Cause/By dari Sub Case

Labels: Automation, Backend, Frontend, High

Checklist:

- `Bad Quality Product` -> `BRAND`
- `Expired` -> `BRAND`
- `Misunderstanding of the product` -> `CUSTOMER`
- `OOS` -> `KAE`
- `Promotion` -> `PROMO`
- `Change Mind` -> `CUSTOMER`
- Di luar itu tetap editable manual

### Card: Implement automation Status dari Last Step pada Complaint

Labels: Automation, Backend, High

Checklist:

- Mapping `Solved`
- Mapping `Whitelist`
- Mapping `Pending`
- Pastikan hasil sinkron antara backend dan frontend

### Card: Implement automation Priority dari Last Step pada Complaint

Labels: Automation, Backend, High

Checklist:

- Mapping `Cool`
- Mapping `Mines`
- Mapping `P1`
- Mapping `P2`
- Mapping `P3`
- Mapping `P4`
- Mapping `P5`
- Mapping `P6`
- Mapping `P7`

### Card: Implement automation SLA pada Complaint

Labels: Automation, Backend, High

Checklist:

- Hitung dari `tanggal complaint`
- Gunakan tanggal berjalan untuk case pending
- Hentikan perhitungan di `tanggal update` jika status solved
- Tampilkan SLA di list dan detail

### Card: Implement automation Category Customer pada Complaint

Labels: Automation, Backend, Medium

Checklist:

- Complaint pertama: kosong
- Complaint kedua: `complaint ke 2`
- Complaint ketiga dan seterusnya: `complaint ke Nx`
- Basis hitung dari `username`

### Card: Implement automation Riwayat OOS pada Complaint

Labels: Automation, Backend, Integration, Medium

Checklist:

- Cek `order_id` ke tabel OOS
- Jika ditemukan, tampilkan `Ada Riwayat OOS`
- Jika tidak ditemukan, kosongkan field

### Card: Implement interface list Complaint

Labels: Frontend, High

Checklist:

- Tampilkan tabel complaint
- Tampilkan status badge
- Tampilkan priority badge
- Tampilkan SLA
- Tampilkan pagination
- Tampilkan search
- Tampilkan sort

### Card: Implement interface tab dan filter Complaint

Labels: Frontend, High

Checklist:

- Tab berdasarkan `CS Name`
- Tab berdasarkan `Status`
- Tab berdasarkan `Angka`
- Tab berdasarkan `Priority`
- Filter berdasarkan `Brand`
- Filter berdasarkan `Priority`

### Card: Implement detail dan edit Complaint

Labels: Frontend, Backend, High

Checklist:

- Buka detail complaint
- Edit field yang diizinkan
- Simpan perubahan tanpa merusak field automation
- Tampilkan histori file upload bila ada

### Card: Implement hapus Complaint

Labels: Backend, Frontend, Medium

Checklist:

- Tambahkan confirm delete
- Pastikan hanya role tertentu yang bisa menghapus
- Pastikan file terkait ikut ditangani jika diperlukan

### Card: Testing modul Complaint

Labels: QA, High

Checklist:

- Test buka halaman complaint
- Test create complaint
- Test edit complaint
- Test delete complaint
- Test conditional fields
- Test automation status
- Test automation priority
- Test automation SLA
- Test filter dan search

## List: Sprint 2 - Bad Review

### Card: Implement form input Bad Review sesuai PDF

Labels: Frontend, Backend, High

Checklist:

- Field `tanggal review`
- Field `brand`
- Field `platform`
- Field `order id`
- Field `username`
- Field `star`
- Field `product name`
- Field `sku`
- Field `category review`
- Field `by`
- Field `review notes`
- Field `progress`
- Field `tanggal update`
- Field `cs name`
- Field `month`
- Field `status`

### Card: Implement automation Bad Review

Labels: Automation, Backend, High

Checklist:

- `By` mengikuti automation complaint
- `Progress = Follow Up Customer` -> `Pending`
- `Progress = Auto Reply` -> `Solved`
- `Month` diambil dari `tanggal review`

### Card: Implement list dan filter Bad Review

Labels: Frontend, Medium

Checklist:

- Tampilkan list data
- Filter berdasarkan `brand`
- Filter berdasarkan `platform`
- Filter berdasarkan `star`
- Filter berdasarkan `status`
- Filter berdasarkan `cs name`
- Filter berdasarkan `month`

### Card: Implement CRUD Bad Review

Labels: Backend, Frontend, High

Checklist:

- Create data
- Edit data
- Delete data
- Pagination
- Search dasar

### Card: Testing modul Bad Review

Labels: QA, Medium

Checklist:

- Test create
- Test edit
- Test delete
- Test status automation
- Test month automation

## List: Sprint 3 - Order Tracking

### Card: Implement form input Order Tracking sesuai PDF

Labels: Frontend, Backend, High

Checklist:

- Field `data source`
- Field `tanggal input`
- Field `tanggal order`
- Field `brand`
- Field `platform`
- Field `order id`
- Field `value`
- Field `logistics`
- Field `awb`
- Field `erp status`
- Field `payment method`
- Field `WH note`
- Field `cs name`
- Field `category`
- Field `last step`
- Field `update`
- Field `tanggal update`
- Field `value receive`
- Field `insurance info`
- Field `video unboxing`
- Field `BAP`
- Kolom update dari `WH`
- Kolom update dari `Finance`
- Field `status`
- Field `month`
- Field `automation track`
- Field `tanggal TTS`
- Field `reason whitelist`
- Field `reason late respons`

### Card: Implement validasi Order Tracking

Labels: Backend, High

Checklist:

- Validasi upload video
- Validasi upload image BAP
- Validasi conditional `reason whitelist`
- Validasi conditional `reason late respons`
- Validasi enum `payment method`
- Validasi enum `insurance info`

### Card: Implement automation Status pada Order Tracking

Labels: Automation, Backend, High

Checklist:

- Gunakan mapping status yang sama dengan Complaint
- Pastikan hasil `Pending`, `Solved`, `Whitelist`

### Card: Implement automation Month pada Order Tracking

Labels: Automation, Backend, Medium

Checklist:

- Ambil dari `tanggal input`
- Format `Month Year`

### Card: Implement automation Tanggal TTS untuk Lazada

Labels: Automation, Backend, Medium

Checklist:

- Jika platform Lazada, `tanggal TTS = tanggal order + 24 hari`
- Jika bukan Lazada, kosong

### Card: Implement automation Track MERGER ke Complaint

Labels: Automation, Backend, Integration, High

Checklist:

- Cari `order_id` di data Complaint
- Jika ditemukan, isi `MERGER`
- Jika tidak ditemukan, lanjut cek sumber lain

### Card: Implement integrasi Auto Track ke RGO

Labels: Integration, Backend, High, Blocked

Checklist:

- Cari `order_id` di list RGO
- Jika ditemukan, isi `Sudah diRGO`
- Tentukan fallback jika service gagal

### Card: Implement integrasi Auto Track ke Jet Track

Labels: Integration, Backend, High, Blocked

Checklist:

- Cari `awb` ke aplikasi Jet Track
- Jika ditemukan, isi label `ADA DI JET TRACK`
- Ambil dan isi otomatis `kondisi barang` jika diperlukan
- Tentukan timeout dan retry policy

### Card: Implement list dan filter Order Tracking

Labels: Frontend, High

Checklist:

- Tampilkan tabel tracking
- Filter berdasarkan `brand`
- Filter berdasarkan `platform`
- Filter berdasarkan `logistics`
- Filter berdasarkan `order date`
- Filter berdasarkan `data source`
- Filter berdasarkan `auto track`
- Filter berdasarkan `status`

### Card: Implement CRUD Order Tracking

Labels: Backend, Frontend, High

Checklist:

- Create data
- Edit data
- Delete data
- Search dasar
- Pagination

### Card: Testing modul Order Tracking

Labels: QA, High

Checklist:

- Test create
- Test edit
- Test delete
- Test upload video
- Test upload BAP
- Test status automation
- Test TTS Lazada
- Test MERGER dari Complaint

## List: Sprint 4 - OOS

### Card: Implement form input OOS sesuai PDF

Labels: Frontend, Backend, High

Checklist:

- Field `tanggal input`
- Field `brand`
- Field `platform`
- Field `no order`
- Field `product name`
- Field `sku`
- Field `reason`
- Field `solusi`
- Field `note/detail varian`
- Field `update CS`
- Field `tanggal blast`
- Field `feedback customers`
- Field `month`

### Card: Implement automation Month pada OOS

Labels: Automation, Backend, Medium

Checklist:

- Ambil dari `tanggal input`
- Format `Month Year`

### Card: Implement list dan filter OOS

Labels: Frontend, Medium

Checklist:

- Tampilkan tabel OOS
- Filter berdasarkan `brand`
- Filter berdasarkan `platform`
- Filter berdasarkan `reason`
- Filter berdasarkan `update CS`
- Filter berdasarkan `month`

### Card: Implement CRUD OOS

Labels: Backend, Frontend, High

Checklist:

- Create data
- Edit data
- Delete data
- Pagination
- Search dasar

### Card: Integrasikan OOS ke Complaint

Labels: Integration, Backend, Medium

Checklist:

- Gunakan `order_id`/`no order` sebagai penghubung
- Tampilkan riwayat OOS pada Complaint
- Pastikan nama field konsisten

### Card: Testing modul OOS

Labels: QA, Medium

Checklist:

- Test create
- Test edit
- Test delete
- Test month automation
- Test integrasi riwayat OOS ke Complaint

## List: Sprint 5 - Dashboard

### Card: Implement kartu summary realtime Dashboard

Labels: Dashboard, Backend, Frontend, High

Checklist:

- Total pending Complaint berjalan
- Total pending Order Tracking berjalan
- OOS today
- Total gabungan ketiganya

### Card: Implement grafik weekly Complaint

Labels: Dashboard, High

Checklist:

- Incoming new complaint per week
- Solved complaint per week
- Tampilkan range tanggal yang jelas

### Card: Implement grafik weekly Bad Review

Labels: Dashboard, Medium

Checklist:

- Incoming bad review per week
- Filter bulan berjalan bila diperlukan

### Card: Implement grafik weekly OOS

Labels: Dashboard, Medium

Checklist:

- Incoming OOS per week
- Tampilkan tren mingguan

### Card: Implement widget Pending Complaint per By

Labels: Dashboard, High

Checklist:

- Agregasi pending complaint per `By`
- Tampilkan chart atau table

### Card: Implement widget Pending Complaint per Level Customer

Labels: Dashboard, Medium

Checklist:

- Agregasi pending complaint per `level customer`

### Card: Implement widget Pending Complaint per Sub Case dan Category SLA

Labels: Dashboard, High

Checklist:

- Agregasi per `sub_case`
- Agregasi per kategori SLA
- Definisikan kategori SLA untuk dashboard

### Card: Implement widget Pending Complaint Last Step External

Labels: Dashboard, High

Checklist:

- Analysis MP Late Delivery
- Analysis MP Non Late Delivery
- FU Courier MP Non aktif
- On the way return and plan banding
- On the way return and plan refund
- On the way return and plan replace
- Pending return and plan banding
- Pending return and plan refund
- Pending return and plan replace
- Pending RGO and plan refund
- Waiting Claim
- Waiting Data From Customer

### Card: Implement widget Pending Complaint Last Step Internal

Labels: Dashboard, High

Checklist:

- FU After Sales Team
- FU KAE/Brand
- Kingdee Processing
- FU WH
- Replacement product on the way

### Card: Implement widget Bad Review bulan berjalan per Brand

Labels: Dashboard, Medium

Checklist:

- Hitung per bulan berjalan
- Breakdown per brand

### Card: Implement widget Bad Review bulan berjalan per Category

Labels: Dashboard, Medium

Checklist:

- Hitung per bulan berjalan
- Breakdown per category

### Card: Implement widget Pending Order Tracking per Brand

Labels: Dashboard, Medium

Checklist:

- Agregasi pending tracking per brand

### Card: Implement widget Pending Order Tracking per Platform

Labels: Dashboard, Medium

Checklist:

- Agregasi pending tracking per platform

### Card: Implement widget Pending Order Tracking per Logistics

Labels: Dashboard, Medium

Checklist:

- Agregasi pending tracking per logistics

### Card: Implement widget Pending Order Tracking per Order Date

Labels: Dashboard, Medium

Checklist:

- Agregasi pending tracking per tanggal order

### Card: Implement widget Pending Order Tracking per Auto Track

Labels: Dashboard, Medium

Checklist:

- Agregasi pending tracking per `MERGER`, `Sudah diRGO`, `ADA DI JET TRACK`, kosong

### Card: Implement widget Pending Order Tracking per Data Source

Labels: Dashboard, Medium

Checklist:

- Agregasi pending tracking per `WH`, `Finance`, `Reject Return`

### Card: Implement widget OOS D-Days dan D-1 yang belum Done Blast

Labels: Dashboard, High

Checklist:

- Hitung OOS masuk D-Days
- Hitung OOS masuk D-1
- Filter yang `update CS != Done Blast`

### Card: Implement widget OOS bulan berjalan per Brand

Labels: Dashboard, Medium

Checklist:

- Hitung OOS bulan berjalan
- Breakdown per brand

## List: Sprint 6 - Agent Productivity

### Card: Implement dashboard distribusi task per agent realtime

Labels: Dashboard, High

Checklist:

- Total Complaint didistribusi ke agent per hari
- Total Bad Review didistribusi ke agent per hari
- Total Order Tracking didistribusi ke agent per hari
- Total OOS didistribusi ke agent per hari

### Card: Implement dashboard task handled per agent realtime

Labels: Dashboard, High

Checklist:

- Total Complaint dikerjakan per agent
- Total Bad Review dikerjakan per agent
- Total Order Tracking dikerjakan per agent
- Total OOS dikerjakan per agent

### Card: Implement dashboard solved per agent realtime

Labels: Dashboard, High

Checklist:

- Total Complaint solved per agent
- Total Bad Review solved per agent
- Total Order Tracking solved per agent

### Card: Implement rekap productivity harian per agent

Labels: Dashboard, Backend, High

Checklist:

- Total task distribusi per agent
- Total task handled per agent
- Total solved per agent
- Total productivity harian

### Card: Implement form input productivity harian agent end of duty

Labels: Frontend, Backend, High

Checklist:

- Form input end of duty
- Simpan per tanggal
- Simpan per agent
- Tampilkan recap harian

### Card: Implement interface agent summary

Labels: Frontend, Dashboard, Medium

Checklist:

- Tampilkan total solved harian
- Tampilkan pending
- Tampilkan whitelist
- Tampilkan handling berdasarkan priority

## List: QA / UAT

### Card: Susun test scenario per modul

Labels: QA, High

Checklist:

- Scenario Complaint
- Scenario Bad Review
- Scenario Order Tracking
- Scenario OOS
- Scenario Dashboard
- Scenario Agent Productivity

### Card: Tambahkan automated feature test untuk modul utama

Labels: QA, Backend, High

Checklist:

- Test auth dan permission
- Test Complaint
- Test Bad Review
- Test Order Tracking
- Test OOS
- Test dashboard endpoint

### Card: Lakukan UAT dengan user bisnis

Labels: QA, High

Checklist:

- UAT CS
- UAT Finance
- UAT WH
- UAT KAE
- UAT After Sales
- Catat bug dan gap requirement

### Card: Lakukan performance review query dashboard

Labels: Backend, Dashboard, Medium

Checklist:

- Review query realtime
- Tambahkan index jika perlu
- Cek kemungkinan caching untuk widget berat

## List: Ready for Release

### Card: Final regression test

Labels: QA, High

Checklist:

- Regression auth
- Regression complaint
- Regression bad review
- Regression order tracking
- Regression OOS
- Regression dashboard

### Card: Siapkan dokumentasi operasional

Labels: Medium

Checklist:

- Dokumentasi setup
- Dokumentasi role
- Dokumentasi automation
- Dokumentasi integrasi eksternal
- Dokumentasi SOP input per modul

### Card: Deployment checklist

Labels: Backend, High

Checklist:

- Cek `.env`
- Cek database migration
- Cek storage link
- Cek file permission
- Cek scheduler/queue jika diperlukan
- Smoke test production/staging
