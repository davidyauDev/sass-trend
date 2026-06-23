# AGENTS.md

## Proyecto

Este proyecto usa Laravel con Livewire.

## Stack oficial

- PHP 8.3+
- Laravel 13
- Livewire 4.1+
- Flux 2
- Pest 4
- Larastan / PHPStan
- Laravel Pint
- Vite / npm

## Prioridad de reglas

Estas instrucciones son obligatorias para Codex.

Si existe conflicto entre este archivo, ejemplos externos, documentación antigua o una Skill externa, seguir este `AGENTS.md`.

Codex debe asumir siempre este stack salvo que el usuario indique explícitamente lo contrario.

## Reglas obligatorias

- Asumir siempre PHP 8.3 o superior.
- Usar características modernas de PHP 8.3 cuando aporten claridad.
- No generar código para PHP 7.x, PHP 8.0, PHP 8.1 o versiones antiguas.
- Usar siempre Laravel 13.
- Usar siempre Livewire 4.1+.
- Nunca usar sintaxis, patrones o ejemplos de Livewire 2 o Livewire 3.
- No usar `$emit()` ni patrones legacy de Livewire.
- Usar `dispatch()` para eventos Livewire.
- Priorizar Livewire, Blade, Flux y Alpine antes que frameworks frontend externos.
- No introducir dependencias nuevas sin justificarlo.

## Estructura esperada

```text
app/
├── Actions/
├── Livewire/
├── Models/
├── Policies/
├── Services/
└── Http/

resources/
├── views/
│   ├── components/
│   │   └── ui/
│   └── livewire/
├── css/
└── js/

routes/
├── web.php
└── auth.php

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
└── Unit/
```

## Estándar de componentes Livewire

Todos los componentes deben seguir estas reglas:

- Usar Livewire 4.1+.
- Usar PHP 8.3+ con tipos explícitos.
- Declarar propiedades públicas con tipo.
- Declarar métodos con tipo de retorno.
- Usar `mount()` solo para inicializar estado.
- Usar `#[Computed]` para datos calculados.
- Usar `#[Validate]` para validaciones simples.
- Usar Form Objects para formularios complejos.
- Usar Actions o Services para lógica de negocio.
- No poner lógica pesada dentro del Blade.
- No crear componentes gigantes.
- Evitar consultas dentro de la vista Blade.
- Evitar consultas N+1 usando eager loading.

## Single File Components

Se permite usar componentes Livewire en el mismo `.blade.php` solo para casos simples:

- Perfil
- Configuración
- Cambiar estado
- Cambiar categoría
- Formularios pequeños
- Modales simples
- Acciones con una sola responsabilidad

Reglas:

- Máximo 3 a 5 campos.
- Máximo 1 responsabilidad principal.
- Máximo 150-200 líneas.
- Si crece más, separar en clase Livewire + vista Blade.

Ejemplo permitido:

```php
<?php

use Livewire\Component;

new class extends Component {
    public string $name = '';

    public function save(): void
    {
        //
    }
};

?>

<div>
    //
</div>
```

## Componentes grandes

Para CRUDs, tablas, filtros, reportes o módulos de negocio usar clase Livewire separada más vista Blade.

Ejemplo:

```text
app/Livewire/Products/Index.php
resources/views/livewire/products/index.blade.php
```

No usar Single File Component para:

- CRUD completo.
- Tablas con filtros.
- Formularios largos.
- Reportes.
- Importaciones.
- Exportaciones.
- Lógica de negocio compleja.

## CRUDs

Cuando se genere un CRUD, considerar crear según necesidad:

- Modelo.
- Migración.
- Factory.
- Seeder si aporta valor.
- Componentes Livewire.
- Actions para lógica de negocio.
- Tests con Pest.
- Policy solo si hay reglas reales de autorización.

No crear archivos innecesarios si el módulo es simple.

Estructura sugerida para CRUDs grandes:

```text
app/
├── Actions/
│   └── Products/
│       ├── CreateProductAction.php
│       ├── UpdateProductAction.php
│       └── DeleteProductAction.php
├── Livewire/
│   └── Products/
│       ├── Index.php
│       ├── Create.php
│       └── Edit.php
└── Models/
    └── Product.php

resources/views/livewire/products/
├── index.blade.php
├── create.blade.php
└── edit.blade.php
```

## Rutas

- Mantener rutas organizadas por módulo.
- Evitar archivos `web.php` gigantes.
- Usar `Route::prefix()` y `Route::name()` cuando el módulo tenga varias rutas.
- Preferir componentes Livewire como páginas.
- No crear controladores para CRUD simples si Livewire resuelve el flujo.

Ejemplo:

```php
use App\Livewire\Products;

Route::prefix('products')
    ->name('products.')
    ->group(function (): void {
        Route::get('/', Products\Index::class)->name('index');
        Route::get('/create', Products\Create::class)->name('create');
        Route::get('/{product}/edit', Products\Edit::class)->name('edit');
    });
```

## Controladores

No crear controladores por defecto.

Usar controladores solo cuando:

- Sea una API.
- Sea un endpoint no interactivo.
- Sea una integración externa.
- Sea una descarga, webhook o callback.
- La solución no encaje bien como página Livewire.

Para páginas interactivas, preferir Livewire.

## Modelos

- Definir siempre `$fillable` o `$guarded`.
- Definir relaciones explícitas con tipo de retorno.
- Usar casts cuando corresponda.
- Evitar lógica pesada en modelos.
- Evitar consultas dentro de Blade.
- Usar scopes para filtros reutilizables.
- Usar nombres claros y convenciones Eloquent.

Ejemplo:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

## Migraciones

- Usar `foreignId()->constrained()` para relaciones.
- Agregar índices cuando sean necesarios.
- No usar campos `nullable()` innecesariamente.
- Usar `timestamps()` salvo que haya razón para no hacerlo.
- Usar nombres de columnas claros.
- Definir restricciones de base de datos cuando aporten integridad.
- Elegir correctamente entre `cascadeOnDelete()`, `nullOnDelete()` o `restrictOnDelete()`.

Ejemplo:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            $table->string('name');

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

## Convención de nombres

Modelos:

```text
Product
Category
Customer
```

Tablas:

```text
products
categories
customers
```

Livewire:

```text
App\Livewire\Products\Index
App\Livewire\Products\Create
App\Livewire\Products\Edit
```

Actions:

```text
App\Actions\Products\CreateProductAction
App\Actions\Products\UpdateProductAction
App\Actions\Products\DeleteProductAction
```

Policies:

```text
App\Policies\ProductPolicy
```

Vistas Livewire:

```text
resources/views/livewire/products/index.blade.php
resources/views/livewire/products/create.blade.php
resources/views/livewire/products/edit.blade.php
```

## Actions y Services

Usar Actions para casos de uso concretos.

Ejemplo:

```text
CreateProductAction
UpdateProductAction
ChangeProductCategoryAction
DeleteProductAction
```

Usar Services cuando exista lógica compartida o integración externa.

Los componentes Livewire deben coordinar la interacción, no contener toda la lógica de negocio.

Ejemplo:

```php
public function save(CreateProductAction $action): void
{
    $action->handle($this->validate());
}
```

## Validaciones

- Usar `#[Validate]` para validaciones simples.
- Usar Form Objects para formularios complejos.
- No duplicar reglas en varios métodos.
- Usar mensajes claros cuando sea necesario.
- Validar siempre datos antes de crear o actualizar modelos.

## Policies

No crear Policies automáticamente para todos los modelos.

Crear Policy únicamente cuando existan reglas de autorización específicas por:

- Usuario.
- Rol.
- Empresa.
- Equipo.
- Propiedad del recurso.
- Estado del recurso.

No crear Policies para catálogos o tablas maestras simples salvo requerimiento explícito.

Ejemplos donde sí puede convenir Policy:

```text
Product
Customer
Invoice
Order
Project
Document
User
```

Ejemplos donde no siempre hace falta Policy:

```text
Category
Country
Currency
Status
Unit
Tag
```

## UI y componentes propios

Si no se usa Flux Pro para un caso, crear componentes propios en:

```text
resources/views/components/ui/
```

Reglas:

- Usar `<x-ui.*>` antes de repetir HTML.
- Usar Tailwind CSS.
- No usar CSS inline salvo casos mínimos.
- Crear componentes reutilizables para botones, inputs, modales, cards, alerts y tablas.
- Mantener compatibilidad con Livewire 4.
- No repetir clases Tailwind largas en muchas vistas.

Ejemplos:

```text
<x-ui.button />
<x-ui.input />
<x-ui.modal />
<x-ui.card />
<x-ui.table />
<x-ui.badge />
<x-ui.alert />
```

## Flux

- Usar Flux cuando esté disponible y aporte consistencia.
- No depender de Flux Pro para componentes que puedan resolverse con componentes propios.
- Mantener UI consistente entre Flux y componentes `<x-ui.*>`.
- No mezclar múltiples sistemas visuales sin necesidad.

## Rendimiento

- Evitar N+1.
- Usar eager loading cuando se muestren relaciones.
- Usar paginación para tablas grandes.
- Usar filtros en query builder, no en colecciones en memoria si la tabla puede crecer.
- No cargar datos innecesarios en `mount()`.
- Usar `#[Computed]` para datos derivados.
- Evitar queries repetidas dentro de bucles.

Ejemplo:

```php
Product::query()
    ->with('category')
    ->latest()
    ->paginate(10);
```

## Testing

- Usar Pest.
- Crear pruebas para funcionalidades importantes.
- Para CRUDs grandes, probar creación, actualización, eliminación y validación.
- No probar detalles internos innecesarios.
- Priorizar tests Feature para flujos Livewire.

## Calidad de código

- Usar Laravel Pint.
- Usar Larastan / PHPStan.
- Mantener métodos pequeños.
- Usar nombres descriptivos.
- No duplicar lógica.
- No duplicar HTML grande.
- Extraer componentes cuando se repita UI.
- No agregar comentarios obvios.
- Comentar solo decisiones importantes o lógica no evidente.

## Comandos del proyecto

Usar los scripts definidos en `composer.json`.

Para desarrollo:

```bash
composer dev
```

Para formatear:

```bash
composer lint
```

Para verificar formato:

```bash
composer lint:check
```

Para análisis estático:

```bash
composer types:check
```

Para pruebas completas:

```bash
composer test
```

Para CI local:

```bash
composer ci:check
```

## Antes de finalizar cambios importantes

Antes de terminar una tarea importante, ejecutar o sugerir:

```bash
composer lint
composer types:check
composer test
```

Si no se ejecutan, explicar brevemente por qué.

## Respuesta esperada de Codex

Cuando Codex proponga cambios:

- Indicar archivos creados o modificados.
- Explicar brevemente la decisión técnica.
- Mencionar comandos de validación usados o recomendados.
- No generar código antiguo de Livewire.
- No introducir arquitectura innecesaria.

## Modales

Usar modales para acciones cortas y enfocadas.

Casos recomendados:

- Crear un registro simple.
- Editar pocos campos.
- Cambiar estado.
- Cambiar categoría.
- Confirmar eliminación.
- Confirmar acciones peligrosas.
- Mostrar detalles rápidos.

No usar modales para:

- Formularios largos.
- CRUDs completos complejos.
- Flujos con muchos pasos.
- Pantallas que necesitan filtros, tablas o navegación propia.
- Formularios con demasiadas validaciones o relaciones.

Reglas:

- Cada modal debe tener una sola responsabilidad.
- El estado del modal debe ser claro y explícito.
- No mezclar varios formularios complejos dentro de un mismo modal.
- Resetear el formulario al cerrar el modal.
- Resetear errores de validación al cerrar el modal.
- Confirmar acciones destructivas.
- Usar nombres claros para métodos: `openCreateModal`, `openEditModal`, `closeModal`, `save`, `delete`.
- Si el modal supera 150-200 líneas o tiene mucha lógica, extraerlo a un componente Livewire separado.
