jQuery(document).ready(function($) {
    if (typeof kargoData === 'undefined') {
        return;
    }

    function replaceInputWithSelect(type) {
        var cityInput = $('#' + type + '_city');
        if (cityInput.length && cityInput.is('input[type="text"]')) {
            var parentDiv = cityInput.closest('.woocommerce-input-wrapper, .form-row');
            var select = $('<select></select>')
                .attr('id', type + '_city')
                .attr('name', type + '_city')
                .attr('autocomplete', cityInput.attr('autocomplete') || '')
                .addClass(cityInput.attr('class') || '')
                .addClass('kargo-district-select');
            
            cityInput.replaceWith(select);
            return select;
        } else if (cityInput.length && cityInput.is('select')) {
            if (!cityInput.hasClass('kargo-district-select')) {
                cityInput.addClass('kargo-district-select');
            }
            return cityInput;
        }
        return null;
    }

    function updateDistricts(type) {
        var citySelect = $('#' + type + '_state');
        var districtSelect = replaceInputWithSelect(type);
        
        if (!districtSelect) return;

        var cityId = citySelect.val();
        var currentDistrict = districtSelect.val();
        
        districtSelect.empty();
        
        if (!cityId || cityId === '') {
            districtSelect.append($('<option></option>').attr('value', '').text(kargoData.choose_city_first));
            districtSelect.prop('disabled', true); 
        } else {
            districtSelect.prop('disabled', false);
            districtSelect.append($('<option></option>').attr('value', '').text(kargoData.select_district_text));
            
            if (kargoData.districts[cityId]) {
                $.each(kargoData.districts[cityId], function(id, name) {
                    var option = $('<option></option>').attr('value', name).text(name);
                    if (currentDistrict === name) {
                        option.prop('selected', true);
                    }
                    districtSelect.append(option);
                });
            }
        }
        districtSelect.trigger('change');
    }

    function handleCountryChange(type) {
        var country = $('#' + type + '_country').val();
        if (country === 'TR') {
            updateDistricts(type);
        }
    }

    // Bind events
    $(document.body).on('change', '#billing_state, #shipping_state', function() {
        var type = $(this).attr('id').includes('billing') ? 'billing' : 'shipping';
        if ($('#' + type + '_country').val() === 'TR') {
            updateDistricts(type);
        }
    });

    $(document.body).on('change', '#billing_country, #shipping_country', function() {
        var type = $(this).attr('id').includes('billing') ? 'billing' : 'shipping';
        setTimeout(function() {
            handleCountryChange(type);
        }, 100);
    });

    // Initial run on load/update
    function initFields() {
        if ($('#billing_country').val() === 'TR') {
            handleCountryChange('billing');
        }
        if ($('#shipping_country').val() === 'TR') {
            handleCountryChange('shipping');
        }
        if ($.fn.select2) {
            $('.kargo-district-select').select2();
        }
    }

    $(document.body).on('updated_checkout', function() {
        setTimeout(initFields, 100);
    });
    
    initFields();
});
