var Script = function () {
//toggle button
    window.prettyPrint && prettyPrint();

    $('#active-toggle-button').toggleButtons({
        width: 160,
        label: {
            enabled: "Yes",
            disabled: "No"
        },
        style: {
            // Accepted values ["primary", "danger", "info", "success", "warning"] or nothing
            enabled: "success",
            disabled: "danger"
        }
    });
    
    function setTabButtons (tab) {
        if (tab > 1) {
            $('#btn-last').removeAttr('disabled');
        } else if (tab == 1) {
            $('#btn-last').attr('disabled','disabled');
        } 

        if (tab == 3) {
            $('#btn-next').attr('disabled','disabled');
        } else if (tab < 3) {
            $('#btn-next').removeAttr('disabled');
        }
    }
    
    $('#btn-next').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsAddProduct li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab<3)?activeTab+1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab);
            $('a[href=#widget_tab'+nextTab+']').tab('show');
        }
        
    });
    
    
    
    // last button press
    $('#btn-last').on('click', function(e) {
        e.preventDefault();
        var activeTab = $("ul#tabsAddProduct li.active a").attr('data-number');
        if (activeTab == undefined) {
            return false;
        }
        
        activeTab = parseInt(activeTab);
        var nextTab = (activeTab>1)?activeTab-1:activeTab;
        
        if (activeTab != nextTab) {
            setTabButtons (nextTab);
            $('a[href=#widget_tab'+nextTab+']').tab('show');
        }
        
    });
    
        // begin first table
    $('#legacy_tbl').dataTable({
        "sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap",
        "oLanguage": {
            "sLengthMenu": "_MENU_ records per page",
            "oPaginate": {
                "sPrevious": "Prev",
                "sNext": "Next"
            }
        },
        bProcessing: false,
        bServerSide: true,
        iDisplayLength:15,
        aLengthMenu: [[5, 10, 15, 20, 25, 50], [5, 10, 15, 20, 25, 50]],
        "aoColumns": [
            null,
            { "sClass": "hidden-phone" },
            { 'bSortable': false, "sClass": "hidden-phone" },
            { "sClass": "hidden-phone" },
            { 'bSortable': false }
        ],
        sAjaxSource: "/legacy/list/"
    });

    jQuery('#legacy_tbl .group-checkable').change(function () {
        var set = jQuery(this).attr("data-set");
        var checked = jQuery(this).is(":checked");
        jQuery(set).each(function () {
            if (checked) {
                $(this).attr("checked", true);
            } else {
                $(this).attr("checked", false);
            }
        });
        jQuery.uniform.update(set);
    });

    jQuery('#legacy_tbl_wrapper .dataTables_filter input').addClass("input-medium"); // modify table search input
    jQuery('#legacy_tbl_wrapper .dataTables_length select').addClass("input-mini"); // modify table per page dropdown

        
    $('#legacy-add-btn').on('click', function(e){
        e.preventDefault();
        $('#modalLegacyAdd').modal();
        return false;
    });
    
    $('#LegacyConfigForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            resetFormErrors($(this).attr('name'));
            $('#productMsgs').empty();
            var url = $(this).attr('action');
            var params = 'ts='+Math.round(new Date().getTime()/1000)+'&'+$(this).serialize();
            
            $('#legacyAddLoader').fadeIn(function(){
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
                                            additional+=obj.info[i]+'<br>';
                                        }
                                        if (tab>1){
                                            switch (i) {
                                                case 'category': case 'description': case 'emergency': case 'dim_item': case 'dim_unit': tab = 1; break;
                                                case 'quantity': case 'pwr_item': case 'pwr_ballast': if (tab>=2) tab = 2; break;
                                                case 'product': if (tab>=3) tab = 3; break;
                                            }
                                        }
                                    }
                                }

                                if (additional != '') {
                                    msgAlert('productMsgs',{
                                        mode: 3,
                                        body: 'Error: '+additional,
                                        empty: true
                                    });
                                }

                                $('ul#tabsAddProduct a[href=#widget_tab'+tab+']').tab('show');

                            } else{ // no errors
                                window.location.reload();
                            }
                        }
                        catch(error){

                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#legacyAddLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
    });
    
    $('#btn-legacy-add').on('click', function(e) {
        e.preventDefault();
        $('#LegacyConfigForm').submit();
        return false;
    });
    
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

}();