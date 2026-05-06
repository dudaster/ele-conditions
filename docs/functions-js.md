# Funcții JavaScript — triggers.js

Fișier: `assets/js/triggers.js`

Toate funcțiile sunt encapsulate într-un IIFE (`(function() { ... })()`) pentru a nu polua scope-ul global. Scriptul este încărcat în footer via `wp_enqueue_script`.

---

## Inițializare automată

La încărcarea scriptului (înainte de orice funcție), se execută:

```js
var visitKey   = 'elecond_v_' + window.location.pathname;
var visitCount = parseInt( store.get( visitKey ) || '0', 10 ) + 1;
store.set( visitKey, visitCount );
```

- Incrementează contorul de vizite pentru URL-ul curent în `localStorage`.
- `visitCount` este disponibil în scope-ul IIFE și folosit de trigger-urile `first_visit` și `nth_visit`.
- Contorul se stochează per pathname (ex. `elecond_v_/despre-noi`), independent per pagină.

---

## `store` — wrapper localStorage

```js
var store = {
    get: function( k ) { ... },
    set: function( k, v ) { ... }
};
```

Wrapper care învelește `localStorage` în `try/catch`. Previne crash-ul scriptului în Safari Private Browsing și alte contexte unde `localStorage` aruncă excepție.

### `store.get( key )`

**Parametri** — `key: string`

**Returnează** `string | null` — valoarea stocată, sau `null` dacă cheia nu există sau `localStorage` nu e disponibil.

### `store.set( key, value )`

**Parametri** — `key: string`, `value: any`

**Returnează** `void`. Eșuează silențios dacă `localStorage` nu e disponibil.

**Comportament la indisponibilitate localStorage**

- `store.get()` → returnează `null` → `visitCount` devine `1` → trigger-urile `first_visit` și `nth_visit` funcționează parțial (nu persistă între sesiuni).
- A/B groups nu sunt persistate → utilizatorul primește un grup nou la fiecare reload.
- Celelalte trigger-uri (click, hover, delay, scroll, exit intent) funcționează normal.

---

## `getAbGroup( name )`

```js
function getAbGroup( name ) { ... }
```

Returnează grupul A/B al utilizatorului curent pentru un test dat. La prima apelare, asignează aleatoriu `'a'` sau `'b'` și persistă în `localStorage`.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `name` | `string` | Identificatorul testului (ex. `'hero_banner'`) |

**Returnează** `'a' | 'b'`

**Cheie localStorage** — `elecond_ab_{name}` (ex. `elecond_ab_hero_banner`).

**Garanții**

- Aceeași `name` returnează întotdeauna același grup în aceeași sesiune/browser.
- Grupuri diferite pentru teste diferite sunt independente.
- Asignarea este 50/50 prin `Math.random() < 0.5`.

---

## `onExitIntent( cb )`

```js
function onExitIntent( cb ) { ... }
```

Înregistrează un callback care se execută când utilizatorul încearcă să iasă din pagină (cursorulmutse în afara ferestrei spre bara de adrese).

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `cb` | `function` | Funcția apelată la exit intent |

**Returnează** `void`

**Comportament**

- Adaugă `mouseleave` pe `document` o singură dată, indiferent de câte ori este apelată `onExitIntent()`.
- La primul eveniment cu `clientY <= 0`, execută **toate** callback-urile înregistrate și le șterge (fire-once).
- Apeluri ulterioare ale aceluiași eveniment nu mai execută nimic (callbacks = []).

**Detectare exit intent**

Condiția `e.clientY <= 0` identifică mișcarea cursorului spre zona browserului (tab bar, address bar), care indică intenția de a închide sau naviga.

---

## `getTarget( self, selector )`

```js
function getTarget( self, selector ) { ... }
```

Rezolvă ținta unei acțiuni: fie elementul curent, fie un element găsit prin CSS selector.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `self` | `Element` | Elementul pe care este definit trigger-ul |
| `selector` | `string` | CSS selector opțional (ex. `'#modal'`, `'.popup'`) |

**Returnează** `Element`

**Logică**

- `selector` gol sau doar spații → returnează `self`.
- `document.querySelector(selector)` găsit → returnează elementul găsit.
- `document.querySelector(selector)` nu găsit → fallback la `self`.

---

## `executeAction( self, trigger )`

```js
function executeAction( self, trigger ) { ... }
```

Execută acțiunea definită într-un trigger pe ținta rezolvată.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `self` | `Element` | Elementul sursă (pe care e definit trigger-ul) |
| `trigger` | `object` | Rândul din REPEATER (date din `data-elecond-triggers`) |

**Returnează** `void`

**Proprietăți citite din `trigger`**

| Proprietate | Folosită pentru |
|---|---|
| `trigger.action_type` | Identifică acțiunea de executat |
| `trigger.action_target` | CSS selector pentru țintă (opțional) |
| `trigger.action_class` | Clasa CSS pentru acțiuni de clasă |
| `trigger.action_group_class` | Clasa de grup pentru `close_others` |

**Acțiuni suportate**

| `action_type` | Efect |
|---|---|
| `show` | `target.style.removeProperty('display')` + `removeProperty('visibility')` |
| `hide` | `target.style.display = 'none'` |
| `toggle` | Alternează `display: none` / restored based on `getComputedStyle` |
| `add_class` | `target.classList.add(action_class)` |
| `remove_class` | `target.classList.remove(action_class)` |
| `toggle_class` | `target.classList.toggle(action_class)` |
| `scroll_to` | `target.scrollIntoView({ behavior: 'smooth', block: 'start' })` |
| `focus` | `target.focus()` |
| `close_others` | `querySelectorAll('.{group_class}')` → ascunde toate ≠ `self` |

**Notă `toggle`**: citește `window.getComputedStyle(target).display`, nu `target.style.display`, pentru a detecta corect și elementele ascunse via CSS (nu doar inline style).

---

## `attachTrigger( el, trigger )`

```js
function attachTrigger( el, trigger ) { ... }
```

Atașează un trigger la un element DOM. Logica variază în funcție de `trigger.trigger_type`.

**Parametri**

| Parametru | Tip | Descriere |
|---|---|---|
| `el` | `Element` | Elementul DOM pe care se atașează trigger-ul |
| `trigger` | `object` | Rândul din REPEATER |

**Returnează** `void`

**Trigger-uri și comportamentul lor**

| `trigger_type` | Mecanism | Câmpuri adiționale |
|---|---|---|
| `click` | `addEventListener('click')` — `stopPropagation` pentru a nu bubbling | — |
| `hover` | `addEventListener('mouseenter')` | — |
| `delay` | `setTimeout(fn, trigger_delay_ms)` | `trigger_delay_ms` (ms, default: 0) |
| `scroll_into_view` | `IntersectionObserver.observe(el)` — fire-once via `unobserve` | — |
| `time_on_page` | `setTimeout(fn, trigger_time_seconds * 1000)` | `trigger_time_seconds` (secunde, default: 10) |
| `exit_intent` | Delegat la `onExitIntent()` | — |
| `first_visit` | Sincron — verifică `visitCount === 1` | — |
| `nth_visit` | Sincron — verifică `visitCount === trigger_visit_count` | `trigger_visit_count` (default: 2) |
| `ab_group_a` | Sincron — verifică `getAbGroup(trigger_ab_name) === 'a'` | `trigger_ab_name` |
| `ab_group_b` | Sincron — verifică `getAbGroup(trigger_ab_name) === 'b'` | `trigger_ab_name` |

**Notă `scroll_into_view`**: dacă `window.IntersectionObserver` nu există (browser vechi), trigger-ul este ignorat silențios (`break` fără fallback).

**Trigger-uri sincrone** (`first_visit`, `nth_visit`, `ab_group_a`, `ab_group_b`): acțiunea se execută imediat în `init()`, fără a aștepta un eveniment. Dacă `trigger_hide_initially` este activat, elementul este deja ascuns înainte de evaluarea acestor trigger-uri — deci un trigger `show` + `first_visit` va afișa corect elementul.

---

## `init()`

```js
function init() { ... }
```

Funcția de inițializare principală. Rulează după `DOMContentLoaded` (sau imediat dacă DOM-ul e deja gata).

**Pași**

1. **Hide initially** — selectează `[data-elecond-hide-initially]` și setează `display: none` pe fiecare.
2. **Attach triggers** — selectează `[data-elecond-triggers]`, parsează JSON din atribut, iterează trigger-urile și apelează `attachTrigger(el, trigger)` pentru fiecare.

**Ordinea este importantă**: hide initially se aplică *înaintea* atașării trigger-urilor, astfel încât un trigger sincron de tip `show` poate revela imediat un element care a fost ascuns.

**Rezistență la erori**

- JSON invalid în `data-elecond-triggers` → `try/catch` → elementul este sărit fără crash.
- `data-elecond-triggers` care nu este un array → `Array.isArray()` check → sărit.

---

## Fluxul complet (end-to-end)

```
PHP (la render):
  elecond_attach_triggers()
    → data-elecond-triggers='[{"trigger_type":"click","action_type":"toggle",...}]'
    → data-elecond-hide-initially='1'  (dacă e activat)

JS (la DOMContentLoaded):
  init()
    → querySelector('[data-elecond-hide-initially]') → display:none
    → querySelector('[data-elecond-triggers]')
      → JSON.parse(atribut)
      → forEach trigger → attachTrigger(el, trigger)
        → click: addEventListener('click', () => executeAction(el, trigger))
        → first_visit: if (visitCount===1) executeAction(el, trigger)
        → ab_group_a: if (getAbGroup(name)==='a') executeAction(el, trigger)
        ...

Utilizator interacționează:
  → eveniment declanșat
  → executeAction(el, trigger)
    → getTarget(el, trigger.action_target) → targetEl
    → switch(action_type) → modifică DOM
```
