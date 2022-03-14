/**
 * 2007-2015 PrestaShop
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
 *  @author    euPago, Instituição de Pagamento Lda <suporte@eupago.pt>
 *  @copyright 2016 euPago, Instituição de Pagamento Lda
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

$(function () {
    var payment_deadline = $('#EUPAGO_MULTIBANCO_TIPO_DL_on').parent();
    var payment_deadline_on = $('#EUPAGO_MULTIBANCO_TIPO_DL_on');
    var ndias = $('#EUPAGO_MULTIBANCO_DL_DIAS');
    var ndiasFormGroup = ndias.parent().parent().parent();
    var showDeadline = $('#EUPAGO_MULTIBANCO_TIPO_DL_on').parent();
    var showDeadline_on = $('#EUPAGO_MULTIBANCO_SHOW_DL_on').parent().parent().parent();
    console.log(showDeadline.html());
    show();

    if (ndias.val() == "") {
        ndias.val("3");
    }

    payment_deadline.on("click", function () {
        show();
    });

    function show() {
        if (payment_deadline_on.is(':checked')) {
            ndiasFormGroup.fadeIn();
            showDeadline_on.fadeIn();
        } else {
            ndiasFormGroup.hide();
            showDeadline_on.hide();
        }
    }


});