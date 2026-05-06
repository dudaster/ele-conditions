# Extindere — variabile custom

## Adăugarea de variabile custom prin filtru PHP

Orice variabilă poate fi adăugată în `functions.php` sau într-un plugin separat prin filtrul `eleconditions_vars`.

### Sintaxă de bază

```php
add_filter( 'eleconditions_vars', function( array $vars ): array {
    $vars['my_variable'] = 'my_value';
    return $vars;
} );
```

Variabila `my_variable` devine disponibilă imediat în câmpul **"Type manually…"** din UI.

---

## Exemple practice

### Variabilă statică

```php
add_filter( 'eleconditions_vars', function( $vars ) {
    $vars['site_version'] = get_option( 'my_plugin_version', '1.0' );
    return $vars;
} );
```

Condiție în UI: `site_version == 2.0`

---

### Variabilă din query string custom

```php
add_filter( 'eleconditions_vars', function( $vars ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $vars['ref'] = sanitize_text_field( $_GET['ref'] ?? '' );
    return $vars;
} );
```

Condiție în UI: `ref == partner123`

---

### Variabilă din cookie

```php
add_filter( 'eleconditions_vars', function( $vars ) {
    $vars['has_promo'] = isset( $_COOKIE['promo_accepted'] ) ? 'true' : 'false';
    return $vars;
} );
```

Condiție în UI: `has_promo == true`

---

### Variabilă din opțiune WooCommerce

```php
add_filter( 'eleconditions_vars', function( $vars ) {
    if ( function_exists( 'WC' ) && is_user_logged_in() ) {
        $customer = new WC_Customer( get_current_user_id() );
        $vars['wc_orders_count'] = $customer->get_order_count();
        $vars['wc_total_spent']  = (float) $customer->get_total_spent();
    }
    return $vars;
} );
```

Condiție în UI: `wc_orders_count >= 3` sau `wc_total_spent > 500`

---

### Variabilă cu valoare boolean (pattern corect)

Variabilele boolean trebuie returnate ca string `'true'`/`'false'` pentru compatibilitate cu UI-ul.

```php
add_filter( 'eleconditions_vars', function( $vars ) {
    $vars['is_vip'] = get_user_meta( get_current_user_id(), 'is_vip', true ) ? 'true' : 'false';
    return $vars;
} );
```

Condiție în UI: `is_vip == true`

---

### Variabilă numerică

```php
add_filter( 'eleconditions_vars', function( $vars ) {
    $vars['post_views'] = (int) get_post_meta( get_the_ID(), 'post_views_count', true );
    return $vars;
} );
```

Condiție în UI: `post_views > 1000`

---

## Tipuri de date și operatori recomandați

| Tip variabilă | Valori exemple | Operatori recomandați |
|---|---|---|
| Numeric | `42`, `3.14`, `0` | `==`, `!=`, `>`, `<`, `>=`, `<=` |
| String exact | `'publish'`, `'administrator'` | `==`, `!=`, `===`, `!==` |
| Boolean | `'true'`, `'false'` | `== true`, `== false` |
| Gol/prezent | `''`, `'any_value'` | `== ''` (gol), `!= ''` (prezent) |

---

## Prioritatea lookup-ului de variabile

Când se evaluează o variabilă, ordinea de rezolvare este:

1. **Filtrul `eleconditions_vars`** ← variabilele tale custom au prioritate maximă
2. Proprietăți ale obiectului WP curent (post, term)
3. Post meta (`get_post_meta`)
4. Câmpuri ACF (`get_field`)
5. Query vars WordPress
6. User meta cu prefix `um_`

Deci dacă definești `$vars['post_status'] = 'custom_value'`, aceasta suprascrie valoarea din `$post->post_status`.

---

## Debugging variabile custom

Activează **Debug mode** în secțiunea "Ele Conditions" a elementului din Elementor. Fiecare condiție va afișa un bloc colorat cu:

- Valoarea brută a variabilei (roz dacă nu a fost rezolvată, verde dacă a fost).
- Valoarea comparată.
- Operatorul.
- Rezultatul (`true`/`false`).

Vizibil doar pentru utilizatorii cu rol **Editor** sau **Administrator**.
