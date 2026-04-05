@php $indent = $depth * 12; @endphp

<div style="padding-left: {{ $indent }}px">
    {{-- Folder row --}}
    @if($renamingFolderId === $folder->id)
        <div class="flex items-center gap-1 py-0.5">
            <input
                type="text"
                wire:model="renamingFolderName"
                wire:keydown.enter="renameFolder({{ $folder->id }})"
                wire:keydown.escape="cancelRenamingFolder"
                autofocus
                class="flex-1 min-w-0 text-sm rounded border border-gray-300 dark:border-white/20 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500"
            />
            <button wire:click="renameFolder({{ $folder->id }})" class="text-primary-600 hover:text-primary-700 p-1" title="Gem">
                <x-filament::icon icon="heroicon-o-check" class="w-3.5 h-3.5" />
            </button>
            <button wire:click="cancelRenamingFolder" class="text-gray-400 hover:text-gray-600 p-1" title="Annuller">
                <x-filament::icon icon="heroicon-o-x-mark" class="w-3.5 h-3.5" />
            </button>
        </div>
    @else
        <div class="group/folder flex items-center gap-1 py-0.5">
            <button
                wire:click="setFolder({{ $folder->id }})"
                @class([
                    'flex items-center gap-1.5 flex-1 min-w-0 text-left px-2 py-1.5 rounded-lg text-sm transition-colors',
                    'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 font-medium' => $activeFolderId === $folder->id,
                    'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' => $activeFolderId !== $folder->id,
                ])
            >
                <x-filament::icon icon="heroicon-o-folder" class="w-4 h-4 flex-shrink-0" />
                <span class="truncate">{{ $folder->name }}</span>
            </button>

            {{-- Folder actions (visible on hover) --}}
            <div class="flex items-center gap-0.5 opacity-0 group-hover/folder:opacity-100 transition-opacity flex-shrink-0">
                <button
                    wire:click="startCreatingFolder({{ $folder->id }})"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    title="Ny undermappe"
                >
                    <x-filament::icon icon="heroicon-o-plus" class="w-3.5 h-3.5" />
                </button>
                <button
                    wire:click="startRenamingFolder({{ $folder->id }}, '{{ addslashes($folder->name) }}')"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    title="Omdøb"
                >
                    <x-filament::icon icon="heroicon-o-pencil" class="w-3.5 h-3.5" />
                </button>
                <button
                    wire:click="requestDeleteFolder({{ $folder->id }})"
                    class="p-1 text-gray-400 hover:text-danger-500"
                    title="Slet"
                >
                    <x-filament::icon icon="heroicon-o-trash" class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    @endif

    {{-- Children --}}
    @foreach($folder->children as $child)
        @include('cms::filament.partials.folder-tree-item', ['folder' => $child, 'depth' => $depth + 1])
    @endforeach

    {{-- Inline create subfolder input --}}
    @if($creatingSubfolderUnder === $folder->id)
        <div class="flex items-center gap-1 mt-0.5 pl-6">
            <input
                type="text"
                wire:model="newFolderName"
                wire:keydown.enter="createFolder"
                wire:keydown.escape="cancelCreatingFolder"
                placeholder="Mappenavn..."
                autofocus
                class="flex-1 min-w-0 text-sm rounded border border-gray-300 dark:border-white/20 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500"
            />
            <button wire:click="createFolder" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 p-1">
                <x-filament::icon icon="heroicon-o-check" class="w-4 h-4" />
            </button>
            <button wire:click="cancelCreatingFolder" class="text-gray-400 hover:text-gray-600 p-1">
                <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endif
</div>
