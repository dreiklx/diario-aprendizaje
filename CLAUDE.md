# CLAUDE.md

Memoria permanente del proyecto. Léelo antes de tocar código: explica por
qué el proyecto está hecho como está, qué reglas no deben romperse, y
cómo hacer las tareas más comunes (agregar una entrada, ejecutar
localmente, desplegar).

## 1. Qué es esto

Diario de Aprendizaje digital para el curso **SR-0022 Seminario de
Realidad Nacional II — "Producción y Desarrollo"**, Universidad de Costa
Rica, Sede del Caribe, II Ciclo 2026. Es un sitio editorial de una sola
persona (sin login, sin comentarios, sin base de datos) que documenta 15
entradas semanales a lo largo del semestre.

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
  (`api/data/*.php`).
- Sin autenticación, sin panel admin, sin CMS.

Si en algún momento sientes la tentación de añadir cualquiera de estas
cosas para "mejorar" el proyecto: no lo hagas sin que el usuario lo pida
explícitamente. Es una decisión de producto, no un descuido.

## 3. Arquitectura

```
api/
  index.php              Front controller único (la ÚNICA función de Vercel)
  lib/
    router.php            Resuelve REQUEST_URI -> {page, params}
    entries.php            Lógica de negocio: estado, progreso, acceso a datos
    dates.php               Formato y comparación de fechas (ES)
    render.php               Motor de plantillas mínimo (extract + include + ob_*)
  data/
    course.php              Metadatos del curso (única fuente de verdad)
    entries.php              Las 15 entradas del diario (única fuente de verdad)
  templates/
    layout.php               <html> completo: head, nav, <main>, footer, scripts
    partials/                 nav, footer, progress, status-badge, timeline
    pages/                     home, week, course, not-found

assets/
  css/
    tokens.css                Variables: color, tipografía, espaciado, radios, motion
    base.css                   Reset + estilos de elementos base + a11y
    layout.css                  Nav, footer, contenedores, disposición de secciones
    components.css               Badge, progress bar, timeline, artículo de semana, pager
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

Edita **solo** `api/data/entries.php`. Busca el arreglo con el `week`
correspondiente y completa los campos (`title`, `theme`, `reflexion`,
`aprendizaje`, `cuestionamiento`, `aplicacion`, `evidencia`). No toques
ningún otro archivo.

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

```
vercel               # deployment de preview
vercel --prod         # producción (requiere confirmación explícita del usuario)
```

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

## 12. Convenciones de nombres

- Archivos PHP y CSS: `kebab-case` (`status-badge.php`, `not-found.php`).
- Clases CSS: BEM-ish, ver sección 7.
- Claves de arrays de datos: `snake_case` en inglés técnico
  (`semester_start`, `week_start`, `class_date`) pero valores de
  contenido en español (es el idioma del sitio).
- Rutas: en español, en minúsculas (`/semana/3`, `/curso`).

## 13. Decisiones que no deben romperse (resumen)

1. PHP puro, sin framework, sin base de datos, sin login. (Pedido
   explícito del usuario — no es un mínimo viable a "mejorar" después.)
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
