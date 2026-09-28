{**
 * m4p_customermanager
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="card mt-2">
    <h3 class="card-header">
        <i class="material-icons">forum</i>
        {l s='Messages with the account manager' d='Modules.M4pcustomermanager.Admin'}
    </h3>
    <div class="card-body">
        {if $m4p_messages}
            <ul class="m4p-admin-thread list-unstyled">
                {foreach from=$m4p_messages item=m}
                    <li class="mb-2">
                        <strong>
                            {if $m.from_customer}
                                {l s='Customer' d='Modules.M4pcustomermanager.Admin'}
                            {else}
                                {$m.author|escape:'html':'UTF-8'}
                            {/if}
                        </strong>
                        <small class="text-muted">{$m.date_add|escape:'html':'UTF-8'}</small>
                        <div>{$m.message|escape:'html':'UTF-8'|nl2br nofilter}</div>
                        <form method="post" action="{$m4p_action|escape:'html':'UTF-8'}" class="d-inline">
                            <input type="hidden" name="id_customer" value="{$m4p_id_customer|intval}">
                            <input type="hidden" name="id_message" value="{$m.id_message|intval}">
                            <button type="submit" name="deleteM4pCustomerMessage" class="btn btn-link btn-sm text-danger p-0">
                                {l s='Delete' d='Modules.M4pcustomermanager.Admin'}
                            </button>
                        </form>
                    </li>
                {/foreach}
            </ul>
        {else}
            <p class="text-muted">{l s='No messages yet.' d='Modules.M4pcustomermanager.Admin'}</p>
        {/if}

        <form method="post" action="{$m4p_action|escape:'html':'UTF-8'}">
            <input type="hidden" name="id_customer" value="{$m4p_id_customer|intval}">
            <div class="form-group">
                <textarea name="m4p_message" rows="3" class="form-control"
                          placeholder="{l s='Write to the customer' d='Modules.M4pcustomermanager.Admin'}"></textarea>
            </div>
            <button type="submit" name="submitM4pCustomerMessage" class="btn btn-primary">
                {l s='Send' d='Modules.M4pcustomermanager.Admin'}
            </button>
        </form>
    </div>
</div>
