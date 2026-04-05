<x-filament-panels::page>
    {{-- Library browser --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        <div class="flex flex-col lg:flex-row min-h-[400px]">

            {{-- Folder tree sidebar --}}
            @php $folderTree = $this->getFolderTree(); @endphp
            <div class="lg:w-56 xl:w-64 flex-shrink-0 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-white/10 p-4 flex flex-col gap-1">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Mapper</h3>

                {{-- Root --}}
                <button
                    wire:click="setFolder(null)"
                    @class([
                        'flex items-center gap-2 w-full text-left px-2 py-1.5 rounded-lg text-sm transition-colors',
                        'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 font-medium' => $activeFolderId === null,
                        'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' => $activeFolderId !== null,
                    ])
                >
                    <x-filament::icon icon="heroicon-o-home" class="w-4 h-4 flex-shrink-0" />
                    Hjem
                </button>

                {{-- Recursive folder tree --}}
                @foreach($folderTree as $folder)
                    @include('cms::filament.partials.folder-tree-item', ['folder' => $folder, 'depth' => 0])
                @endforeach

                {{-- Create root folder --}}
                @if($creatingSubfolderUnder === -1)
                    <div class="flex items-center gap-1 mt-1 pl-2">
                        <input
                            type="text"
                            wire:model="newFolderName"
                            wire:keydown.enter="createFolder"
                            wire:keydown.escape="cancelCreatingFolder"
                            placeholder="Mappenavn..."
                            autofocus
                            class="flex-1 min-w-0 text-sm rounded border border-gray-300 dark:border-white/20 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        />
                        <button wire:click="createFolder" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 p-1" title="Opret">
                            <x-filament::icon icon="heroicon-o-check" class="w-4 h-4" />
                        </button>
                        <button wire:click="cancelCreatingFolder" class="text-gray-400 hover:text-gray-600 p-1" title="Annuller">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @else
                    <button
                        wire:click="startCreatingFolder(null)"
                        class="flex items-center gap-1.5 mt-1 px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                    >
                        <x-filament::icon icon="heroicon-o-plus" class="w-3.5 h-3.5" />
                        Ny mappe
                    </button>
                @endif
            </div>

            {{-- Media grid --}}
            <div class="flex-1 p-6 flex flex-col gap-4">
                {{-- Breadcrumb + search --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    {{-- Breadcrumb --}}
                    <nav class="flex items-center gap-1 text-sm flex-wrap">
                        <button wire:click="setFolder(null)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                            Hjem
                        </button>
                        @foreach($this->getActiveFolderBreadcrumb() as $crumb)
                            <x-filament::icon icon="heroicon-o-chevron-right" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <button
                                wire:click="setFolder({{ $crumb['id'] }})"
                                @class([
                                    'transition-colors',
                                    'text-gray-900 dark:text-white font-medium' => $loop->last,
                                    'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => !$loop->last,
                                ])
                            >{{ $crumb['name'] }}</button>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-2">
                        <x-filament::input.wrapper class="max-w-xs">
                            <x-filament::input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Søg..."
                            />
                        </x-filament::input.wrapper>
                        {{-- View mode toggle --}}
                        <div class="flex rounded-lg border border-gray-300 dark:border-white/20 overflow-hidden">
                            <button
                                wire:click="$set('viewMode', 'grid')"
                                title="Fliser"
                                @class([
                                    'p-1.5 transition-colors',
                                    'bg-primary-500 text-white' => $viewMode === 'grid',
                                    'text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10' => $viewMode !== 'grid',
                                ])
                            >
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="w-4 h-4" />
                            </button>
                            <button
                                wire:click="$set('viewMode', 'list')"
                                title="Tabel"
                                @class([
                                    'p-1.5 transition-colors',
                                    'bg-primary-500 text-white' => $viewMode === 'list',
                                    'text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10' => $viewMode !== 'list',
                                ])
                            >
                                <x-filament::icon icon="heroicon-o-list-bullet" class="w-4 h-4" />
                            </button>
                        </div>
                        <x-filament::button wire:click="openUploadModal" size="sm" icon="heroicon-o-arrow-up-tray">
                            Upload filer
                        </x-filament::button>
                    </div>
                </div>

                {{-- Selection toolbar --}}
                @if(count($selectedUuids) > 0)
                    <div class="flex items-center gap-3 px-3 py-2 bg-primary-50 dark:bg-primary-500/10 rounded-lg text-sm">
                        <span class="text-primary-700 dark:text-primary-400 font-medium flex-1">
                            {{ count($selectedUuids) }} {{ count($selectedUuids) === 1 ? 'fil valgt' : 'filer valgt' }}
                        </span>
                        <x-filament::button
                            color="gray"
                            size="xs"
                            wire:click="openMoveModal"
                        >
                            Flyt
                        </x-filament::button>
                        <x-filament::button
                            color="danger"
                            size="xs"
                            wire:click="requestDeleteSelected"
                        >
                            Slet
                        </x-filament::button>
                        <button
                            wire:click="clearSelection"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-0.5"
                            title="Fjern valg"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @endif

                {{-- Media grid --}}
                @php $mediaItems = $this->getMediaItems(); @endphp
                @if($mediaItems->isEmpty())
                    <div class="flex-1 flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                        <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 mb-3 opacity-40" />
                        <p class="text-sm">Ingen filer her.</p>
                    </div>
                @else
                    {{-- File count + select all --}}
                    @php $allSelected = $mediaItems->count() > 0 && count($selectedUuids) === $mediaItems->count(); @endphp
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 -mb-1">
                        <span>{{ $mediaItems->count() }} {{ $mediaItems->count() === 1 ? 'fil' : 'filer' }}</span>
                        @if($allSelected)
                            <button wire:click="clearSelection" class="text-primary-600 hover:underline dark:text-primary-400">Fravælg alle</button>
                        @else
                            <button wire:click="selectAll" class="text-primary-600 hover:underline dark:text-primary-400">Vælg alle</button>
                        @endif
                    </div>

                    @if($viewMode === 'grid')
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($mediaItems as $media)
                                @php $isSelected = in_array($media->uuid, $selectedUuids); @endphp
                                <div
                                    wire:click="toggleSelection('{{ $media->uuid }}')"
                                    @class([
                                        'relative group rounded-lg overflow-hidden bg-gray-100 dark:bg-white/5 aspect-square cursor-pointer ring-2 transition-all',
                                        'ring-primary-500' => $isSelected,
                                        'ring-transparent hover:ring-gray-300 dark:hover:ring-white/20' => !$isSelected,
                                    ])
                                >
                                    @if(str_starts_with($media->mime_type ?? '', 'image/'))
                                        @php $thumb = $media->getFirstResponsiveImage() ?? $media->getCmsUrl(); @endphp
                                        <img
                                            src="{{ $thumb }}"
                                            alt="{{ $media->name }}"
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                        />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <x-filament::icon icon="heroicon-o-film" class="w-10 h-10 text-gray-400" />
                                        </div>
                                    @endif

                                    {{-- Hover overlay with individual actions --}}
                                    <div @click.stop class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-end justify-between gap-1 p-1.5">
                                        {{-- Buttons top-right --}}
                                        <div class="flex flex-col items-end gap-1">
                                            <a
                                                href="{{ $media->getCmsUrl() }}"
                                                target="_blank"
                                                rel="noopener"
                                                @click.stop
                                                class="inline-flex items-center justify-center rounded-lg font-semibold text-xs px-2 py-1 bg-white/20 hover:bg-white/30 text-white transition-colors"
                                            >
                                                Vis
                                            </a>
                                            <x-filament::button
                                                color="gray"
                                                size="xs"
                                                wire:click.stop="startRenamingMedia('{{ $media->uuid }}', '{{ addslashes($media->name) }}')"
                                            >
                                                Omdøb
                                            </x-filament::button>
                                            <x-filament::button
                                                color="gray"
                                                size="xs"
                                                wire:click.stop="openMoveModal('{{ $media->uuid }}')"
                                            >
                                                Flyt
                                            </x-filament::button>
                                            <x-filament::button
                                                color="gray"
                                                size="xs"
                                                wire:click.stop="openReplaceModal('{{ $media->uuid }}')"
                                            >
                                                Erstat
                                            </x-filament::button>
                                            <x-filament::button
                                                color="danger"
                                                size="xs"
                                                wire:click.stop="requestDeleteMedia('{{ $media->uuid }}')"
                                            >
                                                Slet
                                            </x-filament::button>
                                        </div>
                                        {{-- Name bottom --}}
                                        <p class="text-white text-xs truncate w-full leading-tight px-0.5">{{ $media->name }}</p>
                                    </div>

                                    {{-- Selection indicator (rendered on top of overlay) --}}
                                    <div @class([
                                        'absolute top-1.5 left-1.5 w-5 h-5 rounded-full flex items-center justify-center shadow transition-opacity',
                                        'bg-primary-500 opacity-100' => $isSelected,
                                        'bg-white/90 border border-gray-400 opacity-0 group-hover:opacity-100' => !$isSelected,
                                    ])>
                                        @if($isSelected)
                                            <x-filament::icon icon="heroicon-s-check" class="w-3 h-3 text-white" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Table view --}}
                        @php
                            $sortIcon = fn(string $col) => $sortColumn === $col
                                ? ($sortDirection === 'asc' ? 'heroicon-s-chevron-up' : 'heroicon-s-chevron-down')
                                : 'heroicon-o-chevron-up-down';
                        @endphp
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 overflow-visible">
                            <table class="w-full table-fixed text-sm">
                                <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                    <tr>
                                        <th class="w-8 px-3 py-2"></th>
                                        <th class="w-12 px-2 py-2"></th>
                                        <th class="px-3 py-2 text-left w-full">
                                            <button wire:click="sortBy('name')" class="flex items-center gap-1 font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                                Navn
                                                <x-filament::icon :icon="$sortIcon('name')" class="w-3.5 h-3.5" />
                                            </button>
                                        </th>
                                        <th class="px-3 py-2 text-left hidden sm:table-cell w-28">
                                            <button wire:click="sortBy('mime_type')" class="flex items-center gap-1 font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                                Type
                                                <x-filament::icon :icon="$sortIcon('mime_type')" class="w-3.5 h-3.5" />
                                            </button>
                                        </th>
                                        <th class="px-3 py-2 text-left hidden md:table-cell w-28">
                                            <button wire:click="sortBy('size')" class="flex items-center gap-1 font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                                Størrelse
                                                <x-filament::icon :icon="$sortIcon('size')" class="w-3.5 h-3.5" />
                                            </button>
                                        </th>
                                        <th class="px-3 py-2 text-left hidden lg:table-cell w-46">
                                            <button wire:click="sortBy('created_at')" class="flex items-center gap-1 font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                                Uploadet
                                                <x-filament::icon :icon="$sortIcon('created_at')" class="w-3.5 h-3.5" />
                                            </button>
                                        </th>
                                        <th class="px-3 py-2 w-10"></th>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                    @foreach($mediaItems as $media)
                                        @php $isSelected = in_array($media->uuid, $selectedUuids); @endphp
                                        <tr
                                            wire:click="toggleSelection('{{ $media->uuid }}')"
                                            @class([
                                                'group cursor-pointer transition-colors',
                                                'bg-primary-50 dark:bg-primary-500/10' => $isSelected,
                                                'hover:bg-gray-50 dark:hover:bg-white/5' => !$isSelected,
                                            ])
                                        >
                                            {{-- Checkbox --}}
                                            <td class="px-3 py-2">
                                                <div @class([
                                                    'w-5 h-5 rounded-full flex items-center justify-center border transition-colors',
                                                    'bg-primary-500 border-primary-500' => $isSelected,
                                                    'border-gray-400 dark:border-gray-500 group-hover:border-primary-400' => !$isSelected,
                                                ])>
                                                    @if($isSelected)
                                                        <x-filament::icon icon="heroicon-s-check" class="w-3 h-3 text-white" />
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- Thumbnail --}}
                                            <td class="px-2 py-1.5">
                                                <div class="w-10 h-10 rounded overflow-hidden bg-gray-100 dark:bg-white/5 flex-shrink-0">
                                                    @if(str_starts_with($media->mime_type ?? '', 'image/'))
                                                        @php $thumb = $media->getFirstResponsiveImage() ?? $media->getCmsUrl(); @endphp
                                                        <img src="{{ $thumb }}" alt="" class="w-full h-full object-cover" loading="lazy" />
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <x-filament::icon icon="heroicon-o-film" class="w-5 h-5 text-gray-400" />
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- Name --}}
                                            <td class="px-3 py-2 w-full overflow-hidden">
                                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $media->name }}</p>
                                            </td>
                                            {{-- Type --}}
                                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                                {{ strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION)) }}
                                            </td>
                                            {{-- Size --}}
                                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 hidden md:table-cell whitespace-nowrap">
                                                @if($media->size >= 1024 * 1024)
                                                    {{ number_format($media->size / (1024 * 1024), 1) }} MB
                                                @else
                                                    {{ number_format($media->size / 1024, 0) }} KB
                                                @endif
                                            </td>
                                            {{-- Date --}}
                                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 hidden lg:table-cell" title="{{ $media->created_at->format('d/m/Y H:i') }}">
                                                {{ $media->created_at->diffForHumans() }}
                                            </td>
                                            {{-- Actions --}}
                                            <td @click.stop class="px-3 py-2 relative">
                                                <div
                                                    x-data="{ open: false }"
                                                    x-on:click.stop
                                                    class="flex justify-end"
                                                >
                                                    <button
                                                        @click="open = !open"
                                                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                                        title="Handlinger"
                                                    >
                                                        <x-filament::icon icon="heroicon-o-ellipsis-vertical" class="w-4 h-4" />
                                                    </button>

                                                    <div
                                                        x-show="open"
                                                        x-on:click.outside="open = false"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-75"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute z-50 top-full mt-1 w-36 right-0 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 py-1 text-sm"
                                                        style="display: none;"
                                                    >
                                                        <a
                                                            href="{{ $media->getCmsUrl() }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            @click="open = false"
                                                            class="flex items-center gap-2 px-3 py-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                                        >
                                                            <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-4 h-4 flex-shrink-0" />
                                                            Vis
                                                        </a>
                                                        <button
                                                            @click="open = false"
                                                            wire:click="startRenamingMedia('{{ $media->uuid }}', '{{ addslashes($media->name) }}')"
                                                            class="flex items-center gap-2 w-full px-3 py-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                                        >
                                                            <x-filament::icon icon="heroicon-o-pencil" class="w-4 h-4 flex-shrink-0" />
                                                            Omdøb
                                                        </button>
                                                        <button
                                                            @click="open = false"
                                                            wire:click="openMoveModal('{{ $media->uuid }}')"
                                                            class="flex items-center gap-2 w-full px-3 py-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                                        >
                                                            <x-filament::icon icon="heroicon-o-folder-arrow-down" class="w-4 h-4 flex-shrink-0" />
                                                            Flyt
                                                        </button>
                                                        <button
                                                            @click="open = false"
                                                            wire:click="openReplaceModal('{{ $media->uuid }}')"
                                                            class="flex items-center gap-2 w-full px-3 py-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                                        >
                                                            <x-filament::icon icon="heroicon-o-arrow-path-rounded-square" class="w-4 h-4 flex-shrink-0" />
                                                            Erstat
                                                        </button>
                                                        <div class="my-1 border-t border-gray-100 dark:border-white/10"></div>
                                                        <button
                                                            @click="open = false"
                                                            wire:click="requestDeleteMedia('{{ $media->uuid }}')"
                                                            class="flex items-center gap-2 w-full px-3 py-1.5 text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-500/10 transition-colors"
                                                        >
                                                            <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4 flex-shrink-0" />
                                                            Slet
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Replace media modal --}}
    @if($replacingMediaUuid !== null)
        @php $replacingMedia = \RolandSolutions\ViltCms\Models\Media::where('uuid', $replacingMediaUuid)->first(); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="cancelReplacingMedia"></div>
            <div
                x-data="{ uploading: 0 }"
                x-on:livewire-upload-start.window="uploading++"
                x-on:livewire-upload-finish.window="uploading--"
                x-on:livewire-upload-error.window="uploading--"
                x-on:file-upload-started.window="uploading++"
                x-on:file-upload-finished.window="uploading--"
                x-on:file-upload-error.window="uploading--"
                class="relative z-10 w-full max-w-lg bg-white dark:bg-gray-900 rounded-xl shadow-xl flex flex-col max-h-[90vh]"
            >
                {{-- Header --}}
                <div class="flex items-start justify-between px-6 pt-6 pb-4 flex-shrink-0">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Erstat fil</h3>
                        @if($replacingMedia)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 truncate max-w-xs">{{ $replacingMedia->name }}</p>
                        @endif
                    </div>
                    <button wire:click="cancelReplacingMedia" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-0.5 -mt-1 -mr-1">
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Form --}}
                <div class="overflow-y-auto flex-1 px-6 pb-2">
                    {{ $this->replaceForm }}
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-2 px-6 py-4 flex-shrink-0 border-t border-gray-100 dark:border-white/10">
                    <x-filament::button color="gray" wire:click="cancelReplacingMedia" type="button" x-bind:disabled="uploading > 0">Annuller</x-filament::button>
                    <x-filament::button wire:click="executeReplaceMedia" wire:loading.attr="disabled" wire:target="executeReplaceMedia" x-bind:disabled="uploading > 0">Erstat</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Rename media modal --}}
    @if($renamingMediaUuid !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="cancelRenamingMedia"></div>
            <div class="relative z-10 w-full max-w-sm bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Omdøb fil</h3>
                <input
                    type="text"
                    wire:model="renamingMediaName"
                    wire:keydown.enter="saveMediaName"
                    wire:keydown.escape="cancelRenamingMedia"
                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-white/20 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    x-init="$el.focus(); $el.select()"
                />
                <div class="flex justify-end gap-2 mt-4">
                    <x-filament::button color="gray" wire:click="cancelRenamingMedia">Annuller</x-filament::button>
                    <x-filament::button wire:click="saveMediaName">Gem</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Move media modal --}}
    @if($showMoveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="cancelMovingMedia"></div>
            <div class="relative z-10 w-full max-w-sm bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-1">Flyt til mappe</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    @if($pendingSingleMoveUuid !== null)
                        1 fil
                    @else
                        {{ count($selectedUuids) === 1 ? '1 fil' : count($selectedUuids) . ' filer' }}
                    @endif
                </p>

                <div class="space-y-1 max-h-64 overflow-y-auto">
                    <button
                        wire:click="$set('moveTargetFolderId', null)"
                        @class([
                            'flex items-center gap-2 w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
                            'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 font-medium' => $moveTargetFolderId === null,
                            'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' => $moveTargetFolderId !== null,
                        ])
                    >
                        <x-filament::icon icon="heroicon-o-home" class="w-4 h-4 flex-shrink-0" />
                        Hjem
                    </button>
                    @foreach($this->getFolderTree() as $folder)
                        @include('cms::filament.partials.folder-picker-item', ['folder' => $folder, 'depth' => 0])
                    @endforeach
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <x-filament::button color="gray" wire:click="cancelMovingMedia">Annuller</x-filament::button>
                    <x-filament::button wire:click="moveSelected">Flyt hertil</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Folder delete confirmation modal --}}
    @if($confirmingDeleteFolderId !== null)
        @php $confirmingDeleteFolder = \RolandSolutions\ViltCms\Models\MediaFolder::find($confirmingDeleteFolderId); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="cancelDeleteFolder"></div>
            <div class="relative z-10 w-full max-w-sm bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-danger-100 dark:bg-danger-500/20">
                        <x-filament::icon icon="heroicon-o-trash" class="w-5 h-5 text-danger-600 dark:text-danger-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Slet mappe</h3>
                        @if($confirmingDeleteFolder)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Er du sikker på at du vil slette mappen <span class="font-medium text-gray-700 dark:text-gray-300">{{ $confirmingDeleteFolder->name }}</span>? Dette kan ikke fortrydes.
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <x-filament::button color="gray" wire:click="cancelDeleteFolder">Annuller</x-filament::button>
                    <x-filament::button color="danger" wire:click="executeDeleteFolder">Slet</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirmation modal --}}
    @if($confirmingDeleteUuid !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="cancelDelete"></div>
            <div class="relative z-10 w-full max-w-sm bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-danger-100 dark:bg-danger-500/20">
                        <x-filament::icon icon="heroicon-o-trash" class="w-5 h-5 text-danger-600 dark:text-danger-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            @if($confirmingDeleteUuid === '__bulk__')
                                Slet {{ count($selectedUuids) === 1 ? '1 fil' : count($selectedUuids) . ' filer' }}
                            @else
                                Slet fil
                            @endif
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if($confirmingDeleteUuid === '__bulk__')
                                Er du sikker på at du vil slette de valgte filer? Dette kan ikke fortrydes.
                            @else
                                Er du sikker på at du vil slette denne fil? Dette kan ikke fortrydes.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <x-filament::button color="gray" wire:click="cancelDelete">Annuller</x-filament::button>
                    <x-filament::button color="danger" wire:click="executeDelete">Slet</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Upload modal --}}
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="closeUploadModal"></div>
            <div
                x-data="{ uploading: 0 }"
                x-on:livewire-upload-start.window="uploading++"
                x-on:livewire-upload-finish.window="uploading--"
                x-on:livewire-upload-error.window="uploading--"
                x-on:file-upload-started.window="uploading++"
                x-on:file-upload-finished.window="uploading--"
                x-on:file-upload-error.window="uploading--"
                class="relative z-10 w-full max-w-lg bg-white dark:bg-gray-900 rounded-xl shadow-xl flex flex-col max-h-[90vh]"
            >
                {{-- Header --}}
                <div class="flex items-start justify-between px-6 pt-6 pb-4 flex-shrink-0">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Upload filer</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            Uploader til:
                            @if($activeFolderId !== null)
                                @php $uploadFolder = \RolandSolutions\ViltCms\Models\MediaFolder::find($activeFolderId); @endphp
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $uploadFolder?->name ?? 'Rod' }}</span>
                            @else
                                <span class="font-medium text-gray-700 dark:text-gray-300">Hjem</span>
                            @endif
                        </p>
                    </div>
                    <button wire:click="closeUploadModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-0.5 -mt-1 -mr-1">
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Form --}}
                <form wire:submit="save" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    {{-- Scrollable body --}}
                    <div class="overflow-y-auto flex-1 px-6">
                        {{ $this->form }}
                        <p class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mt-3 mb-1">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-3.5 h-3.5 flex-shrink-0" />
                            Billeder optimeres automatisk og der genereres responsive varianter efter upload.
                        </p>
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end gap-2 px-6 py-4 flex-shrink-0 border-t border-gray-100 dark:border-white/10">
                        <x-filament::button color="gray" wire:click="closeUploadModal" type="button" x-bind:disabled="uploading > 0">Annuller</x-filament::button>
                        <x-filament::button type="submit" x-bind:disabled="uploading > 0">
                            <span x-show="uploading === 0" wire:loading.remove wire:target="save">Upload</span>
                            <span x-show="uploading > 0" wire:loading.remove wire:target="save">
                                <x-filament::loading-indicator class="w-5 h-5" />
                            </span>
                            <span wire:loading wire:target="save">
                                <x-filament::loading-indicator class="w-5 h-5" />
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
