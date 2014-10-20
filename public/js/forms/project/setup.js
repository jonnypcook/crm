var Script = function () {
    $(".chzn-select").chosen(); 
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
    
    function setTabButtons (tab, suffix, max) {
        if (tab > 1) {
            $('#btn-last'+suffix).removeAttr('disabled');
        } else if (tab == 1) {
            $('#btn-last'+suffix).attr('disabled','disabled');
        } 

        if (tab == max) {
            $('#btn-next'+suffix).attr('disabled','disabled');
        } else if (tab < max) {
            $('#btn-next'+suffix).removeAttr('disabled');
        }
    }
    
    // next button press
    $('#btn-next').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectSettings li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab<3)?activeTab+1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab,'', 3);
            $('a[href=#widget_tab'+nextTab+']').tab('show');
        }
        
    });
    
    // last button press
    $('#btn-last').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectSettings li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab>1)?activeTab-1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab,'', 3);
            $('a[href=#widget_tab'+nextTab+']').tab('show');
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
                                        if (tab<3){
                                            switch (i) {
                                                case 'name': case 'test': case 'sector': case 'type': case 'financeProvider': case 'financeYears': case 'mcd': case 'ibp': tab = (tab<1)?1:tab; break;
                                                case 'fuelTariff': case 'maintenance': case 'co2': case 'rpi': case 'epi': case 'eca': case 'carbon': case 'model': tab = (tab<2)?2:tab; break;
                                                case 'weighting': case 'status': tab = (tab<3)?3:tab; break;
                                            }
                                        }
                                    }
                                }
                                $('ul#tabsProjectSettings a[href=#widget_tab'+tab+']').tab('show');
                                
                                msgAlert('msgs',{
                                    title: 'Error!',
                                    mode: 3,
                                    body: 'The project configuration could not be updated due to errors in the form (displayed in red).',
                                    empty: true
                                });
                                //scrollFormError('SetupForm', 210);
                            } else{ // no errors
                                growl('Success!', 'The project configuration has been updated successfully.', {time: 3000});
                                /*msgAlert('msgs',{
                                    title: 'Success!',
                                    mode: 1,
                                    body: 'The project configuration has been updated successfully.',
                                    empty: true
                                });/**/
                                scrollFormTop('SetupForm',210);
                                $('ul#tabsProjectSettings a[href=#widget_tab1]').tab('show');
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
    
    
    // next button press
    $('#btn-next-bs').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectBluePaper li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab<5)?activeTab+1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab, '-bs', 5);
            $('a[href=#widgetBS_tab'+nextTab+']').tab('show');
        }
        
    });
    
    // last button press
    $('#btn-last-bs').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectBluePaper li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab>1)?activeTab-1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab, '-bs', 5);
            $('a[href=#widgetBS_tab'+nextTab+']').tab('show');
        }
        
    });
    
    $('#btn-modify-bs').on('click', function(e) {
        $('#BlueSheetForm1').submit();
    });
    
    $('#BlueSheetForm1').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            resetFormErrors($(this).attr('name'));
            $('#msgs').empty();
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$(this).serialize();
            $('#setupBSLoader').fadeIn(function(){
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
                        console.log(response); return;
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            if (obj.err == true) {
                                var tab = 1;
                                if (obj.info != undefined) {
                                    for(var i in obj.info){
                                        addFormError(i, obj.info[i]);
                                        if (tab<3){
                                            switch (i) {
                                                case 'name': case 'test': case 'sector': case 'type': case 'financeProvider': case 'financeYears': case 'mcd': case 'ibp': tab = (tab<1)?1:tab; break;
                                                case 'fuelTariff': case 'maintenance': case 'co2': case 'rpi': case 'epi': case 'eca': case 'carbon': case 'model': tab = (tab<2)?2:tab; break;
                                                case 'weighting': case 'status': tab = (tab<3)?3:tab; break;
                                            }
                                        }
                                    }
                                }
                                $('ul#tabsProjectSettings a[href=#widget_tab'+tab+']').tab('show');
                                
                                msgAlert('msgs',{
                                    title: 'Error!',
                                    mode: 3,
                                    body: 'The project configuration could not be updated due to errors in the form (displayed in red).',
                                    empty: true
                                });
                                //scrollFormError('SetupForm', 210);
                            } else{ // no errors
                                growl('Success!', 'The project configuration has been updated successfully.', {time: 3000});
                                /*msgAlert('msgs',{
                                    title: 'Success!',
                                    mode: 1,
                                    body: 'The project configuration has been updated successfully.',
                                    empty: true
                                });/**/
                                scrollFormTop('SetupForm',210);
                                $('ul#tabsProjectSettings a[href=#widget_tab1]').tab('show');
                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#setupBSLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });
    
    
    $(document).on('click', '#tbl-competitors tbody tr:not(.disabled)', function(e) {
        e.preventDefault();
        var cid = $(this).attr('data-cid');
        if (cid==undefined) {
            return false;
        }
        
        $('#tbl-competitors tbody tr').removeClass('active');
        $(this).addClass('active');
        
        findCompetitor(cid);
    });
    
    $('#add-strength').on('click', function(e) {
        e.preventDefault();
        var len = $('#sec-competitor-strengths input').length;
        var input = $('<input>', {
                type: 'text',
                name: 'strengths[]'
            })
            .attr('placeholder', 'Strength #'+(len+1))
            .addClass('span12');
        $('#sec-competitor-strengths p').remove();
        $('#sec-competitor-strengths').append (
            input,
            $('<div>').addClass('space5')
        );

        input.focus();
        return false;
    });
    
    $('#add-weakness').on('click', function(e) {
        e.preventDefault();
        $('#sec-competitor-weaknesses p').remove();
        var len = $('#sec-competitor-weaknesses input').length;
        var input = $('<input>', {
                type: 'text',
                name: 'weaknesses[]'
            })
            .attr('placeholder', 'Weakness #'+(len+1))
            .addClass('span12');
        $('#sec-competitor-weaknesses').append (
            input,
            $('<div>').addClass('space5')
        );

        input.focus();
        return false;
    });
    
    $('#btn-competitors-delete').on('click', function(e) {
        e.preventDefault();
        if ($('#BlueSheetForm2 input[name=cid]').val()==undefined) {
            return false;
        }

        deleteCompetitor($('#BlueSheetForm2 input[name=cid]').val());
        return false;
    });
    
    $('#btn-competitors-modify').on('click', function(e) {
        e.preventDefault();
        if ($('#BlueSheetForm2 input[name=cid]').val()==undefined) {
            return false;
        }
        saveCompetitor();
        return false;
    });
    
    function deleteCompetitor(cid) {
        try {
            var url = $('#BlueSheetForm2').attr('action').replace(/[%][m]/, 'competitordelete');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&cid='+cid;
            
            $('#btn-competitors-modify').addClass('hidden');
            $('#btn-competitors-delete').addClass('hidden');
            $('#competitorLoader').fadeIn(function(){
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
                            if (obj.err == true) 
                            {
                                growl('Failure!', 'The project competitor details could not be deleted.', {time: 3000});
                            } else{ // no errors
                                $('#tbl-competitors tbody tr[data-cid='+cid+']').remove();
                                var len = $('#tbl-competitors tbody tr').length;
                                if (len==0) {
                                    $('#competitorInfo').css({visibility: 'hidden'});
                                    $('#btn-competitors-modify').addClass('hidden');
                                    $('#btn-competitors-delete').addClass('hidden');
                                } else {
                                    $('#tbl-competitors tbody tr:first-child').trigger('click');
                                }                                
                                growl('Success!', 'The project competitor details have been deleted successfully.', {time: 3000});
                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#btn-competitors-modify').removeClass('hidden');
                        $('#btn-competitors-delete').removeClass('hidden');
                        $('#competitorLoader').fadeOut(function(){});
                        
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    }
    
    function saveCompetitor() {
        try {
            var url = $('#BlueSheetForm2').attr('action').replace(/[%][m]/, 'competitorsave');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$('#BlueSheetForm2').serialize();
            $('#btn-competitors-modify').addClass('hidden');
            $('#btn-competitors-delete').addClass('hidden');
            $('#competitorLoader').fadeIn(function(){
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
                            if (obj.err == true) 
                            {
                                growl('Failure!', 'The project competitor details could not be updated.', {time: 3000});
                            } else{ // no errors
                                growl('Success!', 'The project competitor details have been updated.', {time: 3000});
                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#btn-competitors-modify').removeClass('hidden');
                        $('#btn-competitors-delete').removeClass('hidden');
                        $('#competitorLoader').fadeOut(function(){});
                        
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    }
    
    
    function findCompetitor(cid) {
        try {
            var url = $('#BlueSheetForm2').attr('action').replace(/[%][m]/, 'competitorfind');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&cid='+cid;
            $('#competitorInfo').css({visibility: 'hidden'});
            $('#btn-competitors-modify').addClass('hidden');
            $('#btn-competitors-delete').addClass('hidden');
            $('#competitorLoader').fadeIn(function(){
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
                                //scrollFormError('SetupForm', 210);
                            } else{ // no errors
                                $('#BlueSheetForm2 input[name=cid]').val(obj.info.cid);
                                $('#lbl-competitor-name').text(obj.info.name);
                                $('#lbl-competitor-url').attr('href',obj.info.url).text(obj.info.url);
                                $('#BlueSheetForm2 textarea[name=strategy]').val(obj.info.strategy);
                                $('#BlueSheetForm2 textarea[name=response]').val(obj.info.response);
                                
                                $('#sec-competitor-strengths').empty();
                                $('#sec-competitor-weaknesses').empty();
                                
                                if (obj.info.strengths!=null) {
                                    for (var i in obj.info.strengths) {
                                        $('#sec-competitor-strengths').append (
                                            $('<input>', {
                                                type: 'text',
                                                name: 'strengths[]'
                                            })
                                            .attr('placeholder', 'Strength')
                                            .addClass('span12')
                                            .val(obj.info.strengths[i]),
                                            $('<div>').addClass('space5')
                                        );
                                    }
                                } else {
                                    $('#sec-competitor-strengths').append($('<p>').text('No strengths added to competitor'));
                                }
                                
                                if (obj.info.weaknesses!=null) {
                                    for (var i in obj.info.weaknesses) {
                                        $('#sec-competitor-weaknesses').append (
                                            $('<input>', {
                                                type: 'text',
                                                name: 'weaknesses[]'
                                            })
                                            .attr('placeholder', 'Weakness')
                                            .addClass('span12')
                                            .val(obj.info.weaknesses[i]),
                                            $('<div>').addClass('space5')
                                        );
                                        
                                    }
                                } else {
                                    $('#sec-competitor-weaknesses').append($('<p>').text('No weaknesses added to competitor'));
                                }
                                
                                $('#btn-competitors-modify').removeClass('hidden');
                                $('#btn-competitors-delete').removeClass('hidden');

                            }
                        }
                        catch(error){
                            $('#errors').html($('#errors').html()+error+'<br />');
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#competitorInfo').css({visibility: 'visible'});
                        $('#competitorLoader').fadeOut(function(){});
                        
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    }

}();