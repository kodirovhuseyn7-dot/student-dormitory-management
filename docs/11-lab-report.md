# Кори лаборатории №11 — SDMS

## Мавзӯъ
FRONTEND (2): JavaScript — валидатсия, ҷустуҷӯи фаврӣ ва равзанаи модалӣ.

## Файлҳои кор
- `public/assets/js/app.js` — JavaScript-и ES2020+, debounce, fetch, AbortController, валидатсия.
- `public/api/students/index.php` — API-и муваққатӣ бо 7 сабт, аз ҷумла сабти махсус барои санҷиши XSS.
- `public/static/application-form.html` — шакли ариза бо 7 майдон ва қоидаи махсуси санҷиши санаҳо.
- `public/static/students.html` — ҷустуҷӯи фаврии донишҷӯён.
- `public/static/applications.html` — равзанаи тасдиқи бекоркунӣ бо фиристодани POST.

## Роҳҳои кушодан
- `/static/application-form.html`
- `/static/students.html`
- `/static/applications.html`
- API: `/api/students/index.php?q=STU`

## Скриншотҳо
Дар `docs/11-screens/` нигоҳ дошта шаванд:
1. `01-form-validation.png`
2. `02-live-search-network.png`
3. `03-xss-safe-output.png`
4. `04-confirm-modal.png`

## Эзоҳ
API-и донишҷӯён муваққатӣ аст. Масири бекоркунии ариза барои нишон додани шакли POST омода шудааст; endpoint-и воқеии бекоркунӣ дар қисми backend сохта мешавад.
