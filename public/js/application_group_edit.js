document.addEventListener('DOMContentLoaded', () => {
    const body = $('body');
    body.on('click', 'button[data-action="ag-add"]', function(e){
        e.preventDefault();

        $('#ApplicationGroup_applications option[value="' + $(this).data('application-id') + '"]').attr('selected', 'selected');

        $(this)
            .html($(this).attr('data-button-label-remove'))
            .attr('data-action', 'ag-remove')
            .data('action', 'ag-remove')
            .closest('tr').appendTo($('#linked-applications tbody'));
    });

    body.on('click', 'button[data-action="ag-remove"]', function(e){
        e.preventDefault();

        $('#ApplicationGroup_applications option[value="' + $(this).data('application-id') + '"]').removeAttr('selected');

        $(this)
            .html($(this).attr('data-button-label-add'))
            .attr('data-action', 'ag-add')
            .data('action', 'ag-add')
            .closest('tr').appendTo($('#available-applications tbody'));
    });

    body.on('click', 'button[type="submit"]', function (e) {
        const protocolFileInput = $('#ApplicationGroup_filenameFile_file');
        const buttonValue = $(this).attr('value');

        if (!$(this).attr('data-confirm') && protocolFileInput.get(0).files.length > 0) {
            e.preventDefault();
            const modal = new bootstrap.Modal($('#modal-protocol'));
            modal.show();
            $('#modal-protocol-button').attr('data-original-btn-value', buttonValue);
        }
    });

    body.on('click', '#modal-protocol-button', function(){
        const originalBtnValue = $(this).attr('data-original-btn-value');
        const originalBtn = $('button[value="' + originalBtnValue + '"]');
        originalBtn.attr('data-confirm', true);
        originalBtn.trigger('click')
    });
});
