<div>
    <div class="form__item">
        <div class="selection">
            @foreach ($businessAccounts as $index => $account)
                <input type="radio" name="instagramBusinessAccount"
                       id="instagramBusinessAccount[{{ $account['instagram']['instagram_business_account_id'] }}]"
                       value="{{ $account['instagram']['instagram_business_account_id'] }}"
                       wire:model="selectedAccount"
                       class="form__input sr-only">

                <label class="form__label selection__item"
                       for="instagramBusinessAccount[{{ $account['instagram']['instagram_business_account_id'] }}]">
                    <img src="{{ $account['profile_picture_url'] }}" alt="{{ $account['page_name'] }}"
                         class="selection__image">
                    <strong>Facebook Page:</strong>
                    <p style="margin-bottom: 1rem;">{{ $account['page_name'] }}</p>
                    <strong>Professional Instagram account:</strong>
                    <a href="https://www.instagram.com/{{ $account['instagram']['username'] }}" class="link"
                       target="_blank">{{ '@' . $account['instagram']['username'] }}</a>
                </label>
            @endforeach
        </div>
    </div>
    <div class="form__item button-group">
        <button wire:click="fetchInstagramData" type="submit" class="button button--primary">Connect selected Instagram account</button>
    </div>
</div>
