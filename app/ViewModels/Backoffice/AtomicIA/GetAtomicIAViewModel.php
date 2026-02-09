<?php

namespace App\ViewModels\Backoffice\AtomicIA;

use App\ViewModels\ViewModel;

class GetAtomicIAViewModel extends ViewModel
{
    public function pageTitle(): string
    {
        return __('Atomic IA');
    }
}
