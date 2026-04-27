import React, { useEffect, useState, useCallback } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import Cart from "./Cart";
import toast, { Toaster } from "react-hot-toast";
import CustomerSelect from "./CutomerSelect";

import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

export default function Pos() {
    // Loading page status
    const [status, setStatus] = useState('pending')

    const [products, setProducts] = useState([]);
    const [carts, setCarts] = useState([]);
    const [orderDiscount, setOrderDiscount] = useState(0);
    const [paid, setPaid] = useState(0);
    const [due, setDue] = useState(0);
    const [total, setTotal] = useState(0);
    const [updateTotal, setUpdateTotal] = useState(0);
    const [customerId, setCustomerId] = useState();
    const [cartUpdated, setCartUpdated] = useState(false);
    const [productUpdated, setProductUpdated] = useState(false);
    const [searchQuery, setSearchQuery] = useState("");
    const [searchBarcode, setSearchBarcode] = useState("");
    const { protocol, hostname, port } = window.location;
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(0);
    const [loading, setLoading] = useState(false);
    const fullDomainWithPort = `${protocol}//${hostname}${port ? `:${port}` : ""}`;

    // Payment Methods
    const [paymentMethods, setPaymentMethods] = useState(0);
    const [selectedMethod, setSelectedMethod] = useState(null);
    const [surchargeAmount, setSurchargeAmount] = useState(0);

    const [currency, setCurrency] = useState(null)

    const getProducts = useCallback(
        async (search = "", page = 1, barcode = "") => {
            setLoading(true);
            try {
                const res = await axios.get('/admin/get/products', {
                    params: { search, page, barcode },
                });

                console.log('RESPO', res);

                const productsData = res.data;
                setProducts((prev) => [...prev, ...productsData.data]); // Append new products
                if (productsData.data.length === 1 && barcode != "") {
                    addProductToCart(productsData.data[0].id);
                    getCarts();
                }
                setTotalPages(productsData.meta.last_page); // Get total pages
            } catch (error) {
                console.error("Error fetching products:", error);
            } finally {
                setLoading(false); // Set loading to false
            }
        },
        []
    );

    const getUpdatedProducts = useCallback(async () => {
        try {
            const res = await axios.get('/admin/get/products');
            const productsData = res.data;

            setProducts([])
            setProducts(productsData.data);
            setTotalPages(productsData.meta.last_page); // Get total pages
        } catch (error) {
            console.error("Error fetching products:", error);
        }
    }, []);

    const getCarts = async () => {
        try {
            const res = await axios.get('/admin/cart');
            const data = res.data;

            setTotal(data?.total);

            setCarts(data?.carts);
            let currSurchargeAmount = 0;

            if (data?.wallets.length > 0) {
                setPaymentMethods(data?.wallets);
                const primaryPaymentMethod = data?.wallets.find(m => m.primary === true) || methods[0];

                setSelectedMethod(primaryPaymentMethod);
                currSurchargeAmount = calculateSurchargeAmount(primaryPaymentMethod, data?.total)
            }

            setCurrency(data?.currency)
            setSurchargeAmount(currSurchargeAmount);
            setUpdateTotal(data?.total - orderDiscount + currSurchargeAmount);

            console.log('UPD TOT', data?.total - orderDiscount + currSurchargeAmount);

            setTimeout(() => {
                setStatus('fulfilled')
            }, 1000)
        } catch (error) {
            setStatus('rejected')
            console.error("Error fetching carts:", error);
        }
    };

    function calculateSurchargeAmount(method, subTotal = 0) {
        let calcSurchargeAmount = 0;
        let whichTotal = total === 0 ? subTotal : total

        if (method.surcharge_value > 0) {
            calcSurchargeAmount = method.surcharge_type === 'percentage'
                ? (whichTotal * (method.surcharge_value / 100))
                : parseFloat(method.surcharge_value);
        }

        return calcSurchargeAmount
    }

    // Calculate surcharge amount whenever method or total changes
    const updatePaymentMethod = (method) => {
        setSelectedMethod(method);

        const calcSurchargeAmount = calculateSurchargeAmount(method)
        setSurchargeAmount(calcSurchargeAmount);
    };

    useEffect(() => {
        getCarts();
    }, [cartUpdated]);

    useEffect(() => {
        let paid1 = paid;
        let disc = orderDiscount;

        if (paid == "") {
            paid1 = 0;
        }

        if (orderDiscount == "") {
            disc = 0;
        }

        const updatedTotalAmount = parseFloat(total) - parseFloat(disc) + parseFloat(surchargeAmount);
        const dueAmount = updatedTotalAmount - parseFloat(paid1);

        setUpdateTotal(updatedTotalAmount?.toFixed(2));
        setDue(dueAmount?.toFixed(2));
    }, [orderDiscount, paid, total, surchargeAmount]);

    useEffect(() => {
        if (searchQuery) {
            setProducts([]);
            getProducts(searchQuery, currentPage, "");
        }
        setSearchBarcode("");
    }, [currentPage, searchQuery]);

    useEffect(() => {
        if (searchBarcode) {
            setProducts([]);
            getProducts("", currentPage, searchBarcode);
        }
    }, [searchBarcode]);

    // Infinite scroll logic
    useEffect(() => {
        const handleScroll = () => {
            if (
                window.innerHeight + document.documentElement.scrollTop >=
                document.documentElement.offsetHeight
            ) {
                // Load next page if not on the last page
                if (currentPage < totalPages) {
                    setCurrentPage((prev) => prev + 1);
                }
            }
        };

        window.addEventListener("scroll", handleScroll);
        return () => {
            window.removeEventListener("scroll", handleScroll);
        };
    }, [currentPage, totalPages]);

    function addProductToCart(id) {
        console.log('CUSTO', customerId);


        axios
            .post("/admin/cart", { id })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success(res?.data?.message);

                setSearchQuery("");
                setProducts([]);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(err.response.data.message);
            });
    }

    function cartEmpty() {
        if (total <= 0) {
            return;
        }
        Swal.fire({
            title: "Are you sure you want to delete Cart?",
            showDenyButton: true,
            confirmButtonText: "Yes",
            denyButtonText: "No",
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/cart/empty")
                    .then((res) => {
                        setCartUpdated(!cartUpdated);
                        playSound(SuccessSound);
                        toast.success(res?.data?.message);
                    })
                    .catch((err) => {
                        playSound(WarningSound);
                        toast.error(err.response.data.message);
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }
    function orderCreate() {
        if (total <= 0) {
            return;
        }

        if (!customerId) {
            toast.error("Please select customer");
            return;
        }

        Swal.fire({
            title: `Are you sure you want to complete this order? <br>Due: ${due}`,
            showDenyButton: true,
            confirmButtonText: "Yes",
            denyButtonText: "No",
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/order/create", {
                        customer_id: customerId,
                        order_discount: parseFloat(orderDiscount) || 0,
                        paid: parseFloat(paid) || 0,

                        // Add payment method details
                        payment_method_code: selectedMethod?.code,
                        surcharge_amount: parseFloat(surchargeAmount) || 0,
                    })
                    .then((res) => {
                        setCartUpdated(!cartUpdated);
                        setProductUpdated(!productUpdated);
                        toast.success(res?.data?.message);

                        // window.location.href = `orders/invoice/${res?.data?.order?.id}`;
                        window.location.href = `orders/pos-invoice/${res?.data?.order?.id}`;
                    })
                    .catch((err) => {
                        const errorMsg = err.response?.data?.message || "Something went wrong processing the order";
                        toast.error(errorMsg);
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }

    return (
        <>
            {
                status === 'rejected' ? (
                    <>
                        <main className="flex-grow-1 d-flex justify-content-center align-items-center text-center">
                            <div className="container">
                                {/* Font Awesome Error Icon */}
                                <i className="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>

                                <h4 className="display-5">Something went wrong</h4>

                                <p className="text-muted">
                                    We're having trouble loading this page.<br />
                                    Please try again later.
                                </p>

                                <div className="w-100 d-flex align-items-center justify-content-center">
                                    <button className="btn btn-primary mt-3 d-flex align-items-center justify-content-center gap-2" onClick={() => window.location.reload()}>
                                        <span className="pr-2">Try Again</span>
                                        <i className="fas fa-redo me-2"></i>
                                    </button>
                                </div>
                            </div>
                        </main>
                    </>
                ) : status === 'fulfilled' ? (
                    <>
                        <div className="card h-100">
                            <div className="card-body p-2 p-md-4 pt-0 h-100">
                                <div className="row">
                                    <div className="col-md-6 col-lg-8 mb-2">
                                        <div className="row mb-2">
                                            <div className="col-6">
                                                <CustomerSelect
                                                    setCustomerId={setCustomerId}
                                                />
                                            </div>
                                        </div>

                                        <hr className="my-3 opacity-50" />

                                        <div className="card shadow-none border-0 min-vh-50 mb-0">
                                            <div className="card-header bg-white pb-0 px-0" style={{ paddingTop: 0, border: 0 }}>
                                                <div className="input-group border rounded-2 overflow-hidden bg-light">
                                                    <div className="input-group-prepend border-0">
                                                        <span className="input-group-text bg-transparent border-0"><i className="fas fa-barcode text-muted"></i></span>
                                                    </div>

                                                    <input
                                                        type="text"
                                                        className="form-control border-0 bg-transparent"
                                                        onChange={(e) => setSearchQuery(e.target.value)}
                                                        placeholder="Scan Barcode or Search Product Name..."
                                                        value={searchQuery}
                                                        autoFocus
                                                    />
                                                </div>

                                                {
                                                    products.length > 0 && (
                                                        <div className="position-absolute w-100 shadow-lg rounded mt-1" style={{ zIndex: 1000, left: 0, right: 0 }}>
                                                            <div className="list-group">
                                                                {products.map((product) => (
                                                                    <button key={product.id} className="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onClick={() => addProductToCart(product.id)} >
                                                                        <div>
                                                                            <h6 className="mb-0 font-weight-bold">
                                                                                {product.name} <span className="text-muted small">{product.brand}</span>
                                                                            </h6>

                                                                            <small className="text-muted">Stock: {product.quantity} | SKU: {product.sku}</small>
                                                                        </div>
                                                                        <span className="text-muted font-weigh">
                                                                            {product.currency} {Number(product.purchase_price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                        </span>
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )
                                                }
                                            </div>
                                        </div>

                                        <div className="row mb-2 px-">
                                            <div className="col-12 pt-3">
                                                <Cart
                                                    carts={carts}
                                                    setCartUpdated={setCartUpdated}
                                                    cartUpdated={cartUpdated}
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="col-md-6 col-lg-4 mb-2">
                                        <div className="card">
                                            <div className="card-body">
                                                <div className="mb-4 row">
                                                    <div className="col-8">
                                                        <label htmlFor="payment_method" className="text-uppercase fw-bold text-muted mb-2 d-block">
                                                            Payment Method
                                                        </label>

                                                        <select className="form-control select2" name="payment_method" value={selectedMethod?.code || ''}
                                                            onChange={(e) => {
                                                                const method = paymentMethods.find(m => m.code === e.target.value);
                                                                updatePaymentMethod(method);
                                                            }}
                                                        >
                                                            {paymentMethods.map((method) => (
                                                                <option key={method.code} value={method.code}>
                                                                    {method.name} {method.surcharge_value > 0 ? `(${method.surcharge_value}${method.surcharge_type === 'percentage' ? '%' : ' Flat'})` : ''}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>
                                                </div>


                                                <div className="bg-light rounded-3 p-3 mb-4">
                                                    <div className="d-flex justify-content-between align-items-center mb-3">
                                                        <span className="text-secondary fw-medium">Sub Total</span>
                                                        <span className="fw-bold">
                                                            {currency} {Number(total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </span>
                                                    </div>

                                                    <div className="d-flex justify-content-between align-items-center mb-3">
                                                        <span className="text-secondary fw-medium">Surcharge Type</span>

                                                        <span className="fw-bold">
                                                            {
                                                                selectedMethod?.surcharge_type === 'percentage' ? (
                                                                    <span>{`${selectedMethod?.surcharge_value} %`}</span>
                                                                ) : selectedMethod?.surcharge_type === 'fixed' ? (
                                                                    <span>Fixed</span>
                                                                ) : (
                                                                    <span>None</span>
                                                                )
                                                            }
                                                        </span>
                                                    </div>

                                                    <div className="d-flex justify-content-between align-items-center mb-3">
                                                        <span className="text-secondary fw-medium">Surcharge Value</span>

                                                        <span className="fw-bold">
                                                            {currency} {Number(surchargeAmount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="d-flex justify-content-between align-items-center mb-3">
                                                    <span className="text-secondary fw-medium flex-grow-1">
                                                        Award Discount:
                                                    </span>

                                                    <div className="flex-shrink-0" style={{ width: "150px" }}>
                                                        <div className="input-group input-group-sm">
                                                            <input
                                                                type="number"
                                                                className="form-control form-control-sm fw-bold text-end border-start-0"
                                                                placeholder="0.00"
                                                                min={0}
                                                                disabled={total <= 0}
                                                                value={orderDiscount || ""}
                                                                onFocus={(e) => e.target.select()} // Select all text on click for faster typing
                                                                onChange={(e) => {
                                                                    const value = e.target.value;
                                                                    const numValue = parseFloat(value);

                                                                    // Allow clearing the input (empty string) or valid numbers within range
                                                                    if (value === "" || (numValue >= 0 && numValue <= total)) {
                                                                        setOrderDiscount(value);
                                                                    }
                                                                }}
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr className="my-3 opacity-50" />

                                                <div className="d-flex justify-content-between align-items-center mb-3">
                                                    <span className="text-secondary fw-medium flex-grow-1">
                                                        Amount Paid:
                                                    </span>

                                                    <div className="flex-shrink-0" style={{ width: "150px" }}>
                                                        <div className="input-group input-group-sm">
                                                            <input
                                                                type="number"
                                                                className="form-control form-control-sm"
                                                                placeholder="Enter paid"
                                                                min={0}
                                                                disabled={total <= 0}
                                                                value={paid}
                                                                onChange={(e) => {
                                                                    const value =
                                                                        e.target.value;
                                                                    if (
                                                                        parseFloat(value) < 0 ||
                                                                        parseFloat(value) >
                                                                        updateTotal
                                                                    ) {
                                                                        return;
                                                                    }
                                                                    setPaid(value);
                                                                }}
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Final Totals Section */}
                                                <div className="bg-light rounded p-3 mt-3">
                                                    <div className="d-flex justify-content-between align-items-center mb-1">
                                                        <span className="h5 mb-0 fw-bold">Total Due</span>
                                                        <span className="h4 mb-0 fw-bold text-dark">
                                                            {Number(updateTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </span>
                                                    </div>
                                                    <div className="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-2">
                                                        <span className="small fw-bold text-uppercase">Balance</span>
                                                        <span className={`h5 mb-0 fw-bold ${due > 0 ? 'text-danger' : 'text-success'}`}>
                                                            {Number(due).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col">
                                                <button
                                                    onClick={() => cartEmpty()}
                                                    type="button"
                                                    className="btn bg-gradient-danger btn-block text-white text-bold"
                                                >
                                                    Clear Cart
                                                </button>
                                            </div>

                                            <div className="col">
                                                <button
                                                    onClick={() => {
                                                        orderCreate();
                                                    }}
                                                    type="button"
                                                    className="btn bg-gradient-primary btn-block text-white text-bold"
                                                >
                                                    Checkout
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </>
                ) : (
                    <>
                        <div className="d-flex flex-column h-100">
                            <main className="flex-grow-1 d-flex justify-content-center align-items-center">
                                <div className="spinner-border text-primary" role="status">
                                </div>
                            </main>
                        </div>
                    </>
                )
            }

            <Toaster position="top-right" reverseOrder={false} />
        </>
    );
}