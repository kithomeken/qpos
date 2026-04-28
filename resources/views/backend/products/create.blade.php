@extends('backend.master')

@section('title', 'Create Product')

@section('content')
<div class="container-fluid">
    <form action="{{ route('backend.admin.products.store') }}" method="post" class="accountForm" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-tags me-2"></i> Product Classification
                        </h6>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="category_id" class="form-label font-weight-bold">Category <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $item)
                                    <option value="{{ $item->id }}" {{ old('category_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="brand_id" class="form-label font-weight-bold">Brand <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="brand_id" required>
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $item)
                                    <option value="{{ $item->id }}" {{ old('brand_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> General Information</h6>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-8">
                                <label for="name" class="form-label font-weight-bold">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="e.g. BMW Brake Pads" name="name" value="{{ old('name') }}" required>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="sku" class="form-label font-weight-bold">SKU / Barcode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Generate or Enter SKU" name="sku" value="{{ old('sku') }}" required>
                            </div>

                            <div class="mb-3 col-md-12">
                                <label for="description" class="form-label font-weight-bold">Description</label>
                                <textarea class="form-control" rows="3" placeholder="Enter product specifications..." name="description">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Pricing & Units</h6>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="unit_id" class="form-label font-weight-bold">Unit <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="unit_id" required>
                                    <option value="">Select Unit</option>
                                    @foreach ($units as $item)
                                    <option value="{{ $item->id }}" {{ old('unit_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->title }} ({{ $item->short_name }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="purchase_price" class="form-label font-weight-bold">Purchase Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">KES</span>
                                    <input type="number" step="0.01" class="form-control" name="purchase_price" value="{{ old('purchase_price') }}" required>
                                </div>
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="price" class="form-label font-weight-bold">Selling Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">KES</span>
                                    <input type="number" step="0.01" class="form-control" name="price" value="{{ old('price') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="mb-3 col-md-6">
                                <label for="discount_type" class="form-label font-weight-bold">Discount Type</label>
                                <select class="form-control form-select" name="discount_type" id="discount_type">
                                    <option value="">No Discount</option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                </select>
                            </div>

                            <div class="mb-3 col-md-6" id="discount_value_container">
                                <label for="discount" class="form-label font-weight-bold">Discount Value</label>
                                <input type="number" step="0.01" class="form-control" name="discount" value="{{ old('discount') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-image me-2"></i> Product Media</h6>
                    </div>

                    <div class="card-body text-center">
                        <div class="image-upload-container mx-auto" id="imageUploadContainer" style="cursor: pointer; border: 2px dashed #ddd; padding: 20px; border-radius: 10px;">
                            <input type="file" class="form-control" name="product_image" id="thumbnailInput" accept="image/*" style="display: none;">
                            <div class="thumb-preview" id="thumbPreviewContainer">
                                <img src="{{ asset('backend/assets/images/blank.png') }}" alt="Preview" class="img-fluid mb-2 rounded" id="thumbnailPreview" style="max-height: 150px;">
                                <div class="upload-text text-muted">
                                    <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                    <p class="mt-2 mb-0">Click to upload product image</p>
                                </div>
                            </div>
                        </div>

                        <small class="text-muted mt-2 d-block">Recommended size: 500x500px</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i> Settings</h6>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="expire_date" class="form-label font-weight-bold">Expiry Date</label>
                            <div class="input-group date" id="reservationdate" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" data-target="#reservationdate" name="expire_date" value="{{ old('expire_date') }}" />
                                <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-check form-switch px-5">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input" type="checkbox" name="status" id="active" value="1" checked>
                            <label class="form-check-label font-weight-bold" for="active">Product Active</label>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-sm">
                            <i class="fas fa-save me-2"></i> Save Product
                        </button>
                        <a href="{{ route('backend.admin.products.index') }}" class="btn btn-light btn-block mt-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('style')
<style>
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        padding: 6px !important;
        border: 1px solid #ced4da !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    .card-header {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .btn-block {
        width: 100%;
        display: block;
    }
</style>
@endpush

@push('script')
<script src="{{ asset('js/image-field.js') }}"></script>
<script>
    $(function() {
        $('#reservationdate').datetimepicker({
            format: 'YYYY-MM-DD'
        });

        // UX: Click container to trigger file input
        $('#imageUploadContainer').on('click', function() {
            $('#thumbnailInput').click();
        });

        // UX: Auto-focus first field
        $('.select2').select2();
    })
</script>
@endpush