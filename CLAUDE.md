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
- JavaScript vanilla, deliberadamente mínimo (ver `assets/js/main.js`:
  ~20 líneas, solo navegación por teclado entre semanas).
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
    auth.php                 Sesión del editor: cookie firmada (HMAC), sin estado en servidor
    github.php                Cliente mínimo de la API de contenidos de GitHub
    entries_editor.php         Parser/serializador de entries.php (editor privado)
    editor_actions.php          Controladores de /editar (login, guardar, logout)
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
    editor.css                    Solo para /editar — formularios, botones, lista de semanas
  js/main.js                  Toggle de tema, revelado al scroll (IntersectionObserver), navegación por teclado (← →)

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

- **Desde el navegador (recomendado para el día a día):** entrá a
  `/editar`, iniciá sesión, elegí la semana. Guarda un commit real en
  GitHub y redespliega solo. Ver sección 14 para el detalle completo.
- **Editando el archivo a mano:** editá **solo** `api/data/entries.php`.
  Busca el arreglo con el `week` correspondiente y completa los campos
  (`title`, `theme`, `reflexion`, `aprendizaje`, `cuestionamiento`,
  `aplicacion`, `evidencia`). No toques ningún otro archivo.

- El estado (`próxima` / `disponible` / `completada`) se calcula solo:
  en cuanto `reflexion` deja de estar vacío, la entrada pasa a
  `completada`. No hay un campo `status` que se pueda desincronizar.
- El progreso (`X / 15`) se recalcula solo a partir de esa misma lista.
  Nunca escribas un porcentaje o conteo a mano en ninguna plantilla.

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
8. El editor privado (sección 14) solo puede modificar los campos de
   texto de una entrada en `api/data/entries.php`, nunca otro archivo.
   La contraseña vive solo como hash (`EDITOR_PASSWORD_HASH`), nunca en
   texto plano en ningún archivo ni commit. El dominio de producción
   (`diario-aprendizaje.vercel.app`) no debe cambiar nunca — conectar
   Git a este mismo proyecto es seguro; crear un proyecto nuevo no lo es.

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
Usuario abre /editar/semana/N
  → GET a la API de contenidos de GitHub: contenido + sha ACTUALES de
    entries.php (no la copia local empaquetada en el deployment)
  → formulario prellenado con los campos editables de esa entrada,
    más un input oculto con el sha capturado en este momento
Usuario edita y guarda (POST)
  → valida CSRF (token derivado de la propia cookie de sesión)
  → vuelve a traer el archivo completo de GitHub (versión más reciente)
  → aplica SOLO los campos editables de la semana N sobre esa copia
  → PUT a GitHub con el sha capturado al abrir el formulario
      → si nadie más tocó el archivo mientras tanto: 200, commit real
      → si alguien sí lo tocó: 409, y se muestra el conflicto en vez
        de sobrescribir en silencio
  → GitHub dispara su webhook a Vercel (repo conectado, sección 10)
  → Vercel construye y despliega — mismo projectId, mismo dominio
  → la página de guardado confirma "Cambios enviados correctamente"
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
  respuesta y terminan la petición.
- **`api/templates/pages/editor-*.php` + `assets/css/editor.css`** —
  vista. Reutiliza los mismos componentes/tokens del sitio público
  (`status-badge`, tipografía, modo claro/oscuro); `editor.css` se
  carga solo cuando `layout.php` recibe `'private' => true` (headers
  `X-Robots-Tag: noindex` y `Cache-Control: no-store` también van ahí).

### Qué puede y qué no puede modificar

Campos editables de una entrada (`EDITABLE_ENTRY_FIELDS` en
`entries_editor.php`): `title`, `theme`, `reflexion`, `aprendizaje`,
`cuestionamiento`, `aplicacion`. **No** son editables desde el panel:
`week`, `week_start`, `class_date` (son el modelo de calendario, no
contenido de reflexión — cambiarlos ahí podría romper el cálculo de
estado) ni `evidencia` (fuera de alcance, no pedido). Ningún otro
archivo del repositorio es alcanzable desde el editor.

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
  otro contenido, no hay una ruta de salida nueva sin escapar.
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
verificación → reversión (así se probó en esta sesión):
1. Cargá `/editar/semana/N`, anotá el `sha` que trae el formulario.
2. Guardá un valor de prueba claramente marcado como tal.
3. Verificá con un GET directo a la API de GitHub que **solo** cambió
   el campo esperado (`diff` contra el archivo local sin tocar).
4. Esperá el redeploy (unos 15-30s) y confirmá en producción.
5. Volvé a `/editar/semana/N` (el `sha` ya cambió, el formulario trae
   el nuevo automáticamente) y guardá el valor original para revertir.
6. Confirmá otra vez con `diff` que quedó byte a byte igual que antes.

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
