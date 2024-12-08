<?php $__env->startSection('content'); ?>
<div class="section section--dashboard">
   <section class="section section--select-account">
      <article>
         <h1 class="section__header">Select account</h1>
      </article>
       
   <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('select-account-component');

$__html = app('livewire')->mount($__name, $__params, 'lw-1628548509-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('app.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\resources\views/app/account_selection.blade.php ENDPATH**/ ?>