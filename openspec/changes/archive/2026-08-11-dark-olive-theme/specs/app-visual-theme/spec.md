## RENAMED Requirements

### Requirement: Modo oscuro se preserva íntegro
FROM: Modo oscuro se preserva íntegro
TO: Modo oscuro con identidad olivo

## MODIFIED Requirements

### Requirement: Modo oscuro con identidad olivo

El sistema SHALL aplicar en modo oscuro la identidad olivo de marca: acento verde `#73AC32`, estructura de superficies verde-olivo derivada del negro `#080808`, texto principal crema `#F1EDE2`. La escala `secondary` y las ramas `zinc` del bloque `.dark` de `app.css` MUST resolver a la rampa olivo oscura (body `#151812`, superficies `#262B20`, bordes `#3C4335`, texto secundario `#8A9480`). Las ramas `indigo`/`primary` en modo oscuro MUST resolver a la misma escala verde del modo claro. El modo claro MUST permanecer sin cambios.

#### Scenario: Estructura oscura con tonos olivo

- **WHEN** la app se muestra en modo oscuro y un componente usa `bg-secondary-900`, `bg-secondary-800`, `dark:bg-zinc-800`, `dark:border-secondary-700` o `dark:hover:bg-secondary-700`
- **THEN** los colores MUST resolver a la rampa olivo oscura: body `#151812`, superficies `#262B20`, bordes `#3C4335`, sin tonos azulados slate

#### Scenario: Acento verde en modo oscuro

- **WHEN** la app se muestra en modo oscuro y un componente usa `dark:text-indigo-400`, `dark:bg-indigo-900`, `dark:bg-indigo-500`, `bg-primary-600` o `dark:bg-primary-900`
- **THEN** los colores MUST resolver a la escala verde olivo (`#8DBB52`, `#223C12`, `#73AC32`, `#5C8C28`, `#223C12`), no a índigo

#### Scenario: Texto crema en modo oscuro

- **WHEN** la app se muestra en modo oscuro y un componente usa `dark:text-zinc-100`, `dark:text-zinc-200`, `text-secondary-50` o `text-primary-app`
- **THEN** el texto de alto énfasis MUST resolver a crema `#F1EDE2` y el texto secundario (`dark:text-secondary-400`, `dark:text-zinc-400`) a tonos olivo-claro (`#8A9480`, `#9BA68F`)

#### Scenario: Valores claros no se filtran al modo oscuro

- **WHEN** la app se muestra en modo oscuro
- **THEN** ninguna clase `zinc-*`, `indigo-*` o `primary-*` MUST resolver a los valores cálidos del modo claro (`#FAF9F5`, `#F1EDE2`, etc.)

#### Scenario: Modo claro no se ve afectado

- **WHEN** la app se muestra en modo claro
- **THEN** los colores MUST ser idénticos a los del tema claro actual (cálidos `#FAF9F5`/`#F1EDE2`/`#E5E2D9` y acento `#73AC32`), sin cambios por la redefinición del bloque `.dark`
