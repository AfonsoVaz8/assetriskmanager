<div class="relative">
    <input type="hidden" name="manager" value="{{ $selectedManagerId }}">

    <div class="relative">
        <input
            type="text"
            wire:model.live="searchTerm"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500"
            placeholder="{{ __('Search manager by name or email...') }}"
        >

        @if($selectedManagerId)
            <button type="button" wire:click="clearSelection" class="absolute right-2.5 bottom-2.5 text-gray-400 hover:text-gray-600 text-xl font-bold">
                &times;
            </button>
        @endif
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
