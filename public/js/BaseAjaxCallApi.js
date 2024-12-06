export default class BaseAjaxCallApi {
    callApi(apiUrl,
            method = 'GET',
            data = null,
            successFunction = null,
            errorFunction = null,
            beforeSendFunction = null,
            contentType = 'application/x-www-form-urlencoded; charset=UTF-8',
            processData = true,
            cache = true
    ) {
    return  $.ajax({
            url: apiUrl,
            type: method,
            data: data,
            contentType: contentType,
            processData: processData,
            cache: cache,
            beforeSend: function() {
                if(beforeSendFunction) {
                    beforeSendFunction();
                }
            },
            success: function(data) {
                if(successFunction) {
                    successFunction(data)
                }
            },
            error: function (data) {
                if(errorFunction) {
                    errorFunction(data);
                }
            }
        });
    }
}
