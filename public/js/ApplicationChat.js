import BaseAjaxCallApi from "./BaseAjaxCallApi.js";

export default class ApplicationChat extends BaseAjaxCallApi {
    constructor(apiPostMessage, apiPatchMessage, applicationMessageId = null) {
        super();
        let that = this;
        this.btnSend = $('#btn-send-message');
        this.btnSave = $('#btn-save-message');
        this.listMessagesContainer = $('#list-messages-container');
        this.formApplicationMessage = $('#form-application-message');
        this.inputTextMessage = $('#text');
        this.inputFile = $('#attachments');
        this.wrapperDraftNewAttachments = $('#wrapper-draft-new-attachments');
        this.wrapperDraftOldAttachments = $('#wrapper-draft-old-attachments');
        this.oldDraftAttachmentsDataRole = '[data-role="old-draft-attachment"]';
        this.btnsDeleteMessageAttachmentsDataRole = '[data-role="btn-delete-message-attachment"]';
        this.apiPostMessage = apiPostMessage;
        this.apiPatchMessage = apiPatchMessage;
        this.applicationMessageId = applicationMessageId;
        this.currentApiUrl = '';
        this.currentMethod = '';
        this.apiDeleteAttachmentList = [];
        this.singleTemplateMessage = function(entity) {
            return `<div class="direct-chat-msg right">
                        <div class="direct-chat-info clearfix">
                            <span class="direct-chat-name pull-right">${entity.created_by.firstname}</span>
                            <span class="direct-chat-timestamp pull-right">${ApplicationChat.getDateTimeFormatted(new Date(entity.updated_at.date))}</span>
                        </div>
                        <img class="direct-chat-img" src="/images/chat_avatar_placeholder.png"
                             alt="message user image">
                        <div class="direct-chat-text">
                            ${(entity.text) ? entity.text : ''}
                            ${entity.attachments.length > 0 ? entity.attachments.map((item) => {
                                  return '<a target="_blank" href='+item.file_web_path+'>'+item.original_filename+'</a>';
                            }).join('<br/>') : ''}
                        </div>
                    </div>`
            ;
        };
        this.singleTemplateDraftAttachment = function(file) {
            return `<a href="#">
                        ${file.name}
                    </a>
                    <br/>`
            ;
        };
        this.singleTemplateDraftAttachmentMessageSaved = function(attachment) {
            return `<div class="single-item-attachment-wrapper">
                        <a class="btn btn-danger btn-delete-attachment"
                           data-api-delete="${attachment.api_delete}"
                           data-role="btn-delete-message-attachment"
                        ><i class="fa fa-remove"></i>
                        </a>
                        <a target="_blank"
                           href="${attachment.file_web_path}"
                           data-role="old-draft-attachment"
                           data-old-attachment-id="{{ att.id }}"
                        >
                            ${attachment.original_filename}
                        </a>
                        <br/>
                        <br/>
                    </div>`
                ;
        };
        this.beforeSendFunction = function() {
//TODO check field not empty [GDA]
            that.inputTextMessage.addClass('loading');
            that.btnSend.prop('disabled', true);
            that.btnSave.prop('disabled', true);
        };
        this.successSendFunction = function(data) {
            // that.listMessagesContainer.append(that.singleTemplateMessage(data));
            that.clearTextField();
            that.listMessagesContainer.scrollTop(that.listMessagesContainer.prop('scrollHeight'));
            that.inputTextMessage.removeClass('loading');
            that.btnSend.prop('disabled', false);
            that.btnSave.prop('disabled', false);
            that.wrapperDraftNewAttachments.html('');
            that.wrapperDraftOldAttachments.html('');
            $(that.oldDraftAttachmentsDataRole).remove();
            that.applicationMessageId = null;
            that.inputFile[0].value = "";
            that.apiDeleteAttachmentList = [];
            window.location.href = "#message_" + data.id;
            location.reload();
        };
        this.errorSendFunction = function() {
            alert('Errore! Non è stato possibile inviare il messaggio!');
            that.inputTextMessage.removeClass('loading');
            that.btnSend.prop('disabled', false);
            that.btnSave.prop('disabled', false);
        };
        this.successSaveFunction = function(data) {
            that.inputTextMessage.removeClass('loading');
            that.btnSend.prop('disabled', false);
            that.btnSave.prop('disabled', false);
            that.applicationMessageId = data.id;
            that.wrapperDraftNewAttachments.html('');
            that.wrapperDraftOldAttachments.html('');
            data.attachments.forEach(function (attachment) {
                that.wrapperDraftOldAttachments.append(that.singleTemplateDraftAttachmentMessageSaved(attachment));
            });
            that.inputFile[0].value = "";
            that.apiDeleteAttachmentList = [];
        };

        this.initEvents();
    }

    initEvents() {
        let that = this;

        that.btnSave.click(function (e) {
            e.preventDefault();
            let data = {
                text: that.inputTextMessage.val(),
                published: 0,
            };
            that.manageApiUrlAndMethod();
            that.callApisDeleteAndSave(data, that.successSaveFunction);
        });

        that.btnSend.click(function (e) {
            e.preventDefault();
            let data = {
                text: that.inputTextMessage.val(),
                published: 1,
            };
            that.manageApiUrlAndMethod();
            that.callApisDeleteAndSave(data, that.successSendFunction);
        });

        that.inputFile.change(function () {
            let files = that.inputFile[0].files;
            that.wrapperDraftNewAttachments.html('');
            for(let i = 0; i < files.length; i++) {
                that.wrapperDraftNewAttachments.append(that.singleTemplateDraftAttachment(files[i]));
            }
        });

        $(document).on('click', that.btnsDeleteMessageAttachmentsDataRole, function () {
            that.apiDeleteAttachmentList.push($(this).data('api-delete'));
            $(this).parent('.single-item-attachment-wrapper').remove();
        })
    }

    callApisDeleteAndSave(data, successFunction) {
        let that = this;
        this.apiDeleteAttachmentList.forEach(function (apiDelete, index) {
            let ajaxReturn = that.callApi(apiDelete, 'DELETE');
            ajaxReturn.done(function () {
                if(index === that.apiDeleteAttachmentList.length - 1) {
                    that.callApiMessageWithFiles(that.currentApiUrl, data, that.currentMethod, successFunction, that.errorSendFunction, that.beforeSendFunction);
                }
            })
        });
        if(that.apiDeleteAttachmentList.length === 0) {
            that.callApiMessageWithFiles(that.currentApiUrl, data, that.currentMethod, successFunction, that.errorSendFunction, that.beforeSendFunction);
        }
    }

    callApiMessageWithFiles(apiUrl, data, method, successFunction = null, errorFunction = null, beforeSendFunction = null) {
        let formData = new FormData();
        if(method === 'PATCH') {
            formData.append('_method', method);
            method = 'POST';
        }
        formData.append('text', data.text);
        if(data.published) {
            formData.append('published', data.published);
        }
        let lastIndexOldAttachment = 0;
        $(this.oldDraftAttachmentsDataRole).each(function (i) {
            let oldAttachmentDraftId = $(this).data('old-attachment-id');
            formData.append('attachments['+i+'][uploadFile][id]', oldAttachmentDraftId);
            lastIndexOldAttachment++;
        });
        for(let i = 0; i < this.inputFile[0].files.length; i++) {
            formData.append('attachments[' + lastIndexOldAttachment + i +'][uploadFile]', this.inputFile[0].files[i]);
        }
        this.callApi(apiUrl, method, formData, successFunction, errorFunction, beforeSendFunction, false, false, false);
    }

    isNewApplicationMessage() {
        return this.applicationMessageId ? false : true;
    }

    manageApiUrlAndMethod() {
        if(this.isNewApplicationMessage()) {
            this.currentMethod = 'POST';
            this.currentApiUrl = this.apiPostMessage;
        } else {
            this.currentMethod = 'PATCH';
            this.currentApiUrl = this.apiPatchMessage.replace('%7Bid%7D', this.applicationMessageId);
        }
    }

    clearTextField() {
        this.inputTextMessage.val('');
    }

    static getDateTimeFormatted(date) {
        return (date.getDate()) + '/' +
            (date.getMonth() + 1) + '/' +
            (date.getFullYear()) + ' ' + date.getHours() + ':' + (date.getMinutes()<10?'0':'') + date.getMinutes()
            ;
    }
}
