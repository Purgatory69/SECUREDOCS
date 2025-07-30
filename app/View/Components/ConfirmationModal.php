<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ConfirmationModal extends Component
{
    public $title;
    public $message;
    public $content;
    public $confirmButtonText;
    public $cancelButtonText;
    public $confirmAction;
    public $show;
    public $id;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        string $title = 'Confirm Action',
        string $message = 'Are you sure you want to perform this action?',
        string $content = '',
        string $confirmButtonText = 'Confirm',
        string $cancelButtonText = 'Cancel',
        string $confirmAction = '',
        bool $show = false,
        string $id = 'confirmationModal'
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->content = $content;
        $this->confirmButtonText = $confirmButtonText;
        $this->cancelButtonText = $cancelButtonText;
        $this->confirmAction = $confirmAction;
        $this->show = $show;
        $this->id = $id;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.confirmation-modal');
    }
}
