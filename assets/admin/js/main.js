'use strict';
(function($) {

    $(function() {
        // ready
        setupConditions();
    });

    // Add new button listener click
    $(document).on('click touch', '.conditions-new-button input', function(e) {
        e.preventDefault();

        // Prevent double click on button
        $(this).addClass('disabled');

        const data = {
            action: 'napps-sc-new-condition',
        };

        $.post(ajaxurl, data, (response) => {
            $('.conditions-list').append(response);
            setupConditions();

            // Allow to add new condition
            $(this).removeClass('disabled');
        });
    });

    // Remove condition listner click
    $(document).on('click touch', '.condition .remove-condition', function(e) {
        e.preventDefault();
        $(this).closest('.condition').remove();
    });

    const setupConditions = () => {

        document.querySelectorAll('.condition-settings').forEach((element) => {
            setupCondition(element)
        })

    }

    const setupCondition = (element) => {
        const targetElement = element.querySelector("#napps-sc-condition-target")
        const compareElement = element.querySelector("#napps-sc-condition-compare")
        const dateElement = element.querySelector("#napps-sc-condition-date")
        const discountAmountElement = element.querySelector("#napps-sc-condition-discount-amount")
        const attributeElement = element.querySelector("#napps-sc-condition-attribute")

        if(!targetElement || !compareElement || !discountAmountElement || !attributeElement) {
            return
        }

        const setupElements = () => {
            const targetValue = targetElement.value
        
            if (targetValue === 'has_discount') {
                compareElement.classList.add('disabled')
                discountAmountElement.style.display = 'block'
                discountAmountElement.disabled = true
            } else {
                discountAmountElement.style.display = 'none'
                discountAmountElement.disabled = true
                compareElement.classList.remove('disabled')
            }

            if (targetValue === 'created_at') {

                const compareElementValue = compareElement.value
                if(compareElementValue !== 'in_last') {
                    dateElement.style.display = 'block'
                    discountAmountElement.style.display = 'none' 
                } else {
                    discountAmountElement.style.display = 'block'
                    discountAmountElement.disabled = false
                    dateElement.style.display = 'none'
                }

                compareElement.querySelectorAll("#napps-sc-condition-compare_equal, #napps-sc-condition-compare_not_equal").
                    forEach((select) => {
                        select.style.display = 'none'
                    })
                compareElement.querySelectorAll("#napps-sc-condition-compare_after, #napps-sc-condition-compare_before, #napps-sc-condition-compare_in_last").
                    forEach((select) => {
                        select.style.display = 'block'
                    })

                // If current compare value is not related to the created_at condition set is_after so it does not have a invalid value selected
                console.log(compareElementValue)
                if(["is_after", "is_before", "in_last"].indexOf(compareElementValue) == -1) {
                    compareElement.value = 'is_after'
                }

            } else {
                dateElement.style.display = 'none'
                compareElement.querySelectorAll("#napps-sc-condition-compare_equal, #napps-sc-condition-compare_not_equal").
                    forEach((select) => {
                        select.style.display = 'block'
                    })
                compareElement.querySelectorAll("#napps-sc-condition-compare_after, #napps-sc-condition-compare_before, #napps-sc-condition-compare_in_last").
                    forEach((select) => {
                        select.style.display = 'none'
                    })
                compareElement.value = 'is_equal'
            }

            if (targetValue === 'product_attribute') {
                attributeElement.style.display = 'block'
            } else {
                attributeElement.style.display = 'none'
            }
        }

        compareElement.addEventListener('change', function() {
            setupElements()
        })

        targetElement.addEventListener('change', function() {
           setupElements()
        });

        setupElements()
    }


})(jQuery);