var Script = function () {
    //toggle button

    try {
        var url = '/dashboard/mail/';
        var params = 'ts='+Math.round(new Date().getTime()/1000);
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
                console.log(response); //return;
                try{
                    var obj=jQuery.parseJSON(response);
                    // an error has been detected
                    if (obj.err == true) {
                    } else{ // no errors
                        if (obj.count<=0) {
                            $('.mail-count').text(obj.count)
                        } else {
                            $('.mail-count').show().text(obj.count)
                        }
                        
                        var ul = $('ul#mail-items');
                        ul.empty().append(
                            $('<li>').append(
                                $('<p>').text('You have '+obj.count+' new messages')
                                
                            )
                        );
                        for (var i in obj.msg) {
                            ul.append(
                                $('<li>').append(
                                    $('<a>').append(
                                        $('<span>').addClass('subject').append(
                                            $('<span>').addClass('from').text(obj.msg[i].from),
                                            $('<span>').addClass('time').text(obj.msg[i].date)
                                        ),
                                        $('<span>').addClass('message').text(obj.msg[i].subject)
                                    )
                                )
                            );
                        }
                        ul.append(
                            $('<li>').append(
                                $('<a>').attr('href','#').text('See all messages')
                                
                            )
                        );

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

    } catch (ex) {

    }/**/

}();