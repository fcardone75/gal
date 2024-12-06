function handleApplicationAttachmentVichChange(event) {
    const inputTarget = event.target;
    const idFile = inputTarget.dataset.fileUploadId;
    const newFile = inputTarget.files[0];
    const fileSizeInMegabytes = newFile.size > 1024 * 1024;
    const fileSize = fileSizeInMegabytes ? newFile.size / (1024 * 1024) : newFile.size / 1024;
    const spanLabel = $(inputTarget).parent().parent().find('.file_uploaded_label');
    spanLabel.html(newFile.name + ' (' + fileSize.toFixed(2) + ' ' + (fileSizeInMegabytes ? 'MB' : 'KB') + ')');
}
function handleApplicationAttachmentVich(event) {
    var elements = document.getElementsByClassName('vich_upload_file_onchange');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('change', handleApplicationAttachmentVichChange);
    }
}

document.addEventListener('DOMContentLoaded',  handleApplicationAttachmentVich);
document.addEventListener('ea.collection.item-added', handleApplicationAttachmentVich);
