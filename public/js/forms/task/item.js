var Script = function () {
    
    $('#btn-add-task-activity').on('click', function(e) {
        e.preventDefault();
        $('#AddTaskActivityForm').submit();
        return false;
    });
    
    $('#btn-confirm-completed').on('click', function(e) {
        e.preventDefault();
        $('#CompleteTaskForm').submit();
        return false;
    });
    
    
    
    
      var dataTblAddActivity = $('#tbl-task-activities').dataTable({
        sDom: "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
        sPaginationType: "bootstrap",
        iDisplayLength:20,
        aLengthMenu: [[5, 10, 15, 20, 40], [5, 10, 15, 20, 40]],
        oLanguage: {
            sLengthMenu: "_MENU_ per page",
            oPaginate: {
                sPrevious: "",
                sNext: ""
            }
        },
        aoColumnDefs: [{
            'bSortable': true,
            'aTargets': [0]
        }]
    });

    jQuery('#tbl-task-activities_wrapper .dataTables_filter input').addClass("input-medium"); // modify table search input
    jQuery('#tbl-task-activities_wrapper .dataTables_length select').addClass("input-mini"); // modify table per page dropdown
    
    dataTblAddActivity.fnSort( [ [0,'desc'] ] );
    
    $('#CompleteTaskForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            $('#taskCompleteMsgs').empty();
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000);
            $('#taskCompleteLoader').fadeIn(function(){
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
                                var tab = 1;
                                if (obj.info != undefined) {
                                    for(var i in obj.info){
                                        addFormError(i, obj.info[i]);
                                    }
                                }
                                
                                msgAlert('taskCompleteMsgs',{
                                    mode: 3,
                                    body: 'The task could not be marked as completed due to errors.',
                                    empty: true
                                });
                                //scrollFormError('SetupForm', 210);
                            } else{ // no errors
                                //growl('Success!', 'The task has been updated successfully.', {time: 3000});
                                window.location.reload();
                            }
                        }
                        catch(error){
                            
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#taskCompleteLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });
    
    $('#AddTaskActivityForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            resetFormErrors($(this).attr('name'));
            $('#taskAddActivityNoteMsgs').empty();
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$(this).serialize();
            $('#taskAddActivityNoteLoader').fadeIn(function(){
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
                                var tab = 1;
                                if (obj.info != undefined) {
                                    for(var i in obj.info){
                                        addFormError(i, obj.info[i]);
                                    }
                                }
                                
                                msgAlert('taskAddActivityNoteMsgs',{
                                    mode: 3,
                                    body: 'The task activity could not be added due to errors.',
                                    empty: true
                                });
                                //scrollFormError('SetupForm', 210);
                            } else{ // no errors
                                $('#modalAddTask').modal('hide');
                                //growl('Success!', 'The task activity has been added successfully.', {time: 3000});
                                $('#taskAddActivityNoteMsgs textarea').val('');
                                window.location.reload();
                            }
                        }
                        catch(error){
                            
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#taskAddActivityNoteLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });

}();