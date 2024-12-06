document.addEventListener('DOMContentLoaded', () => {
    $('#form_csvFile').change(() => {
        $('#form_submit').attr('disabled', 'disabled');
        $('#validation-report').html(null);
    });

    $('#btn-validate').click((e) => {
        e.preventDefault();
        const formData = new FormData($('#csv-import-users')[0]);
        const files = $('#form_csvFile')[0].files;
        if (files && files.length) {
            formData.append('csvFile', files.item(0));
            $.ajax({
                url: $(this).attr('data-path'),
                data: formData,
                method: 'POST',
                processData: false,
                contentType: false,
                success: (res) => {
                    $('#form_submit').attr('disabled', 'disabled');
                    $('#validation-report').html(null);
                    if (res.errors && res.errors.length) {
                        const errors = res.errors;
                        for (let i = 0; i < res.errors.length; i++) {
                            const el = $('<div></div>');
                            const errorsList = $('<ul></ul>');
                            el.addClass('alert').addClass('alert-danger');
                            el.append($('<strong>').html(['Riga', errors[i].line].join(' ')));

                            if (errors[i].errors) {
                                const lineErrors = errors[i].errors;
                                for (let j = 0; j < lineErrors.length; j++) {
                                    const errorListItem = $('<li></li>');
                                    lineErrors[j]['field'] && errorListItem.append($('<strong>').html(lineErrors[j]['field']));
                                    lineErrors[j]['message'] && errorListItem.append(' ' + lineErrors[j]['message']);
                                    lineErrors[j]['invalid_value'] && errorListItem.append(' <pre>' + lineErrors[j]['invalid_value'] + '</pre>');
                                    errorsList.append(errorListItem);
                                }
                            }

                            el.append(errorsList);

                            $('#validation-report').append(el);
                        }
                    } else {
                        $('#form_submit').removeAttr('disabled');
                        const result = $('<div>');
                        result.addClass('alert').addClass('alert-success').html('CSV corretto. È possibile procedere con il caricamento');
                        $('#validation-report').append(result);
                    }
                }
            });
        } else {
            $('#validation-report').html(null);
            const result = $('<div>');
            result.addClass('alert').addClass('alert-warning').html('Non è stato caricato nessun file');
            $('#validation-report').append(result);
        }
    });

});
