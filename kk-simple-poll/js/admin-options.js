jQuery(document).ready(function($) {
    var $container = $('#kk-sp-options-container');

    // Add Option
    $('#kk-sp-add-option').on('click', function() {
        var newRow = $('<div class="kk-sp-option-row" style="margin-bottom: 10px;">' +
            '<input type="text" name="kk_sp_options[]" value="" class="regular-text" placeholder="Enter option..." /> ' +
            '<button type="button" class="button kk-sp-remove-option">Remove</button>' +
            '</div>');
        $container.append(newRow);
    });

    // Remove Option
    $container.on('click', '.kk-sp-remove-option', function() {
        var $rows = $container.find('.kk-sp-option-row');
        if ($rows.length <= 2) {
            alert('A poll must have at least two options.');
            return;
        }
        $(this).closest('.kk-sp-option-row').remove();
    });
});
