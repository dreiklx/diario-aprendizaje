# Diario de Aprendizaje — SR-0022

Diario de aprendizaje digital para el curso **SR-0022 Seminario de
Realidad Nacional II — "Producción y Desarrollo"** (Universidad de Costa
Rica, Sede del Caribe, II Ciclo 2026).

Autor: Derek Farley Noguera (carné C5F012).

Sitio editorial de una sola persona: 15 entradas semanales, sin base de
datos, sin panel de administración genérico. La lectura pública no
requiere login. Incluye modo claro/oscuro persistente, revelado
progresivo del contenido al hacer scroll, y un editor privado en
`/editar` (protegido por contraseña) para actualizar las reflexiones
desde el navegador sin tocar código — ver más abajo.

## Stack

PHP 8.3+ puro (sin frameworks), HTML semántico, CSS propio (sin
Tailwind/Bootstrap), JavaScript vanilla mínimo. Sin Composer, sin
dependencias de Node en producción. Documentación técnica completa y
decisiones de arquitectura en [`CLAUDE.md`](./CLAUDE.md).

## Estructura

```
api/            Front controller, lógica (lib/), datos (data/), plantillas (templates/)
                 incluye el editor privado (lib/auth.php, github.php, editor_actions.php)
assets/         CSS y JS servidos como archivos estáticos
vercel.json     Configuración de despliegue (runtime PHP + rewrite)
dev-router.php  Router solo para el servidor embebido de PHP en local
```

## Ejecutar localmente

Requiere PHP 8.1+ en el PATH.

```bash
php -S localhost:8000 dev-router.php
```

Abre `http://localhost:8000`. No uses `api/index.php` como entrypoint
del servidor embebido directamente — ver `CLAUDE.md` sección 8.

## Agregar o completar una entrada semanal

**Opción 1 — desde el navegador:** entrá a `/editar`, iniciá sesión con
la contraseña del editor, elegí la semana y guardá. El cambio se
convierte en un commit real en GitHub y el sitio se actualiza solo en
menos de un minuto. Ver "Editor privado" más abajo para configurarlo.

**Opción 2 — editando el archivo:** editá `api/data/entries.php`: busca
el arreglo con el `week` de la semana y completa `title`, `theme`,
`reflexion`, `aprendizaje`, `cuestionamiento`, `aplicacion` y, si
aplica, `evidencia`. No hace falta tocar ningún otro archivo — el
estado de la entrada (próxima / disponible / completada) y el progreso
general del diario se calculan automáticamente en cuanto `reflexion`
deja de estar vacío.

Cada entrada tiene dos fechas distintas: `week_start` (el lunes en que
arranca la semana académica) y `class_date` (el día real de la clase,
los miércoles en este curso). El estado "disponible" se activa cuando
`class_date` ya pasó. Si el calendario real del curso difiere de las
fechas generadas, ajusta ambos campos de la entrada correspondiente en
el mismo archivo.

## Actualizar los metadatos del curso

`api/data/course.php` — nombre, código, sede, ciclo, fecha de inicio del
semestre, cantidad de semanas y el texto de introducción del diario.

## Editor privado (`/editar`)

Permite editar el contenido de una entrada desde el navegador; el
cambio se guarda como un commit real en GitHub, que dispara un
despliegue automático en Vercel. Documentación técnica completa —
arquitectura, seguridad, cómo probarlo — en `CLAUDE.md` sección 14.

Variables de entorno necesarias (Vercel → Settings → Environment
Variables → Production y Preview; marcalas como **Sensitive**):

| Variable | Qué poner | Cómo generarlo |
|---|---|---|
| `EDITOR_PASSWORD_HASH` | Hash bcrypt de tu contraseña, **nunca la contraseña en texto plano** | `php -r "echo password_hash('tu-contraseña', PASSWORD_BCRYPT);"` |
| `SESSION_SECRET` | Una cadena aleatoria larga, para firmar la cookie de sesión | `php -r "echo bin2hex(random_bytes(32));"` |
| `GITHUB_TOKEN` | Un *fine-grained personal access token* de GitHub | Creado a mano en github.com/settings/personal-access-tokens/new — ver pasos abajo |

**Cómo crear el `GITHUB_TOKEN` correctamente** (el paso donde es fácil
equivocarse):
1. Entrá a github.com/settings/personal-access-tokens/new.
2. En "Repository access" elegí **Only select repositories** y
   seleccioná el repo de este proyecto.
3. Abrí "Repository permissions" y en la fila **Contents** elegí
   explícitamente **Read and write** (el valor por defecto es
   solo-lectura o ninguno — si lo dejás así, el editor podrá leer las
   entradas pero no guardar, con un error 403 de GitHub).
4. Generá el token y, antes de copiarlo, confirmá en la pantalla de
   resumen que dice "Contents: Read and write".
5. Pegalo como el valor de `GITHUB_TOKEN` en Vercel.

Para desarrollo local, exportá las tres variables en tu shell antes de
levantar el servidor (ver "Ejecutar localmente" arriba):

```bash
export EDITOR_PASSWORD_HASH='...'
export SESSION_SECRET='...'
export GITHUB_TOKEN='...'
php -S localhost:8000 dev-router.php
```

## Desplegar en Vercel

El proyecto usa el runtime comunitario `vercel-php` (la ruta soportada
y documentada por Vercel para PHP, ver `CLAUDE.md` sección 10 para el
detalle de la investigación). Ya está vinculado y desplegado en
**https://diario-aprendizaje.vercel.app** — ese dominio no debe cambiar
nunca (ver `CLAUDE.md` sección 10).

El proyecto está conectado a un repositorio de GitHub
(`dreiklx/diario-aprendizaje`, rama `master`): el flujo normal es
simplemente hacer push.

```bash
git push origin master   # esto ya despliega — Vercel detecta el push solo
```

`vercel deploy` / `vercel --prod` (subida directa sin pasar por Git)
siguen disponibles como alternativa, pero desincronizan el historial de
Git de lo que está realmente en producción — usalos solo si de verdad
hace falta.

⚠️ No agregues `"cleanUrls": true` a `vercel.json` — rompe el ruteo
(ver `CLAUDE.md` sección 10 para el motivo, verificado en un despliegue
real).

## Limitaciones conocidas

- PHP no es un runtime de primera parte en Vercel: se usa un runtime
  comunitario (`vercel-php`) declarado como runtime personalizado en
  `vercel.json`. Es la ruta documentada por Vercel para este caso; ver
  `CLAUDE.md` para alternativas consideradas y por qué se descartaron.
- Sin base de datos: todo el contenido vive en `api/data/*.php` y se
  versiona con Git. Cada actualización de contenido implica un nuevo
  despliegue.
- Las fechas (`week_start`/`class_date`) se generaron como lunes/miércoles
  consecutivos desde el inicio del ciclo definido en `api/data/course.php`;
  si el calendario oficial del curso salta semanas de receso, ajusta las
  fechas manualmente en `api/data/entries.php`.
- El editor privado no tiene protección contra fuerza bruta más allá de
  un pequeño retraso tras un intento fallido — suficiente para un diario
  personal, no para un objetivo de alto valor. Si la contraseña se
  filtra, rotala de inmediato (`CLAUDE.md` sección 14, "Rotar o cambiar
  secretos").
