jQuery(document).ready(function ($) {
  // filed add logic
  $("#fbpe-formula-type")
    .change(function () {
      var selectedFormula = $(this).val();
      if (selectedFormula == "2" || selectedFormula == "3") {
        $("#fbpe-type").closest("tr").hide();
      } else {
        $("#fbpe-type").closest("tr").show();
      }
    })
    .trigger("change");

  // ajax class

  $("#fbpe-update-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var price = $('input[name="fbpe_price_settings[price]"]').val();
    var formula = $('select[name="fbpe_price_settings[formula]"]').val();
    var type = $('select[name="fbpe_price_settings[type]"]').val();

    if (!price) {
      alert("Price field is required.");
      return;
    }

    if (!formula) {
      alert("Formula field is required.");
      return;
    }

    if ((formula === "0" || formula === "1") && !type) {
      alert("Type field is required for Increment or Decrement formula.");
      return;
    }

    var data = {
      action: "fbpe_update_prices",
      nonce: fbpeAjax.nonce,
      price: price,
      formula: formula,
      offset: 0,
    };

    if (formula === "0" || formula === "1") {
      data.type = type;
    }

    var $progressBar = $("#fbpe-progress-bar");
    var $progressText = $("#fbpe-progress-text");
    var $updatedProductsList = $("#fbpe-updated-products");

    function processBatch(offset) {
      data.offset = offset;

      $.post(fbpeAjax.ajax_url, data, function (response) {
        if (response.success) {
          var progress = Math.round(response.data.progress);
          $progressBar.css("width", progress + "%");
          $progressBar.text(progress + "% completed");
          $progressText.text(progress + "% completed");

          if (response.data.updated_products.length > 0) {
            $updatedProductsList.css("padding", "10px");
            response.data.updated_products.forEach(function (product) {
              $updatedProductsList.append("<li>" + product + "</li>");
            });
          }

          if (response.data.next_offset !== undefined) {
            processBatch(response.data.next_offset);
          } else {
            $progressBar.css("width", "100%");
            $progressText.text("Update complete!");
          }
        } else {
          alert("Error: " + response.data.message);
        }
      });
    }

    $progressBar.css("width", "0%");
    $progressText.text("0% completed");
    $updatedProductsList.empty();

    processBatch(data.offset);
  });
});
