@extends('app.app')
{{--
<div class="account-selection-container">
   @foreach ($businessAccounts as $account)
   <div class="account-card">
      <div class="account-card-header">
         <img src="{{ $account['profile_picture_url'] }}" alt="Facebook Page Profile" class="profile-image">
      </div>
      <div class="account-card-body">
         <h4>Facebook Page:</h4>
         <p>{{ $account['page_name'] }}</p>
         <h4>Professional Instagram account:</h4>
         <a href="https://instagram.com/{{ $account['instagram']['username'] }}" class="instagram-handle">{{'@'.$account['instagram']['username'] }}</a>
      </div>
   </div>
   @endforeach
</div>
--}}
@section('content')
<div class="section section--dashboard">
   <section class="section section--select-account">
      <article>
         <h1 class="section__header">Select account</h1>
      </article>
       {{-- Livewire bileşenini burada çağırıyoruz --}}
   @livewire('select-account-component')

   </section>
   <section class="section">
      <h1 class="section__header">I can't see my Instagram account here, why?</h1>
      <p>In order to connect your account to our website using Connect with Facebook method following requirements
         must be met:
      </p>
      <ol>
         <li>Instagram account that you want to connect to LightWidget must be <strong>Instagram Professional
            Account</strong> (Instagram Business Account or Creator Account).
         </li>
         <li>Instagram Professional Account must be <strong>connected to Facebook Page</strong>.</li>
         <li>You must allow us access to permissions that we require during Facebook Login. Without the permission we
            are not able to get information about your Instagram account.
         </li>
      </ol>
   </section>
</div>
@endsection
