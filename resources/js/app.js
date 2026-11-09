import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

window.posApp = function () {
    return {
        products: [],
        customers: [],
        warehouses: [],
        currencies: {},
        rates: {},
        taxes: [],
        paymentMethods: [],
        customerId: '',
        warehouseId: '',
        currency: 'CLP',
        taxMode: 'iva_19',
        notes: '',
        entryProductId: '',
        entryQuantity: 1,
        entryBinId: '',
        entryChamberId: '',
        productQuery: '',
        productOpen: false,
        customerQuery: '',
        customerOpen: false,
        cart: [],
        bins: [],
        chambers: [],
        payments: [],
        flash: '',
        flashType: 'info',
        confirmOpen: false,

        init() {
            const config = window.__POS_CONFIG__ || {};
            this.products = config.products || [];
            this.customers = config.customers || [];
            this.warehouses = config.warehouses || [];
            this.currencies = config.currencies || {};
            this.rates = config.rates || {};
            this.taxes = config.taxes || [];
            this.paymentMethods = config.paymentMethods || [];
            this.bins = config.bins || [];
            this.chambers = config.chambers || [];
            this.warehouseId = config.defaultWarehouse || '';
            this.currency = config.defaultCurrency || 'CLP';
            this.taxMode = 'iva_19';
            this.payments = [{ method: '', currency_code: this.currency, amount: '', reference: '' }];

            // Pre‑populate fields when editing a draft sale
            if (config.editingSale) {
                const d = config.editingSale;
                this.customerId = d.customer_id ?? '';
                this.warehouseId = d.warehouse_id ?? this.warehouseId;
                this.currency = d.currency_code ?? this.currency;
                this.taxMode = d.tax_mode ?? this.taxMode;
                this.notes = d.notes ?? '';
                this.cart = (d.items ?? []).map(item => ({
                    product_id: item.product_id,
                    chamber_id: item.chamber_id,
                    bin_id: item.bin_id,
                    name: item.name,
                    sku: item.sku,
                    unit: item.unit,
                    quantity: item.quantity,
                    discount: item.discount ?? 0,
                }));
            }
        },

        get selectedCustomer() {
            return this.customers.find((customer) => String(customer.id) === String(this.customerId));
        },

        get taxRate() {
            return Number(this.taxes.find((tax) => tax.value === this.taxMode)?.rate || 0);
        },

        get subtotal() {
            return this.cart.reduce((total, item) => total + this.lineSubtotal(item), 0);
        },

        get discountTotal() {
            return this.cart.reduce((total, item) => total + Number(item.discount || 0), 0);
        },

        get taxTotal() {
            return this.cart.reduce((total, item) => total + this.lineTax(item), 0);
        },

        get total() {
            return this.cart.reduce((total, item) => total + this.lineTotal(item), 0);
        },

        get paidTotal() {
            return this.payments.reduce((total, payment) => {
                if (!payment.amount) {
                    return total;
                }

                return total + this.toSaleCurrency(Number(payment.amount), payment.currency_code);
            }, 0);
        },

        get remaining() {
            return Math.max(0, this.total - this.paidTotal);
        },

        get canFinalize() {
            return this.customerId && this.warehouseId && this.cart.length > 0;
        },

        validatePayments() {
            for (let i = 0; i < this.payments.length; i++) {
                const p = this.payments[i];
                const amt = Number(p.amount || 0);
                if (amt > 0 && !p.method) {
                    this.notify('Seleccione el tipo de Pago para el monto ingresado.', 'error');
                    return false;
                }
            }
            return true;
        },

        requestFinalize() {
            if (!this.canFinalize) {
                this.notify('Selecciona cliente, bodega y al menos un producto.', 'error');
                return;
            }
            if (!this.validatePayments()) {
                return;
            }
            this.confirmOpen = true;
        },

        submitDraft() {
            if (!this.canFinalize) {
                this.notify('Selecciona cliente, bodega y al menos un producto.', 'error');
                return;
            }
            if (this.$refs.modeInput) {
                this.$refs.modeInput.value = 'draft';
            }
            this.$refs.posForm?.submit();
        },

        confirmFinalize() {
            this.confirmOpen = false;
            if (this.$refs.modeInput) {
                this.$refs.modeInput.value = 'finalize';
            }
            this.$nextTick(() => {
                this.$refs.posForm?.submit();
            });
        },

        get filteredProducts() {
            const query = this.productQuery.trim().toLowerCase();

            if (!query) {
                return this.products.slice(0, 20);
            }

            return this.products
                .filter((product) => {
                    const haystack = `${product.sku} ${product.name}`.toLowerCase();

                    return haystack.includes(query);
                })
                .slice(0, 20);
        },

        get filteredBins() {
            if (!this.entryChamberId) {
                return this.bins;
            }

            return this.bins.filter((bin) => {
                // Si no sabemos a qué cámara pertenece cada bin, mostramos todos
                return true;
            });
        },

        get filteredCustomers() {
            const query = this.customerQuery.trim().toLowerCase();

            if (!query) {
                return this.customers.slice(0, 20);
            }

            return this.customers
                .filter((customer) => {
                    const haystack = `${customer.name} ${customer.tax_id}`.toLowerCase();

                    return haystack.includes(query);
                })
                .slice(0, 20);
        },

        get entryProduct() {
            return this.product(this.entryProductId);
        },

        product(id) {
            return this.products.find((product) => product.id === Number(id));
        },

        priceFor(product) {
            return Number(product.prices[this.currency] || 0);
        },

        stockFor(product) {
            const warehouseStock = product.stock_by_warehouse?.[this.warehouseId];
            if (!warehouseStock) {
                return 0;
            }

            if (this.entryChamberId) {
                return Number(warehouseStock.by_chamber?.[this.entryChamberId] || 0);
            }

            return Number(warehouseStock.quantity || 0);
        },

        addEntry() {
            const product = this.product(this.entryProductId);

            if (!product) {
                this.notify('Selecciona un producto para agregarlo.', 'error');

                return;
            }

            const stock = this.stockFor(product);
            const quantity = Math.max(1, Math.floor(Number(this.entryQuantity) || 1));

            if (stock <= 0) {
                this.notify(`Sin stock disponible de ${product.name} en esta bodega.`, 'error');

                return;
            }

            if (quantity > stock) {
                this.notify('La cantidad supera el stock disponible.', 'error');

                return;
            }

            const binId = this.entryBinId ? Number(this.entryBinId) : null;
            const chamberId = this.entryChamberId ? Number(this.entryChamberId) : null;
            const existing = this.cart.find((item) => item.product_id === product.id && String(item.bin_id || '') === String(binId || '') && String(item.chamber_id || '') === String(chamberId || ''));

            if (existing) {
                if (existing.quantity + quantity > stock) {
                    this.notify('La cantidad supera el stock disponible.', 'error');

                    return;
                }

                existing.quantity += quantity;
            } else {
                this.cart.push({
                    product_id: product.id,
                    chamber_id: chamberId,
                    bin_id: binId,
                    name: product.name,
                    sku: product.sku,
                    unit: product.unit,
                    quantity,
                    discount: 0,
                    stock,
                });
            }

            this.entryProductId = '';
            this.entryQuantity = 1;
            this.entryBinId = '';
            this.entryChamberId = '';
            this.productQuery = '';
            this.productOpen = false;
            this.flash = '';

            this.$nextTick(() => {
                this.$refs.productSearch?.focus();
                this.productOpen = false;
            });
        },

        selectProduct(product) {
            if (this.stockFor(product) <= 0) {
                this.notify(`Sin stock disponible de ${product.name} en esta bodega.`, 'error');

                return;
            }

            this.entryProductId = product.id;
            this.productQuery = '';
            this.productOpen = false;

            this.$nextTick(() => {
                this.$refs.quantityInput?.focus();
            });
        },

        selectCustomer(customer) {
            this.customerId = customer.id;
            this.customerQuery = '';
            this.customerOpen = false;
            this.onCustomerChange();
        },

        increment(item) {
            if (item.quantity + 1 > item.stock) {
                this.notify('No hay más stock disponible para este producto.', 'error');

                return;
            }

            item.quantity += 1;
        },

        decrement(item) {
            item.quantity = Math.max(0, item.quantity - 1);

            if (item.quantity === 0) {
                this.removeItem(item);
            }
        },

        removeItem(item) {
            this.cart = this.cart.filter((line) => line !== item);
        },

        clearCart() {
            this.cart = [];
            this.payments = [{ method: '', currency_code: this.currency, amount: '', reference: '' }];
        },

        lineSubtotal(item) {
            return Math.round(this.linePrice(item) * Number(item.quantity) * 100) / 100;
        },

        linePrice(item) {
            const product = this.product(item.product_id);

            return product ? this.priceFor(product) : 0;
        },

        lineDiscount(item) {
            return Math.min(Number(item.discount || 0), this.lineSubtotal(item));
        },

        lineTaxable(item) {
            return this.lineSubtotal(item) - this.lineDiscount(item);
        },

        lineTax(item) {
            return Math.round(this.lineTaxable(item) * this.taxRate * 100) / 100;
        },

        lineTotal(item) {
            return Math.round((this.lineTaxable(item) + this.lineTax(item)) * 100) / 100;
        },

        onCustomerChange() {
            const customer = this.selectedCustomer;

            if (!customer) {
                return;
            }

            this.currency = customer.currency_code || this.currency;
            this.taxMode = customer.tax_mode || this.taxMode;
            this.payments.forEach((payment) => {
                if (!payment.amount) {
                    payment.currency_code = this.currency;
                }
            });
        },

        onCurrencyChange() {
            this.payments.forEach((payment) => {
                if (!payment.amount) {
                    payment.currency_code = this.currency;
                }
            });
        },

        addPayment() {
            this.payments.push({
                method: '',
                currency_code: this.currency,
                amount: this.remaining > 0 ? this.remaining.toFixed(this.currencies[this.currency]?.decimals ?? 2) : '',
                reference: '',
            });
        },

        removePayment(index) {
            this.payments.splice(index, 1);

            if (this.payments.length === 0) {
                this.addPayment();
            }
        },

        fillRemaining(payment) {
            payment.amount = this.remaining.toFixed(this.currencies[this.currency]?.decimals ?? 2);
        },

        onMethodChange(payment) {
            const allowed = this.allowedCurrencies(payment.method);
            if (!allowed.includes(payment.currency_code)) {
                payment.currency_code = allowed[0] || this.currency;
            }
        },

        allowedCurrencies(method) {
            if (!method) {
                return Object.keys(this.currencies);
            }

            return this.paymentMethods.find((item) => item.value === method)?.currencies || Object.keys(this.currencies);
        },

        toSaleCurrency(amount, currency) {
            if (currency === this.currency) {
                return amount;
            }

            const fromRate = Number(this.rates[currency] || 1);
            const saleRate = Number(this.rates[this.currency] || 1);

            return Math.round((amount * fromRate / saleRate) * 100) / 100;
        },

        formatMoney(value, currency = this.currency) {
            const decimals = Number(this.currencies[currency]?.decimals ?? 2);
            const symbol = this.currencies[currency]?.symbol || currency;
            const locale = currency === 'CLP' ? 'es-CL' : 'en-US';

            return `${symbol} ${Number(value || 0).toLocaleString(locale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            })}`;
        },

        notify(message, type = 'info') {
            this.flash = message;
            this.flashType = type;
        },
    };
};

Alpine.start();
