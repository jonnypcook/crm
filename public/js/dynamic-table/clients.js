var Script = function () {

        // begin first table
        $('#clients_tbl').dataTable({
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
                null,
                { 'bSortable': false, "sClass": "hidden-phone" },
                { 'bSortable': false, "sClass": "hidden-phone" },
                { "sClass": "hidden-phone" },
                null,
                { 'bSortable': false }
            ],
            sAjaxSource: "/client/list/"
        });
        
        $(document).on('click', '.action-client-edit', function(e) {
           document.location = '/client-'+$(this).attr('pid'); 
        });

        jQuery('#clients_tbl .group-checkable').change(function () {
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

        jQuery('#clients_tbl_wrapper .dataTables_filter input').addClass("input-medium"); // modify table search input
        jQuery('#clients_tbl_wrapper .dataTables_length select').addClass("input-mini"); // modify table per page dropdown

}();