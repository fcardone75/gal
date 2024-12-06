
document.addEventListener('DOMContentLoaded', () => {
    $('#download-pdf').click(function(event) {
        event.preventDefault();
        fetch($(this).attr('href')).then(resp => resp.arrayBuffer()).then(resp => {
            const filename = $('#download-pdf').data('filename');
            // set the blog type to final pdf
            const file = new Blob([resp], {type: 'application/pdf'});

            // process to auto download it
            const fileURL = URL.createObjectURL(file);
            const link = document.createElement('a');
            // Create link to download pdf
            link.href = fileURL;
            link.download = filename;
            link.click();
            // Create link to reload page after download pdf
            const linkRedirect = document.createElement('a');
            linkRedirect.href = window.location.href;
            linkRedirect.click();
        });
        return false
    } );

    const btnSubmit = $('#financing_provisioning_certification_pdf_submit');
    btnSubmit.on('click', function (e) {
        const financingProvCertPdfFileInput = $('#financing_provisioning_certification_pdf_filenameFile_file');
        if (!$(this).attr('data-confirm') && financingProvCertPdfFileInput.get(0).files.length > 0) {
            e.preventDefault();
//            $('#modal-financing-provisioning-certification-pdf-upload-confirm').modal({backdrop: true, keyboard: true});
            const modal = new bootstrap.Modal($('#modal-financing-provisioning-certification-pdf-upload-confirm'));
            modal.show();
        }
    });

    $('#modal-financing-provisioning-certification-pdf-upload-confirm-button').on('click', function(){
        btnSubmit.attr('data-confirm', true);
        btnSubmit.trigger('click')
    });


    let inputCheckboxCON = $("input[value=CON]");
    let inputCheckboxCFP = $("input[value=CFP]");

    if(inputCheckboxCFP.is(":checked")) {
        inputCheckboxCON.prop("checked", true)
    }

    inputCheckboxCON.change(function() {
        if(!this.checked) {
            inputCheckboxCFP.prop("checked", false)
        }
    });

    inputCheckboxCFP.change(function() {
        if(this.checked) {
            inputCheckboxCON.prop("checked", true)
        }
    });


    let first = $('#financing_provisioning_certification_firstDepreciationDeadline');
    let last = $('#financing_provisioning_certification_lastDepreciationDeadline');
    first.change(()=>{setMinDate()});
    last.change(()=>{setMaxDate()});
});

function setMinDate(){
    let first = $('#financing_provisioning_certification_firstDepreciationDeadline');
    let last = $('#financing_provisioning_certification_lastDepreciationDeadline');
    let min = first.val();
    last.attr({
        "min": min
    })
}
function setMaxDate(){
    let first = $('#financing_provisioning_certification_firstDepreciationDeadline');
    let last = $('#financing_provisioning_certification_lastDepreciationDeadline');
    let max = last.val();
    first.attr({
        "max": max
    })
}


document.addEventListener('DOMContentLoaded', () => {
    let form = $("#form-addition-contributions");
    let formBtn = $("#form-addition-contributions button[type=submit]");
    let yesBtn = $("#addition-contributions-confirm .btn-primary");
    let modal = $('#addition-contributions-confirm');

    form.one('submit', function(e) {
        e.preventDefault();
    });

    modal.on('hidden.bs.modal', function (e) {
        form.one('submit', function(e) {
            e.preventDefault();
        });
    });

    yesBtn.click(function() {
        formBtn.click();
    });
});
