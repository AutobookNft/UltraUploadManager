@extends('uconfig::layouts.uconfig')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
            {{ __('uconfig::uconfig.pages.edit') }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Modifica la configurazione "{{ $config->key }}"
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form di modifica -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    @if ($errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        {{ __('uconfig::uconfig.error.title') }}
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('uconfig.update', $config->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="key" class="block text-sm font-medium text-gray-700">
                                    {{ __('uconfig::uconfig.form.key') }}
                                </label>
                                <div class="mt-1">
                                    <input type="text" id="key" name="key" value="{{ $config->key }}" readonly
                                           class="bg-gray-100 shadow-sm block w-full sm:text-sm border-gray-300 rounded-md cursor-not-allowed">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    La chiave non può essere modificata
                                </p>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="category" class="block text-sm font-medium text-gray-700">
                                    {{ __('uconfig::uconfig.form.category') }}
                                </label>
                                <div class="mt-1">
                                    <select id="category" name="category" 
                                            class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="">{{ __('uconfig::uconfig.form.no_category') }}</option>
                                        @foreach(\Ultra\UltraConfigManager\Enums\CategoryEnum::translatedOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('category', $config->category?->value) === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('category')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-6">
                                <label for="value" class="block text-sm font-medium text-gray-700">
                                    {{ __('uconfig::uconfig.form.value') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1">
                                    <textarea id="value" name="value" rows="3" required
                                            class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('value', $config->value) }}</textarea>
                                </div>
                                @error('value')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-6">
                                <label for="note" class="block text-sm font-medium text-gray-700">
                                    {{ __('uconfig::uconfig.form.note') }}
                                </label>
                                <div class="mt-1">
                                    <textarea id="note" name="note" rows="2"
                                            class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('note', $config->note) }}</textarea>
                                </div>
                                @error('note')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200">
                            <a href="{{ route('uconfig.index') }}" 
                            class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                {{ __('uconfig::uconfig.actions.back') }}
                            </a>
                            <button type="submit" 
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                {{ __('uconfig::uconfig.actions.update') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Informazioni e azioni -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-5 border-b border-gray-200 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        Informazioni
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Creata il
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $config->created_at->format('d/m/Y H:i:s') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Ultima modifica
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $config->updated_at->format('d/m/Y H:i:s') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Registro modifiche
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('uconfig.audit', $config->id) }}" 
                                   class="text-primary-600 hover:text-primary-900 inline-flex items-center">
                                    Visualizza cronologia
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-red-50 px-4 py-5 border-b border-red-200 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-red-900">
                        Azioni pericolose
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form action="{{ route('uconfig.destroy', $config->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div>
                            <p class="text-sm text-gray-500 mb-4">
                                L'eliminazione di questa configurazione potrebbe avere impatti sul sistema. Questa azione non può essere annullata.
                            </p>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    onclick="return confirm('{{ __('uconfig::uconfig.error.delete_confirm') }}')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('uconfig::uconfig.actions.delete') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection