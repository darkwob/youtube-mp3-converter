<?php if(config('sweetalert.alwaysLoadJS') === true || Session::has('alert.config') || Session::has('alert.delete')): ?>
    <?php if(config('sweetalert.animation.enable')): ?>
        <link rel="stylesheet" href="<?php echo e(config('sweetalert.animatecss')); ?>">
    <?php endif; ?>

    <?php if(config('sweetalert.theme') != 'default'): ?>
        <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-<?php echo e(config('sweetalert.theme')); ?>" rel="stylesheet">
    <?php endif; ?>

    <?php if(config('sweetalert.neverLoadJS') === false): ?>
        <script src="<?php echo e($cdn ?? asset('vendor/sweetalert/sweetalert.all.js')); ?>"></script>
    <?php endif; ?>

    <?php if(Session::has('alert.delete') || Session::has('alert.config')): ?>
        <script>
            document.addEventListener('click', function(event) {
                // Check if the clicked element or its parent has the attribute
                var target = event.target;
                var confirmDeleteElement = target.closest('[data-confirm-delete]');

                if (confirmDeleteElement) {
                    event.preventDefault();
                    Swal.fire(<?php echo Session::pull('alert.delete'); ?>).then(function(result) {
                        if (result.isConfirmed) {
                            var form = document.createElement('form');
                            form.action = confirmDeleteElement.href;
                            form.method = 'POST';
                            form.innerHTML = `
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                        `;
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                }
            });

            <?php if(Session::has('alert.config')): ?>
                Swal.fire(<?php echo Session::pull('alert.config'); ?>);
            <?php endif; ?>
        </script>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/resources/views/vendor/sweetalert/alert.blade.php ENDPATH**/ ?>