{**
 * m4p_customermanager
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

{if isset($m4p_manager) && $m4p_manager}
    <article class="m4p-manager-box card">
        <div class="card-body">
            <h4 class="m4p-manager-box__title">{l s='Your account manager' d='Modules.M4pcustomermanager.Shop'}</h4>
            <div class="m4p-manager-box__name">
                {$m4p_manager.firstname|escape:'html':'UTF-8'} {$m4p_manager.lastname|escape:'html':'UTF-8'}
            </div>
            <ul class="m4p-manager-box__contact">
                <li>
                    <i class="material-icons">email</i>
                    <a href="mailto:{$m4p_manager.email|escape:'html':'UTF-8'}">{$m4p_manager.email|escape:'html':'UTF-8'}</a>
                </li>
                {if $m4p_manager.phone}
                    <li>
                        <i class="material-icons">phone</i>
                        <a href="tel:{$m4p_manager.phone|escape:'html':'UTF-8'}">{$m4p_manager.phone|escape:'html':'UTF-8'}</a>
                    </li>
                {/if}
            </ul>

            {if $m4p_note}
                <p class="m4p-manager-box__note">{$m4p_note|escape:'html':'UTF-8'|nl2br nofilter}</p>
            {/if}

            <div class="m4p-manager-box__thread">
                <h5>{l s='Messages' d='Modules.M4pcustomermanager.Shop'}</h5>

                {if $m4p_messages}
                    <ul class="m4p-thread">
                        {foreach from=$m4p_messages item=m}
                            <li class="m4p-thread__item{if $m.from_customer} m4p-thread__item--mine{/if}">
                                <span class="m4p-thread__meta">
                                    {if $m.from_customer}
                                        {l s='You' d='Modules.M4pcustomermanager.Shop'}
                                    {else}
                                        {$m.author|escape:'html':'UTF-8'}
                                    {/if}
                                    · {$m.date_add|escape:'html':'UTF-8'}
                                </span>
                                <span class="m4p-thread__text">{$m.message|escape:'html':'UTF-8'|nl2br nofilter}</span>
                            </li>
                        {/foreach}
                    </ul>
                {else}
                    <p class="m4p-thread__empty">{l s='No messages yet.' d='Modules.M4pcustomermanager.Shop'}</p>
                {/if}

                <form method="post" action="{$m4p_message_action|escape:'html':'UTF-8'}" class="m4p-thread__form">
                    <input type="hidden" name="token" value="{$m4p_token|escape:'html':'UTF-8'}">
                    <textarea name="m4p_message" rows="3" class="form-control"
                              placeholder="{l s='Write to your account manager' d='Modules.M4pcustomermanager.Shop'}"></textarea>
                    <button type="submit" name="submitM4pCustomerMessage" class="btn btn-primary">
                        {l s='Send' d='Modules.M4pcustomermanager.Shop'}
                    </button>
                </form>
            </div>
        </div>
    </article>
{/if}
