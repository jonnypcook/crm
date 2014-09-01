var Script = function () {
    // begin first table
    $('#products_tbl').dataTable({
        sDom: "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
        sPaginationType: "bootstrap",
        oLanguage: {
            sLengthMenu: "_MENU_ per page",
            oPaginate: {
                sPrevious: "",
                sNext: ""
            }
        },
        aoColumnDefs: [{
            'bSortable': false,
            'aTargets': [0]
        }]
    });

    jQuery('#products_tbl_wrapper .dataTables_filter input').addClass("input-medium"); // modify table search input
    jQuery('#products_tbl_wrapper .dataTables_length select').addClass("input-mini"); // modify table per page dropdown
    
}();
        