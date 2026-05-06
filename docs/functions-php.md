# Funcții PHP

## Cuprins

1. [ele-conditions.php](#ele-conditionsphp)
2. [inc/parse_conditions.php — evaluare](#incparse_conditionsphp--evaluare)
3. [inc/parse_conditions.php — lookup valori](#incparse_conditionsphp--lookup-valori)
4. [inc/parse_conditions.php — debug](#incparse_conditionsphp--debug)
5. [inc/controls.php — hooks Elementor](#inccontrolsphp--hooks-elementor)
6. [inc/controls-triggers.php — hooks Elementor](#inccontrols-triggersphp--hooks-elementor)
7. [inc/control-datetime.php — control custom](#inccontrol-datetimephp--control-custom)

---

## ele-conditions.php

### `elecond_keywords( array $custom_vars ): array`

Callback pentru filtrul `eleconditions_vars`. Adaugă toate variabilele built-in în array-ul de variabile disponibile.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `$custom_vars` | `array` | Array-ul de variabile existent, transmis de filtru |

**Returnează** `array` — array-ul completat cu variabilele built-in.

**Variabile adăugate** — vezi [variables-reference.md](variables-reference.md) pentru lista completă.

**Exemplu de utilizare indirectă**

```php
// Adaugă propria variabilă prin același filtru
add_filter( 'eleconditions_vars', function( $vars ) {
    $vars['my_custom_var'] = get_option( 'my_option' );
    return $vars;
} );
```

---

## inc/parse_conditions.php — evaluare

### `elecond_evaluate_group( array $conditions, bool $debug = false ): bool`

Evaluează un grup de condiții din REPEATER-ul Elementor. Aplică logica AND/OR între rânduri.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `$conditions` | `array` | Array de rânduri din REPEATER (fiecare rând = o condiție) |
| `$debug` | `bool` | Dacă `true`, afișează output debug pentru admini/editori |

**Returnează** `bool` — `true` dacă elementul trebuie afișat, `false` dacă trebuie ascuns.

**Comportament**

- Dacă `$conditions` este gol → returnează `true` (nimic nu blochează afișarea).
- Dacă o condiție are variabilă goală → este sărită (echivalentă cu absență).
- Dacă toate condițiile sunt sărite → returnează `true`.
- Logica AND/OR este citită din câmpul `cond_logic` al fiecărui rând (se aplică la rândul *următor*).

**Tipuri de condiții suportate** (`cond_type`)

| Valoare | Handler |
|---|---|
| `simple` | `elecond_parse_condition()` |
| `time_interval` | `elecond_check_time_interval()` |
| `date_interval` | `elecond_check_date_interval()` |

**Exemplu**

```php
$conditions = $widget->get_active_settings()['conditions_list'];
$should_show = elecond_evaluate_group( $conditions );
```

---

### `elecond_check_time_interval( string $from, string $to ): bool`

Verifică dacă ora curentă (timezone WordPress) se află în intervalul `[from, to]`.

**Parametri**

| Parametru | Tip | Format | Exemplu |
|---|---|---|---|
| `$from` | `string` | `HH:MM` | `'09:00'` |
| `$to` | `string` | `HH:MM` | `'17:00'` |

**Returnează** `bool`

**Comportament special**

- Dacă oricare dintre parametri este șir gol → returnează `true` (fără restricție).
- **Cross-midnight**: dacă `$from > $to` (ex. `22:00–06:00`), funcția detectează automat și verifică `current >= from OR current <= to`.

**Exemple**

```php
elecond_check_time_interval( '09:00', '17:00' ); // normal: ore de birou
elecond_check_time_interval( '22:00', '06:00' ); // cross-midnight: noaptea
elecond_check_time_interval( '', '' );            // → true (fără restricție)
```

---

### `elecond_check_date_interval( string $from, string $to ): bool`

Verifică dacă momentul curent (timezone WordPress) se află în intervalul de date `[from, to]`. Ambele capete sunt **inclusive**.

**Parametri**

| Parametru | Tip | Formate acceptate | Exemplu |
|---|---|---|---|
| `$from` | `string` | `YYYY-MM-DD` sau `YYYY-MM-DDTHH:MM` | `'2026-01-15T09:00'` |
| `$to` | `string` | `YYYY-MM-DD` sau `YYYY-MM-DDTHH:MM` | `'2026-03-31'` |

**Returnează** `bool`

**Comportament special**

- Dacă ambii parametri sunt gol → returnează `true`.
- Separatorul `T` din formatul `datetime-local` HTML este normalizat automat la spațiu.
- `$to` specificat ca dată fără oră → implicit `23:59:59` (capăt inclusiv la final de zi).
- `$from` specificat ca dată fără oră → implicit `00:00:00`.

**Exemple**

```php
elecond_check_date_interval( '2026-06-01', '2026-08-31' );        // vară 2026
elecond_check_date_interval( '2026-12-24T00:00', '2026-12-26' );  // Crăciun
elecond_check_date_interval( '2026-01-01', '' );                   // din 2026 în continuare
```

---

### `elecond_parse_condition( string $condition, bool $debug = false ): bool`

Parsează și evaluează o expresie de condiție în format string.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `$condition` | `string` | Expresie de forma `variabila==valoare` |
| `$debug` | `bool` | Dacă `true`, afișează debug HTML (doar pentru admini/editori) |

**Returnează** `bool`

**Operatori suportați** (în ordinea de prioritate la parsing)

| Operator | Semnificație |
|---|---|
| `>` | mai mare |
| `<` | mai mic |
| `!=` | diferit (loose) |
| `!==` | diferit (strict) |
| `==` | egal (loose) |
| `===` | egal (strict) |
| `<=` | mai mic sau egal |
| `>=` | mai mare sau egal |

**Notă**: operatorii cu 3 caractere sunt detectați înaintea celor cu 2 pentru a evita false-matches.

**Exemple**

```php
elecond_parse_condition( 'user_role==administrator' );
elecond_parse_condition( 'cart_count>=1' );
elecond_parse_condition( 'is_logged_in==true' );
elecond_parse_condition( 'post_age_days>30' );
```

---

## inc/parse_conditions.php — lookup valori

### `elecond_prepare_values( array $keys ): array`

Rezolvă un array de chei (nume de variabile) la valorile lor concrete.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `$keys` | `array` | Lista de nume de variabile de rezolvat |

**Returnează** `array` — mapare `[ cheie => valoare ]`.

**Ordinea de lookup (de la prioritate mare la mică)**

1. Variabile din filtrul `eleconditions_vars` (ex. `now`, `user_role`, `utm_source`).
2. Proprietăți ale obiectului WP curent (`$post->ID`, `$post->post_status`, etc.).
3. Post meta (`get_post_meta()`).
4. Atribut produs WooCommerce (`getProductAttributes()`), dacă funcția există.
5. Câmpuri ACF (`get_field()` pe post), dacă ACF e activ.
6. Câmpuri ACF pe taxonomii (`get_field()` pe term).
7. Query vars WordPress (`$wp_query->query_vars`).
8. **User meta** cu prefix `um_` — ex. `um_city` → `get_user_meta( $user_id, 'city', true )`.

**Notă despre prefixul `um_`**

Când un nume de variabilă începe cu `um_`, restul numelui este folosit ca cheie pentru `get_user_meta()` pe utilizatorul curent. Aceasta este convenția internă pentru variabile de tip "User meta field" din UI.

```php
// În condition builder: preset = "User meta field", meta key = "city"
// Intern devine: var = "um_city"
// În prepare_values: get_user_meta( $user_id, 'city', true )
```

---

### `elecond_get_meta_acf_options(): array`

Construiește lista de opțiuni pentru SELECT2-ul "ACF / Meta field" din panoul Elementor.

**Returnează** `array` — mapare `[ cheie => label ]`, sortat ACF fields (cu `[ACF]`) urmate de post meta keys (cu `[meta]`), plus opțiunea `__manual__` la final.

**Sursele de date**

1. Câmpuri ACF din toate field groups (`acf_get_field_groups()` + `acf_get_fields()`).
2. Chei de post meta din DB (primele 300, excluse cheile interne care încep cu `_`).

**Notă**: funcția interogează baza de date — este apelată doar în contextul editorului Elementor.

---

### `elecond_check_value( mixed $val, array $values ): mixed`

Rezolvă o valoare literală sau un nume de variabilă la valoarea sa concretă.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `$val` | `mixed` | Valoarea brută din expresia de condiție |
| `$values` | `array` | Maparea variabile → valori din `elecond_prepare_values()` |

**Returnează** valoarea rezolvată: `true`, `false`, `null`, sau valoarea din `$values`, sau `$val` nemodificat.

**Litere speciale** — convertite la tipuri PHP native:

| String | Tip PHP |
|---|---|
| `'true'` | `true` |
| `'false'` | `false` |
| `'null'` | `null` |

---

### `elecond_evaluate_values( mixed &$cmp1, mixed &$cmp2, string $a, string $b ): void`

Normalizează cele două valori de comparat pentru a asigura comparații corecte.

**Comportament**

- Dacă `$a` este literal special (`true`/`false`/`null`) și `$cmp2` nu a fost rezolvat din variabilă → setează `$cmp2 = ""` (pentru comparație cu string gol).
- Dacă `$a` este numeric și `$cmp2` nu e din variabilă → setează `$cmp2 = 0`.
- Același comportament simetric pentru `$b`/`$cmp1`.

**Scop**: asigură că `is_logged_in == true` funcționează corect (nu compară string `"true"` cu string `"true"`, ci bool `true` cu empty).

---

### `elecond_getQueryVar( array $keys ): object|null`

Returnează obiectul WP curent (post sau queried object) folosit ca sursă pentru lookup de proprietăți.

**Returnează** `WP_Post`, obiectul queriat, sau `null` dacă `$wp_query` nu e disponibil.

---

### `elecond_set( mixed $value = null ): mixed`

Helper null-safe: returnează `$value` dacă este setat, altfel `""`.

---

## inc/parse_conditions.php — debug

### `elecond_debug( string $condition, string $var1, string $var2, mixed $val1, mixed $val2, string $operator, bool $result ): void`

Afișează un bloc HTML de debug cu variabilele, valorile rezolvate și rezultatul evaluării.

**Vizibil doar pentru** utilizatorii cu capabilitatea `editor` sau `administrator`.

**Output** — bloc `div.ele_cond_debug` cu:
- Condiția originală (fundal negru).
- Valoarea rezolvată a fiecărui operand (verde dacă a fost rezolvată din variabilă, roz dacă a rămas literal).
- Operatorul (albastru).
- Rezultatul (portocaliu).

---

## inc/controls.php — hooks Elementor

### Hook: `elementor/element/before_section_start`

Înregistrează secțiunea **"Ele Conditions"** în tab-ul **Advanced** al fiecărui widget și secțiuni Elementor, imediat înaintea secțiunii `_section_responsive`.

**Controale adăugate în REPEATER `conditions_list`**

| Control | Tip | Vizibil când |
|---|---|---|
| `cond_type` | SELECT | mereu |
| `cond_var_preset` | SELECT | `cond_type = simple` |
| `cond_var_acf_meta` | SELECT2 | `preset = acf_meta` |
| `cond_var_acf_manual` | TEXT | `preset = acf_meta` + `acf_meta = __manual__` |
| `cond_var_user_meta` | TEXT | `preset = user_meta` |
| `cond_var_custom` | TEXT | `preset = custom` |
| `cond_operator` | SELECT | `cond_type = simple` |
| `cond_value` | TEXT | `cond_type = simple` |
| `cond_time_from` | TEXT | `cond_type = time_interval` |
| `cond_time_to` | TEXT | `cond_type = time_interval` |
| `cond_datetime_from` | `elecond_datetime` | `cond_type = date_interval` |
| `cond_datetime_to` | `elecond_datetime` | `cond_type = date_interval` |
| `cond_logic` | SELECT | mereu |

**Control individual**

| Control | Tip | Descriere |
|---|---|---|
| `element_condition_debug` | SWITCHER | Activează modul debug pentru element |

---

### Hook: `elementor/widget/render_content`

Filtrează conținutul HTML al widget-ului. Dacă condițiile eșuează, returnează string gol (ascunde complet conținutul).

```
Priority: 10 | Args: 2 ($content, $widget)
```

---

### `elecond_hide_element( \Elementor\Element_Base $element ): void`

Adaugă `style="display:none"` pe wrapper-ul elementului dacă condițiile eșuează.

În **modul debug** (doar admin/editor): în loc de `display:none`, aplică `opacity:0.5; border:3px solid red` pentru a vizualiza elementele ascunse.

**Hooky unde este înregistrată**

```php
add_action( 'elementor/frontend/widget/before_render',  'elecond_hide_element' );
add_action( 'elementor/frontend/section/before_render', 'elecond_hide_element' );
```

---

## inc/controls-triggers.php — hooks Elementor

### Hook: `elementor/element/before_section_start`

Înregistrează secțiunea **"Triggers"** în tab-ul **Advanced**, imediat înaintea `_section_responsive`.

**Controale adăugate în REPEATER `triggers_list`**

| Control | Tip | Vizibil când |
|---|---|---|
| `trigger_type` | SELECT | mereu |
| `trigger_delay_ms` | NUMBER | `trigger_type = delay` |
| `trigger_time_seconds` | NUMBER | `trigger_type = time_on_page` |
| `trigger_visit_count` | NUMBER | `trigger_type = nth_visit` |
| `trigger_ab_name` | TEXT | `trigger_type = ab_group_a` sau `ab_group_b` |
| `action_type` | SELECT | mereu |
| `action_target` | TEXT | `action_type ≠ close_others` |
| `action_class` | TEXT | `action_type ∈ {add_class, remove_class, toggle_class}` |
| `action_group_class` | TEXT | `action_type = close_others` |

**Control individual**

| Control | Tip | Descriere |
|---|---|---|
| `trigger_hide_initially` | SWITCHER | Ascunde elementul la încărcare (înainte de orice trigger) |

---

### `elecond_attach_triggers( \Elementor\Element_Base $element ): void`

Adaugă atributele de date pe wrapper-ul elementului pentru procesare în JavaScript.

**Atribute adăugate**

| Atribut | Condiție | Valoare |
|---|---|---|
| `data-elecond-triggers` | `triggers_list` este completat | JSON array cu toate rândurile din REPEATER |
| `data-elecond-hide-initially` | `trigger_hide_initially` este activat | `"1"` |

**Notă**: `trigger_hide_initially` este verificat **independent** de `triggers_list` — elementul poate fi ascuns inițial chiar dacă nu are triggers definite.

**Hookuri unde este înregistrată**

```php
add_action( 'elementor/frontend/widget/before_render',  'elecond_attach_triggers' );
add_action( 'elementor/frontend/section/before_render', 'elecond_attach_triggers' );
```

---

## inc/control-datetime.php — control custom

### `class Elecond_Datetime_Control extends \Elementor\Base_Data_Control`

Control Elementor custom care redă un `<input type="datetime-local">` nativ în panoul editorului.

**Constant**

```php
Elecond_Datetime_Control::TYPE // = 'elecond_datetime'
```

**Metode publice**

| Metodă | Returnează | Descriere |
|---|---|---|
| `get_type()` | `string` | Identificatorul controlului: `'elecond_datetime'` |
| `get_default_value()` | `string` | Valoarea implicită: `''` |
| `enqueue()` | `void` | Injectează CSS inline în editorul Elementor |
| `content_template()` | `void` | Template Backbone.js pentru input HTML |

**Înregistrare**

```php
add_action( 'elementor/controls/register', function( $controls_manager ) {
    $controls_manager->register( new Elecond_Datetime_Control() );
} );
```

**Format stocat** — `YYYY-MM-DDTHH:MM` (formatul nativ al `datetime-local`). Separatorul `T` este normalizat la spațiu în `elecond_check_date_interval()`.
