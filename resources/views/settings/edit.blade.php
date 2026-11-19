<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Password reset') }}</h3>
                <p class="mt-1 text-sm text-gray-600">
                    A password reset link will be sent to {{ $user->email }}.
                </p>

                @if (session('success'))
                    <p class="mt-4 text-sm text-green-700">{{ session('success') }}</p>
                @endif

                @if (session('error'))
                    <p class="mt-4 text-sm text-red-700">{{ session('error') }}</p>
                @endif

                <form method="POST" action="{{ route('settings.send-reset-link') }}" class="mt-6">
                    @csrf

                    <x-primary-button>{{ __('Send password reset link') }}</x-primary-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
