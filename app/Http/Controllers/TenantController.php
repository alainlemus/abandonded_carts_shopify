<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\StripeService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class TenantController extends Controller
{
    public function register(Request $request, StripeService $stripe)
    {
        Log::info('Registro de inquilino iniciado', [
            'ip' => $request->ip(),
            'name' => $request->name,
            'name_admin' => $request->name_admin,
            'user_agent' => $request->userAgent(),
            'data' => $request->only(['name', 'email']),
            'subdomain' => $request->subdomain,
            'tenant_id' => Str::random(10),
            'timestamp' => now(),
            'stripe' => $stripe,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'name_admin' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'subdomain' => 'required|string|unique:domains,domain|regex:/^[a-z0-9]+$/',
            'password' => 'required|string|min:8', // Agrega min:8 para consistencia
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'subdomain.unique' => 'El subdominio ya está en uso.',
            'subdomain.regex' => 'El subdominio solo puede contener letras minúsculas y números.',
        ]);

        $priceId = env('STRIPE_MONTHLY_PLAN');

        try {

            $tenantId = Str::random(10);

            // Crear tenant y dominio fuera de la transacción
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $request->name,
                'data' => ['email' => $request->email],
            ]);

            $tenant->domains()->create([
                'domain' => $request->subdomain . '.app-localhost.tests',
            ]);

            $shop = Shop::create([
                'tenant_id' => $tenantId,
                'shopify_domain' => $request->subdomain . '.app-localhost.tests',
            ]);

            Log::info("Shop created", [
                'tenant_id' => $tenantId,
                'shopify_domain' => $request->subdomain . '.app-localhost.test',
            ]);

            // Crear usuario dentro de la transacción
            $user = DB::transaction(function () use ($request, $tenantId) {
                $user = User::create([
                    'name' => $request->name_admin,
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'tenant_id' => $tenantId,
                ]);

                // Asociar el usuario al tenant en la tabla pivote tenant_user
                DB::table('tenant_user')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $user;
            });

            // Verificar si $user es válido
            if (!$user) {
                throw new \Exception('No se pudo crear el usuario en la transacción.');
            }

            Log::info('Usuario creado y tenant registrado', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'subdomain' => $request->subdomain,
            ]);

            // Ejecutar migraciones fuera de la transacción
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                throw new \Exception('No se encontró el tenant creado.');
            }

            /*try {
                $tenant->run(function () {
                    // Asegurarse de que las migraciones usen la conexión correcta
                    Artisan::call('migrate', [
                        '--database' => 'tenant',
                        '--force' => true, // Forzar migraciones en producción si es necesario
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Error al ejecutar migraciones para el tenant: ' . $e->getMessage(), ['exception' => $e]);
                throw new \Exception('Error al ejecutar migraciones: ' . $e->getMessage());
            }*/

            Log::info('Migraciones ejecutadas para el tenant', [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
            ]);

            // Crear cliente y sesión de Stripe
            $stripeCustomerId = $stripe->createCustomer($user);
            $user->update(['stripe_id' => $stripeCustomerId]);

            $domain = $request->subdomain . '.app-localhost.test';

            $checkoutSession = \Stripe\Checkout\Session::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => "http://" . ($request->subdomain ?? 'demo') . ".app-localhost.test/admin/".$tenantId."/shopify-settings",
                'cancel_url' => route('register'),
            ]);

            Log::info('Cliente de Stripe creado', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'stripe_customer_id' => $stripeCustomerId,
                'price' => $priceId
            ]);

            Log::info('Sesión de Stripe creada', ['user_id' => $user->id,
                'tenant_id' => $tenantId,
                'stripe_subscription_id' => $stripeCustomerId,
                'price' => $priceId,
                'status' => 'ACTIVO'
            ]);

            $suscription = Subscription::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'stripe_subscription_id' => $stripeCustomerId,
                'status' => 'ACTIVO',
            ]);

            if (!$suscription) {
                throw new \Exception('No se pudo registrar los datos de subscripcion.');
            }

            if (!$checkoutSession || !isset($checkoutSession->id)) {
                throw new \Exception('No se pudo crear la sesión de Stripe.');
            }

            Auth::login($user);

            // Inicializar el tenant
            tenancy()->initialize($tenantId);

            return response()->json([
                'sessionId' => $checkoutSession->id,
            ], 200);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Error de Stripe: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Error con Stripe: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error de base de datos: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Error en la base de datos: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error general: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Error al crear el tenant: ' . $e->getMessage(),
            ], 500);
        }
    }
}
