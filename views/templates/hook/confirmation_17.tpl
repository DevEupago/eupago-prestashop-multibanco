{*
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if (isset($status) == true) && ($status == 'ok')}
    <br/>
    <br/>
    <p class="alert alert-success">{l s='Your order is complete.' mod='eupagomb'}</p>
    <div class="dados_pagamento">
        <table style="width:100%; padding:5px; font-size: 11px; color: #374953; margin:0 auto;">
            <tbody>
            <tr>
                <td style="font-size: 12px; border-top: 0px; border-left: 0px; border-right: 0px; border-bottom: 1px solid #2269af; padding:3px; background-color: #2269af; color: White; height:25px; line-height:25px"
                    colspan="3">
                    <div align="center">{l s='Payment by Multibanco or Homebanking' mod='eupagomb'}</div>
                </td>
            </tr>
            <tr style="background-color:#f1f1f1;">
                <td style="padding-top:8px; width:50%;" rowspan="3">
                    <div align="center"><img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/eupago_mb_p.png"
                                             alt="euPago" style="width:46%;"/></div>
                </td>
                <td style="font-size: 12px; padding:10px 2px 2px 2px; font-weight:bold; text-align:left">{l s='Entity' mod='eupagomb'}</td>
                <td style="font-size: 12px; padding:10px 2px 2px 2px;  text-align:left">{$entidade|escape:'html':'UTF-8'}</td>
            </tr>
            <tr style="background-color:#f1f1f1;">
                <td style="font-size: 12px;  padding:2px; font-weight:bold; text-align:left">{l s='Reference' mod='eupagomb'}</td>
                <td style="font-size: 12px;  padding:2px; text-align:left">{$referencia|escape:'html':'UTF-8'}</td>
            </tr>
            <tr style="background-color:#f1f1f1;">
                <td style="font-size: 12px; padding:2px; padding-bottom:10px; font-weight:bold; text-align:left">{l s='Amount' mod='eupagomb'}</td>
                <td style="font-size: 12px; padding:2px; padding-bottom:10px; text-align:left">{$total|escape:'html':'UTF-8'}</td>
            </tr>
            <tr>
                {if $dataLimite != '' && $dataLimite != '2099-12-31'}
                    <td style="font-size: 12px; color:#dd0000; line-height: 10px; padding:10px; border: 0px; text-align:center;"
                        colspan="3">{l s='Payment deadline' mod='eupagomb'}
                        : </span>{$dataLimite|escape:'html':'UTF-8'}</td>
                {/if}
            </tr>
            <tr>
                <td style="font-size: 12px; padding:4px; border: 0px; text-align:center;"
                    colspan="3">{l s='The ticket provided by atm machine is your payment prove. Please keep it.' mod='eupagomb'}</td>
            </tr>
            </tbody>
        </table>
    </div>
{else}
    <p class="alert alert-danger">{l s='There was an error when generating the ATM reference.' sprintf=$shop_name mod='eupagomb'}</p>
    <p>
        <br/>- {l s='Reference' mod='eupagomb'} <span
                class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
        <br/><br/>{l s='Please, try to order again, or contact us to get the ATM reference' mod='eupagomb'}
        <br/><br/>{l s='If you have questions, comments or concerns, please contact our' mod='eupagomb'} <a
                href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='eupagomb'}</a>
    </p>
{/if}
<hr/>