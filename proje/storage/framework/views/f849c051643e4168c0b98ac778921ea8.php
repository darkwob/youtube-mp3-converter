<?php $__env->startSection('content'); ?>
<main class="main-content">
    <div class="section section--dashboard">
       <section class="section section--instagram-accounts">
          <h1 class="section__header">My Instagram accounts</h1>
          <article class="text-container--center">
             <p>Here you can find the list of Instagram accounts that you can use to create a widget.</p>
          </article>
          <ul class="accounts-list">
             <li class="accounts-list__item">
                <figure class="instagram-account" data-status="active" data-account-id="17841403133142687" data-account-type="1">
                   <div class="avatar ">
                      <img src="https://scontent-fra5-1.xx.fbcdn.net/v/t51.2885-15/400661512_1303008393678904_8719079606846500501_n.jpg?_nc_cat=102&amp;ccb=1-7&amp;_nc_sid=7d201b&amp;_nc_ohc=dB1GqVqJMJgQ7kNvgExeeqX&amp;_nc_zt=23&amp;_nc_ht=scontent-fra5-1.xx&amp;edm=AL-3X8kEAAAA&amp;oh=00_AYBzOOXNMtDdLAF0HMz_ev9uAeYPVoC8CLWnV1FXRVvn5w&amp;oe=673BE351" alt="Sadık Sefa profile picture" class="avatar__image" loading="lazy" width="150" height="150">
                   </div>
                   <figcaption>
                      <p><strong>@astrotmc</strong></p>
                      <p>
                         <svg aria-hidden="true" class="icon icon--smaller account__provider-icon">
                            <use xlink:href="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/symbols.c59524f3.svg#business"></use>
                         </svg>
                         <span>Business connection FB</span>
                      </p>
                      <p class="instagram-account__token-status">
                         Access token:
                         <span class="badge badge--success ">
                            <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Active">
                            Active
                         </span>
                      </p>
                      <div style="border: .2rem solid #e61e50; border-radius: .3rem" data-tooltip="" tabindex="0" aria-label="A new Access Token has been created. Your widgets are queued to refresh. This may take up to 30 minutes. Clearing the browser cache after few minutes should speed up the process.">
                         Widgets are refreshing
                         <div class="chatbot__typing chatbot__typing--now">
                            <img src="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/typing.svg" alt="" aria-hidden="true">
                         </div>
                      </div>
                      <table class="table">
                         <thead>
                            <tr>
                               <th>Followers</th>
                               <th>Posts</th>
                               <th>Following</th>
                            </tr>
                         </thead>
                         <tbody>
                            <tr class="no-hover">
                               <td class="instagram-account__number" data-caption="Followers: ">2009</td>
                               <td class="instagram-account__number" data-caption="Posts: ">2</td>
                               <td class="instagram-account__number" data-caption="Following: ">88</td>
                            </tr>
                         </tbody>
                      </table>
                      <p>&nbsp;</p>
                      <p>
                         <a href="https://lightwidget.com/?username=astrotmc" class="button button--secondary">Create widget</a>
                      </p>
                   </figcaption>
                   <div class="dropdown-menu instagram-account__dropdown-menu" data-popover="" data-popover-enabled="">
                      <button aria-label="Show more options" aria-expanded="false" data-popover-button="" data-popover-message-show="Show more options" data-popover-message-hide="Hide more options" class="button button--transparent button--round">
                         <svg aria-hidden="true" class="icon icon--dark-gray">
                            <use xlink:href="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/symbols.c59524f3.svg#more"></use>
                         </svg>
                      </button>
                      <div class="dropdown-menu__content container container--floating" data-popover-content="">
                         <button class="button button--small instagram-account__remove remove-account button--transparent" aria-label="Remove account" data-account-id="17841403133142687">
                            <svg aria-hidden="true" class="icon icon--light-black icon--smaller button__icon">
                               <use xlink:href="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/symbols.c59524f3.svg#close"></use>
                            </svg>
                            Remove account
                         </button>
                      </div>
                   </div>
                </figure>
             </li>
          </ul>
       </section>
       <section class="section">
          <h1 class="section__header">Which connection method to use?</h1>
          <p>
             If you have a personal Instagram account, please use the <strong>Consumer connection</strong> method.
          </p>
          <p>If you have a creator or a business Instagram account, select one of the Business connection methods.</p>
          <ul>
             <li><strong>Business connection with Instagram Login</strong>: This option redirects through Instagram's
                website and does
                not require a Facebook Page.
             </li>
             <li><strong>Business connection with Facebook Login</strong>: This option redirects through Facebook's
                website and
                requires a Facebook Page.
             </li>
          </ul>
          <p>
             Looking ahead, the Business connection with Facebook login method will be the most beneficial for
             widget owners, offering a range of features unavailable in the Business connection with Instagram
             login.
          </p>
          <p>
             Below, you can find more details about the differences between these methods.
          </p>
          <table class="table" data-difference-instagram-facebook="">
             <thead>
                <tr>
                   <th>&nbsp;</th>
                   <th>Business connection with Facebook Login</th>
                   <th>Business connection with Instagram Login</th>
                   <th>Consumer connection</th>
                </tr>
             </thead>
             <tbody>
                <tr>
                   <td><strong>Type of Instagram account</strong></td>
                   <td data-caption="Business Facebook: ">Only Professional Instagram accounts (Business or Creator Accounts)</td>
                   <td data-caption="Business Instagram: ">Only Professional Instagram accounts (Business or Creator Accounts)</td>
                   <td data-caption="Consumer: ">All accounts</td>
                </tr>
                <tr>
                   <td><strong>Requirements</strong></td>
                   <td data-caption="Business Facebook: ">Having a valid Professional Instagram account connected to Facebook
                      Page
                   </td>
                   <td data-caption="Business Instagram: ">Having a valid Professional Instagram account</td>
                   <td data-caption="Consumer: ">Having a valid Instagram account</td>
                </tr>
                <tr>
                   <td><strong>Facebook Page</strong></td>
                   <td data-caption="Business Facebook: ">Required</td>
                   <td data-caption="Business Instagram: ">Not required</td>
                   <td data-caption="Consumer: ">Not required</td>
                </tr>
                <tr>
                   <td><strong>Number of followers</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                        <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--danger ">
                         <img class="icon badge__icon" src="<?php echo e(asset('app/icons/not.svg')); ?>" alt="Not Available">
                         Not available
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Number of following users</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--danger ">
                         <img class="icon badge__icon" src="<?php echo e(asset('app/icons/not.svg')); ?>" alt="Not Available">
                         Not available
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Profile picture</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--danger ">
                         <img class="icon badge__icon" src="<?php echo e(asset('app/icons/not.svg')); ?>" alt="Not Available">
                         Not available
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Number of likes per post</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--danger ">
                         <img class="icon badge__icon" src="<?php echo e(asset('app/icons/not.svg')); ?>" alt="Not Available">
                         Not available
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Number of comments per post</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Available
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--danger ">
                         <img class="icon badge__icon" src="<?php echo e(asset('app/icons/not.svg')); ?>" alt="Not Available">
                         Not available
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Instagram API</strong></td>
                   <td data-caption="Business Facebook: ">Instagram Graph API</td>
                   <td data-caption="Business Instagram: ">Instagram Graph API</td>
                   <td data-caption="Consumer: ">Basic Display API</td>
                </tr>
                <tr>
                   <td><strong>Photo posts in widgets</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Video posts in widgets</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Carousel posts in widgets</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                </tr>
                <tr>
                   <td><strong>Reel posts in widgets</strong></td>
                   <td data-caption="Business Facebook: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Business Instagram: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                   <td data-caption="Consumer: ">
                      <span class="badge badge--success ">
                          <img class="icon badge__icon" src="<?php echo e(asset('app/icons/success.svg')); ?>" alt="Available">
                         Supported
                      </span>
                   </td>
                </tr>
             </tbody>
          </table>
       </section>
    </div>
    <div class="modal " role="dialog" aria-labelledby="modal-title-remove-account" aria-describedby="modal-description-remove-account" data-modal-id="remove-account">
       <div class="modal__dialog ">
          <header class="modal__header">
             <h2 class="modal__title" id="modal-title-remove-account">Delete this account?</h2>
             <button class="button button--modal button--transparent modal__close" aria-label="Close modal">
                <svg aria-hidden="true" class="icon icon--white icon--smaller">
                   <use xlink:href="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/symbols.c59524f3.svg#close"></use>
                </svg>
             </button>
          </header>
          <div class="modal__contents">
             <p id="modal-description-remove-account">Are you sure you want to permanently delete this account? This action cannot be undone.</p>
             <footer class="button-group button-group--align-right modal__buttons">
                <button class="button button--secondary modal__close">Cancel</button>
                <button class="button button--warning-reverted modal__action-button">
                Delete                    </button>
             </footer>
          </div>
          <div class="modal__error">
             <p class="modal__error-message"></p>
             <footer class="button-group button-group--align-right modal__buttons">
                <button class="button button--secondary modal__close">OK</button>
             </footer>
          </div>
       </div>
    </div>
    <div class="modal " role="dialog" aria-labelledby="modal-title-disconnect-account" aria-describedby="modal-description-disconnect-account" data-modal-id="disconnect-account">
       <div class="modal__dialog ">
          <header class="modal__header">
             <h2 class="modal__title" id="modal-title-disconnect-account">Disconnect this account?</h2>
             <button class="button button--modal button--transparent modal__close" aria-label="Close modal">
                <svg aria-hidden="true" class="icon icon--white icon--smaller">
                   <use xlink:href="https://lightwidget.com/wp-content/themes/lightwidget/dist/svg/symbols.c59524f3.svg#close"></use>
                </svg>
             </button>
          </header>
          <div class="modal__contents">
             <p id="modal-description-disconnect-account">Are you sure you want to disconnect this Instagram account from your developer account? Ownership of all
                widgets created for this account will be transferred to the Instagram account you want to
                disconnect.
             </p>
             <footer class="button-group button-group--align-right modal__buttons">
                <button class="button button--secondary modal__close">Cancel</button>
                <button class="button button--warning-reverted modal__action-button">
                Disconnect                    </button>
             </footer>
          </div>
          <div class="modal__error">
             <p class="modal__error-message"></p>
             <footer class="button-group button-group--align-right modal__buttons">
                <button class="button button--secondary modal__close">OK</button>
             </footer>
          </div>
       </div>
    </div>
 </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("app.app", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/resources/views/app/instagram_accounts.blade.php ENDPATH**/ ?>