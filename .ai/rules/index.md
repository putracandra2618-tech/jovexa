# Peta Rules Jovexa

Catatan bersama untuk area-area project ini. Baca file yang glob-nya cocok dengan
path yang sedang kamu ubah **sebelum** menulis kode.

| File rule | Covers |
| --- | --- |
| [services.md](services.md) | `app/Services/**` — alur status pesanan dan orkestrasi pembayaran |
| [routes.md](routes.md) | `routes/**` — schedule, dan throttle pada endpoint gateway |
| [css.md](css.md) | `resources/css/app.css` — tema playful vs Spark admin |

## Temuan yang belum diperbaiki

[open-risks.md](open-risks.md) berisi temuan audit yang **belum** menjadi aturan
aktif, beserta keputusan desain yang sudah diambil dan satu hal yang tidak bisa
dikerjakan lewat kode.

Itu backlog pekerjaan, bukan constraint yang sudah berlaku. Jangan
memandangnya sebagai aturan yang wajib dipatuhi, tapi jangan juga mengabaikannya
saat menyentuh area terkait.

## Cara memperbarui

Tambahkan aturan lewat alat `record-rule` dengan glob path yang relevan. Alat ini
membuat file area baru bila belum ada, atau menambahkan bagian ke file yang
path-nya sudah tercakup di frontmatter `paths`. Jangan menulis file area secara
manual — supaya frontmatter `paths` tetap konsisten.