<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Your registration has been received and is awaiting approval. You will be able to access PUPCURVEY once your account is approved.
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <x-primary-button>
            {{ __('Log Out') }}
        </x-primary-button>
    </form>
</x-guest-layout>
