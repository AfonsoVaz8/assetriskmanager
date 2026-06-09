<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('M365 Accounts Requiring Action') }}
        </h2>
    </x-slot>

    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-[1.5rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <p class="text-sm leading-6 text-slate-600">
                    {{ __('This list shows every M365 account currently above the priority threshold. Start with accounts that already have alerts, then move into the underlying threat events when no alert exists yet.') }}
                </p>
            </div>

            <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Account') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Why First') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Pressure') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($accounts as $account)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <a href="{{ $account->primary_href }}" class="font-semibold text-slate-900 hover:text-blue-700 hover:underline">
                                            {{ $account->principal }}
                                        </a>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                                {{ __('Priority') }} {{ number_format((float) $account->priority_score, 0) }}
                                            </span>
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700">
                                                {{ __('Alerts') }} {{ $account->incident_count }}
                                            </span>
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">
                                                {{ __('Risk Signals') }} {{ $account->risky_event_count }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        {{ $account->priority_reason ?: __('Priority score elevated by repeated suspicious activity.') }}
                                        <div class="mt-2 text-xs text-slate-500">
                                            {{ __('Last seen') }}: {{ $account->last_seen_at ?: __('Unknown') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        <div>{{ __('Max score') }}: <span class="font-semibold text-slate-900">{{ $account->max_score ?? 0 }}</span></div>
                                        <div class="mt-1">{{ __('Failures') }}: <span class="font-semibold text-slate-900">{{ $account->failure_event_count }}</span></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ $account->primary_href }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                {{ $account->primary_label }}
                                            </a>
                                            <a href="{{ $account->events_href }}" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                {{ __('View events') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                        {{ __('No M365 accounts are currently above the priority threshold.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $accounts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
