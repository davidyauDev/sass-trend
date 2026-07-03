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
        movementLoading: false,
        movementHistory: [],
        movementBranches: [],
        stockAdjustmentOpen: false,
        stockAdjustmentLoading: false,
        stockAdjustmentSaving: false,
        stockAdjustmentErrors: {},
        stockAdjustmentMode: 'increase',
        stockAdjustmentProduct: null,
        stockAdjustmentBranches: [],
        stockAdjustmentForm: {},
        importInventoryOpen: false,
        importInventoryFileName: '',
        importInventoryForm: null,
        quickCreateOpen: false,
        quickCreateSaving: false,
        quickCreateErrors: {},
        quickCreateKind: 'brands',
        quickCreateName: '',
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
            this.stockAdjustmentForm = this.blankStockAdjustmentForm();
            this.importInventoryForm = null;
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
        blankStockAdjustmentForm() {
            return {
                branch_id: '',
                quantity: '',
                comment: '',
            };
        },
        quickCreateMeta(kind = this.quickCreateKind) {
            const meta = {
                brands: {
                    title: 'Nueva marca',
                    label: 'marca',
                    description: 'Crea una marca nueva y quedará seleccionada automáticamente en el producto.',
                    placeholder: 'Ej. Natura',
                },
                categories: {
                    title: 'Nueva categoría',
                    label: 'categoría',
                    description: 'Crea una categoría nueva y quedará seleccionada automáticamente en el producto.',
                    placeholder: 'Ej. Cuidado facial',
                },
                presentations: {
                    title: 'Nuevo formato',
                    label: 'formato',
                    description: 'Crea un formato nuevo y quedará seleccionado automáticamente en el producto.',
                    placeholder: 'Ej. Frasco',
                },
            };

            return meta[kind] ?? meta.brands;
        },
        resetMovementState() {
            this.movementLoading = false;
            this.movementHistory = [];
            this.movementBranches = [];
        },
        resetStockAdjustment() {
            this.stockAdjustmentOpen = false;
            this.stockAdjustmentLoading = false;
            this.stockAdjustmentSaving = false;
            this.stockAdjustmentErrors = {};
            this.stockAdjustmentMode = 'increase';
            this.stockAdjustmentProduct = null;
            this.stockAdjustmentBranches = [];
            this.stockAdjustmentForm = this.blankStockAdjustmentForm();
        },
        openImportInventory() {
            this.importInventoryOpen = true;
        },
        closeImportInventory() {
            this.importInventoryOpen = false;
            this.importInventoryFileName = '';
            this.$refs.importInventoryForm?.reset();
        },
        onImportFileChange(event) {
            const file = event?.target?.files?.[0] ?? null;
            this.importInventoryFileName = file?.name ?? '';
        },
        openCreate() {
            this.isEditing = false;
            this.activeTab = 'basic';
            this.errors = {};
            this.form = this.blankForm();
            this.resetMovementState();
            this.isOpen = true;
        },
        openEdit(product) {
            this.isEditing = true;
            this.activeTab = 'basic';
            this.errors = {};
            this.resetMovementState();
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
            this.loadMovementData(product.id);
        },
        closeModal() {
            this.isOpen = false;
            this.errors = {};
            this.form = this.blankForm();
            this.resetMovementState();
        },
        openStockAdjustment(mode, product) {
            this.resetStockAdjustment();
            this.stockAdjustmentMode = mode;
            this.stockAdjustmentProduct = {
                id: product.id,
                name: product.name,
            };
            this.stockAdjustmentOpen = true;
            this.loadStockAdjustmentDetail(product.id);
        },
        closeStockAdjustment() {
            this.resetStockAdjustment();
        },
        openQuickCreate(kind) {
            this.quickCreateKind = kind;
            this.quickCreateName = '';
            this.quickCreateErrors = {};
            this.quickCreateSaving = false;
            this.quickCreateOpen = true;
        },
        closeQuickCreate() {
            this.quickCreateOpen = false;
            this.quickCreateSaving = false;
            this.quickCreateErrors = {};
            this.quickCreateName = '';
        },
        quickCreateTitle() {
            return this.quickCreateMeta().title;
        },
        quickCreateDescription() {
            return this.quickCreateMeta().description;
        },
        quickCreatePlaceholder() {
            return this.quickCreateMeta().placeholder;
        },
        quickCreateLabel() {
            return this.quickCreateMeta().label;
        },
        quickCreateSubmitLabel() {
            return this.quickCreateSaving ? 'Guardando...' : 'Crear';
        },
        modalTitle() {
            return this.isEditing ? `Editando ${this.form.name || 'producto'}` : 'Nuevo producto';
        },
        footerMessage() {
            if (this.isEditing && this.activeTab === 'movements') {
                return 'Aqui puedes revisar el historial de stock por local del producto seleccionado.';
            }

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
        async fetchMovementDetail(productId) {
            const response = await fetch(`${this.endpoints.movementDetail}/${productId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message ?? 'No se pudo cargar el detalle del stock.');
            }

            return data;
        },
        async loadMovementData(productId) {
            this.movementLoading = true;

            try {
                const data = await this.fetchMovementDetail(productId);

                if (!this.isEditing || String(this.form.id) !== String(productId)) {
                    return;
                }

                this.movementHistory = data.history ?? [];
                this.movementBranches = data.branchStocks ?? [];

                if (data.product?.current_stock !== undefined) {
                    this.form.current_stock = data.product.current_stock;
                }
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'No se pudo cargar el historial del producto.');
            } finally {
                this.movementLoading = false;
            }
        },
        async loadStockAdjustmentDetail(productId) {
            this.stockAdjustmentLoading = true;

            try {
                const data = await this.fetchMovementDetail(productId);

                this.stockAdjustmentProduct = data.product ?? this.stockAdjustmentProduct;
                this.stockAdjustmentBranches = data.branchStocks ?? [];
                this.stockAdjustmentForm.branch_id = this.stockAdjustmentBranches[0]?.id
                    ? String(this.stockAdjustmentBranches[0].id)
                    : '';
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'No se pudo cargar los locales para ajustar stock.');
                this.closeStockAdjustment();
            } finally {
                this.stockAdjustmentLoading = false;
            }
        },
        selectedStockAdjustmentBranch() {
            return this.stockAdjustmentBranches.find((branch) => String(branch.id) === String(this.stockAdjustmentForm.branch_id)) ?? null;
        },
        selectedStockAdjustmentCurrent() {
            return Number.parseFloat(this.selectedStockAdjustmentBranch()?.current_stock ?? '0') || 0;
        },
        stockAdjustmentQuantity() {
            return Math.max(0, Number.parseFloat(this.stockAdjustmentForm.quantity || '0') || 0);
        },
        stockAdjustmentPreviewStock() {
            const currentStock = this.selectedStockAdjustmentCurrent();
            const quantity = this.stockAdjustmentQuantity();

            return this.stockAdjustmentMode === 'increase'
                ? currentStock + quantity
                : Math.max(0, currentStock - quantity);
        },
        stockAdjustmentTitle() {
            if (!this.stockAdjustmentProduct) {
                return 'Ajustar stock';
            }

            return this.stockAdjustmentMode === 'increase'
                ? `Aumentar stock en: ${this.stockAdjustmentProduct.name}`
                : `Disminuir stock en: ${this.stockAdjustmentProduct.name}`;
        },
        stockAdjustmentHelpText() {
            return this.stockAdjustmentMode === 'increase'
                ? 'Aqui podras aumentar el stock existente del producto por cada local.'
                : 'Aqui podras reducir el stock existente del producto por cada local.';
        },
        stockAdjustmentQuantityLabel() {
            const branchName = this.selectedStockAdjustmentBranch()?.name ?? 'el local seleccionado';

            return this.stockAdjustmentMode === 'increase'
                ? `Agregar a ${branchName}`
                : `Disminuir en ${branchName}`;
        },
        stockAdjustmentCommentPlaceholder() {
            return this.stockAdjustmentMode === 'increase'
                ? 'Agregar una nota por el aumento de stock para que quede en el historial'
                : 'Agregar una nota por la reduccion de stock para que quede en el historial';
        },
        stockAdjustmentSubmitLabel() {
            return this.stockAdjustmentSaving ? 'Guardando...' : 'Guardar';
        },
        movementAdjustmentLabel(movement) {
            return `De ${movement.previous_stock} a ${movement.new_stock}`;
        },
        productRequiredErrors() {
            const errors = {};

            if (String(this.form.name ?? '').trim() === '') {
                errors.name = 'El nombre es obligatorio.';
            }

            if (String(this.form.brand_id ?? '') === '') {
                errors.brand_id = 'La marca es obligatoria.';
            }

            if (String(this.form.category_id ?? '') === '') {
                errors.category_id = 'La categoría es obligatoria.';
            }

            if (String(this.form.presentation_id ?? '') === '') {
                errors.presentation_id = 'El formato/presentación es obligatorio.';
            }

            if (String(this.form.commission_type ?? '') === '') {
                errors.commission_type = 'La comisión de venta es obligatoria.';
            }

            if (this.form.stock_alarm_enabled) {
                if (String(this.form.stock_alarm_limit ?? '') === '') {
                    errors.stock_alarm_limit = 'Debes indicar el stock límite.';
                }

                const emails = String(this.form.stock_alarm_emails ?? '').trim();

                if (emails !== '' && emails.split(',').some((email) => email.trim() === '')) {
                    errors.stock_alarm_emails = 'Ingresa correos válidos separados por coma.';
                }
            }

            return errors;
        },
        canSubmitProduct() {
            return Object.keys(this.productRequiredErrors()).length === 0;
        },
        formatStock(value) {
            const number = Number.parseFloat(String(value ?? 0));

            if (!Number.isFinite(number)) {
                return '0';
            }

            return Number.isInteger(number) ? String(number) : number.toFixed(2);
        },
        async saveProduct() {
            this.saving = true;
            this.errors = {};

            try {
                const requiredErrors = this.productRequiredErrors();

                if (Object.keys(requiredErrors).length > 0) {
                    this.errors = requiredErrors;
                    this.activeTab = 'basic';
                    this.showToast('error', 'Revisa el formulario', 'Completa los campos obligatorios antes de agregar el producto.');
                    return;
                }

                const url = this.isEditing
                    ? `${this.endpoints.updateBase}/${this.form.id}`
                    : this.endpoints.store;
                const payload = this.normalizedProductPayload();

                const response = await fetch(url, {
                    method: this.isEditing ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(payload),
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
        async saveStockAdjustment() {
            if (!this.stockAdjustmentProduct) {
                return;
            }

            this.stockAdjustmentSaving = true;
            this.stockAdjustmentErrors = {};

            try {
                const response = await fetch(`${this.endpoints.movementStore}/${this.stockAdjustmentProduct.id}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        branch_id: Number(this.stockAdjustmentForm.branch_id),
                        adjustment_type: this.stockAdjustmentMode,
                        quantity: this.stockAdjustmentQuantity(),
                        comment: this.stockAdjustmentForm.comment,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.applyErrorsTo(this.stockAdjustmentErrors, data.errors);
                        this.showToast('error', 'Revisa el formulario', data.message ?? 'Hay campos que necesitan corrección.');
                        return;
                    }

                    throw new Error(data.message ?? 'No se pudo ajustar el stock.');
                }

                this.persistToast('success', data.message ?? 'Stock actualizado correctamente.');
                this.closeStockAdjustment();
                window.location.reload();
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? 'No se pudo guardar el ajuste.');
            } finally {
                this.stockAdjustmentSaving = false;
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
        async saveQuickCreate() {
            const kind = this.quickCreateKind;
            const label = this.quickCreateLabel();

            this.quickCreateSaving = true;
            this.quickCreateErrors = {};

            try {
                const response = await fetch(this.endpoints[kind], {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ name: this.quickCreateName.trim() }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.applyErrorsTo(this.quickCreateErrors, data.errors);
                        this.showToast('error', 'Revisa el formulario', data.errors.name?.[0] ?? data.message ?? 'Revisa el nombre.');
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

                this.closeQuickCreate();
                this.showToast('success', data.message ?? `${label} creada correctamente.`);
            } catch (error) {
                this.showToast('error', 'Error', error?.message ?? `No se pudo crear la ${label}.`);
            } finally {
                this.quickCreateSaving = false;
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
        applyErrorsTo(target, errors) {
            Object.assign(
                target,
                Object.fromEntries(
                    Object.entries(errors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
                ),
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
