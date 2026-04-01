<div class="relative w-full">
    <div class="relative">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
            </svg>
        </div>
        <input
            id="manager_search"
            type="text"
            wire:model.live.debounce.300ms="searchTerm"
            class="block w-full p-2.5 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 shadow-sm transition-all"
            placeholder="{{__('Search for a manager by name or email...')}}"
            autocomplete="off"
        >
        <div wire:loading wire:target="searchTerm" class="absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    @if($showDropdown && $users && $users->count() > 0)
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700">
            <ul class="max-h-60 overflow-y-auto text-sm text-gray-700 dark:text-gray-200 py-1">
                @foreach($users as $user)
                    <li
                        wire:click="selectUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}')"
                        class="px-4 py-3 cursor-pointer hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-gray-700 dark:hover:text-white transition-colors duration-150 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    >
                        <div class="flex flex-col">
                            <span class="font-semibold">{{ $user->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $user->email }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif($showDropdown && strlen($searchTerm) >= 2 && $users && $users->count() === 0)
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700">
            <div class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{__("No users found matching your search.")}}
            </div>
        </div>
    @endif
</div>