# Referencia Rápida de Símbolos y Notaciones ERD

## Notación Patas de Gallo (Crow's Foot) — Estándar Recomendado

Esta es la notación más usada en la industria y la recomendada para este proyecto.

```
SÍMBOLO          SIGNIFICADO
──||──           Exactamente uno (obligatorio)
──|o──           Cero o uno (opcional)
──||──<          Uno a muchos (obligatorio)
──|o──<          Uno a muchos (opcional)
──><──           Muchos a muchos
──o──<           Cero o muchos
```

### Tabla de Referencia Visual Completa

| Símbolo Izquierdo | Símbolo Derecho | Cardinalidad | Descripción |
|-------------------|-----------------|-------------|-------------|
| `|` | `|` | 1..1 | Exactamente uno a exactamente uno |
| `|` | `o` | 1..0 | Uno a cero o uno |
| `|` | `<` | 1..∞ | Uno a uno o más |
| `o` | `<` | 0..∞ | Cero o uno a cero o más |
| `>` | `<` | ∞..∞ | Muchos a muchos |

---

## Notación de Chen (Clásica)

```
[RECTÁNGULO]         → Entidad
(ÓVALO)              → Atributo
<ROMBO>              → Relación
─────────────        → Línea de conexión
(ÓVALO DOBLE)        → Atributo multivaluado
((ENTIDAD DOBLE))    → Entidad débil
<ROMBO DOBLE>        → Relación débil (identificadora)
```

### Líneas de Cardinalidad en Chen
```
── 1 ──           Exactamente uno
── N ──           Muchos
── M ──           Muchos (segundo lado en N:M)
```

---

## Notación IDEF1X

Usada en herramientas de modelado profesional (ERwin, etc.):
```
──|──            Cardinalidad 1 (obligatorio)
──o──            Cardinalidad 0 (opcional)
──|─P──          Uno o más (obligatorio+)
─────            Relación identificadora (línea sólida)
─ ─ ─            Relación no-identificadora (línea punteada)
```

---

## Equivalencia Eloquent ↔ ERD

| Laravel Eloquent | Cardinalidad ERD | Ejemplo |
|-----------------|-----------------|---------|
| `hasOne()` | 1:1 | Usuario → Perfil |
| `belongsTo()` | N:1 | Stock → Almacén |
| `hasMany()` | 1:N | Almacén → Stocks |
| `hasManyThrough()` | 1:N indirecto | Sucursal → Stocks (a través de Almacén) |
| `belongsToMany()` | N:M | Producto → Categorías |
| `morphTo()` | Polimórfica | Comentario → Modelo |
| `morphMany()` | 1:N polimórfica | Kardex → Modelos |

---

## Tipos de Datos PostgreSQL más Usados en el Proyecto

| Columna Laravel | Tipo PostgreSQL | Ejemplo de Uso |
|----------------|----------------|---------------|
| `uuid()` | `UUID` | PKs y FKs del proyecto |
| `string()` | `VARCHAR(255)` | Nombres, estados, áreas |
| `text()` | `TEXT` | Notas, observaciones |
| `decimal(10,4)` | `NUMERIC(10,4)` | Cantidades, costos, precios |
| `boolean()` | `BOOLEAN` | is_active, has_expiration |
| `date()` | `DATE` | Fechas de documentos |
| `timestamps()` | `TIMESTAMP` | created_at, updated_at |
| `softDeletes()` | `TIMESTAMP NULL` | deleted_at |
| `unsignedBigInteger()` | `BIGINT` | Números correlativos |
