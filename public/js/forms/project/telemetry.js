var Script = function () {
    // google gauge
    google.load("visualization", "1", {packages:["gauge"]});
    google.setOnLoadCallback(drawChart);
    
    var currentTotalWatts = 0;
    
    function drawChart() {

      var dataGG = google.visualization.arrayToDataTable([
        ['Label', 'Value'],
        ['Watts', currentTotalWatts]
      ]);

      var options = {
        width: 220, height: 220,
        redFrom: 90, redTo: 100,
        yellowFrom:75, yellowTo: 90,
        minorTicks: 5
      };

      var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

      chart.draw(dataGG, options);

      setInterval(function() {
        dataGG.setValue(0, 1, currentTotalWatts);
        chart.draw(dataGG, options);
      }, 1000);
    }
    
    // setup toggle buttons
    window.prettyPrint && prettyPrint();

    $('.onoff-toggle').toggleButtons({
        width: 100,
        label: {
            enabled: "On",
            disabled: "Off"
        },
        style: {
            // Accepted values ["primary", "danger", "info", "success", "warning"] or nothing
            enabled: "success",
            disabled: "danger"
        },
        onChange: function ($el, status, e) {
            var node = $el.closest('tr');
            if (node==undefined) {
                return false;
            }
            
            var deviceId = node.attr('data-device');
            if (deviceId==undefined) {
                return false;
            }
            
            node.attr('data-paused-iterations', '10');
            
            $.ajax(
            {
                url : "https://testing.wattzo.com/api/hubs/"+serialNumber+'/devices/'+deviceId+'/status',
                type : 'PUT',
                data: 'status='+(status?'ON':'OFF'),
                dataType: 'json',
                beforeSend: function (request)
                {
                    if ((token!=undefined) && (token!=null)) {
                        request.setRequestHeader("Authorization", "Bearer "+token);
                    }
                },
                crossDomain: true,
                success : function(result, textStatus, request)
                {
                    //process the result
                    //console.log(result);
                    //console.log(textStatus);
                    //console.log(request.status);
                },
                error : function(jqXHR, textStatus, errorThrown) {
                    alert('Error: '+jqXHR.status);
                    alert('ErrorThrown: '+errorThrown)
                },
                complete: function(data){
                }
            });                
            /**/
        }
    });/**/
    
//    live chart
    var logme = 1;

    var serialNumber = "01-001-ABC123";
    var token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3dhdHR6by5hY2Nlc3Njb250cm9sLndpbmRvd3MubmV0LyIsImF1ZCI6Imh0dHBzOi8vdGVzdGluZy53YXR0em8uY29tLyIsIm5iZiI6MTQwNjcxMTkwOSwiZXhwIjoyNTM0MDIzMDA3OTksImVtYWlsIjoicmljaGFyZC53aGl0YnJlYWRAOHBvaW50M2xlZC5jby51ayIsInVuaXF1ZV9uYW1lIjoiUmljaGFyZCBXaGl0YnJlYWQiLCJuYW1laWQiOiIwYzg1MjQyMThkNTk0MzZiYWFlODFlNzkwYzZlNTI1ZCIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vYWNjZXNzY29udHJvbHNlcnZpY2UvMjAxMC8wNy9jbGFpbXMvaWRlbnRpdHlwcm92aWRlciI6IldhdHR6byJ9.Bi8ZlsqvW8pcSLhkGw6ALAZUDOsSIFBU_iBGAlYWHmM';
    var serviceUri = 'https://testing.wattzo.com/api/realtime';

    // we use an inline data source in the example, usually data would
    // be fetched from a server
    
    function CallRestAPI(method, endPoint, param, token)
    {
        var weblink = "https://testing.wattzo.com/api/"+endPoint;
        
        $.ajax(
        {
            url : weblink,
            type : method,
            data: param,
            dataType: 'json',
            beforeSend: function (request)
            {
                if ((token!=undefined) && (token!=null)) {
                    request.setRequestHeader("Authorization", "Bearer "+token);
                }
            },
            crossDomain: true,
            success : function(result, textStatus, request)
            {
                //process the result
                console.log(result);
                console.log(textStatus);
                console.log(request.status);
                /*for (i in result.data) {
                    console.log(result.data[i].id);
                }/**/
            },
            error : function(jqXHR, textStatus, errorThrown) {
                alert('Error: '+jqXHR.status);
                alert('ErrorThrown: '+errorThrown)
            }
        });
    }
    
    $(function () {
        var data = [], totalPoints = 100;
        
        $.signalR.hub.url = serviceUri;
        $.connection.hub.qs = "id=" + serialNumber;// + "&JWT="+token;
        $.ajaxSetup({ beforeSend: function (request)
        {
            request.setRequestHeader("Authorization", "Bearer "+token);
        }});

        $.connection.realtime.client.onUsageReceived = function (usage) {
            //console.info("Usage received for hub.", usage.devices);
            var watts = 0;
            for(var i in usage.devices){
                var connected = (usage.devices[i].connectionStatus=='Connected');
                if (!connected) {
                    $('#devices tbody tr[data-device='+usage.devices[i].id+'] td.deviceWatts span.badge').text(0);
                    $('#devices tbody tr[data-device='+usage.devices[i].id+'] .onoff-toggle-mask').show();
                } else {
                    watts+=usage.devices[i].watts;
                    var deviceOn = ((usage.devices[i].powerStatus=='On')|| (usage.devices[i].powerStatus=='Standby'));
                    var iterations = $('#devices tbody tr[data-device='+usage.devices[i].id+']').attr('data-paused-iterations');
                    var toggleChange = true;
                    if (iterations != undefined) {
                        try {
                            iterations = parseInt(iterations);
                            if (iterations>0) {
                                iterations--;
                                $('#devices tbody tr[data-device='+usage.devices[i].id+']').attr('data-paused-iterations',iterations);
                                toggleChange = false;
                            } else {
                                $('#devices tbody tr[data-device='+usage.devices[i].id+']').removeAttr('data-paused-iterations');
                            }
                        } catch (e1) {}
                    }
                    $('#devices tbody tr[data-device='+usage.devices[i].id+'] td.deviceWatts span.badge').text(usage.devices[i].watts);
                    if (toggleChange) {
                        $('#devices tbody tr[data-device='+usage.devices[i].id+'] .onoff-toggle').toggleButtons('setState', deviceOn, true);
                    }
                    $('#devices tbody tr[data-device='+usage.devices[i].id+'] .onoff-toggle-mask').hide();
                    
                }
                
            }/**/
            
            
            if (watts>100) {
                watts = 100;
            }
            
            currentTotalWatts = watts;
            
            plot.setData([ addWattData(watts) ]);
            // since the axes don't change, we don't need to call plot.setupGrid()
            plot.draw();
        };

        $.connection.hub
        .start({ transport: 'longPolling' })
        //.start()
        .done(function() {
                console.info("Subscribed to realtime feed.", serialNumber);
        })
        .fail(function(ex) {
                console.error("Error occurred while subscribing to realtime feed.", ex);
        });

        function getEmptyData() {
            // do a random walk
            while (data.length < totalPoints) {
                data.push(0);
            }

            // zip the generated y values with the x values
            var res = [];
            for (var i = 0; i < data.length; ++i)
                res.push([i, data[i]])
            return res;
        }        
        
        function addWattData(watt) {
            if (data.length > 0)
                data = data.slice(1);

            data.push(watt);
            
            // zip the generated y values with the x values
            var res = [];
            for (var i = 0; i < data.length; ++i)
                res.push([i, data[i]])
            return res;
        }        
        
        // setup plot
        var options = {
            series: { shadowSize: 0 }, // drawing is faster without shadows
            yaxis: { min: 0, max: 100 },
            xaxis: { show: false }
        };
        var plot = $.plot($("#chart-telemetry"), [ getEmptyData() ], options);

        plot.draw();
    });
    
    
}();