$(document).ready(function () {
  if ($('#doPartialRefundEcommpay').length == 0) {
    const newCheckBox =
      `<div class="cancel-product-element form-group" style="display: block;">
                    <div class="checkbox">
                        <div class="md-checkbox md-checkbox-inline">
                            <label>
                                <input type="checkbox" id="doPartialRefundEcommpay" name="doPartialRefundEcommpay" value="1" checked="checked">
                                <i class="md-checkbox-control"></i>` +
      chb_ecommpay_refund +
      `</label>
                        </div>
                    </div>
                </div>`
    $('.refund-checkboxes-container').prepend(newCheckBox)
  }

  setTimeout(() => {
    addEcommpayPaymentIdColumn()
  }, 400)
})

function addEcommpayPaymentIdColumn() {
  let paymentData = window.ECOMMPAY_TRANSACTION_DATA;

  const $allTables = $('table.table')
  let $paymentTable = null

  $allTables.each(function () {
    const $table = $(this)
    const $headers = $table.find('th')
    const hasPaymentClasses = $headers
      .toArray()
      .some(
        (th) =>
          th.className.includes('table-head-payment') ||
          th.className.includes('table-head-transaction') ||
          th.className.includes('table-head-amount')
      )

    const hasOrderPaymentInputs = $table.find('input[name*="order_payment"], select[name*="order_payment"]').length > 0

    const $buttons = $table.find('button, input[type="button"], input[type="submit"]')
    const hasAddButton = $buttons
      .toArray()
      .some((btn) => btn.className.includes('btn-primary') && btn.type === 'submit')

    if (hasPaymentClasses || (hasOrderPaymentInputs && hasAddButton)) {
      $paymentTable = $table
      return false // break the loop
    }
  })

  if (!$paymentTable || !$paymentTable.length) {
    return
  }

  if (paymentData && paymentData.length > 0) {
    addEcommpayPaymentIdColumnToTable($paymentTable, paymentData)
  }
}

function addEcommpayPaymentIdColumnToTable($paymentTable, paymentData) {
  const $existingHeader = $paymentTable.find('th[data-ecommpay-column="true"]')
  if ($existingHeader.length) {
    return
  }

  const $headerRow = $paymentTable.find('thead tr')
  if (!$headerRow.length) {
    console.error('Header row not found')
    return
  }

  const $headers = $headerRow.find('th')
  let transactionHeaderIndex = -1
  $headers.each(function (index) {
    if (this.className.includes('table-head-transaction')) {
      transactionHeaderIndex = index
      return false // break the loop
    }
  })

  if (transactionHeaderIndex === -1) {
    console.error('Transaction ID header not found')
    return
  }

  const $newHeader = $('<th>')
    .html('<span class="title_box">Ecommpay Payment ID</span>')
    .attr('data-ecommpay-column', 'true')

  if (transactionHeaderIndex + 1 < $headers.length) {
    $headers.eq(transactionHeaderIndex + 1).before($newHeader)
  } else {
    $headerRow.append($newHeader)
  }

  const $dataRows = $paymentTable.find('tbody tr')

  $dataRows.each(function () {
    const $row = $(this)
    const $cells = $row.find('td')

    if ($cells.length < 3) {
      return
    }

    const $existingEcpCell = $row.find('td[data-ecommpay-column="true"]')
    if ($existingEcpCell.length) {
      return
    }

    const $newCell = $('<td>').attr('data-ecommpay-column', 'true')
    const matchingPayment = window.ECOMMPAY_TRANSACTION_DATA.find((payment) => payment.transaction_id === $cells.eq(2).text())

    if (matchingPayment && matchingPayment.ecp_payment_id) {
      $newCell
        .html(`<span style="font-family: monospace; font-size: 0.9em; color: #666;">${matchingPayment.ecp_payment_id}</span>`)
        .attr('title', `Ecommpay Payment ID: ${matchingPayment.ecp_payment_id}`)
    } else {
      $newCell.text('-')
    }

    if (transactionHeaderIndex + 1 < $cells.length) {
      $cells.eq(transactionHeaderIndex + 1).before($newCell)
    } else {
      $row.append($newCell)
    }
  })
}
