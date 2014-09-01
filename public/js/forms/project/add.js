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
    
    
    // tabbed pane setup
    $('#pills').bootstrapWizard({'tabClass': 'nav nav-pills', 'debug': false, onShow: function(tab, navigation, index) {
        //console.log('onShow');
    }, onNext: function(tab, navigation, index) {
        //console.log('onNext');
    }, onPrevious: function(tab, navigation, index) {
        //console.log('onPrevious');
    }, onLast: function(tab, navigation, index) {
        //console.log('onLast');
    }, onTabClick: function(tab, navigation, index) {
        //console.log('onTabClick');
        //alert('on tab click disabled');
    }, onTabShow: function(tab, navigation, index) {
        //console.log('onTabShow');
        var $total = navigation.find('li').length;
        var $current = index+1;
        var $percent = ($current/$total) * 100;
        $('#pills').find('.bar').css({width:$percent+'%'});
        
        if($current >= $total) {
            $('#pills').find('.pager .next').hide();
            $('#pills').find('.pager .finish').show();
            $('#pills').find('.pager .finish').removeClass('disabled');
        } else {
            $('#pills').find('.pager .next').show();
            $('#pills').find('.pager .finish').hide();
        }
    }});

    $('#pills .finish').click(function() {
        $('#ProjectCreateForm').submit();
    });
    
    $('#ProjectCreateForm').on('submit', function(e) {
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
                            var tab = 3;
                            var additional='';
                            if (obj.err == true) {
                                if (obj.info != undefined) {
                                    for(var i in obj.info){
                                        if (!addFormError(i, obj.info[i])) {
                                            additional+='<br>Information: '+obj.info[i];
                                        }
                                        if (tab>1){
                                            switch (i) {
                                                case 'name': case 'test': case 'sector': case 'type': case 'model': case 'ibp': tab = 1; break;
                                                case 'co2': case 'fuelTariff': case 'rpi': case 'epi': case 'mcd': case 'eca': case 'carbon': tab = 2; break;
                                                case 'financeProvider': case 'financeYears': tab = 3; break;
                                                case 'notes': tab = 4; break;
                                            }
                                        }
                                    }
                                }
                                msgAlert('msgs',{
                                    title: 'Error!',
                                    mode: 3,
                                    body: 'The project could not be added due to errors in the form (displayed in red).'+additional,
                                    empty: true
                                });
                                $('a[href=#pills-tab'+tab+']').tab('show'); return;
                            } else{ // no errors
                                $('#btn-project-dashboard').on('click', function(e) {
                                    document.location = '/client-%c/project-%p/'.replace(/[%][c]/, obj.cid).replace(/[%][p]/, obj.pid);
                                });
                                $('#myModal3').modal({})
                                .on('hidden.bs.modal', function (e) {
                                    document.location = '/client-%c/'.replace(/[%][c]/, obj.cid);
                                });

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
    
    $('#new-note').on('click', function(e) {
        e.preventDefault();
        var len = $('#notes .control-group').length;
        if (len>=10) {
            return false;
        }
        
        var inp = $('<input>', {type: 'text', name: 'note[]'})
                    .addClass('span6')
                    .attr('placeholder', 'Additional Note #'+(len+1));
        $('#notes').append(
            $('<div>')
            .addClass('control-group')
            .append(
                inp
            )
        );

        inp.focus();

        return false;
    });

}();