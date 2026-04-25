<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Models\PaymentMethods;
use Illuminate\Http\Request;
use App\Models\Currency;

class PaymentMethodsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('payment_view'), 403);

        if ($request->ajax()) {
            $paymentMethods = PaymentMethods::latest()
                ->orderBy('primary_method', 'desc')
                ->get();

            # Get Default Currency
            $defaultCurrency = Currency::where('active', true)
                ->first();

            return DataTables::of($paymentMethods)
                ->addIndexColumn()
                ->addColumn('name', fn($data) => $data->name
                    . ($data->primary_method ? ' (Default Method)' : ''))
                ->addColumn('code', fn($data) => $data->code)
                ->addColumn('surcharge_type', fn($data) => ucfirst($data->surcharge_type))
                ->addColumn(
                    'surcharge_value',
                    function ($data) use ($defaultCurrency) {
                        switch ($data->surcharge_type) {
                            case 'fixed':
                                return $defaultCurrency->symbol . ' ' . $data->surcharge_value;

                            case 'percentage':
                                return $data->surcharge_value . ' %';

                            default:
                                return '0.00';
                        }
                    }
                )
                ->addColumn('status', fn($data) => $data->active
                    ? '<span class="badge bg-primary">Active</span>'
                    : '<span class="badge bg-danger">Disabled</span>')
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group">
                        <button type="button" class="btn bg-gradient-primary btn-flat">Action</button>
                        <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>

                        <div class="dropdown-menu" role="menu">
                            <a class="dropdown-item" href="' . route('backend.admin.payments.edit', $data->id) . '" ' . ' >
                                <i class="fas fa-edit"></i> Edit
                            </a> 
                            <div class="dropdown-divider"></div>

                            <form action="' . route('backend.admin.payments.destroy', $data->id) . '"method="POST" style="display:inline;">
                                ' . csrf_field() . '
                                ' . method_field("DELETE") . '
                                <button type="submit" class="dropdown-item" onclick="return confirm(\'Are you sure ?\')">
                                    <i class="fas fa-trash"></i> Disable
                                </button>
                            </form>
                            <div class="dropdown-divider"></div>

                            <a class="dropdown-item" onclick="return confirm(\'Are you sure to set Default ?\')" href="' . route('backend.admin.payments.setDefault', $data->id) . '" ' . ' >
                                <i class="fas fa-edit"></i> Set Default
                            </a>
                        </div>';
                })
                ->rawColumns(['name', 'code', 'surcharge_type', 'surcharge_value', 'status', 'action'])
                ->toJson();
        }

        return view('backend.settings.payments.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(!auth()->user()->can('payment_create'), 403);

        # Get Default Currency
        $defaultCurrency = Currency::where('active', true)
            ->first();

        return view('backend.settings.payments.create', compact('defaultCurrency'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('payment_create'), 403);

        $validated = $request->validate([
            'name'             => 'required|string|min:3|max:255|unique:payment_methods,name',
            'code'             => 'required|string|min:3|max:50|unique:payment_methods,code',
            'surcharge_type'   => 'required|in:none,fixed,percentage',
            'surcharge_value'  => 'required|numeric|min:0',
            'primary_method'   => 'nullable|boolean',
        ]);

        // Counter-measure: 
        // If this is set as primary, unset the previous one
        if ($request->has('primary_method') && $request->primary_method == 1) {
            PaymentMethods::where('primary_method', true)
                ->update(['primary_method' => false]);
        }

        // Create the record
        $paymentData = array_merge($validated, [
            'primary_method' => $request->has('primary_method')
        ]);

        PaymentMethods::create($paymentData);

        return redirect()
            ->route('backend.admin.payments.index')
            ->with('success', 'Payment method configured successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(!auth()->user()->can('payment_update'), 403);

        $paymentMethod = PaymentMethods::findOrFail($id);

        # Get Default Currency
        $defaultCurrency = Currency::where('active', true)
            ->first();

        return view('backend.settings.payments.edit', compact('paymentMethod', 'defaultCurrency'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethods::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'required|string|min:3|max:255|unique:payment_methods,name,' . $id,
            'code'             => 'required|string|min:3|max:50|unique:payment_methods,code,' . $id,
            'surcharge_type'   => 'required|in:none,fixed,percentage',
            'surcharge_value'  => 'required|numeric|min:0',
            'primary_method'   => 'nullable|boolean',
        ]);

        // Primary Method Counter-Measure
        $isNowPrimary = $request->has('primary_method');

        if ($isNowPrimary) {
            // If the user checked "Primary", demote everyone else.
            PaymentMethods::where('id', '!=', $id)
                ->where('primary_method', true)
                ->update(['primary_method' => false]);
        } else {
            // OPTIONAL: Safety check. 
            // If this was the ONLY primary method, you might want to prevent 
            // unchecking it so the system always has at least one default.
            if ($paymentMethod->primary_method) {
                $isNowPrimary = true;
            }
        }

        // Sync Surcharge Logic
        // If type is 'none', force value to 0 to prevent database noise
        $surchargeValue = ($validated['surcharge_type'] === 'none') ? 0 : $validated['surcharge_value'];

        // Update
        $paymentMethod->update([
            'name'            => $validated['name'],
            'code'            => $validated['code'],
            'surcharge_type'  => $validated['surcharge_type'],
            'surcharge_value' => $surchargeValue,
            'primary_method'  => $isNowPrimary,
        ]);

        return redirect()
            ->route('backend.admin.payments.index')
            ->with('success', "{$paymentMethod->name} updated successfully!");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Authorization Check
        abort_if(!auth()->user()->can('payment_delete'), 403);

        $paymentMethod = PaymentMethods::findOrFail($id);

        // Primary Method Safety Check
        // You cannot disable the "Auto-selected" method
        if ($paymentMethod->primary_method) {
            return redirect()->back()->with(
                'error',
                'Cannot disable the primary payment method. Please set another method as "Primary" before proceeding.'
            );
        }

        // The "Disable" Logic (Status Toggle)
        // Instead of $paymentMethod->delete(), we update the status
        $paymentMethod->update([
            'active' => false
        ]);

        return redirect()->back()->with('success', "Payment method '{$paymentMethod->name}' has been disabled.");
    }

    public function setDefault($id)
    {
        // Find the payment method
        $paymentMethod = PaymentMethods::findOrFail($id);

        // Crucial: We cannot set a method as default if it is currently disabled (active = false)
        if (!$paymentMethod->active) {
            return redirect()->back()->with(
                'error',
                "Cannot set '{$paymentMethod->name}' as default because it is currently disabled."
            );
        }

        // Perform the Atomic Switch
        // Wrap in a transaction to ensure we don't lose our default if the query fails midway
        DB::transaction(function () use ($id) {
            // Demote all existing defaults
            PaymentMethods::where('primary_method', true)->update(['primary_method' => false]);

            // Promote the selected one
            PaymentMethods::where('id', $id)->update(['primary_method' => true]);
        });

        // Cache Management
        // We refresh the cache so the POS frontend gets the update immediately
        Cache::put('default_payment_method', $paymentMethod, now()->addDays(1));

        return redirect()->back()->with('success', "'{$paymentMethod->name}' is now the default payment method.");
    }
}
