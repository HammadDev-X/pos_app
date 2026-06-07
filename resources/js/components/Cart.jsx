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
            customers: [],
            barcode: "",
            search: "",
            customer_id: "",
            payment_method: "cash",
            translations: {},
        };

        this.loadCart = this.loadCart.bind(this);
        this.handleOnChangeBarcode = this.handleOnChangeBarcode.bind(this);
        this.handleScanBarcode = this.handleScanBarcode.bind(this);
        this.handleChangeQty = this.handleChangeQty.bind(this);
        this.handleEmptyCart = this.handleEmptyCart.bind(this);

        this.loadProducts = this.loadProducts.bind(this);
        this.handleChangeSearch = this.handleChangeSearch.bind(this);
        this.handleSeach = this.handleSeach.bind(this);
        this.setCustomerId = this.setCustomerId.bind(this);
        this.setPaymentMethod = this.setPaymentMethod.bind(this);
        this.handleClickSubmit = this.handleClickSubmit.bind(this);
        this.loadTranslations = this.loadTranslations.bind(this);
    }

    componentDidMount() {
        // load user cart
        this.loadTranslations();
        this.loadCustomers();
        this.loadProducts();
        this.loadCart();
    }

    // load the transaltions for the react component
    loadTranslations() {
        axios
            .get("/admin/locale/cart")
            .then((res) => {
                const translations = res.data;
                this.setState({ translations });
            })
            .catch((error) => {
                console.error("Error loading translations:", error);
                this.setState({ translations: {} });
            });
    }

    loadCustomers() {
        axios.get(`/admin/customers`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            const customers = res.data;
            this.setState({ customers });
        }).catch((error) => {
            console.error("Error loading customers:", error);
            this.setState({ customers: [] });
        });
    }

    loadProducts(search = "") {
        const query = !!search ? `?search=${search}` : "";
        axios.get(`/admin/products${query}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            const products = res.data.data || [];
            this.setState({ products });
        }).catch((error) => {
            console.error("Error loading products:", error);
            this.setState({ products: [] });
        });
    }

    handleOnChangeBarcode(event) {
        const barcode = event.target.value;
        this.setState({ barcode });
    }

    loadCart() {
        axios.get("/admin/cart", {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            const cart = Array.isArray(res.data) ? res.data : [];
            this.setState({ cart });
        }).catch((error) => {
            console.error("Error loading cart:", error);
            this.setState({ cart: [] });
        });
    }
    handleScanBarcode(event) {
        event.preventDefault();
        const { barcode } = this.state;
        if (!!barcode) {
            axios
                .post("/admin/cart", { barcode })
                .then((res) => {
                    this.loadCart();
                    this.setState({ barcode: "" });
                })
                .catch((err) => {
                    Swal.fire("Error!", err.response.data.message, "error");
                });
        }
    }
    handleChangeQty(product_id, qty) {
        const quantity = Number(qty);
        const cart = this.state.cart.map((c) => {
            if (c.id === product_id) {
                c.pivot.quantity = qty === "" ? "" : quantity;
            }
            return c;
        });

        this.setState({ cart });
        if (!quantity || quantity < 1) return;

        axios
            .post("/admin/cart/change-qty", { product_id, quantity })
            .then((res) => {})
            .catch((err) => {
                Swal.fire("Error!", err.response.data.message, "error");
                this.loadCart();
            });
    }

    getTotal(cart) {
        const total = cart.map((c) => c.pivot.quantity * c.price);
        return sum(total).toFixed(2);
    }

    getCartCount(cart) {
        return sum(cart.map((c) => Number(c.pivot.quantity) || 0));
    }

    getLowStockCount(products) {
        return products.filter((p) => p.quantity <= window.APP.warning_quantity).length;
    }
    handleClickDelete(product_id) {
        axios
            .post("/admin/cart/delete", { product_id, _method: "DELETE" })
            .then((res) => {
                const cart = this.state.cart.filter((c) => c.id !== product_id);
                this.setState({ cart });
            });
    }
    handleEmptyCart() {
        axios.post("/admin/cart/empty", { _method: "DELETE" }).then((res) => {
            this.setState({ cart: [] });
        });
    }
    handleChangeSearch(event) {
        const search = event.target.value;
        this.setState({ search });
    }
    handleSeach(event) {
        if (event.keyCode === 13) {
            this.loadProducts(event.target.value);
        }
    }

    addProductToCart(barcode) {
        let product = this.state.products.find((p) => p.barcode === barcode);
        if (!!product) {
            // if product is already in cart
            let cart = this.state.cart.find((c) => c.id === product.id);
            if (!!cart) {
                // update quantity
                this.setState({
                    cart: this.state.cart.map((c) => {
                        if (
                            c.id === product.id &&
                            product.quantity > c.pivot.quantity
                        ) {
                            c.pivot.quantity = c.pivot.quantity + 1;
                        }
                        return c;
                    }),
                });
            } else {
                if (product.quantity > 0) {
                    product = {
                        ...product,
                        pivot: {
                            quantity: 1,
                            product_id: product.id,
                            user_id: 1,
                        },
                    };

                    this.setState({ cart: [...this.state.cart, product] });
                }
            }

            axios
                .post("/admin/cart", { barcode })
                .then((res) => {
                    // this.loadCart();
                })
                .catch((err) => {
                    Swal.fire("Error!", err.response.data.message, "error");
                });
        }
    }

    setCustomerId(event) {
        this.setState({ customer_id: event.target.value });
    }

    setPaymentMethod(event) {
        this.setState({ payment_method: event.target.value });
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
                    })
                    .then((res) => {
                        this.loadCart();
                        return res.data;
                    })
                    .catch((err) => {
                        Swal.showValidationMessage(err.response.data.message);
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
        const { cart = [], products = [], customers = [], barcode = "", translations = {}, payment_method = "cash" } = this.state;
        const total = this.getTotal(cart);
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
                                    disabled={!cart.length}
                                >
                                    <i className="fas fa-times mr-1"></i>
                                    {translations["cancel"] || "Cancel"}
                                </button>
                            </div>

                            <form onSubmit={this.handleScanBarcode} className="mb-2">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={translations["scan_barcode"] || "Scan Barcode"}
                                    value={barcode}
                                    onChange={this.handleOnChangeBarcode}
                                />
                            </form>

                            <select
                                className="form-control mb-2"
                                onChange={this.setCustomerId}
                            >
                                <option value="">
                                    {translations["general_customer"] || "General Customer"}
                                </option>
                                {customers.map((cus) => (
                                    <option
                                        key={cus.id}
                                        value={cus.id}
                                    >{`${cus.first_name} ${cus.last_name}`}</option>
                                ))}
                            </select>

                            <select
                                className="form-control mb-3"
                                value={payment_method}
                                onChange={this.setPaymentMethod}
                            >
                                <option value="cash">{translations["cash"] || "Cash"}</option>
                                <option value="card">{translations["card"] || "Card"}</option>
                                <option value="bank_transfer">{translations["bank_transfer"] || "Bank transfer"}</option>
                                <option value="mobile_money">{translations["mobile_money"] || "Mobile money"}</option>
                            </select>

                            <div className="user-cart">
                                {cart.length === 0 ? (
                                    <div className="empty-cart">
                                        <i className="fas fa-cash-register"></i>
                                        <p>{translations["empty_cart"] || "No products in this sale yet"}</p>
                                    </div>
                                ) : (
                                    cart.map((c) => (
                                        <div className="cart-line" key={c.id}>
                                            <div className="cart-line-main">
                                                <strong>{c.name}</strong>
                                                <span>{window.APP.currency_symbol} {Number(c.price).toFixed(2)}</span>
                                            </div>
                                            <div className="cart-line-actions">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    className="form-control form-control-sm qty"
                                                    value={c.pivot.quantity}
                                                    onChange={(event) =>
                                                        this.handleChangeQty(
                                                            c.id,
                                                            event.target.value
                                                        )
                                                    }
                                                />
                                                <span>{window.APP.currency_symbol} {(c.price * c.pivot.quantity).toFixed(2)}</span>
                                                <button
                                                    className="btn btn-outline-danger btn-sm"
                                                    onClick={() =>
                                                        this.handleClickDelete(
                                                            c.id
                                                        )
                                                    }
                                                >
                                                    <i className="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <button
                                type="button"
                                className="btn btn-primary btn-lg btn-block mt-3"
                                disabled={!cart.length}
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
                                    placeholder={(translations["search_product"] || "Search Product") + "..."}
                                    onChange={this.handleChangeSearch}
                                    onKeyDown={this.handleSeach}
                                />
                            </div>
                            <div className="order-product">
                                {products.map((p) => (
                                    <button
                                        type="button"
                                        onClick={() => this.addProductToCart(p.barcode)}
                                        key={p.id}
                                        className={`item ${p.quantity <= 0 ? "is-disabled" : ""}`}
                                        disabled={p.quantity <= 0}
                                    >
                                        <img src={p.image_url} alt={p.name} />
                                        <span className="product-name">{p.name}</span>
                                        <span className="product-meta">
                                            {window.APP.currency_symbol} {Number(p.price).toFixed(2)}
                                        </span>
                                        <span
                                            className={
                                                window.APP.warning_quantity > p.quantity
                                                    ? "stock-badge low"
                                                    : "stock-badge"
                                            }
                                        >
                                            {p.quantity}
                                        </span>
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
