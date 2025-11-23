jQuery(document).ready(function($) {
    if (typeof kargoData === 'undefined') {
        return;
    }

    function updateDistricts(type) {
        var citySelect = $('#' + type + '_state');
        var districtSelect = $('#' + type + '_city');
        
        // If not a select, maybe WC didn't render it as select?
        // But we changed type in PHP.
        
        // Initial load or change
        var cityId = citySelect.val();
        
        districtSelect.empty();
        
        if (!cityId || cityId === '') {
            districtSelect.append($('<option></option>').attr('value', '').text(kargoData.choose_city_first));
            districtSelect.prop('disabled', true);
        } else {
            districtSelect.prop('disabled', false);
            districtSelect.append($('<option></option>').attr('value', '').text(kargoData.select_district_text));
            
            if (kargoData.districts[cityId]) {
                $.each(kargoData.districts[cityId], function(id, name) {
                    districtSelect.append($('<option></option>').attr('value', name).text(name));
                });
            }
        }
        
        // Trigger change to update Select2 if used
        districtSelect.trigger('change');
    }

    // Bind events
    $('#billing_state').on('change', function() {
        updateDistricts('billing');
    });

    $('#shipping_state').on('change', function() {
        updateDistricts('shipping');
    });

    // Initial run
    // Wait for WC to update fields? WC updates state fields on country change.
    // We need to hook after WC updates.
    
    $(document.body).on('country_to_state_changed', function() {
        // WC just updated the state field.
        // We need to re-apply our logic if the country is TR.
        var billingCountry = $('#billing_country').val();
        var shippingCountry = $('#shipping_country').val();
        
        if (billingCountry === 'TR') {
            updateDistricts('billing');
        }
        if (shippingCountry === 'TR') {
            updateDistricts('shipping');
        }
    });
    
    // Also run on load if TR is selected
    if ($('#billing_country').val() === 'TR') {
        // We might need to wait for WC to populate state options if it does AJAX?
        // But we filtered the states in PHP, so they should be there on page load.
        updateDistricts('billing');
    }
    if ($('#shipping_country').val() === 'TR') {
        updateDistricts('shipping');
    }
    
    // Select2 integration
    if ($.fn.select2) {
        $('.kargo-district-select').select2();
    }
});
