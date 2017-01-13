var Script = function () {
    // begin first table
    var requestUrl = $('#emergency_summary_tbl').attr('data-request-url');
    var tableErrors = $('#emergency_errors_tbl tbody');
    var tableWarnings = $('#emergency_warnings_tbl tbody');
    var sectionErrors = $('#emergency_errors_section');
    var sectionWarnings = $('#emergency_warnings_section');
    var errorCount = $('#errorCount');
    var warningCount = $('#warningCount');
    var devicesCount = $('#devicesCount');
    var synchronizeBtn = $('#btn-synchronize');
    var colspanErrors = 4;
    var colspanWarnings = 4;

    synchronizeBtn.on('click', function(e) {
        reloadEmergencyReport(true);
    });

    function reloadEmergencyReport (synchronize) {
        try {
            var url = requestUrl.replace(/[%][a]/, 'emergencyreport') + "?synchronize=" + (!!synchronize ? 1 : 0);
            var params = 'ts='+Math.round(new Date().getTime()/1000);
            setEmergencyLoading();
            $('#emergencyLoader').fadeIn(function(){
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
                            if (obj.err !== true) {
                                tableErrors.empty();
                                errorCount.text(obj.report.count.errors);
                                warningCount.text(obj.report.count.warnings);
                                devicesCount.text(obj.report.count.devices);

                                setEmergencyErrors(obj.report.errors);
                                setEmergencyWarnings(obj.report.warnings);
                            } else {
                                throw "emergency report data not found";
                            }
                        }
                        catch(error){
                            setEmergencyNoResults();
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#emergencyLoader').fadeOut();
                    }
                });
            });

        } catch (ex) {
            setEmergencyNoResults();
            $('#emergencyLoader').fadeOut();
        }/**/
    }

    function setEmergencyErrors(errors) {
        tableErrors.empty();
        if (!errors || errors.length === 0) {
            sectionErrors.hide();
            return;
        }

        for (var floor in errors) {
            for (var i in errors[floor]) {
                tableErrors.append(
                    $('<tr>').append(
                        $('<td>').text(errors[floor][i][1]),
                        $('<td>').text(errors[floor][i][2]),
                        $('<td>').text(floor),
                        $('<td>').text(errors[floor][i][3])
                    )
                );
            }
        }

        sectionErrors.show();
    }

    function setEmergencyWarnings(warnings) {
        tableWarnings.empty();
        if (!warnings || warnings.length === 0) {
            sectionWarnings.hide();
            return;
        }
        for (var floor in warnings) {
            for (var i in warnings[floor]) {
                tableWarnings.append(
                    $('<tr>').append(
                        $('<td>').text(warnings[floor][i][1]),
                        $('<td>').text(warnings[floor][i][2] + ' days untested'),
                        $('<td>').text(floor),
                        $('<td>').text(warnings[floor][i][3])
                    )
                );
            }
        }
        sectionWarnings.show();
    }

    function setEmergencyLoading() {
        sectionErrors.hide();
        sectionWarnings.hide();
        errorCount.text('0');
        warningCount.text('0');
        devicesCount.text('0');
    }

    function setEmergencyNoResults() {
        sectionErrors.hide();
        sectionWarnings.hide();
        errorCount.text('error running emergency report');
        warningCount.text('error running emergency report');
        devicesCount.text('error running emergency report');
    }

    $(function() {
        reloadEmergencyReport();
    });

}();