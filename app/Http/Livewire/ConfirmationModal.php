<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ConfirmationModal extends Component
{
    public $show = false;
    public $title = '';
    public $message = '';
    public $confirmButtonText = 'Confirm';
    public $cancelButtonText = 'Cancel';
    public $confirmAction = '';

    protected $listeners = ['showConfirmationModal'];

    public function showConfirmationModal($data)
    {
        $this->title = $data['title'];
        $this->message = $data['message'];
        $this->confirmButtonText = $data['confirmButtonText'] ?? 'Confirm';
        $this->cancelButtonText = $data['cancelButtonText'] ?? 'Cancel';
        $this->confirmAction = $data['confirmAction'];
        $this->show = true;
    }

    public function confirm()
    {
        $this->emit($this->confirmAction);
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.confirmation-modal');
    }
}
