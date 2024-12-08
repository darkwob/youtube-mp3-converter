<div>
    <div class="form__item">
        <div class="selection">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $businessAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="radio" name="instagramBusinessAccount"
                       id="instagramBusinessAccount[<?php echo e($account['instagram']['instagram_business_account_id']); ?>]"
                       value="<?php echo e($account['instagram']['instagram_business_account_id']); ?>"
                       wire:model="selectedAccount"
                       class="form__input sr-only">

                <label class="form__label selection__item"
                       for="instagramBusinessAccount[<?php echo e($account['instagram']['instagram_business_account_id']); ?>]">
                    <img src="<?php echo e($account['profile_picture_url']); ?>" alt="<?php echo e($account['page_name']); ?>"
                         class="selection__image">
                    <strong>Facebook Page:</strong>
                    <p style="margin-bottom: 1rem;"><?php echo e($account['page_name']); ?></p>
                    <strong>Professional Instagram account:</strong>
                    <a href="https://www.instagram.com/<?php echo e($account['instagram']['username']); ?>" class="link"
                       target="_blank"><?php echo e('@' . $account['instagram']['username']); ?></a>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </div>
    <div class="form__item button-group">
        <button wire:click="fetchInstagramData" type="submit" class="button button--primary">Connect selected Instagram account</button>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\resources\views/livewire/select-account-component.blade.php ENDPATH**/ ?>