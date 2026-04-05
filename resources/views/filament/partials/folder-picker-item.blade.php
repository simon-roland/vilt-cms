@php $indent = $depth * 16; @endphp

<button
    wire:click="$set('moveTargetFolderId', {{ $folder->id }})"
    style="padding-left: {{ $indent + 12 }}px"
    @class([
        'flex items-center gap-2 w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
        'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 font-medium' => $moveTargetFolderId === $folder->id,
        'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' => $moveTargetFolderId !== $folder->id,
    ])
>
    <x-filament::icon icon="heroicon-o-folder" class="w-4 h-4 flex-shrink-0" />
    {{ $folder->name }}
</button>

@foreach($folder->children as $child)
    @include('cms::filament.partials.folder-picker-item', ['folder' => $child, 'depth' => $depth + 1])
@endforeach
