import axios from "axios";
import React, { useState, useEffect } from "react";
import toast, { Toaster } from "react-hot-toast";
import Swal from "sweetalert2";
import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

export default function Cart({ carts, setCartUpdated, cartUpdated }) {
    const [loadingId, setLoadingId] = useState(null);

    // Optimized Request Handler
    async function handleUpdate(action, id) {
        setLoadingId(id);
        try {
            const res = await axios.put(`/admin/cart/${action}`, { id });
            setCartUpdated(!cartUpdated);
            playSound(SuccessSound);
            // Optional: toast.success(res?.data?.message); // Removed to reduce noise in fast scanning
        } catch (err) {
            playSound(WarningSound);
            toast.error(err.response?.data?.message || "Update failed");
        } finally {
            setLoadingId(null);
        }
    }

    function increment(id) {
        axios
            .put("/admin/cart/increment", {
                id: id,
            })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(err.response.data.message);
            });
    }

    function decrement(id) {
        axios
            .put("/admin/cart/decrement", {
                id: id,
            })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(err.response.data.message);
            });
    }

    function destroy(id) {
        Swal.fire({
            title: "Are you sure you want to delete this item?",
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
                    .put("/admin/cart/delete", {
                        id: id,
                    })
                    .then((res) => {
                        console.log(res);
                        setCartUpdated(!cartUpdated);
                        playSound(SuccessSound);
                        toast.success(res?.data?.message);
                    })
                    .catch((err) => {
                        toast.error(err.response.data.message);
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }

    return (
        <div className="pos-cart-container h-100">
            <div className="card shadow-none border-0 h-100">
                {/* <div className="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 className="mb-0 font-weight-bold flex-grow-1 text-primary">
                        <i className="fas fa-shopping-cart mr-2"></i>
                        Current Order
                    </h6>

                    <span className="badge badge-1 badge-light text-muted">{carts.length} Item(s)</span>
                </div> */}

                <div className="card-body p-0" style={{ overflowY: 'auto', maxHeight: '60vh' }}>
                    {carts.length === 0 ? (
                        <div className="text-center py-5">
                            <i className="fas fa-cart-plus fa-3x text-muted mb-3"></i>
                            <p className="text-muted">Cart is empty</p>
                        </div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-borderless align-middle mb-0">
                                <thead className="thead border-bottom">
                                    <tr className="small text-uppercase text-muted">
                                        <th className="px-2">Product</th>
                                        <th className="text-center">Qty</th>
                                        <th className="text-right px-3">Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {
                                        carts.map((item) => (
                                            <tr key={item.id} className="border-bottom">
                                                <td className="px-2 py-2">
                                                    <div className="font-weight-bold text-dark mb-0">
                                                        {item.product.name}
                                                    </div>

                                                    <div className="small text-muted font-weight-normal">
                                                        {item.currency} {Number(item.product.discounted_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}

                                                        {item.product.price > item.product.discounted_price && (
                                                            <del className="text-muted ml-2 font-weight-normal">{item.product.price}</del>
                                                        )} | {item.brand}
                                                    </div>
                                                </td>

                                                <td className="align-middle px-2 py-2">
                                                    <div className="d-flex align-items-center justify-content-between bg-transparent" style={{width: '120px', margin: '0 auto', padding: '2px 0'}}>
                                                        <button className="btn btn-xs btn-light shadow-none rounded-circle d-flex align-items-center justify-content-center" style={{width: '28px', height: '28px', backgroundColor: '#f8f9fa', border: '1px solid #eee'}} onClick={() => item.quantity > 1 ? handleUpdate('decrement', item.id) : destroy(item.id)} disabled={loadingId === item.id}>
                                                            <i className="fas fa-minus fa-xs text-secondary"></i>
                                                        </button>

                                                        <span className="font-weight-bold text-dark" style={{ fontSize: '1rem' }}>
                                                            {item.quantity}
                                                        </span>

                                                        <button className="btn btn-xs btn-light shadow-none rounded-circle d-flex align-items-center justify-content-center" style={{width: '28px', height: '28px', backgroundColor: '#f8f9fa', border: '1px solid #eee'}} onClick={() => handleUpdate('increment', item.id)} disabled={loadingId === item.id}>
                                                            <i className="fas fa-plus fa-xs text-secondary"></i>
                                                        </button>
                                                    </div>
                                                </td>

                                                <td className="text-right px-2 py-2 align-middle">
                                                    <div className="font-weight-n text-muted">
                                                        {item.currency} {Number(item.row_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                    </div>

                                                    <button className="btn btn-link btn-sm text-danger p-0" onClick={() => destroy(item.id)} title="Remove item">
                                                        <i className="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    }
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
            <Toaster position="top-right" />
        </div>
    );
}
