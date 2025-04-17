@extends('uconfig::layouts.uconfig')

@section('content')
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                {{ __('uconfig::uconfig.audit.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('uconfig::uconfig.audit.for_config', ['key' => $config->key]) }}
            </p>
        </div>
        <a href="{{ route('uconfig.index') }}" 
           class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('uconfig::uconfig.actions.back') }}
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('uconfig::uconfig.audit.date') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('uconfig::uconfig.audit.action') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('uconfig::uconfig.audit.old_value') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('uconfig::uconfig.audit.new_value') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('uconfig::uconfig.audit.user') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($audits as $audit)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $audit->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                      {{ $audit->action === 'created' ? 'bg-green-100 text-green-800' : 
                                        ($audit->action === 'updated' ? 'bg-blue-100 text-blue-800' : 
                                         'bg-red-100 text-red-800') }}">
                                    {{ $audit->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs overflow-auto">
                                    @if($audit->old_value !== null)
                                        <pre class="whitespace-pre-wrap">{{ $audit->old_value }}</pre>
                                    @else
                                        <span class="text-gray-500 italic">N/A</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs overflow-auto">
                                    @if($audit->new_value !== null)
                                        <pre class="whitespace-pre-wrap">{{ $audit->new_value }}</pre>
                                    @else
                                        <span class="text-gray-500 italic">N/A</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $audit->user ? $audit->user->name : __('uconfig::uconfig.audit.unknown_user') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                Nessun record di audit disponibile per questa configurazione
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection