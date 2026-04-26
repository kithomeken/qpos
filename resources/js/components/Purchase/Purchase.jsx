import React, { useCallback, useEffect, useState } from "react";
import Suppliers from "./Suppliers";
import axios from "axios";
import Swal from "sweetalert2";
import toast, { Toaster } from "react-hot-toast";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

export default function Purchase() {
    const [searchTerm, setSearchTerm] = useState("");
    const [barcode, setBarcode] = useState("");
    const [selectedSupplier, setSelectedSupplier] = useState({
        value: 1,
        label: "Own Supplier",
    });
    const [purchaseId, setPurchaseId] = useState(null);
    const [date, setDate] = useState(null);
    const [supplierId, setSupplierId] = useState(null);
    const [tax, setTax] = useState(0);
    const [discount, setDiscount] = useState(0);
    const [shipping, setShipping] = useState(0);
    const [products, setProducts] = useState([]);
    const [searchResults, setSearchResults] = useState([]);

    useEffect(() => {
        const searchParams = new URLSearchParams(window.location.search);
        const barcodeParam = searchParams.get("barcode");
        const purchase_id = searchParams.get("purchase_id");
        if (barcodeParam) {
            setSearchTerm(barcodeParam);
            setBarcode(barcodeParam);
        }
        if (purchase_id) {
            setPurchaseId(purchase_id);
        }
    }, []);
    useEffect(() => {
        if (barcode) {
            getProducts();
        }
    }, [barcode]);
    useEffect(() => {
        if (purchaseId) {
            getPurchaseProducts();
        }
    }, [purchaseId]);
    const getPurchaseProducts = useCallback(async () => {
        try {
            const res = await axios.get(`/admin/purchase/${purchaseId}`);
            const purchaseData = res.data;
            const purchaseProducts = purchaseData?.items?.map((item) => ({
                item_id: item.id,
                id: item.product_id,
                name: item.name,
                price: item.price,
                purchase_price: item.purchase_price,
                stock: item.stock,
                qty: item.quantity,
                subTotal: item.purchase_price * item.quantity,
            }));
            setProducts(purchaseProducts);
            setDate(purchaseData?.date ? purchaseData.date.split(" ")[0] : "");
            setSelectedSupplier({
                value: purchaseData?.supplier_id,
                label: purchaseData?.supplier?.name,
            });
            setTax(purchaseData?.tax);
            setDiscount(purchaseData?.discount_value);
            setShipping(purchaseData?.shipping);
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
        }
    }, [purchaseId]);

    const getProducts = useCallback(async () => {
        if (!searchTerm.trim()) {
            console.log("Search term is empty");
            return;
        }

        // Optional: Uncomment if you want to show loading state
        setLoading(true);

        try {
            const res = await axios.get("/admin/products", {
                params: { search: searchTerm },
            });

            const productsData = res.data;

            // Ensure productsData and productsData.data exist
            if (productsData?.data && productsData.data.length) {
                productsData.data.forEach((product) => {
                    const existingProductIndex = products.findIndex(
                        (p) => p.id === product.id,
                    );
                    if (existingProductIndex !== -1) {
                        // Product exists, increment qty
                        setProducts((prevProducts) => {
                            const updatedProducts = [...prevProducts];
                            updatedProducts[existingProductIndex].qty += 1; // Increment qty
                            updatedProducts[existingProductIndex].subTotal =
                                updatedProducts[existingProductIndex]
                                    .purchase_price *
                                updatedProducts[existingProductIndex].qty; // Update subTotal
                            return updatedProducts;
                        });
                    } else {
                        // New product, add to the list
                        const newProduct = {
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            purchase_price: product.purchase_price,
                            stock: product.quantity,
                            qty: 1,
                            subTotal: product.purchase_price,
                        };
                        setProducts((prevProducts) => [
                            ...prevProducts,
                            newProduct,
                        ]);
                    }
                });
            }
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
            // Optional: Uncomment if you want to hide loading state
            // setLoading(false);

            // Clear searchTerm if needed
            setSearchTerm("");
        }
    }, [searchTerm]); // Don't forget to add searchTerm as a dependency

    // Handle deletion of a product
    const handleDelete = (id) => {
        setProducts(products.filter((product) => product.id !== id));
    };

    // Update quantity and recalculate subtotal
    const handleQtyChange = (id, value) => {
        const updatedProducts = products.map((product) => {
            if (product.id === id) {
                const newQty = parseInt(value) || 0;
                return {
                    ...product,
                    qty: newQty,
                    subTotal: parseFloat(
                        (product.purchase_price * newQty).toFixed(2),
                    ),
                };
            }
            return product;
        });
        setProducts(updatedProducts);
    };

    // Update purchase price and recalculate subtotal
    const handlePriceChange = (id, value) => {
        const updatedProducts = products.map((product) => {
            if (product.id === id) {
                const newPrice = parseFloat(value) || 0;
                return {
                    ...product,
                    purchase_price: newPrice,
                    subTotal: parseFloat((product.qty * newPrice).toFixed(2)),
                };
            }
            return product;
        });
        setProducts(updatedProducts);
    };
    // Add a new product by searching
    const handleSearchAdd = () => {
        getProducts();
    };

    // Calculate totals with two decimal places
    const calculateTotals = () => {
        const subTotal = products.reduce(
            (sum, product) => sum + product.subTotal,
            0,
        );
        const formattedSubTotal = parseFloat(Number(subTotal).toFixed(2));
        const formattedTax = parseFloat((tax || 0).toFixed(2));
        const formattedDiscount = parseFloat((discount || 0).toFixed(2));
        const formattedShipping = parseFloat((shipping || 0).toFixed(2));
        const grandTotal = parseFloat(
            (
                formattedSubTotal +
                formattedTax -
                formattedDiscount +
                formattedShipping
            ).toFixed(2),
        );

        return {
            subTotal: formattedSubTotal,
            tax: formattedTax,
            discount: formattedDiscount,
            shipping: formattedShipping,
            grandTotal,
        };
    };

    const totals = calculateTotals();

    const handleSubmit = async () => {
        if (totals.grandTotal <= 0) {
            //    toast.error("Total must be greater than zero.");
            return;
        }
        if (!date) {
            toast.error("Please select purchase date.");
            return;
        }
        if (!supplierId) {
            toast.error("Please select a supplier.");
            return;
        }

        // Show confirmation dialog
        Swal.fire({
            title: `Are you sure you want to save this purchase?`,
            showDenyButton: true,
            confirmButtonText: "Yes",
            denyButtonText: "No",
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then(async (result) => {
            if (result.isConfirmed) {
                //    console.log("data:", {
                //        products,
                //        supplierId,
                //        totals,
                //    }); return;
                try {
                    const res = await axios.post("/admin/purchase", {
                        purchase_id: purchaseId,
                        date,
                        products,
                        supplierId,
                        totals,
                    });
                    setProducts([]);
                    toast.success(res?.data?.message);
                    window.location.href = "/admin/purchase";
                } catch (err) {
                    toast.error(
                        err.response?.data?.message || "An error occurred",
                    );
                }
            }
        });
    };

    // product search
    useEffect(() => {
        // Define the asynchronous function
        async function getProducts() {
            if (!searchTerm.trim()) {
                setSearchResults([]);
                return;
            }

            try {
                const res = await axios.get("/admin/products", {
                    params: { search: searchTerm },
                });

                const productsData = res.data;
                setSearchResults(productsData?.data || []);
            } catch (error) {
                console.error("Error fetching products:", error);
            }
        }
        // Call the async function inside useEffect
        getProducts();
    }, [searchTerm]);

    // Handle adding selected product to the products list
    // Handle adding selected product to the products list
    const handleProductSelect = (product) => {
        setProducts((prevProducts) => {
            const existingProduct = prevProducts.find((p) => p.id === product.id);

            if (existingProduct) {
                // Return a NEW array with a NEW object for the updated item
                return prevProducts.map((p) =>
                    p.id === product.id
                        ? {
                            ...p,
                            qty: p.qty + 1,
                            subTotal: p.purchase_price * (p.qty + 1)
                        }
                        : p
                );
            }

            // Add new product
            const newProduct = {
                id: product.id,
                name: product.name,
                price: product.price,
                purchase_price: product.purchase_price,
                stock: product.quantity,
                qty: 1,
                subTotal: product.purchase_price,
            };

            return [...prevProducts, newProduct];
        });

        setSearchTerm("");
        setSearchResults([]);
    };

    return (
        <div className="container-fluid">
            <Toaster position="top-right" />

            <div className="row">
                {/* Left Side: Product Selection & Table */}
                <div className="col-lg-9">
                    <div className="card shadow-sm border-0 min-vh-50">
                        <div className="card-header bg-white py-3">
                            <div className="row align-items-center pb-4">
                                <div className="col-md-3">
                                    <label className="small font-weight-bold text-uppercase text-muted mb-1">Purchase Date</label>
                                    <div className="d-flex">
                                        <DatePicker
                                            className="form-control"
                                            selected={date ? new Date(date) : null}
                                            dateFormat="yyyy-MM-dd"
                                            onChange={(d) => setDate(d ? d.toISOString().split("T")[0] : null)}
                                            placeholderText="Select Date"
                                        />
                                    </div>
                                </div>
                                <div className="col-md-5">
                                    <label className="small font-weight-bold text-uppercase text-muted mb-1">Supplier</label>
                                    <Suppliers setSupplierId={setSupplierId} oldSupplier={selectedSupplier} />
                                </div>
                            </div>

                            <div className="input-group border rounded-2 overflow-hidden bg-light">
                                <div className="input-group-prepend border-0">
                                    <span className="input-group-text bg-transparent border-0"><i className="fas fa-barcode text-muted"></i></span>
                                </div>

                                <input
                                    type="text"
                                    className="form-control border-0 bg-transparent"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Scan barcode or type product name..."
                                    autoFocus
                                />
                            </div>

                            {
                                searchResults.length > 0 && (
                                    <div className="position-absolute w-100 shadow-lg rounded mt-1" style={{ zIndex: 1000, left: 0, right: 0 }}>
                                        <div className="list-group">
                                            {searchResults.map((product) => (
                                                <button
                                                    key={product.id}
                                                    className="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                                                    onClick={() => handleProductSelect(product)}
                                                >
                                                    <div>
                                                        <h6 className="mb-0 font-weight-bold">{product.name}</h6>
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

                        <div className="card-body p-0 pb-4">
                            <div className="table-responsive">
                                <table className="table table-hover mb-0">
                                    <thead className="thead-light">
                                        <tr>
                                            <th className="px-4">Product Details</th>
                                            <th width="150">Unit Price</th>
                                            <th width="120" className="text-center">Qty</th>
                                            <th width="150" className="text-right px-4">Subtotal</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {
                                            products.length === 0 ? (
                                                <tr>
                                                    <td colSpan="5" className="text-center py-5 text-muted">
                                                        <i className="fas fa-shopping-cart fa-3x mb-3 opacity-2"></i>
                                                        <p>
                                                            Add products you've purchased by searching from above
                                                        </p>
                                                    </td>
                                                </tr>
                                            ) : (
                                                products.map((product) => (
                                                    <tr key={product.id}>
                                                        <td className="px-4 align-middle">
                                                            <div className="font-weight-bold">{product.name}</div>
                                                            <small className="text-muted">Current Stock: {product.stock}</small>
                                                        </td>

                                                        <td className="align-middle">
                                                            <input
                                                                type="number"
                                                                className="form-control form-control-sm text-right font-weight-bold border-0 bg-light"
                                                                value={product.purchase_price}
                                                                onChange={(e) => handlePriceChange(product.id, e.target.value)}
                                                                onWheel={(e) => e.target.blur()}
                                                            />
                                                        </td>

                                                        <td className="align-middle">
                                                            <input
                                                                type="number"
                                                                className="form-control form-control-sm text-center font-weight-bold"
                                                                value={product.qty}
                                                                onChange={(e) => handleQtyChange(product.id, e.target.value)}
                                                                onWheel={(e) => e.target.blur()}
                                                            />
                                                        </td>

                                                        <td className="align-middle text-right px-4 font-weight-bold">
                                                            {product.currency} {Number(product.subTotal).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                        </td>

                                                        <td className="align-middle text-center">
                                                            <button className="btn btn-link text-danger p-0" onClick={() => handleDelete(product.id)}>
                                                                <i className="fas fa-times-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                ))
                                            )
                                        }
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Side: Calculation & Actions */}
                <div className="col-lg-3">
                    <div className="card shadow-sm border-0 sticky-top" style={{ top: '20px' }}>
                        <div className="card-header bg-primary text-white text-center py-3">
                            <h5 className="mb-0">Order Summary</h5>
                        </div>

                        <div className="card-body">
                            <ul className="list-group list-group-flush mb-3">
                                <li className="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Subtotal</span>
                                    <span className="font-weight-bold">{totals.subTotal.toLocaleString()}</span>
                                </li>

                                <li className="list-group-item px-0 bg-transparent border-0 pb-0">
                                    <label className="small text-muted mb-1">Tax Adjustments</label>
                                    <input type="number" className="form-control form-control-sm text-right" value={tax} onChange={(e) => setTax(parseFloat(e.target.value) || 0)} onWheel={(e) => e.target.blur()} />
                                </li>

                                <li className="list-group-item px-0 bg-transparent border-0 pb-0">
                                    <label className="small text-muted mb-1">Discount</label>
                                    <input type="number" className="form-control form-control-sm text-right text-danger" value={discount} onChange={(e) => setDiscount(parseFloat(e.target.value) || 0)} onWheel={(e) => e.target.blur()} />
                                </li>
                                
                                <li className="list-group-item px-0 bg-transparent border-0">
                                    <label className="small text-muted mb-1">Shipping</label>
                                    <input type="number" className="form-control form-control-sm text-right" value={shipping} onChange={(e) => setShipping(parseFloat(e.target.value) || 0)} onWheel={(e) => e.target.blur()} />
                                </li>
                            </ul>

                            <div className="p-3 bg-light rounded-lg text- mb-3">
                                <div className="small text-uppercase text-muted mb-1 font-weight-bold">Grand Total</div>
                                <h4 className="mb-0 font-weight-bold">
                                    KES {totals.grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                </h4>
                            </div>

                            <button
                                className="btn btn-primary btn-block shadow shadow-sm py-3 font-weight-bold"
                                onClick={handleSubmit}
                                disabled={totals.grandTotal <= 0}
                            >
                                <i className="fas fa-check-circle mr-2"></i> {purchaseId ? 'Update Purchase' : 'Complete Purchase'}
                            </button>

                            <a href="/admin/purchase" className="btn btn-link btn-block text-muted mt-2 small">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
