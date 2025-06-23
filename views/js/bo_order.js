document.addEventListener("DOMContentLoaded", function(){
    if ($('#doPartialRefundEcommpay').length == 0) {
        var newCheckBox = `<div class="cancel-product-element form-group" style="display: block;">
                <div class="checkbox">
                    <div class="md-checkbox md-checkbox-inline">
                        <label>
                            <input type="checkbox" id="doPartialRefundEcommpay" name="doPartialRefundEcommpay" value="1" checked="checked">
                            <i class="md-checkbox-control"></i>` + 
                            chb_ecommpay_refund + 
                            `</label>
                    </div>
                </div>
            </div>`;
        $('.refund-checkboxes-container').prepend(newCheckBox);
    }
});