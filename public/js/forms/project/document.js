var Script = function () {
    //toggle button

    window.prettyPrint && prettyPrint();
    
    $('#documentOption li').on('click', function(e) {
        e.preventDefault();
        $('#document-name').text($(this).attr('data-name'));
        $('#document-desc').text($(this).attr('data-desc'));
        $('#documentId').val($(this).attr('data-id'));
        
        $('#preview-frame').attr('src','about:blank');
        formWizard();
    });
    
    $('#btn-document-preview').on('click', function(e) { 
        e.preventDefault();
        var documentId = $('#documentId').val();
        
        if (documentId == undefined) {
            return false;
        }
        
        if (!documentId.match(/^[0-9]+$/)) {
            return false;
        }
        
        $('#documentInline').val(1);
        
        $('#preview-frame').show();
        $('#previewLoader').fadeIn(function(){
            $('#formWizard')
                .attr('target', 'preview-frame')
                .submit();
            scrollFormTop('preview-frame-container',50);
        });
    
    });
    
    $('.btn-document-newtab').on('click', function(e) { 
        e.preventDefault();
        var documentId = $('#documentId').val();
        
        if (documentId == undefined) {
            return false;
        }
        
        if (!documentId.match(/^[0-9]+$/)) {
            return false;
        }
        
        $('#documentInline').val(1);
        
        var url = $('#formWizard').attr('action')+'?ts='+Math.round(new Date().getTime()/1000)+'&'+$('#formWizard').serialize();
        window.open(url);
    
    });
    

    
    $('#btn-document-download').on('click', function(e) { 
        e.preventDefault();
        var documentId = $('#documentId').val();
        
        if (documentId == undefined) {
            return false;
        }
        
        if (!documentId.match(/^[0-9]+$/)) {
            return false;
        }
        
        $('#documentInline').val(0);
        
        $('#formWizard')
            .attr('target', 'download-frame')
            .submit();
    
    });
    
    $('#btn-document-print').on('click', function(e) { 
        e.preventDefault();
        window.frames["preview-frame"].print()
    
    });
    
    
    $('#preview-frame').bind('load',function(){
        $('#previewLoader').fadeOut();
        if ($(this).get(0).contentWindow.location=='about:blank') {
            $('#preview-opts').fadeOut();
            $(this).slideUp();
        } else {
            $('#preview-opts').fadeIn();
        }

    });


    function formWizard () {
        $('#documentFormContent').html('loading configuration details for document ...');
        try {
            var url = $('#formWizard').attr('action').replace(/generate$/, 'wizard');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$('#formWizard').serialize();
            $('#wizardLoader').fadeIn(function(){
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
                            var k = 0;
                            // an error has been detected
                            if (obj.err == true) {

                            } else{ // no errors
                                $('#documentFormContent').html(obj.form);

                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#wizardLoader').fadeOut(function(){});
                    }
                });
            });
        } catch (ex) {

        }/**/
    }
    
    
    
    


}();