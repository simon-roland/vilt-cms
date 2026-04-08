<?php

namespace RolandSolutions\ViltCms\Livewire;

use RolandSolutions\ViltCms\Models\Media;
use RolandSolutions\ViltCms\Models\MediaFolder;
use RolandSolutions\ViltCms\Models\MediaLibrary;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class MediaPickerField extends Component
{
    #[Modelable]
    public string|array|null $state = null;

    #[Locked]
    public bool $multiple = false;

    #[Locked]
    public ?Model $record = null;

    public bool $open = false;

    public ?int $pickerFolderId = null;

    public string $pickerSearch = '';

    public function selectMedia(string $uuid): void
    {
        if ($this->multiple) {
            $current = is_array($this->state) ? $this->state : [];
            if (!in_array($uuid, $current)) {
                $current[] = $uuid;
            }
            $this->state = $current;
        } else {
            $this->state = $uuid;
            $this->open = false;
        }
    }

    public function removeMedia(string $uuid): void
    {
        if ($this->multiple) {
            $this->state = array_values(array_filter(
                is_array($this->state) ? $this->state : [],
                fn ($u) => $u !== $uuid
            ));
        } else {
            $this->state = null;
        }
    }

    public function closePicker(): void
    {
        $this->open = false;
    }

    public function selectAllInFolder(): void
    {
        if (!$this->multiple) {
            return;
        }

        $uuids = $this->getPickerMedia()->pluck('uuid')->toArray();
        $current = is_array($this->state) ? $this->state : [];
        $this->state = array_values(array_unique(array_merge($current, $uuids)));
    }

    public function setPickerFolder(?int $id): void
    {
        $this->pickerFolderId = $id;
        $this->pickerSearch = '';
    }

    public function getSelectedMedia(): array
    {
        if (empty($this->state)) {
            return [];
        }

        $uuids = is_array($this->state) ? $this->state : [$this->state];

        return Media::whereIn('uuid', $uuids)
            ->whereHasMorph('model', [MediaLibrary::class])
            ->get()
            ->sortBy(fn ($m) => array_search($m->uuid, $uuids))
            ->values()
            ->toArray();
    }

    public function getPickerMedia(): \Illuminate\Support\Collection
    {
        return MediaLibrary::instance()
            ->media()
            ->where('media_folder_id', $this->pickerFolderId)
            ->when($this->pickerSearch !== '', fn ($q) => $q->where('name', 'like', "%{$this->pickerSearch}%"))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPickerCurrentFolder(): ?MediaFolder
    {
        if ($this->pickerFolderId === null) {
            return null;
        }

        return MediaFolder::find($this->pickerFolderId);
    }

    public function getPickerBreadcrumb(): array
    {
        if ($this->pickerFolderId === null) {
            return [];
        }

        $folder = MediaFolder::find($this->pickerFolderId);
        if (!$folder) {
            return [];
        }

        return $folder->ancestors()->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        return view('cms::livewire.media-picker-field');
    }
}
