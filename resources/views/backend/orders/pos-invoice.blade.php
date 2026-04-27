@extends('backend.master')
@section('title', 'Receipt_'.$order->id)
@section('content')

<div class="card">
    <!-- Main content -->
    <div class="receipt-container mt-0" id="printable-section" style="max-width: {{ $maxWidth}}; font-size: 12px; font-family: 'Courier New', Courier, monospace;">
        <div class="text-center">
            @if(readConfig('is_show_logo_invoice'))
            <img src="{{ assetImage(readconfig('site_logo')) }}" style="max-height: 50px; width: auto; margin-bottom: 5px;" alt="Logo">
            @endif

            @if(readConfig('is_show_site_invoice'))
            <h3 style="margin: 0; text-transform: uppercase;">{{ readConfig('site_name') }}</h3>
            @endif

            <div style="font-size: 11px;">
                @if(readConfig('is_show_address_invoice')) {{ readConfig('contact_address') }}<br> @endif
                @if(readConfig('is_show_phone_invoice')) Tel: {{ readConfig('contact_phone') }}<br> @endif
                @if(readConfig('is_show_email_invoice')) Email: {{ readConfig('contact_email') }}<br> @endif
            </div>

            <div style="margin-top: 5px; padding: 3px 0;">
                {{ date('h:i A') }} {{ date('D d M Y') }}
            </div>

            <div class="text-center" style="padding-top: 10px; padding-bottom: 10px;">
                <div style="display: inline-block;">
                    {!! DNS1D::getBarcodeHTML($order->reference_no, 'C128', 1.5, 33) !!}
                </div>

                <div style="font-size: 10px; letter-spacing: 2px;">
                    {{ $order->reference_no }}
                </div>
            </div>
        </div>

        <hr>

        <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px dashed #000;">
                    <th style="text-align: left; padding-bottom: 3px;">ITEM</th>
                    <th style="text-align: right; padding-bottom: 3px;">TOTAL</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($order->products as $item)
                <tr>
                    <td colspan="2" style="padding-top: 5px;">
                        {{ strtoupper($item->product->name) }}
                    </td>
                </tr>

                <tr>
                    <td style="text-align: left; font-size: 11px;">
                        {{ number_format($item->quantity, 2) }} {{ $item->product->unit->short_name ?? 'Units' }} @ {{ number_format($item->price, 2) }}
                    </td>
                    <td style="text-align: right; vertical-align: bottom;">
                        {{ number_format($item->total, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <hr>

        <div style="margin-top: 10px;">
            <table style="width: 100%; font-size: 13px;">
                <tr>
                    <td>Sub Total:</td>
                    <td style="text-align: right;">{{ number_format($order->sub_total, 2) }}</td>
                </tr>

                @if($order->discount > 0)
                <tr>
                    <td>Discount:</td>
                    <td style="text-align: right;">-{{ number_format($order->discount, 2) }}</td>
                </tr>
                @endif

                @if ($order->surcharge_type <> 'none')
                    <tr>
                    <td>Surcharge:</td>
                    <td style="text-align: right;">
                        @if($order->surcharge_type === 'percentage')
                        {{ number_format($order->surcharge_value, 1) }}%
                        @elseif($order->surcharge_type === 'fixed')
                        Fixed
                        @else
                        None
                        @endif
                    </td>
                </tr>
                @endif

                <tr>
                    <td>Surcharge Amount:</td>
                    <td style="text-align: right;">{{ number_format($order->surcharge_amount, 2) }}</td>
                </tr>

                <tr style="font-size: 16px;">
                    <td><strong>GRAND TOTAL:</strong></td>
                    <td style="text-align: right;"><strong>{{ number_format($order->total, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <div style="border-top: 1px dashed #000; margin-top: 10px; padding-top: 5px;">
            {{ 'Total Items: ' . number_format($order->products->sum('quantity'), 0) }}<br>
            {{ 'Served By: '. auth()->user()->name }}<br>
            {{ 'Order Reference: '.$order->reference_no}}<br>
        </div>

        @if(readConfig('is_show_customer_invoice') && isset($order->customer))
        <div style="margin-top: 10px; padding: 5px; border: 2px dashed #000;">
            <p class="mb-1">
                <strong>CUSTOMER:</strong>
            </p>

            Name: {{ $order->customer->name ?? 'N/A' }}<br>
            Address: {{ $order->customer->address ?? 'N/A' }}<br>
            Phone: {{ $order->customer->phone ?? 'N/A' }}<br>
        </div>
        @endif

        <div style="text-align: center; margin-top: 15px;">
            <p style="font-style: italic; font-size: 11px;">
                @if(readConfig('is_show_note_invoice'))
                {{ readConfig('note_to_customer_invoice') }}
                @else
                Thank you for shopping with us!
                @endif
            </p>
            <div style="font-size: 10px;">*** End of Fiscal Receipt ***</div>
        </div>
    </div>

    <!-- Print Button -->
    <div class="text-center mt-3 no-print pb-3">
        <button type="button" onclick="window.print()" class="btn bg-gradient-primary text-white"><i class="fas fa-print"></i> Print</button>
    </div>
</div>
@endsection

@push('style')
<style>
    .receipt-container {
        border: 1px dotted #000;
        padding: 8px;
    }

    hr {
        border: none;
        border-top: 1px dashed #000;
        margin: 5px 0;
    }

    table {
        width: 100%;
    }

    td,
    th {
        padding: 2px 0;
    }

    .text-right {
        text-align: right;
    }

    @media print {
        @page {
            margin-top: 5px !important;
            margin-left: 0px !important;
            padding-left: 0px !important;
        }

        footer {
            display: none !important;
        }
    }
</style>
@endpush

@push('script')
<script>
    // window.print();
</script>
@endpush