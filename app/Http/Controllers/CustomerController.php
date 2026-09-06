<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
            ))
            ->when($request->string('type')->toString(), fn ($q, $type) => $q->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer(['type' => 'lead', 'country_code' => 'IN', 'opted_in' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $customer = Customer::create($data);

        ActivityLogger::log('customer.created', "{$customer->name} added as a {$customer->type}", $customer, $data);

        return redirect()->route('customers.index')->with('status', "{$customer->name} added.");
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $original = $customer->only(['name', 'phone', 'email', 'type', 'status', 'opted_in']);
        $customer->update($this->validated($request, $customer));

        ActivityLogger::log('customer.updated', "{$customer->name} updated", $customer, [
            'before' => $original,
            'after' => $customer->only(['name', 'phone', 'email', 'type', 'status', 'opted_in']),
        ]);

        return redirect()->route('customers.index')->with('status', "{$customer->name} updated.");
    }

    public function destroy(Customer $customer)
    {
        $name = $customer->name;
        $customer->delete();

        ActivityLogger::log('customer.deleted', "{$name} removed", null, ['name' => $name]);

        return back()->with('status', "{$name} removed.");
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required', 'string', 'max:20', 'regex:/^[0-9]{8,15}$/',
                Rule::unique('customers', 'phone')
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->ignore($customer?->id),
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'type' => ['required', Rule::in(['customer', 'lead'])],
            'country_code' => ['required', 'string', 'size:2'],
            'tags' => ['nullable', 'string', 'max:190'],
            'opted_in' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'blocked', 'unsubscribed'])],
        ], [
            'phone.regex' => 'Use the full number with country code and no symbols, for example 919812345678.',
        ]);

        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        $data['opted_in'] = $request->boolean('opted_in');
        $data['country_code'] = strtoupper($data['country_code']);

        return $data;
    }
}
