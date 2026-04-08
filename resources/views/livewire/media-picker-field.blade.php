<div class="space-y-2">
    {{-- Selected media preview --}}
    @php $selectedMedia = $this->getSelectedMedia(); @endphp

    @if(!empty($selectedMedia))
        <div class="{{ $multiple ? 'flex flex-wrap gap-2' : '' }}">
            @foreach($selectedMedia as $media)
                <div class="relative inline-flex group">
                    @if(str_starts_with($media['mime_type'] ?? '', 'image/'))
                        @php
                            $responsive = $media['responsive_images']['webp'] ?? [];
                            $urls = $responsive['urls'] ?? [];
                            $thumb = isset($urls[0])
                                ? route('media', ['filename' => "{$media['id']}/responsive-images/{$urls[0]}"])
                                : ($media['conversions_disk'] ? route('media', ['filename' => "{$media['id']}/{$media['file_name']}"]) : '');
                        @endphp
                        <img
                            src="{{ $thumb }}"
                            alt="{{ $media['name'] }}"
                            class="w-24 h-24 object-contain rounded-lg border border-gray-200 dark:border-white/10 bg-gray-100 dark:bg-white/5"
                        />
                    @else
                        <div class="w-24 h-24 flex flex-col items-center justify-center rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 gap-1">
                            <x-filament::icon icon="heroicon-o-film" class="w-8 h-8 text-gray-400" />
                            <span class="text-xs text-gray-500 truncate max-w-full px-1">{{ $media['name'] }}</span>
                        </div>
                    @endif
                    <button
                        type="button"
                        wire:click="removeMedia('{{ $media['uuid'] }}')"
                        class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-danger-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow"
                        title="Fjern"
                    >×</button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Open picker button --}}
    <x-filament::button
        type="button"
        color="gray"
        size="sm"
        icon="heroicon-o-photo"
        wire:click="$set('open', true)"
    >
        {{ $multiple ? 'Tilføj medier' : (empty($selectedMedia) ? 'Vælg medie' : 'Skift medie') }}
    </x-filament::button>

    {{-- Picker modal --}}
    @if($open)
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            wire:key="picker-modal"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closePicker"
            ></div>

            {{-- Modal content --}}
            <div class="relative z-10 w-full max-w-4xl max-h-[85vh] flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-xl overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-white/10">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Vælg medie</h3>
                    <button
                        type="button"
                        wire:click="closePicker"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Folder navigation + search --}}
                <div class="px-6 py-3 border-b border-gray-200 dark:border-white/10 flex flex-col gap-2">
                    {{-- Breadcrumb --}}
                    <div class="flex items-center gap-1 text-sm flex-wrap">
                        <button
                            type="button"
                            wire:click="setPickerFolder(null)"
                            @class([
                                'transition-colors',
                                'text-gray-900 dark:text-white font-medium' => $pickerFolderId === null,
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $pickerFolderId !== null,
                            ])
                        >Hjem</button>
                        @foreach($this->getPickerBreadcrumb() as $crumb)
                            <x-filament::icon icon="heroicon-o-chevron-right" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <button
                                type="button"
                                wire:click="setPickerFolder({{ $crumb['id'] }})"
                                @class([
                                    'transition-colors',
                                    'text-gray-900 dark:text-white font-medium' => $loop->last,
                                    'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => !$loop->last,
                                ])
                            >{{ $crumb['name'] }}</button>
                        @endforeach
                    </div>

                    {{-- Current folder's subfolders as pills --}}
                    @php
                        $currentFolder = $this->getPickerCurrentFolder();
                        $subfolders = $currentFolder
                            ? $currentFolder->children()->orderBy('name')->get()
                            : \RolandSolutions\ViltCms\Models\MediaFolder::whereNull('parent_id')->orderBy('name')->get();
                    @endphp
                    @if($subfolders->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($subfolders as $subfolder)
                                <button
                                    type="button"
                                    wire:click="setPickerFolder({{ $subfolder->id }})"
                                    class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20 transition-colors"
                                >
                                    <x-filament::icon icon="heroicon-o-folder" class="w-3.5 h-3.5" />
                                    {{ $subfolder->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Search --}}
                    <x-filament::input.wrapper class="max-w-xs">
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="pickerSearch"
                            placeholder="Søg..."
                        />
                    </x-filament::input.wrapper>
                </div>

                {{-- Media grid --}}
                <div class="flex-1 overflow-y-auto p-6">
                    @php $pickerMedia = $this->getPickerMedia(); @endphp
                    @if($pickerMedia->isEmpty())
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 mx-auto mb-3 opacity-40" />
                            <p class="text-sm">Ingen filer her.</p>
                            <p class="text-xs mt-1">
                                <a href="{{ route('filament.admin.pages.manage-media-library') }}" class="text-primary-600 hover:underline" target="_blank">
                                    Upload filer i mediebiblioteket
                                </a>
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                            @foreach($pickerMedia as $media)
                                @php
                                    $isSelected = is_array($this->state)
                                        ? in_array($media->uuid, $this->state)
                                        : $this->state === $media->uuid;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectMedia('{{ $media->uuid }}')"
                                    @class([
                                        'relative aspect-square rounded-lg overflow-hidden border-2 transition-all',
                                        'border-primary-500 ring-2 ring-primary-300' => $isSelected,
                                        'border-transparent hover:border-gray-300 dark:hover:border-white/30' => !$isSelected,
                                    ])
                                >
                                    @if(str_starts_with($media->mime_type ?? '', 'image/'))
                                        @php
                                            $thumb = $media->getFirstResponsiveImage() ?? $media->getCmsUrl();
                                        @endphp
                                        <img
                                            src="{{ $thumb }}"
                                            alt="{{ $media->name }}"
                                            class="w-full h-full object-contain bg-gray-100 dark:bg-white/5"
                                            loading="lazy"
                                        />
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-white/5 gap-1 p-2">
                                            <x-filament::icon icon="heroicon-o-film" class="w-8 h-8 text-gray-400" />
                                            <span class="text-xs text-gray-500 truncate w-full text-center">{{ $media->name }}</span>
                                        </div>
                                    @endif
                                    @if($isSelected)
                                        <div class="absolute top-1 right-1 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center">
                                            <x-filament::icon icon="heroicon-s-check" class="w-3 h-3 text-white" />
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                @if($multiple)
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-white/10 flex items-center justify-between">
                        <x-filament::button
                            type="button"
                            color="gray"
                            wire:click="selectAllInFolder"
                        >
                            Vælg alle i mappen
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            wire:click="closePicker"
                        >
                            Færdig
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
