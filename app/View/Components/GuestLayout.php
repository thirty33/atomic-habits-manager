<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  string|null  $eyebrow  Small mono-uppercase label above the title.
     */
    public function __construct(public ?string $eyebrow = null) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
