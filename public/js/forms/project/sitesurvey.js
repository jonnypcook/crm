var Script = function () {
    $(".chzn-select").chosen(); 
    //toggle button

    window.prettyPrint && prettyPrint();

    $('#text-toggle-button').toggleButtons({
        label: {
            enabled: "Yes",
            disabled: "No"
        },
        style: {
            // Accepted values ["primary", "danger", "info", "success", "warning"] or nothing
            enabled: "success",
            disabled: "danger"
        }
    });/**/
    
    $('input[name=surveyed]').datepicker({
        format: 'dd/mm/yyyy'
    }).on('changeDate', function (e) {
        $('input[name=surveyed]').datepicker('hide').blur();
    });
    
    /**
     * add building change event
     */
    $('#frmAddBuilding select[name=name]').on('change', function (e) {
        e.preventDefault();
        if ($(this).val().toLowerCase() === 'other') {
            $('#buildingNameOther').fadeIn('fast', function() {
                $(this).focus();
            });
        } else {
            $('#buildingNameOther').hide();
        }
        return false;
    });
    
    /**
     * add building event
     */
    $('#btn-add-building').on('click', function(e) {
        e.preventDefault();
        var error = [];
        
        var name = $('#frmAddBuilding select[name=name]').val();
        if (name.toLowerCase() === 'other') {
            name = $('#buildingNameOther').val();
        }
        
        if (!name || name.length < 0) {
            error.push('Please enter a space name or select a default name from the drop-down');
        }

        if (error.length > 0) {
            growl('Error!', "There were errors in the form:<br>- " + error.join('<br>- '), {time: 4000});
            return;
        }
        
        $('#setupTabPanelLoader').fadeIn(function () {
            var url = $('#frmAddBuilding').attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000) + '&name=' + name;
            
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
                            growl('Error!', 'The building could not be added to the survey.', {time: 3000});
                            //scrollFormError('SetupForm', 210);
                        } else{ // no errors
                            $('#tbl-buildings').empty();
                            
                            for (var i in obj.buildings) {
                                $('#tbl-buildings').append(
                                    $('<tr>').append(
                                        $('<td>').text((parseInt(i) + 1)),
                                        $('<td>').text(obj.buildings[i].name)
                                    )
                                )
                            }
                            
                            if (!!obj.building) {
                                $('#branches-spaces').append($('<option>').val(obj.building['id']).text(obj.building['name']));
                            }
                            
                            
                            
                            growl('Success!', 'The building has been added to the survey successfully.', {time: 3000});
                            $('#buildingNameOther').val('');
                            $('#frmAddBuilding select[name=name]').val('');
                            $('#frmAddBuilding select[name=name]').trigger('change');
                        }
                    }
                    catch(error){
                        //$('#errors').html($('#errors').html()+error+'<br />');
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupTabPanelLoader').fadeOut(function(){});
                }
            });            
        });
        return false;
    });
    
    /**
     * update project details event
     */
    $('#btn-update-project').on('click', function(e) {
        e.preventDefault();
        var error = [];
        
        // validate name
        var surveyed = $('#SiteSurveyForm input[name=surveyed]').val();
        if (!surveyed || surveyed.length < 0) {
            error.push('Please enter a survey date');
        }

        if (error.length > 0) {
            growl('Error!', "There were errors in the form:<br>- " + error.join('<br>- '), {time: 4000});
            return;
        }

        var url = $('#SiteSurveyForm').attr('action');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#SiteSurveyForm').serialize();

        $('#setupTabPanelLoader').fadeIn(function () {
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
                            growl('Error!', 'The project survey details could not be saved.', {time: 3000});
                        } else{ // no errors
                            growl('Success!', 'The project survey details have been updated successfully.', {time: 3000});
                        }
                    }
                    catch(error){
                        growl('Error!', 'The project survey details could not be saved.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupTabPanelLoader').fadeOut(function(){});
                }
            });            
        });
        return false;
    });
    
    /**
     * add new space event
     */
    $('#btn-space-add-new').on('click', function (e) {
        e.preventDefault();
        var url = $('#frmAddSpace').attr('action');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#frmAddSpace').serialize() + '&buildingId=' + $('#branches-spaces').val();
        
        $('#setupTabPanelLoader').fadeIn(function () {
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
                        if (obj.err == true) {
                            growl('Error!', 'The building could not be added to the survey.', {time: 3000});
                            //scrollFormError('SetupForm', 210);
                        } else{ // no errors
                            loadSpaceData(obj.spaceId);
                            $('#frmAddSpace input[name=name]').val('');
                            growl('Success!', 'The building has been added to the survey successfully.', {time: 3000});
                        }
                    }
                    catch(error){
                        //$('#errors').html($('#errors').html()+error+'<br />');
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupTabPanelLoader').fadeOut(function(){});
                }
            });            
        });
        
        return false;
    });
    
    /**
     * branch change event
     */
    $('#branches-spaces').on('change', function(e) {
        e.preventDefault();
        loadSpaceData();
        return false;
    })
    
    /**
     * load space data and trigger space data load
     * @param {type} spaceId
     * @returns {undefined}
     */
    function loadSpaceData(spaceId) {
        if (!$('#branches-spaces').val()) {
            return;
        }

        var url = $('#frmLoadSpaces').attr('action');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&buildingId=' + $('#branches-spaces').val();
        
        $('#setupTabPanelLoader').fadeIn(function () {
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

                        $('#tbl-building-spaces').empty();
                        
                        // an error has been detected
                        if (obj.err == true) {
                            growl('Error!', 'The spaces could not be loaded for the selected building.', {time: 3000});
                            //scrollFormError('SetupForm', 210);
                        } else{ // no errors

                            for (var i in obj.spaces) {
                                $('#tbl-building-spaces').append(
                                    $('<tr>')
                                    .attr('data-sid', obj.spaces[i].spaceId)
                                    .append(
                                        $('<td>').text(obj.spaces[i].name)
                                    )
                                )
                            }
                            
                            if (!!spaceId && ('' + spaceId).match(/^[\d]+$/)) {
                                $('#tbl-building-spaces tr[data-sid="' + spaceId + '"]').trigger('click');
                            } else {
                                $('#tbl-building-spaces tr:first-child').trigger('click');
                            }
                            
                        }
                    }
                    catch(error){
                        growl('Error!', 'The spaces could not be loaded for the selected building.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupTabPanelLoader').fadeOut(function(){});
                }
            }); 
        }); 
    }

    /**
     * Event: add system button click
     * validates the system add form and requests system data addition
     */
    $('#btn-update-space').on('click', function (e) {
        e.preventDefault();
        var sid = $('#spaceId').val();
        
        if (!sid.match(/^[\d]+$/, sid)) {
            return;
        }
        var error = [];
        
        // validate name
        var name = $('#SpaceCreateForm input[name=name]').val();
        if (!name || name.length < 0) {
            error.push('Please enter a valid name for the space');
        }

        var dimy = $('#SpaceCreateForm input[name=dimy]').val();
        if (!!dimy && (!dimy.match(/^[\d]+([.][\d]+)?$/) || parseInt(dimy) < 0)) {
            error.push('Please enter a valid room length or leave blank');
        }
        
        var dimx = $('#SpaceCreateForm input[name=dimx]').val();
        if (!!dimx && (!dimx.match(/^[\d]+([.][\d]+)?$/) || parseInt(dimx) < 0)) {
            error.push('Please enter a valid room width or leave blank');
        }
        
        var dimh = $('#SpaceCreateForm input[name=dimh]').val();
        if (!!dimh && (!dimh.match(/^[\d]+([.][\d]+)?$/) || parseInt(dimh) < 0)) {
            error.push('Please enter a valid ceiling height or leave blank');
        }
        
        var voidDimension = $('#SpaceCreateForm input[name=voidDimension]').val();
        if (!!voidDimension && (!voidDimension.match(/^[\d]+([.][\d]+)?$/) || parseInt(voidDimension) < 0)) {
            error.push('Please enter a valid void dimension or leave blank');
        }
        
        var luxLevel = $('#SpaceCreateForm input[name=luxLevel]').val();
        if (!!luxLevel && (!luxLevel.match(/^[\d]+([.][\d]+)?$/) || parseInt(luxLevel) < 0)) {
            error.push('Please enter a valid lux level or leave blank');
        }
        
        if (error.length > 0) {
            growl('Error!', "There were errors in the form:<br>- " + error.join('<br>- '), {time: 4000});
            return;
        }

        var url = $('#SpaceCreateForm').attr('action').replace(/[%][s]/, sid);
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#SpaceCreateForm').serialize() + '&systemInfo=1';
        
//        params = params.replace(/[^=&]+[=][&]/g, '');
        
        
        $('#spaceLoader').fadeIn(function () {
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
                        //console.log(response); //return;
                        var obj=jQuery.parseJSON(response);
                        var k = 0;

                        // an error has been detected
                        if (obj.err == true) {
                            growl('Error!', 'The space could not be updated updated.', {time: 3000});
                        } else{ // no errors
                            $('#pcCompleteSpace').text(findSpaceCompletePC());
                            growl('Success!', 'The space has been successfully updated.', {time: 3000});
                        }
                    }
                    catch(error){
                        growl('Error!', 'The space could not be updated updated.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            }); 
            
        });
        
        return false;
    });
    
    
    /**
     * Event: add hazard to space button click
     * adds the new hazard to the space
     */
    $('#btn-add-space-hazard').on('click', function (e) {
        e.preventDefault();
        var sid = $('#spaceId').val();
        
        if (!sid.match(/^[\d]+$/, sid)) {
            return;
        }
        var error = [];
        var hazard = $('#SpaceHazardForm select[name=hazard]').val();
        if (!hazard.match(/^[\d]+$/) || parseInt(hazard) <= 0) {
            error.push('Please select a hazard type');
        }

        var location = $('#SpaceHazardForm input[name=location]').val();
        if (!location || location.length <= 0) {
            error.push('Please enter the location/description of the hazard');
        }

        if (error.length > 0) {
            growl('Error!', "There were errors in the form:<br>- " + error.join('<br>- '), {time: 4000});
            return;
        }

        var url = $('#SpaceHazardForm').attr('action').replace(/[%][s]/, sid);
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#SpaceHazardForm').serialize() + '&hazardInfo=1';
        
        $('#spaceLoader').fadeIn(function () {
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
                        //console.log(response); //return;
                        var obj=jQuery.parseJSON(response);
                        var k = 0;

                        // an error has been detected
                        if (obj.err == true) {
                            growl('Error!', 'The hazard could not be added into the space.', {time: 3000});
                        } else{ // no errors
                            $('#SpaceHazardForm select[name=hazard]').val('');
                            $('#SpaceHazardForm input[name=location]').val('');
                            resetHazardInformation(obj.hazards);
                            growl('Success!', 'The hazard has been successfully added into the space.', {time: 3000});
                        }
                    }
                    catch(error){
                        growl('Error!', 'The hazard could not be added into the space.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            }); 
            
        });
        
        return false;
    });
    
    
    /**
     * Event: add system button click
     * validates the system add form and requests system data addition
     */
    $('#btn-add-system').on('click', function (e) {
        e.preventDefault();
        var sid = $('#spaceId').val();
        
        if (!sid.match(/^[\d]+$/, sid)) {
            return;
        }
        var error = [];
        var legacy = $('#SpaceAddProductForm select[name=legacy]').val();
        if (!legacy.match(/^[\d]+$/) || parseInt(legacy) <= 0) {
            error.push('Please select a legacy product');
        }

        var fixing = $('#SpaceAddProductForm select[name=fixing]').val();
        if (!fixing.match(/^[\d]+$/) || parseInt(fixing) <= 0) {
            error.push('Please select a fixing method');
        }

        var fixing = $('#SpaceAddProductForm input[name=cutout]').val();
        if (!fixing.match(/^[\d]+([.][\d]+)?$/) || parseInt(fixing) < 0) {
            error.push('Please enter a valid cutout value');
        }

        var qtty = $('#SpaceAddProductForm input[name=legacyQuantity]').val();
        if (!qtty.match(/^[\d]+$/) || parseInt(qtty) <= 0) {
            error.push('Please enter a positive quantity');
        }
        $('#SpaceAddProductForm input[name=quantity]').val(qtty);
        
        if (error.length > 0) {
            growl('Error!', "There were errors in the form:<br>- " + error.join('<br>- '), {time: 4000});
            return;
        }

        var url = $('#SpaceAddProductForm').attr('action').replace(/[%][s]/, sid);
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#SpaceAddProductForm').serialize() + '&systemInfo=1';
        
        $('#spaceLoader').fadeIn(function () {
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
                        //console.log(response); //return;
                        var obj=jQuery.parseJSON(response);
                        var k = 0;

                        // an error has been detected
                        if (obj.err == true) {
                            growl('Error!', 'The legacy system setup could not be added into the space.', {time: 3000});
                        } else{ // no errors
                            resetSystemInformation(obj.system);
                            resetHazardInformation(obj.hazards);
                            resetSystemAddForm();
                            growl('Success!', 'The legacy system item has been successfully added into the space.', {time: 3000});
                        }
                    }
                    catch(error){
                        growl('Error!', 'The legacy system setup could not be added into the space.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            }); 
            
        });
        
        return false;
    });
    
    
    /**
     * Event: delete system button click
     * validates the system remove form and requests system data removal
     */
    $(document).on('click', '.btn-remove-system', function (e) {
        e.preventDefault();
        var sysId = $(this).attr('sid');
        
        if (!sysId || !sysId.match(/^[\d]+$/, sid)) {
            return;
        }
        
        var sid = $('#spaceId').val();
        if (!sid || !sid.match(/^[\d]+$/, sid)) {
            return;
        }
        

        var url = $('#frmManageSpace').attr('action').replace(/[%][s]/, sid).replace(/[%][m]/, 'deleteSystem');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&systemInfo=1&sid=' + sysId;

        $('#spaceLoader').fadeIn(function () {
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
                        //console.log(response); //return;
                        var obj=jQuery.parseJSON(response);
                        var k = 0;

                        // an error has been detected
                        if (obj.err == true) {
                            growl('Error!', 'The legacy system setup could not be removed from the space.', {time: 3000});
                        } else{ // no errors
                            resetSystemInformation(obj.system);
                        }
                    }
                    catch(error){
                        growl('Error!', 'The legacy system setup could not be removed from the space.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            }); 
            
        });
        
        return false;
    });
    
    /**
     * Event: legacy item changed
     * sets the hidden form variables to the legacy product defaults
     */
    $('form[name=SpaceAddProductForm] select[name=legacy]').on('change', function(e) {
        var opt = $(this).find('option[value='+$(this).val()+']');
        if (opt == undefined) {
            return;
        }
            
        $('form[name=SpaceAddProductForm] input[name=legacyWatts]').val(opt.attr('data-pwr'));
        $('form[name=SpaceAddProductForm] input[name=legacyMcpu]').val(opt.attr('data-mcpu'));
        //$('form[name=SpaceAddProductForm] input[name=product]').val(opt.attr('data-pid'));
    });
    
    
    /**
     * show/hide details click event
     */
    $('#hide-space-hazard-btn').on('click', function (e) {
        e.preventDefault();
        showSpaceHazardDetails (!$('#space-hazard-details').is(':visible'));
        return false;
    });
    
    /**
     * show/hide space details panel
     * @param {type} show
     * @param {type} fast
     * @returns {undefined}
     */
    function showSpaceHazardDetails (show, fast) {
        if (show === true) {
            if (!!fast) $('#space-hazard-details').show();
            else $('#space-hazard-details').slideDown();
            
            $('#hide-space-hazard-btn').text('Hide');
        } else {
            if (!!fast) $('#space-hazard-details').hide();
            else $('#space-hazard-details').slideUp();
            $('#hide-space-hazard-btn').text('Show');
        }
    }
    
    
    /**
     * show/hide details click event
     */
    $('#hide-space-btn').on('click', function (e) {
        e.preventDefault();
        showSpaceDetails (!$('#space-details').is(':visible'));
        return false;
    });
    
    
    /**
     * show/hide space details panel
     * @param {type} show
     * @param {type} fast
     * @returns {undefined}
     */
    function showSpaceDetails (show, fast) {
        if (show === true) {
            if (!!fast) $('#space-details').show();
            else $('#space-details').slideDown();
            
            $('#hide-space-btn').text('Hide');
        } else {
            if (!!fast) $('#space-details').hide();
            else $('#space-details').slideUp();
            $('#hide-space-btn').text('Show');
        }
    }
    
    
    $('#btn-branches-refresh').on('click', function(e) {
        e.preventDefault();
        
        $('#tbl-building-spaces tr.row-selected').trigger('click');
        
        return false;
    });
    
    /**
     * Event: space item click
     * loads the system and space data for the sleected space
     */
    $(document).on('click', '#tbl-building-spaces tr:not(.disabled)', function(e) {
        e.preventDefault();
        var sid = $(this).attr('data-sid');

        var url = $('#frmManageSpace').attr('action').replace(/[%][s]/, sid).replace(/[%][m]/, 'get');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&systemInfo=1';

        $('#tbl-building-spaces tr').removeClass('row-selected');
        $(this).addClass('row-selected');
        
        $('#spaceContent').hide();
        $('#spaceMessage').show().html('loading please wait ...');
        $('#spaceLoader').fadeIn(function () {
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
                        if (obj.err == true) {
                            $('#spaceMessage').html('No space information loaded');
                            growl('Error!', 'The space information could not be loaded.', {time: 3000});
                            //scrollFormError('SetupForm', 210);
                        } else{ // no errors
                            $('#spaceId').val(sid);
                            
                            resetSpaceData(obj.space);
                            resetSystemInformation(obj.system);
                            resetHazardInformation(obj.hazards);
                            resetSystemAddForm();
                            
                            showSpaceDetails (findSpaceCompletePC() < 70, true);
                            showSpaceHazardDetails ((!!obj.hazards && obj.hazards.length > 0), true);
                            
                            $('#spaceMessage').hide();
                            $('#spaceContent').show();
                        }
                    }
                    catch(error){
                        $('#spaceMessage').html('No space information loaded!');
                        growl('Error!', 'The space information could not be loaded.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            }); 
            
        });
        return false;
    });
    
    
    /**
     * finish survey click event
     */
    $('#btn-finish-survey').on('click', function (e) {
        e.preventDefault();
        var url = $('#frmFinishSurvey').attr('action');
        var params = 'ts='+Math.round(new Date().getTime()/1000);

        $('#setupTabPanelLoader').fadeIn(function () {
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
                        //console.log(response); //return;
                        var obj=jQuery.parseJSON(response);
                        var k = 0;

                        // an error has been detected
                        if (obj.err == true) {
                            growl('Error!', 'The survey could not be marked as completed.', {time: 3000});
                            //scrollFormError('SetupForm', 210);
                        } else{ // no errors
                            document.location.href = '/project/survey';
                        }
                    }
                    catch(error){
                        $('#spaceMessage').html('No space information loaded!');
                        growl('Error!', 'The space information could not be loaded.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupTabPanelLoader').fadeOut(function(){});
                }
            }); 
            
        });
        return false;
    });
    
    
    /**
     * function used to reset system add form to defaults
     * @returns {undefined}
     */
    function resetSystemAddForm () {
        $('#SpaceAddProductForm select[name=legacy]').val('');
        $('#SpaceAddProductForm select[name=fixing]').val('');
        $('#SpaceAddProductForm input[name=cutout]').val('0');
        $('#SpaceAddProductForm input[name=legacyQuantity]').val('');
        $('#SpaceAddProductForm input[name=quantity]').val('');
        $('#SpaceAddProductForm input[name=legacyWatts]').val('');
        $('#SpaceAddProductForm input[name=legacyMcpu]').val('');
        //$('#SpaceAddProductForm input[name=product]').val($('#LegacyConfigForm input[name=product]').val());
    }
    
    /**
     * reset space data
     * @param {type} space
     * @returns {undefined}
     */
    function resetSpaceData (space) {
        $('#SpaceCreateForm input[name=name]').val(!!space.name ? space.name : '');
        $('#SpaceCreateForm input[name=dimx]').val(!!space.dimx ? space.dimx : '');
        $('#SpaceCreateForm select[name=ceiling]').val(!!space.ceilingId ? space.ceilingId : '');
        $('#SpaceCreateForm select[name=tileSize]').val(!!space.tileSizeId ? space.tileSizeId : '');
        $('#SpaceCreateForm input[name=voidDimension]').val(!!space.voidDimension ? space.voidDimension : '');
        $('#SpaceCreateForm select[name=grid]').val(!!space.gridId ? space.gridId : '');
        $('#SpaceCreateForm input[name=dimy]').val(!!space.dimy ? space.dimy : '');
        $('#SpaceCreateForm input[name=dimh]').val(!!space.dimh ? space.dimh : '');
        $('#SpaceCreateForm select[name=metric]').val(!!space.metric ? space.metric : '');
        $('#SpaceCreateForm input[name=tileType]').val(!!space.tileType ? space.tileType : '');
        $('#SpaceCreateForm select[name=electricConnector]').val(!!space.electricConnectorId ? space.electricConnectorId : '');
        $('#SpaceCreateForm input[name=luxLevel]').val(!!space.luxLevel ? space.luxLevel : '');
        $('#SpaceCreateForm input[name=building]').val(!!space.buildingId ? space.buildingId : '');
        $('#SpaceCreateForm input[name=spaceType]').val(!!space.typeId ? space.typeId : '');
        
        $('#tbl-space-notes').empty();
        var noteCount = 0;
        if (!!space.notes) {
            var obj=jQuery.parseJSON(space.notes);
            for (var i in obj) {
                noteCount++;
                $('#tbl-space-notes').append(
                    $('<tr>').append(
                        $('<td>').text(obj[i]),
                        $('<td>').append(
                            $('<button>').addClass("btn btn-danger btn-remove-note").attr('nid', i).html('<i class="icon-trash"></i>')
                        )
                    )
                );
            }
        } 
        
        if (noteCount === 0) {
            $('#tbl-space-notes').append(
                $('<tr>').addClass('no-notes').append(
                    $('<td>').attr('colspan', 2).text('No notes have been added to this space')
                )
            );
        }
        
        $('#pcCompleteSpace').text(findSpaceCompletePC());
    }
    
    
    /**
     * delete note click event
     */
    $(document).on('click', '.btn-remove-note', function(e) {
        e.preventDefault();
        var sid = $('#spaceId').val();
        if (!sid || !sid.match(/^[\d]+$/)) {
            return;
        }

        var nid = $(this).attr('nid');
        if (!nid || !nid.match(/^[\d]+$/)) {
            return;
        }

        var parent = $(this).parent().parent();
        var url = $('#frmManageSpace').attr('action').replace(/[%][s]/, sid).replace(/[%][m]/, 'deletenote');
        var params = 'ts='+Math.round(new Date().getTime()/1000)+'&nid='+nid;
        
        $('#spaceLoader').fadeIn(function(){
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
                            growl('Error!', 'The note could not be removed from the space.', {time: 3000});
                        } else{ // no errors
                            parent.remove();
                            if ($('#tbl-space-notes tr').length < 1) {
                               $('#tbl-space-notes').append(
                                    $('<tr>').addClass('no-notes').append(
                                        $('<td>').attr('colspan', 2).text('No notes have been added to this space')
                                    )
                                ); 
                            }
                            growl('Success!', 'The note has been deleted from the space.', {time: 3000});
                        }
                    }
                    catch(error){ }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            });
        });

        
        return false;
    });
    
    
    /**
     * add note click event
     */
    $('#btn-add-note').on('click', function(e) {
        e.preventDefault();
        var sid = $('#spaceId').val();
        if (!sid || !sid.match(/^[\d]+$/)) {
            return;
        }

        var note = $('#newSpaceNote').val();
        if (!note || note.length < 0) {
            return;
        }

        var url = $('#frmManageSpace').attr('action').replace(/[%][s]/, sid).replace(/[%][m]/, 'addnote');
        var params = 'ts='+Math.round(new Date().getTime()/1000)+'&note=' + note;
        
        $('#spaceLoader').fadeIn(function(){
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
                            growl('Error!', 'The note could not be added to the space.', {time: 3000});
                        } else{ // no errors
                            $('#tbl-space-notes tr.no-notes').remove();
                            $('#tbl-space-notes').append(
                                $('<tr>').append(
                                    $('<td>').text(note),
                                    $('<td>').append(
                                        $('<button>').addClass("btn btn-danger btn-remove-note").attr('nid', obj.id).html('<i class="icon-trash"></i>')
                                    )
                                )
                            );
                            $('#newSpaceNote').val('');
                            growl('Success!', 'The note has been added to the space.', {time: 3000});
                        }
                    }
                    catch(error){ }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            });
        });

        
        return false;
    });
    
    
    /**
     * finds the completed percentage
     * @returns {completed.length|total.length|Number}
     */
    function findSpaceCompletePC () {
        var total = $('#SpaceCreateForm').serialize().match(/[^=&]+[=]/g),
            totalCount = !!total ? total.length : 0,
            completed = $('#SpaceCreateForm').serialize().match(/[^=&]+[=][^&]+/g),
            completedCount = !!completed ? completed.length : 0;
    
        return ((completedCount / totalCount) * 100).toFixed(0);

    }
    
    /**
     * reset the system table with new data
     * @param {type} system
     * @returns {undefined}
     */
    function resetSystemInformation (system) {
        $('#tbl-space-systems').empty();
        
        if (!!system && system.length && system.length > 0) {
            for (var i in system) {
                if (!system[i].legacyId) {
                    continue;
                }
                
                $('#tbl-space-systems').append(
                    $('<tr>').append(
                        $('<td>').text(system[i].description),
                        $('<td>').text(system[i].fixingName),
                        $('<td>').css({'text-align': 'right'}).text(system[i].legacyQuantity),
                        $('<td>').css({'text-align': 'right'}).text(!!system[i].cutout ? system[i].cutout : '0.00'),
                        $('<td>').append(
                            $('<button>').attr('sid', system[i].systemId).addClass('btn btn-sm btn-danger pull-right btn-remove-system').append(
                                $('<i>').addClass('icon-trash')
                            )
                        )
                    )
                );
            }
        } else {
            $('#tbl-space-systems').append($('<tr>').append($('<td>').attr('colspan', 5).text('No system items have been added to the space')));
        }
    }

    
    /**
     * reset the hazard table with new data
     * @param {type} hazards
     * @returns {undefined}
     */
    function resetHazardInformation (hazards) {
        $('#tbl-space-hazards').empty();
        
        if (!!hazards && hazards.length && hazards.length > 0) {
            for (var i in hazards) {
                $('#tbl-space-hazards').append(
                    $('<tr>').append(
                        $('<td>').text(hazards[i].name),
                        $('<td>').text(hazards[i].location)
                    )
                );
            }
        } else {
            $('#tbl-space-hazards').append($('<tr>').append($('<td>').attr('colspan', 4).text('No hazards have been added to the space')));
        }
    }
    
    
    function setTabButtons (tab, suffix, max) {
        if (tab > 1) {
            $('.btn-last'+suffix).removeAttr('disabled');
        } else if (tab == 1) {
            $('.btn-last'+suffix).attr('disabled','disabled');
        } 

        if (tab == max) {
            $('.btn-next'+suffix).attr('disabled','disabled');
        } else if (tab < max) {
            $('.btn-next'+suffix).removeAttr('disabled');
        }
    }
    
    
    // next button press
    $('.btn-next-bs').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectSiteSurvey li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab < 5)?activeTab+1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab, '-bs', 5);
            $('a[href=#widgetBS_tab'+nextTab+']').tab('show');
        }
        
    });
    
    // last button press
    $('.btn-last-bs').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectSiteSurvey li.active a").attr('data-number');
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
    
    
    /**
     * change events for legacy power related data
     */
    $('#LegacyConfigForm input[name=quantity], #LegacyConfigForm input[name=pwr_item], #LegacyConfigForm input[name=pwr_ballast]').on('change', function(e) {
        try {
            var qty = parseInt($('#LegacyConfigForm input[name=quantity]').val());
            var pwrItem = parseInt($('#LegacyConfigForm input[name=pwr_item]').val());
            var pwrBallast = parseInt($('#LegacyConfigForm input[name=pwr_ballast]').val());

            $('#total-pwr').val((qty*pwrItem) + pwrBallast);
        } catch (e) {
            $('#total-pwr').val(0);
        }
        
    });
    
    /**
     * add legacy light item to catalog
     */
    $('#btn-add-legacy').on('click', function(e) {
        e.preventDefault();
        var url = $('#LegacyConfigForm').attr('action');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#LegacyConfigForm').serialize();

        $('#setupTabPanelLoader').fadeIn(function(){
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
                        if (obj.err === true) {
                            growl('Error!', 'The legacy product could not be added to the system catalog.', {time: 3000});
                        } else{ // no errors
                            growl('Success!', 'The legacy product has been successfully added to the system catalog.', {time: 3000});
                            $('#LegacyConfigForm select[name="category"]').val('');
                            $('#LegacyConfigForm input[name="description"]').val('');
                            $('#LegacyConfigForm input[name="dim_item"]').val('');
                            $('#LegacyConfigForm input[name="dim_unit"]').val('');
                            $('#LegacyConfigForm input[name="quantity"]').val('1');
                            $('#LegacyConfigForm input[name="pwr_item"]').val('0');
                            $('#LegacyConfigForm input[name="pwr_ballast"]').val('0');
                            $('#LegacyConfigForm input[name="pwr_item"]').trigger('change');
                            
                            reloadLegacyData();
                        }
                    }
                    catch(error){ 
                        growl('Error!', 'The legacy product could not be added to the system catalog.', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupTabPanelLoader').fadeOut(function(){});
                }
            });
        });

    });
    
    /**
     * function used to get latest legacy item list
     * @returns {undefined}
     */
    function reloadLegacyData () {
        var url = '/legacy/listall';
        var params = 'ts='+Math.round(new Date().getTime()/1000);

        $('#spaceLoader').fadeIn(function(){
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
                        if (obj.err === true) {
                            growl('Error!', 'Could not reload legacy fitting data', {time: 3000});
                        } else{ // no errors
                            var legacyList = $('#legacyFitting');
                            legacyList.empty();
                            
                            legacyList.append($('<option>').text('Select Legacy Fitting'));
                            
                            var groups = {};
                            for (var i in obj.legacy) {
                                if (!groups[obj.legacy[i].category]) {
                                    groups[obj.legacy[i].category] = [];
                                }
                                
                                groups[obj.legacy[i].category].push(obj.legacy[i]);
                            }
                            
                            for (i in groups) {
                                var group = $('<optgroup>').attr('label', i);
                                for (j in groups[i]) {
                                    group.append($('<option>')
                                        .val(groups[i][j].legacyId)
                                        .attr('data-pwr', ((groups[i][j].quantity * groups[i][j].pwr_item) + groups[i][j].pwr_ballast))
                                        .attr('data-pid', groups[i][j].productId)
                                        .attr('data-mcpu', groups[i][j].maintenance)
                                        .text(groups[i][j].description));
                                }
                                legacyList.append(group);
                            }
                        }
                    }
                    catch(error){ 
                        growl('Error!', 'Could not reload legacy fitting data', {time: 3000});
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#spaceLoader').fadeOut(function(){});
                }
            });
        });
    }

    // load the initial space data
    loadSpaceData();
}();