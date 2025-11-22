<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PortalProfileController extends Controller
{
    /**
     * Display the profile edit form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer record found for this user.');
        }

        return Inertia::render('Portal/Profile/Edit', [
            'user' => $user,
            'customer' => $customer,
        ]);
    }

    /**
     * Update the customer's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer record found for this user.');
        }

        $validated = $request->validate([
            // User information
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],

            // Customer information
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'country' => ['sometimes', 'string', 'max:100'],

            // Company information (if applicable)
            'company_name' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],

            // Password update (optional)
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Update user
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        // Update password if provided
        if (isset($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors([
                    'current_password' => 'The provided password does not match your current password.',
                ]);
            }

            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Update customer
        $customerData = [];
        if (isset($validated['mobile'])) {
            $customerData['mobile'] = $validated['mobile'];
        }
        if (isset($validated['address'])) {
            $customerData['address'] = $validated['address'];
        }
        if (isset($validated['city'])) {
            $customerData['city'] = $validated['city'];
        }
        if (isset($validated['postal_code'])) {
            $customerData['postal_code'] = $validated['postal_code'];
        }
        if (isset($validated['country'])) {
            $customerData['country'] = $validated['country'];
        }
        if (isset($validated['company_name'])) {
            $customerData['company_name'] = $validated['company_name'];
        }
        if (isset($validated['vat_number'])) {
            $customerData['vat_number'] = $validated['vat_number'];
        }

        if (!empty($customerData)) {
            $customer->update($customerData);
        }

        return back()->with('success', 'Profile updated successfully!');
    }
}
