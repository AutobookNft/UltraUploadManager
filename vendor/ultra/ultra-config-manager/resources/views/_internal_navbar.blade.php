<div class="bg-gray-800 text-white shadow-md mb-6">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ __('uconfig::uconfig.title') }} - {{ __('uconfig::uconfig.subtitle') }}</h1>
            <p class="text-sm">{{ __('uconfig::uconfig.author') }}</p>
        </div>
        <nav class="space-x-4">
            <a href="{{ route('uconfig.index') }}" class="hover:text-yellow-400">{{ __('uconfig::uconfig.nav.dashboard') }}</a>
            <a href="{{ route('uconfig.create') }}" class="hover:text-yellow-400">{{ __('uconfig::uconfig.nav.add_config') }}</a>
        </nav>
    </div>
</div>