import React, { Component } from "react";
import { createRoot } from "react-dom/client";
import axios from "axios";
import Swal from "sweetalert2";
import { sum } from "lodash";

class Cart extends Component {
    constructor(props) {
        super(props);
        this.state = {
            cart: [],
            products: [],
            quickItems: [],
            categories: [],
            openItems: [],
            customers: [],
            barcode: "",
            search: "",
            selectedCategoryId: "",
            openItemName: "",
            openItemPrice: "",
            openItemQuantity: "1",
            customer_id: "",
            payment_method: "cash",
            discount: "0",
            due_date: "",
            translations: {},
        };

        this.loadCart = this.loadCart.bind(this);
        this.loadProducts = this.loadProducts.bind(this);
        this.loadQuickItems = this.loadQuickItems.bind(this);
        this.loadCategories = this.loadCategories.bind(this);
        this.handleOnChangeBarcode = this.handleOnChangeBarcode.bind(this);
        this.handleScanBarcode = this.handleScanBarcode.bind(this);
        this.handleChangeQty = this.handleChangeQty.bind(this);
        this.handleEmptyCart = this.handleEmptyCart.bind(this);
        this.handleChangeSearch = this.handleChangeSearch.bind(this);
        this.handleSeach = this.handleSeach.bind(this);
        this.handleSelectCategory = this.handleSelectCategory.bind(this);
        this.handleAddOpenItem = this.handleAddOpenItem.bind(this);
        this.setCustomerId = this.setCustomerId.bind(this);
        this.setPaymentMethod = this.setPaymentMethod.bind(this);
        this.setDiscount = this.setDiscount.bind(this);
        this.setDueDate = this.setDueDate.bind(this);
        this.handleClickSubmit = this.handleClickSubmit.bind(this);
        this.loadTranslations = this.loadTranslations.bind(this);
    }

    componentDidMount() {
        this.loadTranslations();
        this.loadCustomers();
        this.loadCategories();
        this.loadProducts();
        this.loadQuickItems();
        this.loadCart();
    }

    loadTranslations() {
        axios
            .get("/admin/locale/cart")
            .then((res) => this.setState({ translations: res.data }))
            .catch(() => this.setState({ translations: {} }));
    }

    loadCustomers() {
        axios.get("/admin/customers", {
            headers: { Accept: "application/json", "Content-Type": "application/json" },
        }).then((res) => {
            this.setState({ customers: res.data });
        }).catch(() => this.setState({ customers: [] }));
    }

    loadCategories() {
        axios.get("/admin/categories?active=1", {
            headers: { Accept: "application/json", "Content-Type": "application/json" },
        }).then((res) => {
            this.setState({ categories: Array.isArray(res.data) ? res.data : [] });
        }).catch(() => this.setState({ categories: [] }));
    }

    loadProducts(search = this.state.search, categoryId = this.state.selectedCategoryId) {
        const params = new URLSearchParams({ active: "1", per_page: "100" });
        if (search) params.set("search", search);
        if (categoryId) params.set("category_id", categoryId);

        axios.get(`/admin/products?${params.toString()}`, {
            headers: { Accept: "application/json", "Content-Type": "application/json" },
        }).then((res) => {
            this.setState({ products: res.data.data || [] });
        }).catch(() => this.setState({ products: [] }));
    }

    loadQuickItems() {
        axios.get("/admin/products?is_quick=1&active=1&per_page=100", {
            headers: { Accept: "application/json", "Content-Type": "application/json" },
        }).then((res) => {
            this.setState({ quickItems: res.data.data || [] });
        }).catch(() => this.setState({ quickItems: [] }));
    }

    loadCart() {
        axios.get("/admin/cart", {
            headers: { Accept: "application/json", "Content-Type": "application/json" },
        }).then((res) => {
            const cart = Array.isArray(res.data) ? res.data.map((item) => ({
                ...item,
                pivot: {
                    ...item.pivot,
                    quantity: parseInt(item.pivot.quantity, 10) || 1,
                },
            })) : [];

            this.setState({ cart });
        }).catch(() => this.setState({ cart: [] }));
    }

    handleOnChangeBarcode(event) {
        this.setState({ barcode: event.target.value });
    }

    handleScanBarcode(event) {
        event.preventDefault();
        const barcode = this.state.barcode.trim();
        if (!barcode) return;

        axios
            .post("/admin/cart", { barcode })
            .then(() => {
                this.loadCart();
                this.setState({ barcode: "" });
            })
            .catch((err) => {
                Swal.fire("Error!", err.response?.data?.message || "Product not found", "error");
            });
    }

    handleChangeQty(product_id, qty) {
        const quantity = parseInt(qty, 10);
        const cart = this.state.cart.map((c) => {
            if (c.id === product_id) {
                c.pivot.quantity = qty === "" ? "" : quantity;
            }
            return c;
        });

        this.setState({ cart });
        if (!quantity || quantity <= 0) return;

        axios
            .post("/admin/cart/change-qty", { product_id, quantity })
            .catch((err) => {
                Swal.fire("Error!", err.response?.data?.message || "Unable to change quantity", "error");
                this.loadCart();
            });
    }

    handleChangeSearch(event) {
        this.setState({ search: event.target.value });
    }

    handleSeach(event) {
        if (event.keyCode === 13) {
            this.loadProducts(event.target.value, this.state.selectedCategoryId);
        }
    }

    handleSelectCategory(categoryId) {
        this.setState({ selectedCategoryId: categoryId }, () => {
            this.loadProducts(this.state.search, categoryId);
        });
    }

    handleAddOpenItem(event) {
        event.preventDefault();
        const name = this.state.openItemName.trim();
        const price = Number(this.state.openItemPrice);
        const quantity = parseInt(this.state.openItemQuantity, 10);

        if (!name || !price || price <= 0 || !quantity || quantity <= 0) {
            Swal.fire("Error!", "Enter a custom item name, price, and quantity.", "error");
            return;
        }

        this.setState({
            openItems: [...this.state.openItems, { id: `open-${Date.now()}`, name, price, quantity }],
            openItemName: "",
            openItemPrice: "",
            openItemQuantity: "1",
        });
    }

    getTotal(cart) {
        const productTotal = cart.map((c) => Number(c.pivot.quantity) * Number(c.price));
        const openTotal = this.state.openItems.map((item) => item.quantity * item.price);
        const subtotal = sum(productTotal) + sum(openTotal);
        const discount = Math.max(Number(this.state.discount) || 0, 0);
        return Math.max(subtotal - discount, 0).toFixed(2);
    }

    getCartCount(cart) {
        return sum(cart.map((c) => parseInt(c.pivot.quantity, 10) || 0)) +
            sum(this.state.openItems.map((item) => parseInt(item.quantity, 10) || 0));
    }

    getLowStockCount(products) {
        return products.filter((p) => p.track_stock && Number(p.quantity) <= window.APP.warning_quantity).length;
    }

    handleClickDelete(product_id) {
        axios
            .post("/admin/cart/delete", { product_id, _method: "DELETE" })
            .then(() => {
                this.setState({ cart: this.state.cart.filter((c) => c.id !== product_id) });
            });
    }

    handleEmptyCart() {
        axios.post("/admin/cart/empty", { _method: "DELETE" }).then(() => {
            this.setState({ cart: [], openItems: [] });
        });
    }

    addProductToCartById(product) {
        const exists = this.state.cart.find((c) => c.id === product.id);
        if (exists) {
            this.setState({
                cart: this.state.cart.map((c) =>
                    c.id === product.id ? { ...c, pivot: { ...c.pivot, quantity: Number(c.pivot.quantity) + 1 } } : c
                ),
            });
        } else {
            this.setState({
                cart: [...this.state.cart, { ...product, pivot: { quantity: 1, product_id: product.id, user_id: 1 } }],
            });
        }

        axios.post("/admin/cart", { product_id: product.id })
            .then(() => {
                this.loadCart();
                this.loadProducts(this.state.search, this.state.selectedCategoryId);
                this.loadQuickItems();
            })
            .catch((err) => {
                Swal.fire("Error!", err.response?.data?.message || "Error adding product", "error");
                this.loadCart();
            });
    }

    setCustomerId(event) {
        this.setState({ customer_id: event.target.value });
    }

    setPaymentMethod(event) {
        this.setState({ payment_method: event.target.value });
    }

    setDiscount(event) {
        this.setState({ discount: event.target.value });
    }

    setDueDate(event) {
        this.setState({ due_date: event.target.value });
    }

    handleClickSubmit() {
        const total = this.getTotal(this.state.cart);
        Swal.fire({
            title: this.state.translations["received_amount"],
            html: `
                <div class="text-left mb-2">${this.state.translations["amount_due"] || "Amount due"}: <strong>${window.APP.currency_symbol} ${total}</strong></div>
                <input id="checkout-amount" class="swal2-input" type="number" min="0" step="0.01" value="${total}">
            `,
            focusConfirm: false,
            cancelButtonText: this.state.translations["cancel_pay"],
            showCancelButton: true,
            confirmButtonText: this.state.translations["confirm_pay"],
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const amount = document.getElementById("checkout-amount").value;
                return axios
                    .post("/admin/orders", {
                        customer_id: this.state.customer_id,
                        amount,
                        payment_method: this.state.payment_method,
                        discount: this.state.discount,
                        due_date: this.state.due_date,
                        custom_items: this.state.openItems.map((item) => ({
                            name: item.name,
                            price: item.price,
                            quantity: item.quantity,
                        })),
                    })
                    .then((res) => {
                        this.loadCart();
                        this.setState({ openItems: [] });
                        return res.data;
                    })
                    .catch((err) => {
                        Swal.showValidationMessage(err.response?.data?.message || "Unable to create order");
                    });
            },
            allowOutsideClick: () => !Swal.isLoading(),
        }).then((result) => {
            if (result.value) {
                Swal.fire({
                    title: this.state.translations["order_complete"] || "Order complete",
                    text: result.value.message,
                    icon: "success",
                    showCancelButton: true,
                    confirmButtonText: this.state.translations["print_receipt"] || "Print Receipt",
                    cancelButtonText: this.state.translations["new_sale"] || "New Sale",
                }).then((receiptResult) => {
                    if (receiptResult.isConfirmed) {
                        window.open(`/admin/orders/${result.value.order_id}`, "_blank");
                    }
                });
            }
        });
    }

    render() {
        const {
            cart = [],
            products = [],
            quickItems = [],
            categories = [],
            openItems = [],
            customers = [],
            barcode = "",
            translations = {},
            payment_method = "cash",
            discount = "0",
            due_date = "",
            selectedCategoryId = "",
        } = this.state;
        const total = this.getTotal(cart);
        const hasSaleItems = cart.length > 0 || openItems.length > 0;

        return (
            <div className="pos-workspace">
                <div className="pos-summary">
                    <div>
                        <span>{translations["items"] || "Items"}</span>
                        <strong>{this.getCartCount(cart)}</strong>
                    </div>
                    <div>
                        <span>{translations["products"] || "Products"}</span>
                        <strong>{products.length}</strong>
                    </div>
                    <div>
                        <span>{translations["low_stock"] || "Low stock"}</span>
                        <strong>{this.getLowStockCount(products)}</strong>
                    </div>
                    <div>
                        <span>{translations["total"] || "Total"}</span>
                        <strong>{window.APP.currency_symbol} {total}</strong>
                    </div>
                </div>

                <div className="row">
                    <div className="col-md-6 col-xl-4">
                        <div className="pos-panel pos-checkout-panel">
                            <div className="pos-panel-header">
                                <div>
                                    <span className="text-muted">{translations["current_sale"] || "Current sale"}</span>
                                    <h2>{window.APP.currency_symbol} {total}</h2>
                                </div>
                                <button
                                    type="button"
                                    className="btn btn-outline-danger btn-sm"
                                    onClick={this.handleEmptyCart}
                                    disabled={!hasSaleItems}
                                >
                                    <i className="fas fa-times mr-1"></i>
                                    {translations["cancel"] || "Cancel"}
                                </button>
                            </div>

                            <form onSubmit={this.handleScanBarcode} className="mb-2">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={translations["scan_barcode"] || "Search or scan by barcode, SKU, short code, or product name"}
                                    value={barcode}
                                    onChange={this.handleOnChangeBarcode}
                                />
                            </form>

                            <form onSubmit={this.handleAddOpenItem} className="open-item-form mb-3">
                                <div className="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{translations["open_item"] || "Open Item"}</strong>
                                    <button type="submit" className="btn btn-outline-primary btn-sm">
                                        <i className="fas fa-plus mr-1"></i>
                                        {translations["add"] || "Add"}
                                    </button>
                                </div>
                                <input
                                    type="text"
                                    className="form-control form-control-sm mb-2"
                                    placeholder={translations["item_name"] || "Custom item name"}
                                    value={this.state.openItemName}
                                    onChange={(event) => this.setState({ openItemName: event.target.value })}
                                />
                                <div className="d-flex">
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        className="form-control form-control-sm mr-2"
                                        placeholder={translations["price"] || "Price"}
                                        value={this.state.openItemPrice}
                                        onChange={(event) => this.setState({ openItemPrice: event.target.value })}
                                    />
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        className="form-control form-control-sm"
                                        placeholder={translations["quantity"] || "Qty"}
                                        value={this.state.openItemQuantity}
                                        onChange={(event) => this.setState({ openItemQuantity: event.target.value })}
                                    />
                                </div>
                            </form>

                            <select className="form-control mb-2" onChange={this.setCustomerId}>
                                <option value="">{translations["general_customer"] || "General Customer"}</option>
                                {customers.map((cus) => (
                                    <option key={cus.id} value={cus.id}>
                                        {`${cus.customer_code || `CUST-${String(cus.id).padStart(6, "0")}`} - ${cus.first_name} ${cus.last_name}`}
                                    </option>
                                ))}
                            </select>

                            <select className="form-control mb-3" value={payment_method} onChange={this.setPaymentMethod}>
                                <option value="cash">{translations["cash"] || "Cash"}</option>
                                <option value="card">{translations["card"] || "Card"}</option>
                                <option value="bank_transfer">{translations["bank_transfer"] || "Bank transfer"}</option>
                                <option value="mobile_money">{translations["mobile_money"] || "Mobile money"}</option>
                                <option value="jazzcash">{translations["jazzcash"] || "JazzCash"}</option>
                                <option value="easypaisa">{translations["easypaisa"] || "EasyPaisa"}</option>
                                <option value="account">{translations["account"] || "Account"}</option>
                            </select>

                            <div className="d-flex mb-3">
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    className="form-control mr-2"
                                    placeholder={translations["discount"] || "Discount"}
                                    value={discount}
                                    onChange={this.setDiscount}
                                />
                                <input
                                    type="date"
                                    className="form-control"
                                    value={due_date}
                                    onChange={this.setDueDate}
                                />
                            </div>

                            <div className="user-cart">
                                {!hasSaleItems ? (
                                    <div className="empty-cart">
                                        <i className="fas fa-cash-register"></i>
                                        <p>{translations["empty_cart"] || "No products in this sale yet"}</p>
                                    </div>
                                ) : (
                                    <>
                                        {cart.map((c) => (
                                            <div className="cart-line" key={c.id}>
                                                <div className="cart-line-main">
                                                    <strong>{c.name}</strong>
                                                    <span>{window.APP.currency_symbol} {Number(c.price).toFixed(2)}</span>
                                                </div>
                                                <div className="cart-line-actions">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        step="1"
                                                        className="form-control form-control-sm qty"
                                                        value={c.pivot.quantity === "" ? "" : parseInt(c.pivot.quantity, 10)}
                                                        onChange={(event) => this.handleChangeQty(c.id, event.target.value)}
                                                    />
                                                    <span>{window.APP.currency_symbol} {(c.price * c.pivot.quantity).toFixed(2)}</span>
                                                    <button className="btn btn-outline-danger btn-sm" onClick={() => this.handleClickDelete(c.id)}>
                                                        <i className="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                        {openItems.map((item) => (
                                            <div className="cart-line" key={item.id}>
                                                <div className="cart-line-main">
                                                    <strong>{item.name}</strong>
                                                    <span>{window.APP.currency_symbol} {Number(item.price).toFixed(2)}</span>
                                                </div>
                                                <div className="cart-line-actions">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        step="1"
                                                        className="form-control form-control-sm qty"
                                                        value={item.quantity}
                                                        onChange={(event) => {
                                                            const quantity = parseInt(event.target.value, 10);
                                                            this.setState({
                                                                openItems: openItems.map((openItem) =>
                                                                    openItem.id === item.id ? { ...openItem, quantity } : openItem
                                                                ),
                                                            });
                                                        }}
                                                    />
                                                    <span>{window.APP.currency_symbol} {(item.price * item.quantity).toFixed(2)}</span>
                                                    <button
                                                        className="btn btn-outline-danger btn-sm"
                                                        onClick={() => this.setState({ openItems: openItems.filter((openItem) => openItem.id !== item.id) })}
                                                    >
                                                        <i className="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </>
                                )}
                            </div>

                            <button
                                type="button"
                                className="btn btn-primary btn-lg btn-block mt-3"
                                disabled={!hasSaleItems}
                                onClick={this.handleClickSubmit}
                            >
                                <i className="fas fa-credit-card mr-1"></i>
                                {translations["checkout"] || "Checkout"}
                            </button>
                        </div>
                    </div>
                    <div className="col-md-6 col-xl-8">
                        <div className="pos-panel">
                            <div className="pos-search">
                                <i className="fas fa-search"></i>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={translations["search_product"] || "Search or scan by barcode, SKU, short code, or product name"}
                                    value={this.state.search}
                                    onChange={this.handleChangeSearch}
                                    onKeyDown={this.handleSeach}
                                />
                            </div>

                            {quickItems.length > 0 ? (
                                <div className="pos-section mb-3">
                                    <div className="pos-section-title">{translations["quick_items"] || "Quick Items"}</div>
                                    <div className="quick-items">
                                        {quickItems.map((q) => (
                                            <button key={q.id} type="button" className="quick-item" onClick={() => this.addProductToCartById(q)}>
                                                <img src={q.image_url} alt={q.name} />
                                                <span>{q.name}</span>
                                                <small>{window.APP.currency_symbol} {Number(q.price).toFixed(2)} / {q.unit || "pcs"}</small>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            <div className="pos-section mb-3">
                                <div className="pos-section-title">{translations["categories"] || "Categories"}</div>
                                <div className="category-buttons">
                                    <button
                                        type="button"
                                        className={!selectedCategoryId ? "active" : ""}
                                        onClick={() => this.handleSelectCategory("")}
                                    >
                                        {translations["all"] || "All"}
                                    </button>
                                    {categories.map((category) => (
                                        <button
                                            key={category.id}
                                            type="button"
                                            className={String(selectedCategoryId) === String(category.id) ? "active" : ""}
                                            onClick={() => this.handleSelectCategory(category.id)}
                                        >
                                            {category.name}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="order-product">
                                {products.map((p) => (
                                    <button
                                        type="button"
                                        onClick={() => this.addProductToCartById(p)}
                                        key={p.id}
                                        className={`item ${p.track_stock && p.quantity <= 0 ? "is-disabled" : ""}`}
                                        disabled={p.track_stock && p.quantity <= 0}
                                    >
                                        <img src={p.image_url} alt={p.name} />
                                        <span className="product-name">{p.name}</span>
                                        <span className="product-meta">
                                            {window.APP.currency_symbol} {Number(p.price).toFixed(2)} / {p.unit || "pcs"}
                                        </span>
                                        {p.track_stock ? (
                                            <span className={window.APP.warning_quantity > p.quantity ? "stock-badge low" : "stock-badge"}>
                                                {Number(p.quantity).toFixed(0)}
                                            </span>
                                        ) : null}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

export default Cart;

const root = document.getElementById("cart");
if (root) {
    const rootInstance = createRoot(root);
    rootInstance.render(<Cart />);
}
