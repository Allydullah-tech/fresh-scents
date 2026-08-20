# FRESH SCENTS — Mfumo Kamili wa Kuuzia Manukato
**Harufu Nzuri, Hadhi Ya Kifalme**

Mfumo huu una sehemu tatu kuu:
- `frontend/` — Tovuti ya wateja (HTML, CSS, JS)
- `backend/` — PHP (API, Admin Panel, Usajili/Login)
- `database/` — Muundo wa Database (MySQL)

---

## 1. MAHITAJI
- PHP 8.0 au zaidi (na PDO MySQL extension)
- MySQL / MariaDB
- Apache/Nginx (au XAMPP/WAMP/Laragon kwa kompyuta yako)

## 2. JINSI YA KUSAKINISHA (INSTALLATION)

### Hatua 1 — Weka Faili
Weka folda nzima ya `fresh-scents` (frontend, backend, database) ndani ya root ya server yako, mfano:
`htdocs/fresh-scents/` (XAMPP) au `www/fresh-scents/` (WAMP/Laragon).

### Hatua 2 — Tengeneza Database
1. Fungua phpMyAdmin (au mysql CLI).
2. Fungua faili `database/schema.sql` na uende "Import" — hii itatengeneza database `fresh_scents_db` pamoja na majedwali yote na bidhaa za mfano.

### Hatua 3 — Unganisha Database
Fungua `backend/config/db.php` na weka taarifa sahihi za database yako:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fresh_scents_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Hatua 4 — Sakinisha Akaunti ya Kwanza ya Admin
Fungua kwenye browser:
```
http://localhost/fresh-scents/backend/install.php
```
Jaza taarifa za duka na akaunti ya Msimamizi Mkuu (Super Admin) ikiwemo swali la usalama. Hii ndiyo njia pekee ya kuunda admin wa kwanza — hakuna akaunti ya default.

### Hatua 5 — Fungua Tovuti
- **Tovuti ya Wateja:** `http://localhost/fresh-scents/frontend/index.html`
- **Admin Panel:** `http://localhost/fresh-scents/backend/admin/login.php`

---

## 3. VIPENGELE VYA MFUMO

### Kwa Wateja (Frontend)
- Ukurasa mkuu wa kuvutia wenye alama (logo) ya FRESH SCENTS
- Kuangalia manukato yote, kutafuta na kuchuja kwa aina
- Ukurasa wa bidhaa wenye maelezo, ML na bei
- Kikapu cha manunuzi (cart) kinachohifadhiwa kwenye kifaa
- Kuagiza bila akaunti (Guest Checkout) au kwa akaunti
- Kuchagua: Kuchukua Dukani AU Delivery (delivery inahitaji malipo)
- Ujumbe wa maelekezo ya malipo unapoonekana papo hapo baada ya kuagiza delivery
- Kutuma taarifa za malipo (jina + namba ya muamala) wakati wowote
- Kufuatilia oda kwa Namba ya Oda AU Namba ya Simu — bila akaunti
- Akaunti ya mteja: kubadilisha password, swali la usalama, kuona oda zote
- Kurejesha password kwa swali la usalama (Umesahau Password)
- Mfumo mzima kwa Kiswahili

### Kwa Admin (Backend Panel)
- Dashibodi yenye takwimu za haraka (mauzo, faida, maombi yanayosubiri, stock kidogo)
- Kuongeza/kuhariri/kufuta bidhaa (jina, ML, bei, picha, stock)
- Kusimamia Maombi/Oda za Wateja — kuthibitisha malipo, kusasisha hali
- Kuweka alama "Imekamilika" — mteja anapata ujumbe sahihi kutegemea Delivery au Dukani
- Kusimamia Stock Zinazoingia
- Ripoti ya Mauzo (kwa kipindi chochote)
- Mapato na Matumizi (kuongeza gharama, kuona faida halisi)
- Kusimamia Wafanyakazi (majina, nafasi, mshahara)
- Kurekodi Malipo ya Mshahara kwa kila mfanyakazi
- Kuongeza Wasimamizi (Admins) wengine — Msimamizi Mkuu tu
- Mipangilio ya Duka (jina, namba ya malipo, anuani)
- Wasifu binafsi — kubadilisha password na swali la usalama

---

## 4. UJUMBE WA MALIPO
Ujumbe huu unaonekana kiotomatiki mteja anapochagua "Delivery":

> *"Oda yako imepokelewa. Lipia kulipia namba hii [namba], jina [jina], ukishalipia tuma jina na namba ya muamala kwa uthibitisho."*

Namba na jina hivi vinabadilishwa kwenye **Admin Panel → Mipangilio ya Duka**.

---

## 5. USALAMA
- Password zote zinahifadhiwa kwa usimbaji (bcrypt hashing) — hazionekani wazi kamwe.
- Kila mtumiaji (Admin na Mteja) ana Swali la Usalama la kurejesha password.
- `install.php` inafanya kazi mara moja tu — baada ya admin wa kwanza kuundwa, huwezi kuitumia tena.

---

## 6. MSAADA
Endapo utapata hitilafu ya "Imeshindwa kuunganisha na database", hakikisha:
1. MySQL inaendesha (running)
2. Umeingiza `database/schema.sql`
3. Taarifa za `backend/config/db.php` ni sahihi

Kwa maswali zaidi, wasiliana na muundaji wa mfumo wako.

---
© FRESH SCENTS — Harufu Nzuri, Hadhi Ya Kifalme
