const createToastMixin = (storageKey) => ({
    toastTimer: null,
    toast: {
        visible: false,
        type: 'success',
        title: '',
        message: '',
    },
    showToast(type, title, message) {
        this.toast = {
            visible: true,
            type,
            title,
            message,
        };

        window.clearTimeout(this.toastTimer);
        this.toastTimer = window.setTimeout(() => {
            this.toast.visible = false;
        }, 3500);
    },
    persistToast(type, message) {
        window.sessionStorage.setItem(storageKey, JSON.stringify({
            type,
            title: type === 'success' ? 'Listo' : 'Atención',
            message,
        }));
    },
    restoreToast() {
        const raw = window.sessionStorage.getItem(storageKey);

        if (!raw) {
            return;
        }

        window.sessionStorage.removeItem(storageKey);

        try {
            const toast = JSON.parse(raw);
            this.showToast(toast.type ?? 'success', toast.title ?? 'Listo', toast.message ?? '');
        } catch {
            //
        }
    },
});

document.addEventListener('alpine:init', () => {
    Alpine.data('productInventory', (config) => ({
        activeTab: 'basic',
        isOpen: false,
        isEditing: false,
        saving: false,
        errors: {},
        toastTimer: null,
        toast: {
            visible: false,
            type: 'success',
            title: '',
            message: '',
        },
        brands: config.catalogs?.brands ?? [],
        categories: config.catalogs?.categories ?? [],
        presentations: config.catalogs?.presentations ?? [],
        endpoints: config.endpoints ?? {},
        csrf: config.csrf ?? '',
        form: {},
        init() {
            this.form = this.blankForm();
            this.restoreToast();
        },
        blankForm() {
            return {
                name: '',
                barcode: '',
                brand_id: '',
                category_id: '',
                presentation_id: '',
                public_sale_price: 0,
                current_stock: 0,
                purchase_cost: 0,
                internal_sale_price: 0,
                sale_commission: 0,
                commission_type: 'percent',
                includes_tax: false,
                description: '',
                stock_alarm_enabled: false,
                stock_alarm_limit: '',
                stock_alarm_emails: '',
                is_active: true,
            };
        },
        openCreate() {
            this.isEditing = false;
            this.activeTab = 'basic';
            this.errors = {};
            this.form = this.blankForm();
            this.isOpen = true;
        },
        openEdit(product) {
            this.isEditing = true;
            this.activeTab = 'basic';
            this.errors = {};
            this.form = {
                ...this.blankForm(),
                ...product,
                barcode: product.barcode ?? '',
                description: product.description ?? '',
                stock_alarm_limit: product.stock_alarm_limit ?? '',
                stock_alarm_emails: product.stock_alarm_emails ?? '',
                includes_tax: Boolean(product.includes_tax),
                stock_alarm_enabled: Boolean(product.stock_alarm_enabled),
                is_active: Boolean(product.is_active),
            };
            this.isOpen = true;
        },
        closeModal() {
            this.isOpen = false;
            this.errors = {};
            this.form = this.blankForm();
        },
        modalTitle() {
            return this.isEditing ? 'Editar producto' : 'Nuevo producto';
        },
        footerMessage() {
            return this.isEditing
                ? 'Los cambios se guardarán sobre el producto seleccionado.'
                : 'Los productos nuevos quedan activos por defecto.';
        },
        submitLabel() {
            return this.saving
                ? 'Guardando...'
                : this.isEditing
                    ? 'Guardar'
                    : 'Agregar';
        },
        async saveProduct() {
            this.saving = true;
            this.errors = {};

            try {
                const url = this.isEditing
                    ? `${this.endpoints.updateBase}/${this.form.id}`
                    : this.endpoints.store;

                const response = await fetch(url, {
                    method: this.isEditing ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.applyErrors(data.errors);
                        this.showToast('error', 'Revisa el formulario', data.message ?? 'Hay campos que necesitan corrección.');
                        return;
                    }

                    throw new Error(data.message ?? 'No se pudo guardar el producto.');
                }

                this.persistToast('success', data.message ?? 'Producto guardado correctamente.');
                this.closeModal();
                window.location.reload();
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'Ocurrió un problema al guardar.');
            } finally {
                this.saving = false;
            }
        },
        async deleteProduct(productId, productName) {
            if (!window.confirm(`¿Eliminar ${productName}?`)) {
                return;
            }

            try {
                const response = await fetch(`${this.endpoints.destroyBase}/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? 'No se pudo eliminar el producto.');
                }

                this.persistToast('success', data.message ?? 'Producto eliminado correctamente.');
                window.location.reload();
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'No se pudo eliminar.');
            }
        },
        async quickCreate(kind) {
            const label = {
                brands: 'marca',
                categories: 'categoría',
                presentations: 'formato',
            }[kind] ?? 'registro';

            const name = window.prompt(`Nombre de la nueva ${label}`);

            if (!name || !name.trim()) {
                return;
            }

            try {
                const response = await fetch(this.endpoints[kind], {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ name: name.trim() }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.showToast('error', 'No se pudo crear', data.errors.name?.[0] ?? data.message ?? 'Revisa el nombre.');
                        return;
                    }

                    throw new Error(data.message ?? `No se pudo crear la ${label}.`);
                }

                const record = data.record;

                if (kind === 'brands') {
                    this.upsertCatalog(this.brands, record);
                    this.form.brand_id = record.id;
                }

                if (kind === 'categories') {
                    this.upsertCatalog(this.categories, record);
                    this.form.category_id = record.id;
                }

                if (kind === 'presentations') {
                    this.upsertCatalog(this.presentations, record);
                    this.form.presentation_id = record.id;
                }

                this.showToast('success', data.message ?? `${label} creada correctamente.`);
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? `No se pudo crear la ${label}.`);
            }
        },
        upsertCatalog(items, record) {
            const index = items.findIndex((item) => item.id === record.id);

            if (index === -1) {
                items.push(record);
                return;
            }

            items.splice(index, 1, record);
        },
        applyErrors(errors) {
            this.errors = Object.fromEntries(
                Object.entries(errors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
            );
        },
        showToast(type, title, message) {
            this.toast = {
                visible: true,
                type,
                title,
                message,
            };

            window.clearTimeout(this.toastTimer);
            this.toastTimer = window.setTimeout(() => {
                this.toast.visible = false;
            }, 3500);
        },
        persistToast(type, message) {
            window.sessionStorage.setItem('products-inventory-toast', JSON.stringify({
                type,
                title: type === 'success' ? 'Listo' : 'Atención',
                message,
            }));
        },
        restoreToast() {
            const raw = window.sessionStorage.getItem('products-inventory-toast');

            if (!raw) {
                return;
            }

            window.sessionStorage.removeItem('products-inventory-toast');

            try {
                const toast = JSON.parse(raw);
                this.showToast(toast.type ?? 'success', toast.title ?? 'Listo', toast.message ?? '');
            } catch {
                //
            }
        },
    }));

    Alpine.data('productSales', (config) => ({
        ...createToastMixin('products-sales-toast'),
        isOpen: false,
        saving: false,
        errors: {},
        branches: config.branches ?? [],
        products: config.products ?? [],
        endpoints: config.endpoints ?? {},
        filters: config.filters ?? {},
        form: {},
        init() {
            this.form = this.blankForm();
            this.restoreToast();
        },
        blankForm() {
            const firstBranch = this.branches[0];
            const firstProduct = this.products[0];

            return {
                branch_id: this.filters.branch_id ?? firstBranch?.id ?? '',
                product_id: this.filters.product_id ?? firstProduct?.id ?? '',
                quantity: 1,
                unit_price: firstProduct?.public_sale_price ?? 0,
                notes: '',
            };
        },
        openCreate() {
            this.errors = {};
            this.form = this.blankForm();
            this.isOpen = true;
        },
        closeModal() {
            this.isOpen = false;
            this.errors = {};
            this.form = this.blankForm();
        },
        selectedProduct() {
            return this.products.find((product) => String(product.id) === String(this.form.product_id));
        },
        syncProductPrice() {
            const product = this.selectedProduct();

            if (product) {
                this.form.unit_price = product.public_sale_price;
            }
        },
        submitLabel() {
            return this.saving ? 'Guardando...' : 'Registrar venta';
        },
        async saveSale() {
            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch(this.endpoints.store, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf ?? '',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.errors = Object.fromEntries(
                            Object.entries(data.errors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
                        );
                        this.showToast('error', 'Revisa el formulario', data.message ?? 'Hay campos que necesitan corrección.');
                        return;
                    }

                    throw new Error(data.message ?? 'No se pudo registrar la venta.');
                }

                this.persistToast('success', data.message ?? 'Venta registrada correctamente.');
                this.closeModal();
                window.location.reload();
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'Ocurrió un problema al guardar.');
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('productMovements', (config) => ({
        ...createToastMixin('products-movements-toast'),
        isOpen: false,
        loading: false,
        saving: false,
        errors: {},
        currentProduct: null,
        branches: [],
        history: [],
        stockByBranch: {},
        endpoints: config.endpoints ?? {},
        csrf: config.csrf ?? '',
        init() {
            this.restoreToast();
        },
        openMovement(productId) {
            this.isOpen = true;
            this.loading = true;
            this.errors = {};
            this.currentProduct = null;
            this.branches = [];
            this.history = [];
            this.stockByBranch = {};

            this.loadMovement(productId);
        },
        async loadMovement(productId) {
            try {
                const response = await fetch(`${this.endpoints.detail}/${productId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? 'No se pudo cargar el movimiento.');
                }

                this.currentProduct = data.product ?? null;
                this.branches = data.branches ?? [];
                this.history = data.history ?? [];
                this.stockByBranch = Object.fromEntries(
                    (data.branchStocks ?? []).map((branchStock) => [String(branchStock.id), branchStock.current_stock]),
                );
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'No se pudo cargar el detalle del stock.');
                this.closeModal();
            } finally {
                this.loading = false;
            }
        },
        closeModal() {
            this.isOpen = false;
            this.loading = false;
            this.saving = false;
            this.errors = {};
            this.currentProduct = null;
            this.branches = [];
            this.history = [];
            this.stockByBranch = {};
        },
        title() {
            return this.currentProduct ? `Movimiento de stock ${this.currentProduct.name}` : 'Movimiento de stock';
        },
        historyLabel(movement) {
            return movement.quantity_delta >= 0 ? `De ${movement.previous_stock} a ${movement.new_stock}` : `De ${movement.previous_stock} a ${movement.new_stock}`;
        },
        async saveMovement() {
            if (!this.currentProduct) {
                return;
            }

            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch(`${this.endpoints.store}/${this.currentProduct.id}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        stock_by_branch: this.stockByBranch,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.errors = Object.fromEntries(
                            Object.entries(data.errors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
                        );
                        this.showToast('error', 'Revisa el formulario', data.message ?? 'Hay campos que necesitan corrección.');
                        return;
                    }

                    throw new Error(data.message ?? 'No se pudo actualizar el stock.');
                }

                this.persistToast('success', data.message ?? 'Stock actualizado correctamente.');
                this.closeModal();
                window.location.reload();
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'No se pudo guardar el ajuste.');
            } finally {
                this.saving = false;
            }
        },
    }));
});
