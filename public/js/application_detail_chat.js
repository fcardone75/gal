import ApplicationChat from "./ApplicationChat.js";
document.addEventListener('DOMContentLoaded', () => {
    new ApplicationChat(
        $('#text').attr('data-app_admin_applicationcrud_postmessages'),
        $('#text').attr('data-app_admin_applicationcrud_patchmessages'),
        $('#text').attr('data-applicationMessageForm_vars_value'),
    );
});

