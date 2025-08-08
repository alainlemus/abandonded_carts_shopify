<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de Carritos Abandonados</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <header class="bg-blue-600 text-white p-4">
        <h1 class="text-3xl">Recupera tus Ventas con Nuestra App</h1>
        <p class="mt-2">Automatiza la recuperación de carritos abandonados con WhatsApp y descuentos.</p>
    </header>
    <main class="p-6">
        <section class="max-w-lg mx-auto">
            <h2 class="text-2xl mb-4">Crea tu Cuenta</h2>
            <form id="register-form" action="{{ route('register.tenant') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name_admin" class="block">Nombre admin</label>
                    <input type="text" id="name_admin" name="name_admin" class="w-full p-2 border" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block">Email</label>
                    <input type="email" id="email" name="email" class="w-full p-2 border" required>
                </div>
                <div class="mb-4">
                    <label for="subdomain" class="block">Subdominio</label>
                    <input type="text" id="subdomain" name="subdomain" class="w-full p-2 border" placeholder="ejemplo" required>
                    <p>.app.localhost.test</p>
                </div>
                <div class="mb-4">
                    <label for="password" class="block">Contraseña</label>
                    <input type="password" id="password" name="password" class="w-full p-2 border" required>
                </div>
                <div class="mb-4">
                    <label for="name" class="block">Nombre de tienda</label>
                    <input type="text" id="name" name="name" class="w-full p-2 border" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white p-2 rounded">Registrarse y Pagar</button>
            </form>
        </section>
    </main>
    <script>
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                console.log('Estado HTTP:', response.status); // Log del estado HTTP
                const data = await response.json();
                console.log('Respuesta del servidor:', data); // Log de la respuesta completa
                if (response.ok && data.sessionId) {
                    console.log('Redirigiendo a Stripe Checkout con sessionId:', data.sessionId);
                    const { error } = await stripe.redirectToCheckout({ sessionId: data.sessionId });
                    if (error) {
                        console.error('Error en Stripe:', error.message);
                        alert('Error al redirigir a Stripe: ' + error.message);
                    }
                } else {
                    console.error('Error del servidor:', data.error || 'Error desconocido');
                    alert(data.error || 'Error al registrar. Intenta de nuevo.');
                }
            } catch (error) {
                console.error('Error en la solicitud:', error);
                alert('Error en la solicitud: ' + error.message);
            }
        });
    </script>
</body>
</html>
