<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\InstagramAccount;
use Illuminate\Support\Facades\Auth;

class InstagramAccounts extends Component
{
    public $instagramAccounts;

    public function mount()
    {
        // Giriş yapan kullanıcının seçtiği hesapları çekiyoruz
        $this->instagramAccounts = InstagramAccount::where('user_id', Auth::id())->get();
    }

    public function render()
    {
        return view('livewire.instagram-accounts', [
            'accounts' => $this->instagramAccounts,
        ]);
    }
}
