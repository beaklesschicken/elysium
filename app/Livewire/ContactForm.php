<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Mail;

class ContactForm extends Component
{
    public $name;
    public $email;
    public $business;
    public $zipcode;
    public $comment;
    public $success;
    protected $rules = [
        'name' => 'required',
        'email' => 'required|email',
        'business' => 'required',
        'zipcode' => 'required',
        'comment' => 'required|min:5',
    ];

    public function contactFormSubmit()
    {
        $contact = $this->validate();

        Mail::send('email',
        array(
            'name' => $this->name,
            'email' => $this->email,
            'business' => $this->business,
            'zipcode' => $this->zipcode,
            'comment' => $this->comment,
            ),
            function($message){
                $message->from('contact@elysiumhospitality.net');
                $message->to('contact@elysiumhospitality.net', 'Eric')->subject('Contact Form');
            }
        );

        $this->success = 'Thank you for reaching out to us! We\'ll be in touch!';

        $this->clearFields();
    }

    private function clearFields()
    {
        $this->name = '';
        $this->email = '';
        $this->business = '';
        $this->zipcode = '';
        $this->comment = '';
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}

