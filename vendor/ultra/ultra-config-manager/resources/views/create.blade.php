@extends('uconfig::layouts.uconfig')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
            {{ __('uconfig::uconfig.pages.create') }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Aggiungi una nuova configurazione al sistema
        </p>
    </div>

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

            <form method="POST" action="{{ route('uconfig.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="key" class="block text-sm font-medium text-gray-700">
                            {{ __('uconfig::uconfig.form.key') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input type="text" id="key" name="key" value="{{ old('key') }}" required
                                   class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            La chiave è unica e non può essere modificata in seguito
                        </p>
                        @error('key')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                                    <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>
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
                                      class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('value') }}</textarea>
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
                                      class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('note') }}</textarea>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Una nota opzionale per documentare lo scopo di questa configurazione
                        </p>
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
                        {{ __('uconfig::uconfig.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection