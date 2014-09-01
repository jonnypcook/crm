var Script = function () {
    //toggle button

    window.prettyPrint && prettyPrint();

    $('#text-toggle-button').toggleButtons({
        width: 160,
        label: {
            enabled: "Test",
            disabled: "Live"
        },
        style: {
            // Accepted values ["primary", "danger", "info", "success", "warning"] or nothing
            enabled: "danger",
            disabled: "info"
        }
    });
    
    $('#SetupForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            resetFormErrors($(this).attr('name'));
            $('#msgs').empty();
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$(this).serialize();
            $('#setupLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        //console.log(response); //return;
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            if (obj.err == true) {
                                if (obj.info != undefined) {
                                    for(var i in obj.info){
                                        addFormError(i, obj.info[i]);
                                    }
                                }
                                
                                msgAlert('msgs',{
                                    title: 'Error!',
                                    mode: 3,
                                    body: 'The project configuration could not be updated due to errors in the form (displayed in red).',
                                    empty: true
                                });
                                scrollFormError('SetupForm', 110);
                            } else{ // no errors
                                growl('Success!', 'The project configuration has been updated successfully.', {time: 3000});
                                msgAlert('msgs',{
                                    title: 'Success!',
                                    mode: 1,
                                    body: 'The project configuration has been updated successfully.',
                                    empty: true
                                });
                                scrollFormTop('SetupForm',110);
                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#setupLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });
    
    


}();