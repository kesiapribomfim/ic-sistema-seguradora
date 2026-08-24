<x-layouts.app title="Redefinir senha">
    <main class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <section class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-900">Definir nova senha</h1>
            <p class="mt-2 text-sm text-gray-600">Escolha uma senha nova para acessar sua conta.</p>

            <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Nova senha</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar nova senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    Redefinir senha
                </button>
            </form>
        </section>
    </main>
</x-layouts.app>
