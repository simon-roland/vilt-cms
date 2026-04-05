<?php

namespace RolandSolutions\ViltCms\Filament\Pages;

use RolandSolutions\ViltCms\Models\Media;
use RolandSolutions\ViltCms\Models\MediaFolder;
use RolandSolutions\ViltCms\Models\MediaLibrary;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use BackedEnum;
use UnitEnum;

class ManageMediaLibrary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?string
    {
        return __('cms::cms.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('cms::cms.media_title');
    }

    public function getTitle(): string
    {
        return __('cms::cms.media_title');
    }

    protected string $view = 'cms::filament.pages.manage-media-library';

    public ?array $data = [];

    public ?int $activeFolderId = null;

    public string $search = '';

    // Folder creation state: -1 = creating at root, positive int = creating under that parent
    public ?int $creatingSubfolderUnder = null;

    public string $newFolderName = '';

    // Folder rename state
    public ?int $renamingFolderId = null;

    public string $renamingFolderName = '';

    // Multi-select state
    public array $selectedUuids = [];

    // Move media state (null = closed, true = open for selection)
    public bool $showMoveModal = false;

    public ?int $moveTargetFolderId = null;

    // When moving a single file via its hover button, track it separately so selectedUuids is untouched
    public ?string $pendingSingleMoveUuid = null;

    // Rename media state
    public ?string $renamingMediaUuid = null;

    public string $renamingMediaName = '';

    // Replace media state
    public ?string $replacingMediaUuid = null;

    public array $replaceFormData = [];

    // View mode: 'grid' or 'list'
    public string $viewMode = 'list';

    // Table sort state
    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    // Upload modal state
    public bool $showUploadModal = false;

    // Delete confirmation state (null = closed, '__bulk__' = bulk, uuid string = single)
    public ?string $confirmingDeleteUuid = null;

    // Folder delete confirmation state
    public ?int $confirmingDeleteFolderId = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model(MediaLibrary::instance())
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            SpatieMediaLibraryFileUpload::make('files')
                ->label(__('cms::cms.media_upload'))
                ->disk(config('cms.media_disk'))
                ->required()
                ->multiple()
                ->filterMediaUsing(
                    fn (MediaCollection $media): MediaCollection => $media->filter(fn () => false)
                )
                ->customProperties(fn () => ['media_folder_id' => $this->activeFolderId])
                ->imageEditor()
                ->imageEditorAspectRatioOptions([
                    null,
                    '16:9',
                    '5:4',
                    '4:3',
                    '5:3',
                    '1:1',
                ])
                ->acceptedFileTypes(['image/*', 'video/*'])
                ->maxSize(200 * 1024)
                ->conversion('webp'),
        ]);
    }

    public function replaceForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('replaceFormData')
            ->components([
                FileUpload::make('file')
                    ->label(__('cms::cms.media_new_file'))
                    ->disk('local')
                    ->directory('replace-tmp')
                    ->preserveFilenames()
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions([
                        null,
                        '16:9',
                        '5:4',
                        '4:3',
                        '5:3',
                        '1:1',
                    ])
                    ->acceptedFileTypes(['image/*', 'video/*'])
                    ->maxSize(200 * 1024)
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $this->form->getState(); // Validate form

        $this->form->model(MediaLibrary::instance())->saveRelationships();
        $this->form->fill();

        $this->showUploadModal = false;

        Notification::make()
            ->title(__('cms::cms.media_files_uploaded'))
            ->success()
            ->send();
    }

    public function openUploadModal(): void
    {
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->form->fill();
    }

    public function getMediaItems(): \Illuminate\Support\Collection
    {
        $allowed = ['name', 'size', 'created_at', 'mime_type'];
        $col = in_array($this->sortColumn, $allowed) ? $this->sortColumn : 'created_at';
        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return MediaLibrary::instance()
            ->media()
            ->where('media_folder_id', $this->activeFolderId)
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy($col, $dir)
            ->get();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function getFolderTree(): \Illuminate\Support\Collection
    {
        return MediaFolder::whereNull('parent_id')
            ->orderBy('name')
            ->with('children.children.children')
            ->get();
    }

    public function getActiveFolderBreadcrumb(): array
    {
        if ($this->activeFolderId === null) {
            return [];
        }

        $folder = MediaFolder::find($this->activeFolderId);
        if (!$folder) {
            return [];
        }

        return $folder->ancestors()->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->toArray();
    }

    public function setFolder(?int $id): void
    {
        $this->activeFolderId = $id;
        $this->search = '';
        $this->selectedUuids = [];
    }

    public function toggleSelection(string $uuid): void
    {
        if (in_array($uuid, $this->selectedUuids)) {
            $this->selectedUuids = array_values(array_filter($this->selectedUuids, fn ($u) => $u !== $uuid));
        } else {
            $this->selectedUuids[] = $uuid;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedUuids = [];
    }

    public function selectAll(): void
    {
        $this->selectedUuids = $this->getMediaItems()->pluck('uuid')->toArray();
    }

    public function startCreatingFolder(?int $parentId): void
    {
        // Use -1 as a sentinel value for "create at root"
        $this->creatingSubfolderUnder = $parentId ?? -1;
        $this->newFolderName = '';
    }

    public function cancelCreatingFolder(): void
    {
        $this->creatingSubfolderUnder = null;
        $this->newFolderName = '';
    }

    public function createFolder(): void
    {
        $name = trim($this->newFolderName);

        if ($name === '') {
            return;
        }

        $parentId = $this->creatingSubfolderUnder === -1 ? null : $this->creatingSubfolderUnder;

        $exists = MediaFolder::where('name', $name)
            ->where('parent_id', $parentId)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title(__('cms::cms.media_folder_exists'))
                ->warning()
                ->send();

            return;
        }

        MediaFolder::create([
            'name' => $name,
            'parent_id' => $parentId,
        ]);

        $this->creatingSubfolderUnder = null;
        $this->newFolderName = '';
    }

    public function startRenamingFolder(int $id, string $currentName): void
    {
        $this->renamingFolderId = $id;
        $this->renamingFolderName = $currentName;
    }

    public function cancelRenamingFolder(): void
    {
        $this->renamingFolderId = null;
        $this->renamingFolderName = '';
    }

    public function renameFolder(int $id): void
    {
        $name = trim($this->renamingFolderName);

        if ($name === '') {
            return;
        }

        $folder = MediaFolder::find($id);
        if (!$folder) {
            return;
        }

        $exists = MediaFolder::where('name', $name)
            ->where('parent_id', $folder->parent_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title(__('cms::cms.media_folder_exists'))
                ->warning()
                ->send();

            return;
        }

        $folder->update(['name' => $name]);

        $this->renamingFolderId = null;
        $this->renamingFolderName = '';
    }

    public function deleteFolder(int $id): void
    {
        $folder = MediaFolder::with('children')->find($id);

        if (!$folder) {
            return;
        }

        if ($folder->children->isNotEmpty() || $folder->media()->exists()) {
            Notification::make()
                ->title(__('cms::cms.media_folder_not_empty'))
                ->body(__('cms::cms.media_folder_not_empty_body'))
                ->danger()
                ->send();

            return;
        }

        // If the active folder is being deleted, navigate to root
        if ($this->activeFolderId === $id) {
            $this->activeFolderId = null;
        }

        $folder->delete();

        Notification::make()
            ->title(__('cms::cms.media_folder_deleted'))
            ->success()
            ->send();
    }

    public function openMoveModal(?string $uuid = null): void
    {
        $this->pendingSingleMoveUuid = $uuid;
        $this->showMoveModal = true;
        $this->moveTargetFolderId = $this->activeFolderId;
    }

    public function cancelMovingMedia(): void
    {
        $this->showMoveModal = false;
        $this->moveTargetFolderId = null;
        $this->pendingSingleMoveUuid = null;
    }

    public function moveSelected(): void
    {
        $uuids = $this->pendingSingleMoveUuid !== null
            ? [$this->pendingSingleMoveUuid]
            : $this->selectedUuids;

        if (empty($uuids)) {
            return;
        }

        Media::whereIn('uuid', $uuids)
            ->whereHasMorph('model', [MediaLibrary::class])
            ->each(function (Media $media) {
                $media->media_folder_id = $this->moveTargetFolderId;
                $media->saveQuietly();
            });

        if ($this->pendingSingleMoveUuid === null) {
            $this->selectedUuids = [];
        }
        $this->pendingSingleMoveUuid = null;
        $this->showMoveModal = false;
        $this->moveTargetFolderId = null;
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedUuids)) {
            return;
        }

        Media::whereIn('uuid', $this->selectedUuids)
            ->whereHasMorph('model', [MediaLibrary::class])
            ->each(fn (Media $media) => $media->delete());

        $count = count($this->selectedUuids);
        $this->selectedUuids = [];

        Notification::make()
            ->title($count === 1 ? __('cms::cms.media_file_deleted') : trans('cms::cms.media_files_deleted', ['count' => $count]))
            ->success()
            ->send();
    }

    public function deleteMedia(string $uuid): void
    {
        $this->selectedUuids = [$uuid];
        $this->deleteSelected();
    }

    public function requestDeleteMedia(string $uuid): void
    {
        $this->confirmingDeleteUuid = $uuid;
    }

    public function requestDeleteSelected(): void
    {
        $this->confirmingDeleteUuid = '__bulk__';
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteUuid = null;
    }

    public function executeDelete(): void
    {
        if ($this->confirmingDeleteUuid === '__bulk__') {
            $this->deleteSelected();
        } elseif ($this->confirmingDeleteUuid !== null) {
            $this->deleteMedia($this->confirmingDeleteUuid);
        }

        $this->confirmingDeleteUuid = null;
    }

    public function requestDeleteFolder(int $id): void
    {
        $this->confirmingDeleteFolderId = $id;
    }

    public function cancelDeleteFolder(): void
    {
        $this->confirmingDeleteFolderId = null;
    }

    public function executeDeleteFolder(): void
    {
        if ($this->confirmingDeleteFolderId !== null) {
            $this->deleteFolder($this->confirmingDeleteFolderId);
        }

        $this->confirmingDeleteFolderId = null;
    }

    public function startRenamingMedia(string $uuid, string $currentName): void
    {
        $this->renamingMediaUuid = $uuid;
        $this->renamingMediaName = $currentName;
    }

    public function cancelRenamingMedia(): void
    {
        $this->renamingMediaUuid = null;
        $this->renamingMediaName = '';
    }

    public function saveMediaName(): void
    {
        $name = trim($this->renamingMediaName);

        if ($name === '' || $this->renamingMediaUuid === null) {
            return;
        }

        $media = Media::where('uuid', $this->renamingMediaUuid)
            ->whereHasMorph('model', [MediaLibrary::class])
            ->first();

        if (!$media) {
            return;
        }

        $media->name = $name;
        $media->saveQuietly();

        $this->renamingMediaUuid = null;
        $this->renamingMediaName = '';

        Notification::make()
            ->title(__('cms::cms.media_file_renamed'))
            ->success()
            ->send();
    }

    public function openReplaceModal(string $uuid): void
    {
        $this->replacingMediaUuid = $uuid;
        $this->replaceForm->fill();
    }

    public function cancelReplacingMedia(): void
    {
        $this->replacingMediaUuid = null;
        $this->replaceForm->fill();
    }

    public function executeReplaceMedia(): void
    {
        $state = $this->replaceForm->getState();

        $filePath = $state['file'] ?? null;
        if (is_array($filePath)) {
            $filePath = array_values($filePath)[0] ?? null;
        }

        if (!$filePath) {
            return;
        }

        $media = Media::where('uuid', $this->replacingMediaUuid)
            ->whereHasMorph('model', [MediaLibrary::class])
            ->first();

        if (!$media) {
            return;
        }

        $absolutePath = Storage::disk('local')->path($filePath);
        $newFileName = basename($filePath);
        $fileObj = new \Illuminate\Http\File($absolutePath);
        $mimeType = $fileObj->getMimeType();
        $size = $fileObj->getSize();

        // Delete old file and its responsive images
        $mediaDisk = config('cms.media_disk');
        Storage::disk($mediaDisk)->delete("{$media->id}/{$media->file_name}");
        Storage::disk($mediaDisk)->deleteDirectory("{$media->id}/conversions");
        Storage::disk($mediaDisk)->deleteDirectory("{$media->id}/responsive-images");

        // Store replacement file at the same directory
        Storage::disk($mediaDisk)->putFileAs((string) $media->id, $fileObj, $newFileName);

        // Clean up temp file
        Storage::disk('local')->delete($filePath);

        // Update record metadata, clear cached conversions
        $media->file_name = $newFileName;
        $media->mime_type = $mimeType ?: $media->mime_type;
        $media->size = $size;
        $media->responsive_images = [];
        $media->generated_conversions = [];
        $media->saveQuietly();

        // Regenerate WebP responsive image conversions
        app(FileManipulator::class)->createDerivedFiles($media, withResponsiveImages: true);

        $this->replacingMediaUuid = null;
        $this->replaceForm->fill();

        Notification::make()
            ->title(__('cms::cms.media_file_replaced'))
            ->success()
            ->send();
    }
}
