<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta http-equiv="X-UA-Compatible" content="ie=edge">
      <title>Instagram App</title>
      <link rel="stylesheet" href="<?php echo e(asset('app/css/sado_app.css')); ?>">
      <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

   </head>
   <body class="page" cz-shortcut-listen="true">
    <?php echo $__env->make('sweetalert::alert', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
      <header class="main-header main-header--vertical">
         <a href="<?php echo e(url('/')); ?>" class="logo logo--small">
         <img src="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/lightwidget-logo.svg" alt="LightWidget - Responsive Widget for Instagram" width="100" height="100">
         </a>
         <nav data-menu="" class="nav" data-popover="" data-popover-enabled="">
            <button aria-label="Open main menu" class="nav__toggle nav__toggle--mobile" aria-expanded="false" data-popover-button="" data-popover-message-show="Open main menu" data-popover-message-hide="Close main menu">
               <svg aria-hidden="true" class="icon icon--extra-large icon--text-color">
                  <use xlink:href="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/symbols.c59524f3.svg#menu"></use>
               </svg>
            </button>
            <ul class="nav__list nav__list--vertical" data-popover-content="">
               <li class="nav__item">
                  <a class="nav__link nav__link--active nav__link--with-background" href="<?php echo e(url('dashboard')); ?>" aria-label="Dashboard">
                  <img class="icon icon--extra-large" src="<?php echo e(asset('app/icons/dashboard.svg')); ?>" alt="">
                  <span class="nav__link-text">Dashboard</span>
                  </a>
               </li>
               <li class="nav__item">
                  <a class="nav__link" href="<?php echo e(url('instagram-accounts')); ?>" aria-label="Accounts">
                  <img class="icon icon--extra-large" src="<?php echo e(asset('app/icons/accounts.svg')); ?>" alt="">
                  <span class="nav__link-text">Accounts</span>
                  </a>
               </li>
               <li class="nav__item">
                  <a class="nav__link" href="<?php echo e(url('my-widgets')); ?>" aria-label="Widgets">
                    <img class="icon icon--extra-large" src="<?php echo e(asset('app/icons/widget.svg')); ?>" alt="">
                     <span class="nav__link-text">Widgets</span>
                  </a>
               </li>
               <li class="nav__item">
                  <a class="nav__link" href="<?php echo e(url('settings')); ?>" aria-label="Settings">
                    <img class="icon icon--extra-large" src="<?php echo e(asset('app/icons/settings.svg')); ?>" alt="">
                     <span class="nav__link-text">Settings</span>
                  </a>
               </li>
               <li class="nav__item">
                  <a class="nav__link" href="<?php echo e(url('logout')); ?>" aria-label="Log out">
                    <img class="icon icon--extra-large" src="<?php echo e(asset('app/icons/logout.svg')); ?>" alt="">
                     <span class="nav__link-text">Log out</span>
                  </a>
               </li>
               <li class="nav__item nav__item--stick-to-bottom">
                  <div class="profile" data-tooltip="" data-tooltip-position="right" aria-label="Logged in as web@ucyirmiiki.com">
                     <div class="avatar profile__avatar">
                        <div class="avatar__replacement" aria-hidden="true">
                           we
                        </div>
                     </div>
                     <div class="profile__username">web@ucyirmiiki.com</div>
                  </div>
               </li>
            </ul>
         </nav>
      </header>
      <main class="main-content">
         <?php echo $__env->yieldContent('content'); ?>
      </main>
      <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

   </body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/resources/views/app/app.blade.php ENDPATH**/ ?>