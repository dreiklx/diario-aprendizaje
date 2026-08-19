# CLAUDE.md

Memoria permanente del proyecto. Léelo antes de tocar código: explica por
qué el proyecto está hecho como está, qué reglas no deben romperse, y
cómo hacer las tareas más comunes (agregar una entrada, ejecutar
localmente, desplegar).

## 1. Qué es esto

Diario de Aprendizaje digital para el curso **SR-0022 Seminario de
Realidad Nacional II — "Producción y Desarrollo"**, Universidad de Costa
Rica, Sede del Caribe, II Ciclo 2026. Autor: Derek Farley Noguera (carné
C5F012). Es un sitio editorial de una sola persona (sin comentarios, sin
base de datos) que documenta 15 entradas semanales a lo largo del
semestre. La lectura pública no requiere login; existe un editor privado
protegido por contraseña en `/editar` (ver sección 14) para que el autor
edite sus reflexiones desde el navegador sin tocar código.

No es una plantilla ni un proyecto de curso "genérico": la dirección de
diseño (tipografía Fraunces + Inter, paleta editorial cálida, timeline
como tabla de contenidos) es deliberada. Ver sección 6.

## 2. Stack y restricciones no negociables

- PHP 8.3+ puro. Sin frameworks (Laravel, Symfony, Slim). Sin Composer
  (no hay dependencias que lo justifiquen).
- HTML semántico generado por plantillas PHP (PHP es el propio lenguaje
  de plantillas: no hay Blade, Twig, ni sintaxis inventada).
- CSS propio, sin frameworks (Tailwind, Bootstrap). Variables CSS
  (custom properties), sin preprocesador.
- JavaScript vanilla, sin librerías. `assets/js/main.js` (sitio público,
  ~80 líneas: tema, revelado al scroll, navegación por teclado) es
  deliberadamente mínimo. `assets/js/editor.js` (solo `/editar`, no se
  carga en el sitio público) es más grande porque **es** la interfaz del
  editor de bloques — no hay forma de construir esa experiencia con
  menos JS sin perder la edición visual que pidió el usuario; ver
  sección 14-bis.
- Sin base de datos. Los datos viven en arrays PHP versionados en Git
  (`api/data/*.php`); el editor privado los modifica vía la API de
  contenidos de GitHub (commits reales), no vía un almacén aparte.
- Sin autenticación **en el sitio público** — la lectura del diario nunca
  pide contraseña. Sí existe autenticación en `/editar` (contraseña +
  cookie firmada), pedida explícitamente por el usuario; ver sección 14.
  No es un panel admin genérico ni un CMS: solo puede editar los campos
  de texto de una entrada, nada más.

Si en algún momento sientes la tentación de añadir algo de esta lista
(o de ampliar lo que el editor puede tocar) para "mejorar" el proyecto:
no lo hagas sin que el usuario lo pida explícitamente. Es una decisión
de producto, no un descuido.

## 3. Arquitectura

```
api/
  index.php              Front controller único (la ÚNICA función de Vercel)
  lib/
    router.php            Resuelve REQUEST_URI -> {page, params}
    entries.php            Lógica de negocio: estado, progreso, acceso a datos
    dates.php               Formato y comparación de fechas (ES)
    render.php               Motor de plantillas mínimo (extract + include + ob_*)
    blocks.php               Modelo de contenido por bloques: renderer seguro + sanitización (ver sección 4-bis)
    auth.php                  Sesión del editor: cookie firmada (HMAC), sin estado en servidor
    github.php                 Cliente mínimo de la API de contenidos de GitHub
    entries_editor.php          Parser/serializador de entries.php (editor privado)
    editor_actions.php           Controladores de /editar (login, guardar, logout)
  data/
    course.php              Metadatos del curso (única fuente de verdad)
    entries.php              Las 15 entradas del diario (única fuente de verdad)
  templates/
    layout.php               <html> completo: head, nav, <main>, footer, scripts
    partials/                 nav, footer, progress, status-badge, timeline
    pages/                     home, week, course, not-found, editor-login, editor-weeks, editor-week

assets/
  css/
    tokens.css                Variables: color, tipografía, espaciado, radios, motion
    base.css                   Reset + estilos de elementos base + a11y
    layout.css                  Nav, footer, contenedores, disposición de secciones
    components.css               Badge, progress bar, timeline, artículo de semana, pager
    editor.css                    Solo para /editar — formularios, editor de bloques, lista de semanas
  js/
    main.js                      Sitio público: tema, revelado al scroll, navegación por teclado
    editor.js                     Solo /editar: editor de bloques completo (ver sección 14-bis)

vercel.json                  Runtime PHP + rewrite catch-all
dev-router.php               Router SOLO para `php -S` local (ver sección 8)
```

**Por qué todo el PHP vive bajo `/api`:** el runtime `vercel-php` sube el
árbol de archivos junto al entrypoint, y Vercel nunca sirve el contenido
de `/api` como archivo estático. Poniendo `data/`, `lib/` y
`templates/` dentro de `/api` nos aseguramos de que (a) `require`
funcione igual en local y en producción, y (b) nadie pueda pedir
`GET /data/entries.php` y recibir el código fuente en texto plano. NO
muevas estas carpetas fuera de `/api`.

**Front controller único:** `vercel.json` reescribe *toda* petición a
`/api/index.php` (excepto archivos reales bajo `/assets`, que Vercel
sirve directo por filesystem antes de aplicar el rewrite). `index.php`
lee `$_SERVER['REQUEST_URI']`, lo resuelve con `resolve_route()` y
despacha a la página correspondiente. No hay `.htaccess`, no hay rutas
adicionales fuera de `router.php`.

**Flujo de datos:** `api/data/entries.php` → `get_entries()` /
`entry_status()` / `progress_stats()` (en `lib/entries.php`) → variables
pasadas a `render_page()` → plantilla. Ninguna plantilla lee
`api/data/*.php` directamente ni recalcula estado/fechas por su cuenta.

## 4. Cómo agregar o completar una entrada semanal

Dos formas, el mismo archivo de destino:

- **Desde el navegador (recomendado):** en `/` o en `/semana/N`, cada
  semana tiene un enlace "+ Agregar" o "Editar" junto a su badge de
  estado (visible para cualquiera, pero lleva a la pantalla de
  contraseña si no hay sesión — ver sección 14). Construís la reflexión
  con el editor de bloques y guardás: commit real en GitHub, redeploy
  automático. Ver sección 14 y 14-bis para el detalle completo.
- **Editando el archivo a mano:** editá **solo** `api/data/entries.php`.
  Busca el arreglo con el `week` correspondiente y completa `title`,
  `theme` y `blocks` (ver sección 4-bis para el formato de `blocks`). No
  toques ningún otro archivo.

- El estado (`próxima` / `disponible` / `completada`) se calcula solo:
  en cuanto `blocks` tiene al menos un bloque con contenido real, la
  entrada pasa a `completada` (`entry_has_content()` en
  `api/lib/blocks.php`). No hay un campo `status` que se pueda
  desincronizar.
- El progreso (`X / 15`) se recalcula solo a partir de esa misma lista.
  Nunca escribas un porcentaje o conteo a mano en ninguna plantilla.

## 4-bis. Modelo de contenido: bloques

Hasta la iteración anterior cada entrada tenía cuatro campos de texto
fijos (`reflexion`, `aprendizaje`, `cuestionamiento`, `aplicacion`). Se
reemplazaron por **`blocks`**: una lista ordenada de bloques tipados,
para poder construir una reflexión con la estructura que haga falta en
cada semana en vez de encajarla en cuatro casilleros fijos. Todo vive en
`api/lib/blocks.php`.

**Tipos de bloque** (`BLOCK_TYPES`): `heading` (subtítulo de sección),
`paragraph`, `highlight` (párrafo destacado, fondo de acento),
`quote`, `list` (`style`: `ordered`/`unordered` + `items[]`), `divider`
(sin contenido), `link` (`text` + `url`), `image` (`url` + `alt` +
`caption`, siempre por URL — nunca hubo subida de archivos, ver sección
14-bis). Se consolidó a propósito "Título" y "Subtítulo" (que el pedido
original listaba por separado) en un solo tipo `heading` — el título
grande de la entrada ya es el campo `title` de nivel superior, mostrado
como `<h1>`; un segundo nivel de heading dentro del cuerpo alcanza para
dividir la reflexión en secciones sin duplicar jerarquías.

**Marcado en línea** (dentro de `text`/`items`/`caption`): `**negrita**`,
`*cursiva*`, `==destacado==` (span con el color de acento — la única
"opción de color" del editor, a propósito: nada de selector RGB libre
que pueda romper la identidad visual, ver sección 6), `[texto](url)`.
Es el único formato admitido — nunca HTML crudo.

**Seguridad — por qué esto no es una superficie de XSS:**
`render_inline_markup()` primero pasa el texto completo por `e()`
(`htmlspecialchars`) y **solo después** aplica las sustituciones de
arriba, que son las únicas fuentes de etiquetas HTML en la salida
(`<strong>`, `<em>`, `<mark>`, `<a>`). Ningún dato del usuario llega
nunca a imprimirse como HTML sin escapar primero. Los `url` (bloque
`link`/`image`, y los de `[texto](url)`) pasan por
`sanitize_block_url()`: solo `http(s)://` absoluto o `/ruta` relativa
del propio sitio — cualquier otro esquema (`javascript:`, `data:`, etc.)
se rechaza. Si alguna vez agregás un tipo de bloque nuevo, seguí el
mismo patrón: escapar primero, insertar etiquetas fijas después, nunca
un `$html .= $input` directo.

**`sanitize_blocks_input()`** es el segundo cinturón de seguridad: pase
lo que pase por HTTP (el editor manda `blocks_json`, un JSON armado en
el navegador), el servidor vuelve a validar tipo por tipo antes de
tocar `entries.php` — nunca confía en la forma que asume el JS. Límites
duros: `BLOCK_MAX_COUNT` (40 bloques), `BLOCK_TEXT_MAX` (4000
caracteres), `BLOCK_LIST_ITEMS_MAX` (30 ítems). Un bloque sin contenido
(texto vacío, sin URL válida) se descarta silenciosamente al guardar —
no llega a persistirse un bloque vacío.

### Semana académica vs. día de clase — dos fechas, no una

Cada entrada tiene **dos** fechas distintas, a propósito:

- `week_start` — el **lunes** en que arranca esa semana académica.
- `class_date` — el día real de la sesión de clase (los **miércoles**
  en este curso). No asumas que coinciden: la semana empieza el lunes,
  la clase es el miércoles.

Esto importa porque cada cálculo usa la fecha correcta a propósito, no
la que sea más conveniente:

- `entry_status()` (`api/lib/entries.php`) compara contra **`class_date`**
  — hasta que no hubo clase, no hay nada que reflexionar, así que el
  estado "disponible" no se activa el lunes, se activa el miércoles.
- `current_week_number()` compara contra **`week_start`** — la semana ya
  está en curso desde el lunes, aunque la clase sea hasta el miércoles.
- `format_week_range()` / `format_class_short()` (`api/lib/dates.php`)
  formatean cada una para su propio uso: el rango completo de la semana
  ("10 — 16 AGO 2026") y la fecha corta de clase con día
  ("Miércoles 12 AGO"). Nunca dupliques ese formato a mano en una
  plantilla — siempre pasa por estas funciones.

Si el calendario real de la profesora difiere de las fechas generadas
(lunes consecutivos desde `semester_start` en `api/data/course.php`,
clase siempre dos días después), edita `week_start`/`class_date` de la
entrada afectada directamente — no hay que tocar ninguna lógica.

## 5. Reglas de PHP

- `declare(strict_types=1)` en el front controller.
- Toda salida dinámica a HTML pasa por `e()` (htmlspecialchars) en
  `api/lib/render.php`. No hay excepciones a esto.
- Las plantillas (`api/templates/**/*.php`) son solo presentación: usan
  variables ya calculadas, no llaman lógica de negocio compleja ni
  hacen queries. La lógica vive en `api/lib/`.
- `render_partial()` y `render_page()` son el único mecanismo de
  inclusión de plantillas. No uses `include`/`require` directo en una
  plantilla para renderizar otra plantilla.
- Sin dependencias de Composer. Si un problema parece necesitar una
  librería, probablemente se puede resolver con PHP estándar dado el
  tamaño del proyecto.

## 6. Reglas visuales (no rompas esto sin una razón de diseño explícita)

Dirección: editorial cálido, no "dashboard", no plantilla de curso.

- **Tipografía:** Fraunces (display: h1–h4, hero, cursiva de acento) +
  Inter (cuerpo, UI, metadata). Son las únicas dos familias. No agregues
  una tercera sin una razón fuerte — el peso visual del sitio depende de
  este contraste serif/sans, no de la cantidad de fuentes.
- **Color:** todo pasa por las variables en `tokens.css`. Nunca un color
  hexadecimal suelto en `layout.css`/`components.css`/plantillas. Los
  tres estados de las entradas usan colores semánticos separados
  (`--color-completed`, `--color-available`, `--color-upcoming`) — sus
  tonos de texto están calibrados para ≥4.5:1 de contraste sobre su
  fondo `-soft` correspondiente (ver sección 9); si cambias uno, vuelve
  a comprobar el contraste.
- **Fondo:** papel cálido (`--color-bg: #faf7f0`), no blanco puro. Es
  parte de la identidad "diario/editorial", no un descuido.
- **Timeline como tabla de contenidos:** el índice de semanas en `/` es
  el componente central del sitio (`partials/timeline.php` +
  `.timeline` en `components.css`). No lo conviertas en una grilla de
  tarjetas — el hilo vertical con nodos de estado es intencional.
- **Sin:** gradientes, glassmorphism, sombras pronunciadas, iconos
  genéricos de librería, animaciones decorativas porque sí. El
  movimiento (fill de progreso, revelado al hacer scroll, transición de
  tema, hover/foco) siempre respeta `prefers-reduced-motion` — ver
  sección 6-bis.
- **No inventar contenido académico:** las reflexiones del diario son
  del estudiante. Si un campo no tiene contenido, se muestra un estado
  "pendiente" explícito — nunca un texto de relleno inventado. La
  semana 1 (`api/data/entries.php`) ya tiene una reflexión real escrita
  por el estudiante; es la referencia de tono para cualquier entrada
  futura: primera persona, natural, nada de lenguaje académico
  artificial ("hoy en la clase...", no "en el marco de la presente
  sesión...").
- **Un solo color "de énfasis" en el contenido, no un selector RGB
  libre:** el editor permite destacar texto en línea (`==texto==`), pero
  solo con `--color-accent` — el mismo azul que ya se usa en enlaces,
  semana actual, etc. Fue deliberado (pedido explícito): un selector de
  color arbitrario en el contenido rompería la paleta cuidada del
  sitio. Si en algún momento se pide más de un color de énfasis, que
  sean tokens con nombre (`--color-accent`, `--color-secondary`...),
  nunca un `<input type="color">` libre.

## 6-bis. Modo claro/oscuro

Sistema completo de tokens, no una inversión de colores. Vive en
`assets/css/tokens.css`: la paleta clara está en `:root`, la paleta
oscura se declara **dos veces** y debe mantenerse igual en ambas copias:

1. `@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { ... } }`
   — sigue el sistema operativo mientras el usuario no haya elegido nada.
2. `:root[data-theme="dark"] { ... }` — el toggle manual, siempre gana.

Cualquier color nuevo que dependa del tema se declara en **ambos**
bloques a la vez (y en `:root` para el valor claro). Nunca un color
fijo fuera de esta capa — la única excepción deliberada es
`.skip-link` en `base.css` (overlay aislado que nunca se mezcla con el
fondo de la página, así que no necesita adaptarse entre temas).

**Cómo se activa:**
- `api/templates/layout.php` tiene un `<script>` inline, **antes** de
  cualquier CSS, que lee `localStorage.theme` (o `prefers-color-scheme`
  si no hay nada guardado) y hace `document.documentElement.setAttribute
  ('data-theme', …)` de forma síncrona. Esto evita el flash del tema
  equivocado al cargar — no lo muevas a `main.js` (que es `defer` y
  correría demasiado tarde) ni lo hagas async.
- Ese mismo script agrega la clase `.js` a `<html>` — la usa el sistema
  de revelado progresivo (ver abajo).
- `assets/js/main.js` maneja el clic del botón `#theme-toggle` (en
  `partials/nav.php`): alterna el atributo y lo persiste en
  `localStorage.theme`.
- `color-scheme` (light/dark) se declara junto a cada bloque de tokens
  para que los controles nativos del navegador (scrollbar, inputs) usen
  el tema correcto automáticamente.

**Contraste:** los colores de estado (completada/disponible/próxima) y
`--color-text-faint` están calibrados a ≥4.5:1 en AMBOS temas (script de
verificación ad-hoc con la fórmula de luminancia relativa WCAG — no hay
un test automatizado permanente para esto). Si cambias un color de
tema, vuelve a calcular el contraste antes de dar por buena la sesión.

## 6-ter. Animaciones y revelado progresivo

- **Revelado al hacer scroll / al cargar:** cualquier elemento con la
  clase `.reveal` empieza invisible (`opacity:0`, `translateY(14px)`)
  **solo si** `<html>` tiene la clase `.js` (ver arriba). Sin JS, `.reveal`
  nunca se oculta — degradación seguro por diseño, revisar antes de
  quitar esa dependencia de `.js`. `assets/js/main.js` usa un único
  `IntersectionObserver` (`rootMargin: '0px 0px -8% 0px'`) para agregar
  `.is-visible` la primera vez que cada elemento entra al viewport, y
  deja de observarlo (no se re-anima al volver a hacer scroll). Los
  elementos ya visibles al cargar (hero, nav) se revelan casi
  inmediatamente porque el observer los evalúa apenas se registra.
- **Stagger:** el retraso escalonado (hero, filas de timeline) se
  calcula en PHP e inyecta como `style="transition-delay: …ms"` directo
  en la plantilla — no hay `:nth-child` mágico que mantener sincronizado
  con el número de elementos.
- **`prefers-reduced-motion: reduce`:** dos capas de seguridad en
  `base.css` — `.reveal` se fuerza a visible sin transición, y una regla
  global reduce cualquier `animation`/`transition` restante a 0.01ms.
- **Cross-fade de tema:** `body` y una lista explícita de selectores en
  `base.css` (nav, footer, badges, timeline, etc.) transicionan
  `background-color`/`color`/`border-color` en `--duration-theme`
  (300ms) al alternar claro/oscuro. Si agregas un componente nuevo con
  colores de tema, agrégalo a esa lista si quieres que cruce suave en
  vez de cambiar de golpe.

## 7. CSS: organización y convenciones

Cuatro archivos, cargados en este orden fijo desde `layout.php`:
`tokens.css` → `base.css` → `layout.css` → `components.css`. Mobile-first
en cada archivo (reglas base = móvil, `@media (min-width: …)` para
ampliar). Breakpoints usados en todo el proyecto — no existen como
variable CSS porque los media queries no pueden leer custom properties:

```
480px  móvil grande
560px  nav pasa de columna a fila
640px  overview/timeline pasan a fila
768px  tablet
1024px laptop
```

- BEM-ish: `.bloque__elemento--modificador` (p. ej. `.timeline__item--completada`).
- Nada de `!important`, nada de estilos inline, nada de selectores por
  id salvo `#contenido` (target del skip-link).
- `asset_url()` en `render.php` añade `?v=filemtime` a cada CSS/JS para
  invalidar caché sin tener que renombrar archivos.

## 8. Cómo ejecutar localmente

```
php -S localhost:8000 dev-router.php
```

**No uses `php -S localhost:8000 api/index.php` directamente** — el
front controller siempre genera una respuesta, así que el servidor
embebido nunca cae al modo "sirve el archivo estático tal cual" y todo
lo de `/assets` devuelve 404. `dev-router.php` reproduce el
comportamiento real de Vercel (archivo estático real → se sirve tal
cual; si no existe → pasa a `api/index.php`) y es la única razón de que
ese archivo exista. No lo despliegues ni lo canonicalices como entrypoint
de producción: `api/index.php` sigue siendo el front controller real.

## 9. Cómo probar

No hay suite de tests automatizada (no se justifica para este tamaño de
proyecto). Antes de dar por buena una sesión de trabajo:

1. `php -l` sobre todos los `.php` tocados (sin dependencias, es instantáneo).
2. Levantar `dev-router.php` y comprobar con `curl` los códigos de
   estado de: `/`, `/curso`, `/semana/1`, `/semana/15`, `/semana/99`
   (debe dar 404), `/assets/css/tokens.css`.
3. Revisión visual en al menos 600px, 768px y 1440px (evita <500px con
   Chrome headless, ver sección 11). Revisa **ambos temas**: para forzar
   uno en headless sin depender del tema del sistema operativo, usa
   `--blink-settings=preferredColorScheme=1` (claro) o `=2` (oscuro) —
   en la práctica solo `=1` se comprobó confiable en esta sesión; para
   oscuro es más seguro simplemente no pasar el flag si el SO ya está en
   modo oscuro.
4. Si tocas colores de estado (completada/disponible/próxima) o
   `--color-text-faint`, vuelve a calcular el contraste texto-sobre-fondo
   en AMBOS temas; el objetivo es ≥4.5:1 porque son textos pequeños
   (`--text-xs`/`--text-sm`) y no califican como "texto grande" en WCAG AA.
5. Prueba el toggle de tema: cambia de claro a oscuro y viceversa,
   recarga la página y confirma que el tema elegido persiste (localStorage).
6. Con JavaScript deshabilitado (o revisando el HTML crudo), confirma
   que todo el contenido con `.reveal` sigue siendo visible — es la
   prueba de que la degradación seguro del revelado progresivo funciona.
7. Si tocaste algo de `/editar`: seguí los pasos de la sección 14
   ("Cómo probar el editor localmente") — incluyen el ciclo completo de
   edición/verificación/reversión contra el repositorio real, no solo
   contra el servidor local.

## 10. Cómo desplegar (Vercel + PHP)

**Situación de PHP en Vercel (investigado agosto 2026):** Vercel no
tiene runtime de PHP de primera parte. La ruta soportada y documentada
por Vercel para PHP es el runtime comunitario `vercel-php`
(github.com/vercel-community/php), declarado como *custom runtime* en
`vercel.json` — este patrón está documentado explícitamente en la propia
documentación oficial de Vercel
(`docs/functions/configuring-functions/runtime`). La alternativa oficial
es desplegar PHP dentro de un contenedor Docker (FrankenPHP/Caddy) vía
`runtime: "container"`; se descartó por ser complejidad innecesaria para
un sitio sin base de datos ni dependencias de servidor. Ver decisión
completa más abajo.

`vercel.json`:
```json
{
  "functions": { "api/index.php": { "runtime": "vercel-php@0.9.0" } },
  "rewrites": [{ "source": "/(.*)", "destination": "/api/index.php" }],
  "trailingSlash": false
}
```

- `api/index.php` es la única función serverless del proyecto.
- El rewrite catch-all envía todo a esa función; Vercel sirve primero
  cualquier archivo estático real que exista (p. ej. `/assets/css/*`)
  antes de aplicar el rewrite, así que los assets no pasan por PHP.
- Antes de fijar una versión más nueva de `vercel-php@X.Y.Z`, revisa el
  changelog en github.com/vercel-community/php — el runtime sube de
  versión con cierta frecuencia y soporta PHP 7.4–8.5.

**Variables de entorno (Vercel → Settings → Environment Variables,
configuradas en Production y Preview):**

| Variable | Qué es | Cómo se generó |
|---|---|---|
| `EDITOR_PASSWORD_HASH` | Hash bcrypt de la contraseña del editor | `password_hash($pw, PASSWORD_BCRYPT)` — la contraseña en texto plano nunca se guarda en ningún lado |
| `SESSION_SECRET` | Clave para firmar la cookie de sesión del editor (HMAC-SHA256) | `bin2hex(random_bytes(32))`, generada una vez |
| `GITHUB_TOKEN` | Fine-grained PAT de GitHub, permiso **Contents: Read and write** únicamente sobre `dreiklx/diario-aprendizaje`, sin ningún otro permiso | Creado a mano en github.com/settings/personal-access-tokens (no se puede automatizar por CLI — ver sección 14) |

Todas marcadas como **Sensitive** en Vercel (ocultas incluso en el
dashboard después de guardarlas). Ninguna aparece en el repo, en logs, ni
en el HTML/JS que llega al navegador — verificado con `git grep` y
revisando el HTML servido antes de dar la funcionalidad por terminada.

**⚠️ NUNCA agregues `"cleanUrls": true`.** Se probó en un despliegue real
y rompe el sitio por completo (404 en todas las rutas). Motivo: cleanUrls
elimina la extensión `.php` de la ruta pública real de la función (el
endpoint pasa de `/api/index.php` a `/api`), pero el `rewrites` sigue
apuntando al destino viejo `/api/index.php`, que ya no existe → 404 en
todo excepto los assets estáticos. No lo necesitamos: nuestro propio
router ya produce URLs limpias (`/semana/3`, `/curso`).

**Proyecto ya vinculado y desplegado:** este repo está enlazado a
`xdhola439-4877s-projects/diario-aprendizaje` en Vercel (carpeta
`.vercel/`, ignorada por Git). Producción:
**https://diario-aprendizaje.vercel.app**

**⚠️ NO CAMBIAR ESTE DOMINIO.** La profesora ya tiene el enlace. Nunca
crees un proyecto nuevo, ni cambies el nombre del proyecto, ni lo
desconectes/reconectes a un repo distinto — todo eso puede alterar el
alias `diario-aprendizaje.vercel.app`. `vercel git connect` a este mismo
`projectId` es seguro (no cambia el dominio, verificado en esta sesión);
crear un proyecto nuevo NO lo es.

**Despliegue: Git es la fuente de verdad, no la CLI.** Desde esta
sesión, el proyecto está conectado a
**github.com/dreiklx/diario-aprendizaje** (repo público, rama `master`).
Cualquier push a `master` dispara automáticamente un nuevo deployment de
producción en el mismo dominio — así es como el editor privado (sección
14) publica los cambios, y así debería hacerse el trabajo manual también
de ahora en adelante:

```
git push origin master   # esto ya despliega solo — no hace falta "vercel --prod" después
```

`vercel deploy` / `vercel --prod` (subida directa desde la CLI, sin
pasar por Git) siguen funcionando como método alternativo si hiciera
falta, pero **no los uses como flujo normal**: crean una deployment que
no corresponde a ningún commit, así que el historial de Git y lo que
está en producción se desincronizan. Si alguna vez lo hacés, andá
seguido con un commit real a `master` para que vuelvan a coincidir.

- `vercel build` **falla en Windows local** con
  `EPERM: operation not permitted, symlink ...@now/build-utils` — el
  paso de instalación del builder `vercel-php` intenta crear un symlink
  y Windows lo bloquea sin modo desarrollador/permisos de administrador.
  No es un problema del proyecto: en el propio servidor de build de
  Vercel (Linux) no ocurre. Para validar el build sin desplegar en
  Windows, no hay alternativa local fiable — usa `vercel deploy` (crea
  un deployment real, builder corre en Linux) en vez de `vercel build`.
- No hay variables de entorno que configurar — el sitio no tiene
  secretos.
- El primer `vercel deploy` de un proyecto nuevo se promueve
  automáticamente a producción aunque no se pase `--prod` (comportamiento
  de la plataforma, no un flag nuestro). Los despliegues siguientes sí
  son preview a menos que se use `--prod` explícitamente.
- Los deployments de preview tienen Vercel Deployment Protection (SSO)
  activado por defecto — un `curl` a una URL de preview da 302 hacia
  `vercel.com/sso-api`. Es esperado; para probarlos hay que abrirlos
  autenticado en el navegador, o desactivar la protección desde el
  dashboard del proyecto si se quiere acceso público a los previews.

## 11. Errores conocidos y notas de entorno

- **Chrome headless en Windows, ventanas <~500px de ancho:** en captura
  de pantalla vía `chrome --headless --screenshot --window-size=W,H`,
  con `W` por debajo de ~500px, el texto largo se corta a mitad de
  palabra de forma consistente — incluso en HTML sin ningún CSS. Se
  verificó con una página mínima sin hojas de estilo: el mismo corte
  aparece en 320/375/390/414px y desaparece a partir de 600px. Es un
  bug de medición de texto del propio headless Chrome en esa
  configuración, no un bug de layout del sitio. No lo "arregles" en el
  CSS del sitio si vuelve a aparecer — confírmalo primero con una página
  de prueba sin CSS antes de tocar `assets/css/*`.
- **`php -S` con router script y archivos estáticos:** ver sección 8.
  Si `/assets/*` da 404 en local, es casi seguro que se está usando
  `api/index.php` como entrypoint directo en vez de `dev-router.php`.
- **`render_page()` debe hacer `extract($data)`:** si una plantilla de
  layout o un partial reporta "Undefined variable", casi siempre es
  porque se llamó a `render_partial()`/`render_page()` sin pasar la
  variable en el array `$data`, no un problema del motor de plantillas.
- **`.timeline` usa `grid-template-areas`, nunca posicionamiento
  absoluto.** Se intentó un hilo vertical con nodos posicionados
  `position: absolute` sobre una línea `::before` y la matemática de
  márgenes anidados no cuadró (el nodo quedaba desalineado ~1rem de la
  línea). Se descartó por otro enfoque robusto: grid-area para node/
  index/body/status, igual que el resto del sitio — sin coordenadas
  calculadas a mano. Si en el futuro se quiere retomar la idea del hilo
  continuo, prototipar visualmente primero (con capturas reales), no a
  ciegas.
- **Columnas de ancho fijo + números grandes = riesgo de wrap.**
  `.timeline__index` (el número "01", "02"…) se parte en dos líneas si
  la columna de grid es más angosta que el ancho real del glyph en
  Fraunces a `--text-3xl` (pasó con `3.5rem`, se corrigió a `5.5rem` +
  `white-space: nowrap`). Cualquier número/cifra grande en una columna
  de grid con ancho fijo necesita ese mismo colchón + nowrap.
- **Fine-grained PAT de GitHub con "Contents: Read-only" en vez de "Read
  and write":** el síntoma es un 403 de la API con el mensaje literal
  `"Resource not accessible by personal access token"` — la lectura
  (GET) funciona perfecto (por eso el formulario de edición carga bien),
  solo la escritura (PUT) falla. La respuesta trae la cabecera
  `x-accepted-github-permissions: contents=write` que confirma el
  diagnóstico. La causa casi siempre es que, al crear el token en
  github.com/settings/personal-access-tokens/new, el dropdown de
  "Contents" bajo "Repository permissions" quedó en su valor por
  defecto (Read-only o No access) en vez de elegir explícitamente
  "Read and write". Hubo que recrear el token dos veces en esta sesión
  antes de acertar — revisá la pantalla de confirmación de GitHub
  (lista los permisos otorgados) antes de dar el token por bueno.
- **⚠️ Nunca pruebes una escritura contra la API de GitHub apuntando
  directamente al path real de `entries.php` con contenido de prueba.**
  En esta sesión, un PUT de diagnóstico con contenido `"x"` se mandó
  por error a `api/data/entries.php` en vez de a un archivo descartable,
  reemplazando el archivo real por un solo carácter durante ~20
  segundos hasta que se detectó y se restauró (el commit "x" y el
  commit de restauración quedan visibles en el historial de Git, a
  propósito — no se reescribió el historial). Vercel llegó a desplegar
  esa versión rota brevemente. Para probar la API de GitHub a mano,
  usá un archivo de prueba fuera de `api/`, o mejor, probá siempre a
  través del propio flujo de la app (`/editar/semana/N`), que nunca
  reemplaza el archivo completo — solo el campo editado (ver sección 14).
- **Un atributo `hidden` no gana contra tu propia clase si le pusiste
  `display` a esa clase.** Pasó con `.editor-draft-banner { display: flex }`:
  el elemento seguía visible con `hidden` puesto, porque un selector de
  clase de autor y el `[hidden] { display: none }` de la hoja de estilos
  del navegador tienen la MISMA especificidad, y el de autor va después
  en la cascada — gana. Arreglo: agregar siempre
  `.tu-clase[hidden] { display: none; }` explícito junto a cualquier
  clase que le dé `display` a un elemento que también se oculta con el
  atributo `hidden`. Ya está hecho para `.editor-draft-banner` y
  `.editor-pane--*`; si agregás otro elemento con este patrón, no te
  olvides de la misma regla.
- **`display: contents` para que un `<a>` que envuelve varios elementos
  de un grid no le rompa el layout al grid.** El timeline necesitaba que
  index+nodo+título fueran un solo enlace clicable pero también
  ocuparan grid-areas independientes de `.timeline__row` — un `<a>` con
  su propia caja no puede hacer eso (sus hijos quedarían anidados un
  nivel más adentro del grid). Solución: `.timeline__link { display:
  contents; }` — el `<a>` deja de generar caja propia, sus hijos pasan a
  participar directo del grid del padre, y sigue funcionando como enlace
  real (clic, foco, lector de pantalla) porque `display: contents` no
  quita la semántica del elemento, solo su caja. `:hover`/`:focus-visible`
  sobre un elemento así siguen funcionando con normalidad (el estado
  seudo-clase no depende de tener una caja). Mismo patrón se puede
  reusar si hace falta un enlace "envolvente" dentro de otro grid/flex
  en el futuro — por ejemplo, es exactamente el mismo problema que
  resolvió el badge + enlace "Editar" al lado del link principal del
  timeline (sección 14-bis, sin anidar un `<a>` dentro de otro `<a>`,
  que es HTML inválido).
- **⚠️ El campo oculto `#blocks-json` tiene que sincronizarse UNA VEZ al
  cargar la página, no solo cuando el usuario edita algo.** Bug real,
  encontrado probando el flujo completo contra producción: `editor.js`
  solo escribía en `#blocks-json` desde `onStateChanged()`, disparado
  por los listeners de cada campo — así que si alguien abría una entrada
  YA con contenido y apretaba "Guardar y publicar" sin tocar nada (por
  ejemplo, para revisar que se ve bien y guardar igual), el campo oculto
  viajaba vacío y `sanitize_blocks_input([])` borraba todos los bloques
  en silencio. Arreglo: `syncBlocksJson()` se llama una vez explícita en
  el arranque (junto a `renderBlocks()`), no solo dentro de
  `onStateChanged()`. Lección general: cualquier campo oculto que un JS
  arma a partir de un estado en memoria necesita sincronizarse en el
  arranque, no asumir que el primer `input` del usuario lo va a poblar.

## 12. Convenciones de nombres

- Archivos PHP y CSS: `kebab-case` (`status-badge.php`, `not-found.php`).
- Clases CSS: BEM-ish, ver sección 7.
- Claves de arrays de datos: `snake_case` en inglés técnico
  (`semester_start`, `week_start`, `class_date`) pero valores de
  contenido en español (es el idioma del sitio).
- Rutas: en español, en minúsculas (`/semana/3`, `/curso`).

## 13. Decisiones que no deben romperse (resumen)

1. PHP puro, sin framework, sin base de datos. La lectura pública sigue
   sin login; el único login que existe es el del editor privado en
   `/editar`, pedido explícitamente por el usuario. (No es un mínimo
   viable a "mejorar" después — ni el sitio público ni el editor deben
   crecer más allá de lo pedido.)
2. Todo el código y datos PHP vive bajo `/api` — nunca fuera, o queda
   expuesto como texto plano en producción.
3. `dev-router.php` es solo para desarrollo local; `api/index.php` sigue
   siendo el entrypoint de producción declarado en `vercel.json`.
4. Una sola fuente de verdad para fechas (`entries.php`) y para estado /
   progreso (funciones puras en `lib/entries.php`) — nunca valores
   escritos a mano en una plantilla.
5. Dos familias tipográficas (Fraunces + Inter), paleta en variables,
   timeline como componente central del diario.
6. `week_start` (lunes) y `class_date` (miércoles) son fechas distintas
   a propósito — no las colapses en un solo campo `date`.
7. El modo oscuro es un sistema de tokens completo (sección 6-bis), no
   un `filter: invert()` ni overrides sueltos — cualquier color nuevo se
   declara en los tres bloques (`:root`, `prefers-color-scheme`,
   `[data-theme="dark"]`).
8. El editor privado (sección 14) solo puede modificar título, tema y
   bloques de una entrada en `api/data/entries.php`, nunca otro archivo.
   La contraseña vive solo como hash (`EDITOR_PASSWORD_HASH`), nunca en
   texto plano en ningún archivo ni commit. El dominio de producción
   (`diario-aprendizaje.vercel.app`) no debe cambiar nunca — conectar
   Git a este mismo proyecto es seguro; crear un proyecto nuevo no lo es.
9. El contenido de una entrada es una lista de `blocks` tipados
   (sección 4-bis), no cuatro campos fijos. Cualquier tipo de bloque
   nuevo se renderiza SIEMPRE escapando primero y agregando etiquetas
   fijas después (`render_inline_markup()` en `api/lib/blocks.php`) —
   nunca una ruta que imprima HTML/Markdown de un usuario sin pasar por
   ahí. El renderer de vista previa en `editor.js` es un espejo del de
   PHP por UX, no por seguridad — la autoridad siempre es PHP.
10. Los botones "+ Agregar"/"Editar" de cada semana viven en el sitio
    público (`/`, `/semana/N`) a propósito — no solo dentro de
    `/editar` — para que el autor no tenga que recordar esa ruta
    (pedido explícito). Son visibles para cualquier visitante, pero no
    son la barrera de seguridad: la contraseña sigue siendo la única
    forma real de editar algo.

## 14. Editor privado (`/editar`)

Permite editar el contenido de una entrada semanal desde el navegador,
sin tocar código, con el cambio llegando realmente al repositorio y al
sitio en producción. Construido en esta sesión; ver la retrospectiva de
por qué esta arquitectura y no otra en la sección "Si la arquitectura
propuesta no es posible" más abajo.

### Flujo completo

```
Usuario entra a /editar
  → sin cookie válida: formulario de contraseña
  → password_verify() contra EDITOR_PASSWORD_HASH (server-side, bcrypt)
  → correcta: cookie firmada (HMAC-SHA256, 4h) → lista de semanas
Click en "+ Agregar"/"Editar" en / o /semana/N (sin sesión)
  → /editar?next=/editar/semana/N → login → aterriza directo en esa semana
    (sanitize_editor_next_path() solo acepta rutas propias de /editar)
Usuario abre /editar/semana/N
  → GET a la API de contenidos de GitHub: contenido + sha ACTUALES de
    entries.php (no la copia local empaquetada en el deployment)
  → editor de bloques prellenado con title/theme/blocks de esa entrada,
    más un input oculto con el sha capturado en este momento
Usuario construye la reflexión en el editor visual (ver 14-bis) y guarda (POST)
  → valida CSRF (token derivado de la propia cookie de sesión)
  → decodifica blocks_json y lo vuelve a validar por completo server-side
    (sanitize_blocks_input() — nunca confía en lo que mandó el navegador)
  → vuelve a traer el archivo completo de GitHub (versión más reciente)
  → aplica título/tema/bloques SOLO de la semana N sobre esa copia
  → PUT a GitHub con el sha capturado al abrir el formulario
      → si nadie más tocó el archivo mientras tanto: 200, commit real
      → si alguien sí lo tocó: 409, y se muestra el conflicto en vez
        de sobrescribir en silencio
  → GitHub dispara su webhook a Vercel (repo conectado, sección 10)
  → Vercel construye y despliega — mismo projectId, mismo dominio
  → la página confirma el commit y editor.js sondea /semana/N cada
    3s hasta ver el contenido nuevo publicado (ver 14-bis, "honestidad
    del estado de publicación") o hasta ~75s, lo que pase primero
```

No hay `session_start()` de PHP en ningún lado: una función serverless
de Vercel no garantiza el mismo disco/proceso entre peticiones, así que
las sesiones basadas en archivos no son fiables ahí. La sesión es
enteramente la cookie firmada — ver `api/lib/auth.php`.

### Piezas, una por archivo

- **`api/lib/auth.php`** — `verify_editor_password()` (bcrypt),
  `issue_editor_session_token()` / `is_editor_authenticated()` (cookie
  HMAC-SHA256, payload `{exp}`, TTL 4h), `set_editor_cookie()` /
  `clear_editor_cookie()` (HttpOnly, SameSite=Strict, Secure solo si la
  petición ya es HTTPS — así funciona igual en local sobre `http://` y
  en producción sobre `https://`), `editor_csrf_token()` /
  `verify_editor_csrf()` (token derivado de la cookie con HMAC, sin
  almacenamiento aparte).
- **`api/lib/github.php`** — cliente mínimo de la API de contenidos de
  GitHub vía `file_get_contents()` + `stream_context_create()` (sin
  curl, para no depender de esa extensión). Dos funciones:
  `github_get_entries_file()` (GET, devuelve contenido + sha) y
  `github_update_entries_file()` (PUT condicionado al sha, para el
  control de concurrencia). La ruta del archivo (`GITHUB_ENTRIES_PATH`)
  y el repo (`GITHUB_REPO`) son constantes fijas en el código — el
  cliente no acepta ninguna ruta que venga del navegador, así que no
  hay forma de que el editor toque otro archivo aunque alguien lo
  intentara.
- **`api/lib/blocks.php`** — modelo de contenido, renderer y
  sanitización de bloques. Ver sección 4-bis; lo usan tanto la página
  pública (`week.php`) como el editor (validación al guardar).
- **`api/lib/entries_editor.php`** — lee y reescribe `entries.php` de
  forma quirúrgica: separa todo lo que hay antes de `return [` (el
  docblock, tal cual) del cuerpo del arreglo, y regenera solo el cuerpo
  en el mismo estilo escrito a mano en el resto del proyecto (comillas
  simples para campos cortos, dobles con `\n` literal para los de texto
  largo — nunca `var_export()`, que destruiría el formato). El contenido
  fresco de GitHub se interpreta con `eval()`; ver la nota de seguridad
  en el docblock del archivo sobre por qué es seguro en este caso
  concreto (mismo nivel de confianza que un `require` normal, porque
  ese archivo solo se puede escribir a través de este mismo editor).
- **`api/lib/editor_actions.php`** — controladores de las rutas.
  Reciben `$course` explícito (nunca `$GLOBALS`), renderizan su propia
  respuesta y terminan la petición. `handle_editor_week_save()` decodifica
  `blocks_json`, lo pasa por `sanitize_blocks_input()` y recién ahí lo
  usa — nunca confía en la forma que mandó el navegador.
- **`api/templates/pages/editor-*.php` + `assets/css/editor.css` +
  `assets/js/editor.js`** — vista. Reutiliza los mismos componentes/
  tokens del sitio público (`status-badge`, tipografía, modo claro/
  oscuro, incluso las clases `.block-*` del renderer PHP para que la
  vista previa se vea igual); `editor.css`/`editor.js` se cargan solo
  cuando `layout.php` recibe `'private' => true` (headers
  `X-Robots-Tag: noindex` y `Cache-Control: no-store` también van ahí).

### Qué puede y qué no puede modificar

Desde el panel se edita `title`, `theme` y `blocks` de UNA entrada.
**No** son editables desde el panel: `week`, `week_start`, `class_date`
(son el modelo de calendario, no contenido de reflexión — cambiarlos
ahí podría romper el cálculo de estado). Ningún otro archivo del
repositorio es alcanzable desde el editor — `github.php` tiene la ruta
del archivo fija en código, no la recibe del navegador.

### Medidas de seguridad implementadas

- Contraseña nunca en texto plano: solo su hash bcrypt
  (`EDITOR_PASSWORD_HASH`), comparado con `password_verify()`
  (timing-safe por diseño).
- Comparaciones de firmas/tokens con `hash_equals()` (timing-safe),
  nunca `===` sobre secretos.
- Cookie de sesión `HttpOnly` (inaccesible desde JS) + `SameSite=Strict`
  (no viaja en peticiones cross-site) + `Secure` en producción.
- CSRF: token derivado de la cookie de sesión, verificado en el POST de
  guardado.
- Freno de fuerza bruta mínimo: `usleep(400_000)` tras un intento
  fallido, sumado al costo intrínseco de bcrypt (~100-300ms). No hay
  bloqueo por IP ni contador de intentos — necesitaría un almacén de
  estado que el proyecto deliberadamente no tiene. Aceptado como
  suficiente para un diario personal de una sola persona, no para un
  objetivo de alto valor.
- Todo el texto que entra por el editor pasa por `e()` al mostrarse en
  el sitio público — el mismo mecanismo de escape que ya usa cualquier
  otro contenido, no hay una ruta de salida nueva sin escapar. El
  contenido enriquecido (negrita/cursiva/destacado/enlaces) nunca es
  HTML enviado por el navegador: es marcado propio, mínimo, procesado
  por `render_inline_markup()` en el servidor (ver sección 4-bis) — el
  editor no tiene ninguna ruta que guarde HTML arbitrario.
- `sanitize_blocks_input()` vuelve a validar cada bloque server-side
  (tipo, longitud, URLs) sin importar lo que mande `blocks_json` —
  nunca se confía en la forma que asumió el JavaScript del navegador.
- El token de GitHub tiene permiso mínimo: *fine-grained*, restringido
  a un único repo, solo `Contents: Read and write` — no puede tocar
  otros repos, Actions, Issues, ni configuración del repo.
- `GITHUB_TOKEN`, `EDITOR_PASSWORD_HASH` y `SESSION_SECRET` viven solo
  como variables de entorno "Sensitive" en Vercel — nunca en el repo,
  nunca en el HTML/JS servido (verificado con `git grep` y revisando el
  HTML antes de dar la tarea por terminada), nunca en un mensaje de
  error mostrado al usuario (los `catch` de `editor_actions.php`
  muestran el código de estado HTTP de GitHub, nunca la respuesta cruda
  completa ni el token).

### Cómo probar el editor localmente

`php -S localhost:8000 dev-router.php` no tiene acceso a las variables
de entorno de Vercel — hay que exportarlas en la misma sesión de shell
antes de levantar el servidor:

```bash
export EDITOR_PASSWORD_HASH='<hash bcrypt real>'
export SESSION_SECRET='<64 hex chars>'
export GITHUB_TOKEN='<fine-grained PAT>'
php -S localhost:8000 dev-router.php
```

Sin `GITHUB_TOKEN` (o con uno inválido/sin permiso de escritura), el
login y la navegación funcionan igual — solo falla, con un mensaje
claro y sin traza de error de PHP, la carga/guardado del contenido de
una semana. Es una forma útil de probar el manejo de errores sin tocar
GitHub.

Para una prueba real de guardado, hacé el ciclo completo edición →
verificación → reversión (así se probó en esta sesión, dos veces: una
vez para los campos simples, otra para el modelo de bloques):
1. Cargá `/editar/semana/N`, anotá el `sha` que trae el formulario.
2. Guardá un valor de prueba claramente marcado como tal.
3. Verificá con un GET directo a la API de GitHub (pedí
   `Accept: application/vnd.github.raw+json` para traer el archivo
   crudo, no en base64) que **solo** cambió el campo esperado (`diff`
   contra el archivo local sin tocar).
4. Esperá el redeploy (unos 15-30s) y confirmá en producción.
5. Volvé a `/editar/semana/N` (el `sha` ya cambió, el formulario trae
   el nuevo automáticamente) y guardá el valor original para revertir.
6. Confirmá otra vez con `diff` que quedó byte a byte igual que antes.

**⚠️ Nunca improvises un PUT de prueba directo a la API de GitHub
apuntando a `api/data/entries.php` con contenido descartable ("x", "test",
etc.) — en la sesión anterior eso reemplazó el archivo real por un
único carácter durante ~20 segundos hasta que se detectó y se restauró
(ver sección 11). Si necesitás probar la API de GitHub a mano (no a
través de la app), primero hacé un GET, usá el `sha` real, y mandá el
contenido real con solo el cambio puntual — o mejor, probá siempre a
través de `/editar/semana/N`, que nunca reemplaza el archivo completo.

Para probar la parte de JavaScript (agregar/mover/duplicar/borrar
bloques, toolbar, tabs Editar/Vista previa) sin arriesgar nada contra
GitHub: no hace falta `GITHUB_TOKEN` válido para esto — todo pasa en el
navegador hasta que se aprieta "Guardar y publicar". Si tenés Chrome
instalado, `puppeteer-core` (`npm install --no-save puppeteer-core`,
apuntando a `executablePath` del Chrome real, sin descargar un Chromium
aparte) permite automatizar clics/tipeo reales para probar esto — se
usó así en esta sesión y después se borró (`node_modules`,
`puppeteer-core` y los scripts de prueba no forman parte del proyecto,
nunca los commitees).

### Rotar o cambiar secretos

```bash
# Contraseña nueva:
php -r "echo password_hash('nueva-contraseña', PASSWORD_BCRYPT);"
vercel env rm EDITOR_PASSWORD_HASH production --yes
vercel env add EDITOR_PASSWORD_HASH production   # pegar el hash cuando lo pida

# Token de GitHub nuevo (si se filtró o expiró):
# 1. Revocarlo en github.com/settings/personal-access-tokens
# 2. Crear uno nuevo con el mismo permiso mínimo (Contents: Read and write,
#    solo dreiklx/diario-aprendizaje)
vercel env rm GITHUB_TOKEN production --yes
vercel env add GITHUB_TOKEN production
```

Repetir con `preview` en vez de `production` para el otro entorno.
Después de rotar, no hace falta redesplegar a mano — el próximo push (o
el próximo uso del editor) ya usa el valor nuevo, porque Vercel inyecta
las variables de entorno en cada invocación de la función, no al build.

### Decisión: GitHub API + Git conectado, no un sistema paralelo

El pedido original era `PHP → GitHub API → commit → Vercel`. Esa
arquitectura resultó totalmente viable, pero necesitó un paso previo
que no existía: **el proyecto no estaba conectado a ningún repositorio
de Git** (los despliegues hasta ahora se habían hecho subiendo archivos
directo con `vercel deploy`, sin Git de por medio — confirmado
revisando `.vercel/project.json` y el deployment en producción, que no
tenía ninguna fuente de Git asociada). Se resolvió así, en este orden:

1. `gh repo create diario-aprendizaje --public --source=.` — repo
   nuevo, público (decisión del usuario), bajo su cuenta de GitHub ya
   autenticada localmente.
2. `git push -u origin master`.
3. `vercel git connect https://github.com/dreiklx/diario-aprendizaje.git`
   — conecta el repo al **proyecto existente** (mismo `projectId`, no
   uno nuevo). Se verificó explícitamente con `vercel inspect` antes y
   después que el alias de producción no cambió.
4. Se confirmó que un push normal a `master` dispara un deployment de
   producción automático (Vercel ya tiene su GitHub App con acceso al
   repo desde el paso 1, al ser el dueño de ambos).

No se creó ningún sistema paralelo de despliegue (nada de subir
archivos manualmente vía la API de Vercel) — el editor solo hace un
commit a GitHub; el redeploy es 100% el mecanismo nativo de Vercel.

## 14-bis. Editor de bloques (interfaz)

`assets/js/editor.js` + `assets/css/editor.css` +
`api/templates/pages/editor-week.php`. Es la pieza más grande de
JavaScript del proyecto — ver sección 2 sobre por qué se aceptó esa
excepción al "JS mínimo".

**Estado y renderizado:** un único objeto `state.blocks` en memoria.
Escribir en un `<textarea>`/`<input>` de un bloque actualiza
`state.blocks[i]` **sin** volver a dibujar el DOM (así no se pierde el
foco/cursor en cada tecla); agregar, mover, duplicar o borrar un bloque
sí vuelve a dibujar todo `#blocks-container` desde `state` — son
acciones discretas de botón, no por tecla, así que redibujar entero es
seguro y más simple que hacer un diff manual. Cada cambio de estado
llama a `onStateChanged()`, que: (1) serializa `state.blocks` al campo
oculto `#blocks-json` (lo único que de verdad viaja al servidor), (2)
actualiza la vista previa (debounced 200ms), (3) programa un
autoguardado local (debounced 1200ms).

**Vista previa — aproximación visual, no la autoridad de seguridad.**
`renderBlockPreview()` en `editor.js` es un espejo en JavaScript de
`render_block_html()`/`render_inline_markup()` en `api/lib/blocks.php`
(mismas clases CSS `.block-*`, para que la vista previa se vea
exactamente como la página pública). Si cambiás qué marcado se admite
o cómo se renderiza un tipo de bloque, **actualizá los dos archivos** —
quedó documentado en el encabezado de `editor.js` para que no se
olvide. La única fuente de verdad para lo que realmente se publica es
siempre el renderer de PHP; el de JS es puramente para que el autor
vea el resultado mientras escribe, nunca se envía al servidor.

**Editar / Vista previa:** en pantallas ≥1024px ambos paneles se ven
lado a lado (CSS grid, sección `.editor-workspace`); por debajo de eso
son pestañas (`#tab-edit`/`#tab-preview`, JS mínimo que alterna
`hidden`). Los estilos de la vista previa (`.week__body` +
`.block-*`) son los mismos que usa `/semana/N` — no hay una hoja de
estilos aparte para la vista previa.

**Toolbar de formato:** cuatro botones (B, I, ◆, 🔗) por cada bloque de
texto, que envuelven la selección actual del `<textarea>` con
`**`/`*`/`==`/`[…](https://)`. Sin selección, insertan la sintaxis con
un placeholder para que el autor escriba encima. Es manipulación directa
de `selectionStart`/`selectionEnd` — no usa `document.execCommand`
(deprecado e inconsistente entre navegadores).

**Borrador local (autoguardado):** `localStorage['diario-editor-draft-week-N']`
guarda `{savedAt, title, theme, blocks}` cada vez que hay un cambio
(debounced). Es **solo** una recuperación ante pérdida accidental
(pestaña cerrada, navegador crasheado) — nunca sustituye al guardado
real. Al cargar la página, si existe un borrador para esa semana se
muestra un banner con "Restaurar"/"Descartar" (nunca se aplica solo).
Se borra automáticamente después de un guardado exitoso.

**Confirmación honesta de publicación (sin token de Vercel):** después
de un guardado exitoso, `pollForPublication()` no asume que "commit en
GitHub" significa "ya está publicado" — sondea la propia
`/semana/N` cada 3s (con `cache: 'no-store'` y un query param de
cache-busting) buscando un `publishCheckMarker` (el título nuevo, o si
no hay título, los primeros ~40 caracteres del primer bloque con
contenido — heurística simple, documentada como tal en
`editor-week.php`) hasta encontrarlo o hasta ~25 intentos (~75s). Así
se evita afirmar "Publicado" cuando Vercel todavía está construyendo,
sin necesitar un token de la API de Vercel (que hubiera sido un secreto
más para gestionar, no pedido explícitamente).

**Redirección tras login (`next`):** los enlaces "+ Agregar"/"Editar"
en `/` y `/semana/N` apuntan directo a `/editar/semana/N`.
`require_editor_auth()` (sin sesión) redirige a
`/editar?next=/editar/semana/N`; el login, al validar la contraseña,
redirige a ese `next` en vez de siempre a la lista de semanas —
`sanitize_editor_next_path()` en `auth.php` solo acepta rutas propias
de `/editar` (regex `^/editar(/semana/\d+)?$`), nunca una URL externa
(protección básica contra open redirect).
