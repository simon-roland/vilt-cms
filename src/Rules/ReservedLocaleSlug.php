<?php

namespace RolandSolutions\ViltCms\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RolandSolutions\ViltCms\Support\Locales;

class ReservedLocaleSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (in_array($value, Locales::keys(), true)) {
            $fail(__('cms::cms.validation.reserved_locale_slug', ['slug' => $value]));
        }
    }
}
