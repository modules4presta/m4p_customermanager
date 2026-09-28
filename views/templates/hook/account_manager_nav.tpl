{**
 * m4p_customermanager
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

{if isset($m4p_manager) && $m4p_manager}
    <div class="m4p-manager-nav">
        <i class="material-icons" aria-hidden="true">support_agent</i>
        <span class="m4p-manager-nav__label">{l s='Account manager:' d='Modules.M4pcustomermanager.Shop'}</span>
        <span class="m4p-manager-nav__name">{$m4p_manager.firstname|escape:'html':'UTF-8'} {$m4p_manager.lastname|escape:'html':'UTF-8'}</span>
        {if $m4p_manager.phone}
            <a class="m4p-manager-nav__link" href="tel:{$m4p_manager.phone|escape:'html':'UTF-8'}">{$m4p_manager.phone|escape:'html':'UTF-8'}</a>
        {/if}
        <a class="m4p-manager-nav__link m4p-manager-nav__mail" href="mailto:{$m4p_manager.email|escape:'html':'UTF-8'}" title="{$m4p_manager.email|escape:'html':'UTF-8'}">
            <i class="material-icons" aria-hidden="true">email</i>
        </a>
    </div>
{/if}
