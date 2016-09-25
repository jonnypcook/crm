var Script = function () {
    $(".chzn-select").chosen(); 
    //toggle button

    window.prettyPrint && prettyPrint();

    
    
    $('input[name=surveyed]').datepicker({
        format: 'dd/mm/yyyy'
    }).on('changeDate', function (e) {
        $('input[name=surveyed]').datepicker('hide').blur();
    });
    
    $('#btn-update-project').on('click', function (e) {
        e.preventDefault();
        
        
        return false;
    });
    
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

        $('#updateProjectLoader').fadeIn(function () {
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
                    $('#updateProjectLoader').fadeOut(function(){});
                }
            });            
        });
        return false;
    });
    
    $('#btn-space-add-new').on('click', function (e) {
        e.preventDefault();
        var url = $('#frmAddSpace').attr('action');
        var params = 'ts='+Math.round(new Date().getTime()/1000) + '&' + $('#frmAddSpace').serialize() + '&buildingId=' + $('#branches-spaces').val();
        
        $('#addSpaceLoader').fadeIn(function () {
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
                    $('#addSpaceLoader').fadeOut(function(){});
                }
            });            
        });
        
        return false;
    });
    
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
        
        $('#addSpaceLoader').fadeIn(function () {
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
                    $('#addSpaceLoader').fadeOut(function(){});
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
    });
    
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
                            resetSystemAddForm();
                            
                            showSpaceDetails (findSpaceCompletePC() < 70, true);
                            
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
                        console.log(response); return;
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
                            resetSystemAddForm();
                            
                            showSpaceDetails (findSpaceCompletePC() < 70, true);
                            
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
        
        $('#pcCompleteSpace').text(findSpaceCompletePC());
    }
    
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
            $('#tbl-space-systems').append(
                $('<tr>').append(
                    $('<td>').attr('colspan', 4).text('No system items have been added to the space')
                )
            );
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
        var activeTab = $("ul#tabsProjectBluePaper li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab<6)?activeTab+1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab, '-bs', 6);
            $('a[href=#widgetBS_tab'+nextTab+']').tab('show');
        }
        
    });
    
    // last button press
    $('.btn-last-bs').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsProjectBluePaper li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab>1)?activeTab-1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab, '-bs', 6);
            $('a[href=#widgetBS_tab'+nextTab+']').tab('show');
        }
        
    });
    
    loadSpaceData();

}();