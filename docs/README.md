# Ele Conditions — Developer Documentation

## Index

| Document | Conținut |
|---|---|
| [functions-php.md](functions-php.md) | Toate funcțiile PHP: evaluare condiții, variabile, Elementor hooks |
| [functions-js.md](functions-js.md) | Funcțiile JavaScript: triggers, actions, localStorage |
| [variables-reference.md](variables-reference.md) | Referință completă pentru toate variabilele built-in |
| [extending.md](extending.md) | Cum adaugi variabile custom prin filtru PHP |

## Structura proiectului

```
ele-conditions/
├── ele-conditions/                     — fișierele pluginului (ce se deployează pe WP.org)
│   ├── ele-conditions.php      — main plugin file, variabile built-in
│   ├── inc/
│   │   ├── controls.php        — secțiunea "Ele Conditions" în panoul Elementor
│   │   ├── controls-triggers.php — secțiunea "Triggers" în panoul Elementor
│   │   ├── parse_conditions.php — engine-ul de evaluare condiții (server-side PHP)
│   │   └── control-datetime.php — control custom Elementor pentru datetime-local
│   ├── assets/
│   │   └── js/
│   │       └── triggers.js     — engine-ul de trigger/action (client-side JS)
│   ├── languages/
│   └── readme.txt
├── docs/                       — documentație developer (GitHub only)
├── tests/
│   ├── test-triggers.js        — 57 teste Node.js pentru triggers.js
│   └── test-parse-conditions.php — teste PHP pentru funcțiile de parsing
├── deploy-svn.sh               — script de deploy pe WordPress.org SVN
└── .gitignore
```

## Deploy pe WordPress.org SVN

```bash
# Verificare înainte de commit (dry run)
./deploy-svn.sh

# Commit la trunk + creare tag de versiune
./deploy-svn.sh --commit
```

## Docker (wpmcp)

Volumul din `docker-compose.yml` montează subfolder-ul `ele-conditions/`:
```yaml
- ${HOME}/Projects/ele-conditions/ele-conditions:/var/www/html/wp-content/plugins/ele-conditions
```

## Arhitectura în două straturi

```
┌─────────────────────────────────────────────────────┐
│  STRATUL PHP (server-side)                          │
│  Condiții evaluate la render — element ascuns/afișat│
│                                                     │
│  eleconditions_vars filter ──► elecond_keywords()   │
│  elecond_evaluate_group() ──► elecond_parse_condition│
│  elecond_check_time_interval / date_interval        │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  STRATUL JS (client-side)                           │
│  Trigger-uri interactive — nu necesită reload       │
│                                                     │
│  data-elecond-triggers (JSON) ──► attachTrigger()   │
│  data-elecond-hide-initially   ──► style.display    │
│  executeAction() ──► DOM manipulation               │
└─────────────────────────────────────────────────────┘
```
