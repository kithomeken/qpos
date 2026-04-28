@extends('backend.master')

@section('title', 'Add Payment Method')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0">Configure New Payment Method</h5>
                </div>

                <form action="{{ route('backend.admin.payments.store') }}" method="post" class="paymentForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="name" class="form-label font-weight-bold">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="e.g., Cash Payment" name="name" value="{{ old('name') }}" required>
                                <div class="form-text small text-muted mt-1">What the customer sees on the receipt.</div>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="code" class="form-label font-weight-bold">System Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="e.g., cash" name="code" value="{{ old('code') }}" required>
                                <div class="form-text small text-muted mt-1">Unique internal identifier (lowercase).</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-2 col-md-4">
                                <div class="form-check form-switch mt-2 mb-1">
                                    <input class="form-check-input" type="checkbox" id="primary_method" name="primary_method" value="1" {{ old('primary_method') ? 'checked' : '' }}>
                                    <label for="primary_method" class="form-check-label font-weight-bold" for="primary_method">Set as Primary?</label>
                                </div>

                                <label class="form-label font-weight-normal text-muted d-block">Make this the default payment method</label>
                            </div>
                        </div>

                        <hr class="mt-2 mb-3">

                        <div class="row mb-3">
                            <div class="col-12">
                                <h5 class="text-black font-weight-bold mb-1">Transaction Surcharges</h5>
                                <p class="text-muted">
                                    Configure extra fees to be applied to the order total when this payment method is used.<br>
                                    This is commonly used to recover merchant processing fees (e.g., Credit Card percentages or Bank flat rates).
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="surcharge_type" class="form-label font-weight-bold">Surcharge Type</label>
                                <select name="surcharge_type" id="surcharge_type" class="form-select form-control">
                                    <option value="none" {{ old('surcharge_type') == 'none' ? 'selected' : '' }}>None (No extra fee)</option>
                                    <option value="fixed" {{ old('surcharge_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount ({{$defaultCurrency->symbol}})</option>
                                    <option value="percentage" {{ old('surcharge_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                </select>
                            </div>

                            <div class="mb-3 col-md-4" id="value_container" style="display: none;">
                                <label for="surcharge_value" class="form-label font-weight-bold">Surcharge Value</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="value_addon"></span>
                                    <input type="number" step="0.01" class="form-control" name="surcharge_value" id="surcharge_value" value="{{ old('surcharge_value', 0) }}">
                                </div>
                                <div class="form-text small text-muted mt-1" id="value_hint">Amount added to the order total.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light text-end">
                        <a href="{{ route('backend.admin.payments.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Add Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // We pass the PHP variable to a JS constant to keep the logic clean
        const currencySymbol = "{{ $defaultCurrency->symbol }}";

        const typeSelect = document.getElementById('surcharge_type');
        const container = document.getElementById('value_container');
        const addon = document.getElementById('value_addon');
        const hint = document.getElementById('value_hint');

        function toggleSurchargeUI() {
            const val = typeSelect.value;

            if (val === 'none') {
                container.style.display = 'none';
            } else {
                container.style.display = 'block';

                if (val === 'fixed') {
                    // Use the dynamic symbol for Fixed amounts
                    addon.innerText = currencySymbol;
                    hint.innerText = `Example: Enter 50 for a ${currencySymbol} 50 flat fee.`;
                } else {
                    // Use % for Percentage
                    addon.innerText = '%';
                    hint.innerText = 'Example: Enter 2.5 for a 2.5% fee on the total.';
                }
            }
        }

        // Event listener for changes
        typeSelect.addEventListener('change', toggleSurchargeUI);

        // Run once on load to handle validation errors/old data
        toggleSurchargeUI();
    });
</script>
@endpush