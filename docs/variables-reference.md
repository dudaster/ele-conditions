# Referință variabile built-in

Toate variabilele sunt disponibile în câmpul **Variable** din condiția de tip "Variable comparison".

> **Cum funcționează**: variabilele sunt evaluate server-side la fiecare render de pagină, în timezone-ul WordPress.

---

## Data / Oră

| Variabilă | Tip | Exemplu valoare | Descriere |
|---|---|---|---|
| `now` | `string` | `'2026-05-06 14:30:00'` | Data și ora curentă în format `Y-m-d H:i:s` |
| `current_date` | `string` | `'2026-05-06'` | Data curentă în format `Y-m-d` |
| `current_hour` | `int` | `14` | Ora curentă, 0–23 (format 24h) |
| `current_day` | `int` | `3` | Ziua săptămânii: 1=Luni … 7=Duminică (ISO 8601) |
| `current_month` | `int` | `5` | Luna curentă: 1–12 |
| `current_year` | `int` | `2026` | Anul curent |

**Exemple**

```
current_day == 6          → Sâmbătă (ziua 6)
current_hour >= 9         → de la ora 9 dimineața
current_month == 12       → luna decembrie
now >= 2026-06-01 00:00:00 → din 1 iunie 2026
```

---

## Post

| Variabilă | Tip | Exemplu valoare | Descriere |
|---|---|---|---|
| `ID` | `int` | `42` | ID-ul postului curent |
| `post_status` | `string` | `'publish'` | Statusul postului |
| `post_type` | `string` | `'post'` | Tipul postului |
| `post_author` | `string` | `'admin'` | Username-ul autorului |
| `post_author_id` | `int` | `1` | ID-ul autorului |
| `comment_count` | `int` | `7` | Numărul de comentarii |
| `content_length` | `int` | `1850` | Lungimea conținutului în caractere (fără HTML) |
| `excerpt_length` | `int` | `320` | Lungimea excerpt-ului în caractere (fără HTML) |
| `post_age_days` | `int` | `45` | Numărul de zile de la publicare |
| `post_has_thumbnail` | `string` | `'true'` | `'true'` sau `'false'` — are imagine featured? |
| `post_word_count` | `int` | `720` | Numărul de cuvinte din conținut |

**Notă `post_has_thumbnail`**: returnează string `'true'`/`'false'`, nu bool PHP. Compară cu `== true` sau `== false`.

**Notă `post_word_count`**: folosește `str_word_count()` — funcționează corect pentru limbi cu spații (română, engleză, etc.). Nu este precis pentru limbile fără spații (chineză, japoneză, etc.).

**Exemple**

```
post_status == publish         → numai posturile publicate
post_age_days > 30             → postul are peste 30 de zile
post_has_thumbnail == true     → are imagine featured
post_word_count >= 500         → articol lung
content_length >= 1000         → minim 1000 de caractere
```

---

## Utilizator

| Variabilă | Tip | Exemplu valoare | Descriere |
|---|---|---|---|
| `user_id` | `int` | `5` | ID-ul utilizatorului curent (0 = nelogat) |
| `user_role` | `string` | `'subscriber'` | Primul rol al utilizatorului curent |
| `is_logged_in` | `string` | `'true'` | `'true'` sau `'false'` |

**Notă `user_role`**: returnează primul rol din array-ul de roluri. Un utilizator cu mai multe roluri — se ia primul.

**Exemple**

```
is_logged_in == true           → utilizator autentificat
user_role == administrator     → doar admini
user_role == subscriber        → abonați
user_id == 0                   → vizitatori nelogați
```

---

## User meta (câmpuri profil utilizator)

Folosind preset-ul **"User meta field…"** din UI, poți accesa orice câmp de user meta al utilizatorului curent.

**Intern**, câmpul `my_field` devine variabila `um_my_field`, rezolvată prin:

```php
get_user_meta( $current_user_id, 'my_field', true )
```

**Exemple UI** — preset: User meta field, meta key: `city`

```
👤 city == București           → utilizatorul are city = București
👤 subscription_plan == pro    → plan Pro activ
👤 age >= 18                   → utilizator adult (dacă câmpul e numeric)
```

**Compatibil cu** orice plugin care stochează date în `wp_usermeta`: Ultimate Member, WooCommerce Customer, Profile Builder, etc.

---

## WooCommerce

Disponibile numai dacă WooCommerce este activ și coșul este inițializat.

| Variabilă | Tip | Exemplu valoare | Descriere |
|---|---|---|---|
| `cart_count` | `int` | `3` | Numărul de produse din coș |
| `cart_total` | `float` | `149.90` | Subtotalul coșului (fără shipping/tax) |

**Exemple**

```
cart_count >= 1                → coș ne-gol
cart_total > 200               → comandă mare (pentru upsell)
cart_count == 0                → coș gol (afișează bannere promo)
```

---

## UTM / Query string

Valorile sunt preluate din URL-ul curent (`$_GET`), sanitizate cu `sanitize_text_field()`.

| Variabilă | Exemplu URL | Exemplu valoare |
|---|---|---|
| `utm_source` | `?utm_source=newsletter` | `'newsletter'` |
| `utm_medium` | `?utm_medium=email` | `'email'` |
| `utm_campaign` | `?utm_campaign=black_friday` | `'black_friday'` |
| `utm_content` | `?utm_content=banner_top` | `'banner_top'` |
| `utm_term` | `?utm_term=wordpress+plugin` | `'wordpress plugin'` |

**Notă**: valorile sunt goale (`''`) dacă parametrul UTM nu este prezent în URL.

**Exemple**

```
utm_source == newsletter       → trafic din newsletter
utm_campaign == black_friday   → campanie Black Friday activă
utm_medium == email            → canal email
```

---

## Câmpuri ACF / Post meta

Selectabile din dropdown-ul **"ACF / Meta field (dropdown)…"** din UI.

- **ACF fields**: enumerate automat din toate field groups ACF.
- **Post meta**: enumerate automat din `wp_postmeta` (primele 300 chei, excluse cheile interne `_*`).
- **Manual**: opțiunea `— type manually —` din dropdown permite introducerea unui câmp care nu apare în listă.

Rezolvare prin `get_post_meta( $post_id, $field_key, true )` sau `get_field( $field_key, $post_id )` (ACF).

---

## Variabile generice (proprietăți obiect)

Accesibile prin preset-ul **"Type manually…"** sau prin SELECT dacă sunt listate:

| Variabilă | Descriere |
|---|---|
| `name` | Proprietatea `name` a obiectului curent (ex. categorie) |
| `post_excerpt` | Excerpt-ul postului |
| `description` | Descrierea termenului (categorie, tag) |
| `permalink` | URL-ul permanent al postului |
| `content` | `true` pe pagini singulare cu conținut |

---

## Tabel complet

| Variabilă | Categorie | Tip | Disponibilă |
|---|---|---|---|
| `now` | Dată/Oră | string | mereu |
| `current_date` | Dată/Oră | string | mereu |
| `current_hour` | Dată/Oră | int | mereu |
| `current_day` | Dată/Oră | int | mereu |
| `current_month` | Dată/Oră | int | mereu |
| `current_year` | Dată/Oră | int | mereu |
| `ID` | Post | int | pe pagini cu post |
| `post_status` | Post | string | pe pagini cu post |
| `post_type` | Post | string | pe pagini cu post |
| `post_author` | Post | string | pe pagini cu post |
| `post_author_id` | Post | int | pe pagini cu post |
| `comment_count` | Post | int | pe pagini cu post |
| `content_length` | Post | int | pe pagini cu post |
| `excerpt_length` | Post | int | pe pagini cu post |
| `post_age_days` | Post | int | pe pagini cu post |
| `post_has_thumbnail` | Post | string | pe pagini cu post |
| `post_word_count` | Post | int | pe pagini cu post |
| `user_id` | User | int | mereu |
| `user_role` | User | string | mereu |
| `is_logged_in` | User | string | mereu |
| `cart_count` | WooCommerce | int | dacă WC activ |
| `cart_total` | WooCommerce | float | dacă WC activ |
| `utm_source` | UTM | string | mereu (gol dacă absent) |
| `utm_medium` | UTM | string | mereu (gol dacă absent) |
| `utm_campaign` | UTM | string | mereu (gol dacă absent) |
| `utm_content` | UTM | string | mereu (gol dacă absent) |
| `utm_term` | UTM | string | mereu (gol dacă absent) |
