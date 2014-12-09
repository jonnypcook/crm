/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var Script = function () {
    $(".chzn-select").chosen(); 
    var wysiwyg = $('.wysihtmleditor5').wysihtml5();
    
    
    $('#mailListForm').on('submit', function(e) {
        e.preventDefault();
        
        try {
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000);
            $('#mailListLoader').fadeIn(function(){
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
                            // an error has been detected
                            var tbl = $('#mailListTbl tbody');
                            tbl.empty();
                            if (obj.err == true) {
                                // catch message
                            } else{ // no errors
                                var cnt = 0;
                                for (var i in obj.mail.msg) {
                                    for (var j in obj.mail.msg[i]) {
                                        tbl.append(
                                            $('<tr>').append(
                                                $('<td>').text(obj.mail.msg[i][j].date),
                                                $('<td>').text(obj.mail.msg[i][j].subject)
                                            )
                                        );
                                        cnt++;
                                    }
                                }
                                
                                if (cnt == 0) {
                                    tbl.append(
                                        $('<tr>').append(
                                            $('<td>').attr('colspan', 2).text('No message threads found on Gmail service')
                                        )
                                    );
                                    $('#mailListCount').html('&nbsp;')
                                } else {
                                    $('#mailListCount').text('Showing messages 1-'+cnt+' of '+obj.mail.count)
                                }
                                
                                

                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#mailListLoader').fadeOut(function(){});
                    }
                });
            });
        } catch (ex) {

        }/**/
        return false;
    });
    
    $('#btn-send-email').on('click', function(e) {
       e.preventDefault();
       $('#EmailForm').submit();
    });
    
    $('#EmailForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            resetFormErrors($(this).attr('name'));
            $('#msgs').empty();
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$(this).serialize();
            $('#emailLoader').fadeIn(function(){
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
                                    body: 'The email could not be sent due to errors in the form (displayed in red).',
                                    empty: true
                                });
                                //scrollFormError('SetupForm', 210);
                            } else{ // no errors
                                growl('Success!', 'The email has been sent successfully.', {time: 3000});
                                $('#EmailForm input[name=subject]').val('');
                                wysiwyg.data("wysihtml5").editor.setValue('');
                                $('#EmailForm select[name="to[]"]').val('');
                                $('#EmailForm select[name="to[]"]').trigger('liszt:updated');  
                                $('#EmailForm select[name="cc[]"]').val('');
                                $('#EmailForm select[name="cc[]"]').trigger('liszt:updated');  
                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#emailLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });
    
    $('#mailListForm').submit();
    
    

}();