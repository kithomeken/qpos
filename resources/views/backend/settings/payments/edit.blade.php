@extends('backend.master')

@section('title', 'Update Payment Method')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0">Update: {{ $paymentMethod->name }}</h5>
                    <p class="text-muted small mb-0">Modify the configuration for this payment rail.</p>
                </div>

                <form action="{{ route('backend.admin.payments.update', $paymentMethod->id) }}" method="post" class="paymentForm">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="name" class="form-label font-weight-bold">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="{{ old('name', $paymentMethod->name) }}" required>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="code" class="form-label font-weight-bold">System Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="code" value="{{ old('code', $paymentMethod->code) }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-2 col-md-4">
                                <label for="primary_method" class="form-label font-weight-bold d-block">Set as Primary?</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="primary_method" name="primary_method" value="1" {{ old('primary_method', $paymentMethod->primary_method) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="primary_method">Make this the default payment method</label>
                                </div>
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
                                    <option value="none" {{ old('surcharge_type', $paymentMethod->surcharge_type) == 'none' ? 'selected' : '' }}>None (No extra fee)</option>
                                    <option value="fixed" {{ old('surcharge_type', $paymentMethod->surcharge_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    <option value="percentage" {{ old('surcharge_type', $paymentMethod->surcharge_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                </select>
                            </div>

                            <div class="mb-3 col-md-4" id="value_container" style="display: none;">
                                <label for="surcharge_value" class="form-label font-weight-bold">Surcharge Value</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="value_addon"></span>
                                    <input type="number" step="0.01" class="form-control" name="surcharge_value" id="surcharge_value"
                                        value="{{ old('surcharge_value', $paymentMethod->surcharge_value) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light text-end">
                        <a href="{{ route('backend.admin.payments.index') }}" class="btn btn-secondary text-white">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Update</button>
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
        const currencySymbol = "{{ $defaultCurrency->symbol }}";
        const typeSelect = document.getElementById('surcharge_type');
        const container = document.getElementById('value_container');
        const addon = document.getElementById('value_addon');

        function toggleSurchargeUI() {
            const val = typeSelect.value;
            if (val === 'none') {
                container.style.display = 'none';
            } else {
                container.style.display = 'block';
                addon.innerText = (val === 'fixed') ? currencySymbol : '%';
            }
        }

        typeSelect.addEventListener('change', toggleSurchargeUI);
        toggleSurchargeUI(); // Correctly handles showing the field if existing data is not 'none'
    });
</script>
@endpush