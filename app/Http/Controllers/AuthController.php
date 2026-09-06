<?php

namespace App\Http\Controllers;

use App\Models\PricingRate;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['workspace'],
                'slug' => Str::slug($data['workspace']).'-'.Str::lower(Str::random(4)),
                'contact_email' => $data['email'],
                'country_code' => strtoupper($data['country_code']),
                'currency' => strtoupper($data['country_code']) === 'IN' ? 'INR' : 'USD',
            ]);

            // Seed this workspace with the default rate card it will be billed at.
            foreach (config('whatsapp.fallback_rates') as $category => $price) {
                PricingRate::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'country_code' => $tenant->country_code, 'category' => $category],
                    ['price' => $price, 'currency' => $tenant->currency],
                );
            }

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'owner',
            ]);
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        ActivityLogger::log('tenant.created', "Workspace “{$user->tenant->name}” created", $user->tenant);

        return redirect()->route('settings.whatsapp')
            ->with('status', 'Workspace ready. Connect your WhatsApp number to start sending.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        $request->session()->regenerate();
        ActivityLogger::log('user.logged_in', Auth::user()->name.' signed in', Auth::user());

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
