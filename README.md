# Diario de Aprendizaje — SR-0022

Diario de aprendizaje digital para el curso **SR-0022 Seminario de
Realidad Nacional II — "Producción y Desarrollo"** (Universidad de Costa
Rica, Sede del Caribe, II Ciclo 2026).

Sitio editorial de una sola persona: 15 entradas semanales, sin login,
sin base de datos, sin panel de administración. Incluye modo claro/oscuro
persistente y revelado progresivo del contenido al hacer scroll.

## Stack

PHP 8.3+ puro (sin frameworks), HTML semántico, CSS propio (sin
Tailwind/Bootstrap), JavaScript vanilla mínimo. Sin Composer, sin
dependencias de Node en producción. Documentación técnica completa y
decisiones de arquitectura en [`CLAUDE.md`](./CLAUDE.md).

## Estructura

```
api/            Front controller, lógica (lib/), datos (data/), plantillas (templates/)
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

Edita `api/data/entries.php`: busca el arreglo con el `week` de la
semana y completa `title`, `theme`, `reflexion`, `aprendizaje`,
`cuestionamiento`, `aplicacion` y, si aplica, `evidencia`. No hace falta
tocar ningún otro archivo — el estado de la entrada (próxima /
disponible / completada) y el progreso general del diario se calculan
automáticamente en cuanto `reflexion` deja de estar vacío.

Cada entrada tiene dos fechas distintas: `week_start` (el lunes en que
arranca la semana académica) y `class_date` (el día real de la clase,
los miércoles en este curso). El estado "disponible" se activa cuando
`class_date` ya pasó. Si el calendario real del curso difiere de las
fechas generadas, ajusta ambos campos de la entrada correspondiente en
el mismo archivo.

## Actualizar los metadatos del curso

`api/data/course.php` — nombre, código, sede, ciclo, fecha de inicio del
semestre, cantidad de semanas y el texto de introducción del diario.

## Desplegar en Vercel

El proyecto usa el runtime comunitario `vercel-php` (la ruta soportada
y documentada por Vercel para PHP, ver `CLAUDE.md` sección 10 para el
detalle de la investigación). Ya está vinculado y desplegado en
**https://diario-aprendizaje.vercel.app**.

```bash
npm i -g vercel
vercel login
vercel          # despliegue de preview
vercel --prod   # producción
```

No hay variables de entorno ni servicios externos que configurar.

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
