var Script = function () {
    
    $('.chbx-building').on('click', function(e) {
        e.preventDefault();
        var buildingId = $(this).attr('data-buildingId');
        
        if ($(this).hasClass('icon-chevron-down')) {
            $('#tbl-export-building-'+buildingId+' tbody tr').hide();
            $(this).removeClass('icon-chevron-down');
            $(this).addClass('icon-chevron-up');
        } else {
            $('#tbl-export-building-'+buildingId+' tbody tr').show();
            $(this).removeClass('icon-chevron-up');
            $(this).addClass('icon-chevron-down');
        }
    });
    
    $('.chbx-space').on('click', function(e) {
        e.preventDefault();
        var spaceId = $(this).attr('data-spaceId');
        
        if ($(this).hasClass('icon-chevron-down')) {
            $('#tbl-export-system-'+spaceId).hide();
            $(this).removeClass('icon-chevron-down');
            $(this).addClass('icon-chevron-up');
        } else {
            $('#tbl-export-system-'+spaceId).show();
            $(this).removeClass('icon-chevron-up');
            $(this).addClass('icon-chevron-down');
        }
    });
    
    $('#btn-select-all').on('click', function(e) {
        e.preventDefault();
        $('.tbl-export-building tbody tr').show();
        $('.tbl-export-system').show();
        $('.chbx-building').removeClass('icon-chevron-up').addClass('icon-chevron-down');
        $('.chbx-space').removeClass('icon-chevron-up').addClass('icon-chevron-down');
        
        return false;
    });
    
    $('#btn-deselect-all').on('click', function(e) {
        e.preventDefault();
        $('.tbl-export-building tbody tr').hide();
        $('.tbl-export-system').hide();
        $('.chbx-building').removeClass('icon-chevron-down').addClass('icon-chevron-up');
        $('.chbx-space').removeClass('icon-chevron-down').addClass('icon-chevron-up');
        
        return false;
    });
    
    $('#btn-confirm-export-to-projis').on('click', function (e) {
        e.preventDefault();
        try {
            resetFormErrors($(this).attr('name'));
            var url = $('#frmExportToProjis').attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000);
            
            $('#exportToProjisLoader').fadeIn(function(){
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
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            var additional='';
                            if (obj.err == true) {
                                growl('Failure!', 'The activity could not be completed for the following reason: <br><br>' + obj.info , {});
                                
                            } else{ // no errors
                                growl('Success!', 'The project has been successfully exported to projis. <br><br><a href="' + obj.url + '" target="_tab">View project in Projis</a>', {});
                                $('#modalExportToProjis').modal('hide');
                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#exportToProjisLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });  

    
   

}();