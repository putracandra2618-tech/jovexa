# Electron.js — Penjelasan Simpel untuk Pemula

## Apa itu Electron?

Bayangkan **website** (HTML, CSS, JavaScript) yang biasanya dibuka di browser.
Electron adalah alat yang membuat website itu bisa **dibuka sebagai aplikasi desktop**, lengkap dengan ikon, jendela sendiri, dan file `.exe` — tanpa perlu dibuka lewat Chrome.

Analogi: website = isi makanan, browser = kotak makan. Electron = kotak makan yang bisa kamu beri label dan jual sebagai produk sendiri.

Contoh aplikasi yang dibuat dengan Electron: **VS Code, Discord, Slack, WhatsApp Desktop**.

## Tiga istilah yang perlu kamu tahu

| Istilah | Istilah mudahnya | Tugasnya |
|---|---|---|
| **Main** (`main.js`) | Manajer | Membuka jendela aplikasi, mengatur menu, menutup aplikasi |
| **Renderer** (`index.html`) | Tampilan | Isi layar yang dilihat pengguna (HTML/CSS/JS) |
| **Preload** (`preload.js`) | Satpam | Penghubung aman antara tampilan dan komputer |

Untuk latihan pertama, kita cukup pakai **main** dan **renderer** dulu.

## Yang perlu disiapkan

1. **Node.js** (sudah terpasang di komputer ini)
2. Editor kode (VS Code)
3. Terminal / Command Prompt

Cek dulu apakah Node sudah ada:

```bash
node -v
npm -v
```

Kalau muncul nomor versi, berarti siap.

## Langkah membuat aplikasi pertama

### Langkah 1 — Buat folder project

```bash
mkdir aplikasi-pertama
cd aplikasi-pertama
npm init -y
```

`npm init -y` membuat file `package.json` (semacam KTP project).

### Langkah 2 — Install Electron

```bash
npm install --save-dev electron
```

Tunggu sampai selesai. Ini mengunduh Electron ke folder `node_modules`.

### Langkah 3 — Buat file `main.js`

Buat file baru bernama `main.js`, isinya:

```js
const { app, BrowserWindow } = require('electron');

function bukaJendela() {
  const jendela = new BrowserWindow({
    width: 900,
    height: 600,
  });

  jendela.loadFile('index.html');
}

app.whenReady().then(bukaJendela);

app.on('window-all-closed', () => {
  app.quit();
});
```

Penjelasan singkat:
- `BrowserWindow` = jendela aplikasi.
- `loadFile('index.html')` = isi jendela diambil dari file HTML kita.
- `app.whenReady()` = tunggu aplikasi siap, baru buka jendela.
- `window-all-closed` = kalau semua jendela ditutup, aplikasi berhenti.

### Langkah 4 — Buat file `index.html`

```html
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Aplikasi Pertama</title>
  </head>
  <body>
    <h1>Halo, ini aplikasi desktop pertamaku!</h1>
    <p>Dibuat dengan Electron.</p>
    <button onclick="alert('Berhasil!')">Klik Saya</button>
  </body>
</html>
```

### Langkah 5 — Atur `package.json`

Buka `package.json`, tambahkan dua baris ini:

```json
{
  "main": "main.js",
  "scripts": {
    "start": "electron ."
  }
}
```

- `main` memberi tahu Electron file mana yang dijalankan pertama.
- `start` adalah perintah untuk menjalankan aplikasi.

### Langkah 6 — Jalankan

```bash
npm start
```

Selesai! Jendela aplikasi desktop akan muncul berisi halaman HTML tadi.

## Cara mengubah isinya

- Ubah tulisan atau tombol di `index.html` → tampilan aplikasi ikut berubah.
- Tambah CSS atau JavaScript di `index.html` seperti membuat website biasa.
- Simpan file, lalu tutup jendela dan jalankan `npm start` lagi.

## Kalau ingin menampilkan website Laravel (jovexa)

Ganti baris ini di `main.js`:

```js
jendela.loadFile('index.html');
```

menjadi:

```js
jendela.loadURL('http://localhost:8000');
```

Penting: Laravel-nya harus tetap berjalan dulu (`php artisan serve` atau lewat Laragon).
Electron hanya jadi "jendela desktop", bukan pengganti server Laravel.

## Membuat file .exe (opsional, nanti saja)

Kalau aplikasinya sudah jadi dan ingin dibagikan:

```bash
npm install --save-dev electron-builder
npx electron-builder
```

Hasilnya ada di folder `dist`. Untuk belajar, langkah ini bisa dilewati dulu.

## Tips keamanan (cukup diingat)

- Jangan biarkan halaman web mengakses file komputer secara bebas.
- Kalau nanti butuh fitur seperti simpan file, gunakan `preload.js` sebagai perantara.
- Hanya jalankan aplikasi dari sumber yang kamu percaya.

## Ringkasan

```
1. npm init -y
2. npm install --save-dev electron
3. Buat main.js   -> mengatur jendela
4. Buat index.html -> isi tampilan
5. Atur package.json ("main" dan "start")
6. npm start
```

Intinya: **Electron = cara membungkus website menjadi aplikasi desktop.**
