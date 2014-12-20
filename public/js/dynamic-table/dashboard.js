var Script = function () {
    // begin contacts table
    $('#contacts_tbl').dataTable({
        sDom: "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span4'i><'span8'p>>",
        sPaginationType: "bootstrap",
        iDisplayLength:3,
        aLengthMenu: [[3, 5, 10, 15, 20], [3, 5, 10, 15, 20]],
        bProcessing: false,
        bServerSide: true,
        oLanguage: {
            sLengthMenu: "_MENU_ per page",
            oPaginate: {
                sPrevious: "",
                sNext: ""
            },
            sInfo: "_START_ to _END_ of _TOTAL_",
            sInfoFiltered: ""
        },
        aoColumnDefs: [{
            'bSortable': false,
            'aTargets': [0]
        }],
        sAjaxSource: "/contact/list/?mini=1"
    });

    jQuery('#contacts_tbl_wrapper .dataTables_filter input').addClass("input-small"); // modify table search input
    jQuery('#contacts_tbl_wrapper .dataTables_length select').addClass("input-mini"); // modify table per page dropdown
    
    
    $(document).on('click', '.contact-info', function(e) {
        e.preventDefault(); 
       //<a href="tel:+4411122233344">+44 (0)111 - 222 333 44</a>
        $('#contact-tel1').html($(this).attr('data-tel1').length?'<a href="tel:'+$(this).attr('data-tel1')+'">'+$(this).attr('data-tel1')+' <i class="icon-phone"></i></a>':'');
        $('#contact-tel2').html($(this).attr('data-tel2').length?'<a href="tel:'+$(this).attr('data-tel2')+'">'+$(this).attr('data-tel2')+' <i class="icon-phone"></i></a>':'');
        $('#contact-email').html($(this).attr('data-email').length?'<a href="mailto:'+$(this).attr('data-email')+'">'+$(this).attr('data-email')+' <i class="icon-envelope"></i></a>':'');
        $('#contact-addr').text($(this).attr('data-addr'));
        $('#contact-name').text($(this).attr('data-name'));
        $('#contact-company').text($(this).attr('data-company'));
        
        $('#modalContacts').modal('show');
//        console.log(tel1);
        return false;
    });
    
    
}();