@extends('backend.master')

@section('title', 'Payment Methods')

@section('content')
<div class="card">

    @can('payment_create')
    <div class="mt-n5 mb-3 d-flex justify-content-end">
        <a href="{{ route('backend.admin.payments.create') }}" class="btn bg-gradient-primary">
            <i class="fas fa-plus-circle"></i>
            Add New
        </a>
    </div>
    @endcan
    
    <div class="card-body p-2 p-md-4 pt-0">
        <div class="row g-4">
            <div class="col-md-12">
                <div class="card-body p-0" id="table_data">
                    <table id="datatables" class="table table-hover">
                        <thead>
                            <tr>
                                <th data-orderable="false">#</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Surcharge Type</th>
                                <th>Surcharge Value</th>
                                <th>Status</th>
                                <th data-orderable="false">Action</th>
                            </tr>
                        </thead>
                    </table>
                    <!-- Pagination Links -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

<script type="text/javascript">
    $(function() {
        let table = $('#datatables').DataTable({
            processing: true,
            serverSide: true,
            ordering: true,
            order: [
                [1, 'asc']
            ],
            ajax: {
                url: "{{ route('backend.admin.payments.index') }}"
            },

            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'surcharge_type',
                    name: 'surcharge_type'
                },
                {
                    data: 'surcharge_value',
                    name: 'surcharge_value'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action'
                },
            ]
        });
    });
</script>
@endpush