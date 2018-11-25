$ = jQuery.noConflict();

var ct_current_role = '';
var ct_current_orderby = '';
var ct_current_order = '';
var ct_current_page = 1;

$(function(){
    $('.tablenav-pages select').on('change', function(){
        load_users_ajax(ct_current_role, ct_current_orderby, ct_current_order, $(this).val());
    });

    $('a[data-filter-role]').click(function(){
        $('.roles-list-nav a').removeClass('current');
        $(this).addClass('current');

        load_users_ajax($(this).attr('data-filter-role'), ct_current_orderby, ct_current_order, 1);
        return false;
    });

    $('a[data-sort-orderby]').click(function(){
        // Remove focus outlines to fix visual bug with sorting indicator.
        $(this).blur();

        // First time
        if( $(this).parent('th').hasClass('sortable') ){
            $('.users-list-table th.sorted')
                .removeClass('sorted asc')
                .addClass('sortable desc')
                .find('a').attr('data-sort-order', 'asc');

            $(this).attr('data-sort-order', 'asc');
        }

        load_users_ajax(ct_current_role, $(this).attr('data-sort-orderby'), $(this).attr('data-sort-order'), ct_current_page);

        var new_order_dir = $(this).attr('data-sort-order') === 'asc' ? 'desc' : 'asc';

        $(this)
            .attr('data-sort-order', new_order_dir)
            .parent('th')
            .removeClass('sortable')
            .addClass('sorted')
            .toggleClass('asc desc');

        return false;
    });

});

function load_users_ajax( role, orderby, order, paged ){
    var postData = {
        'action': 'load_users',
        'role': role,
        'orderby': orderby,
        'order': order,
        'paged': paged
    };

    ct_current_role = role;
    ct_current_orderby = orderby;
    ct_current_order = order;
    ct_current_page = paged;

    // TODO: remove
    console.log(role, orderby, order, paged);

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: ajaxurl,
        data: postData,
        success: function( res ){
            var container = $('#list_table_body');
            container.html('');
            if( !res || !res.total_found ){
                return false;
            }

            $.each(res.found_items, function( i, item ){
                var row = $($('#user_table_row').html());
                row.find('#user_name_link').text(item.user_name).prop('href', item.user_link);
                row.find('#display_name').text(item.display_name);
                row.find('#user_email').text(item.user_email);
                row.find('#user_roles').text(item.user_roles);
                container.append(row);
            })

            if( res.total_pages && $('.tablenav-pages select:first option').length !== res.total_pages ){
                $('.tablenav-pages select').empty();

                for(var x = 1; x <= parseInt(res.total_pages); x++){
                    $('.tablenav-pages select').append($('<option/>').prop('value', x).text(x));
                }
            }

            $('.tablenav-pages .displaying-num').text(res.total_found_formatted);
            $('.tablenav-pages .total-pages').text(res.total_pages_formatted);

            if( res.total_pages < 2 ){
                $('.tablenav-pages').addClass('one-page');
            } else {
                $('.tablenav-pages').removeClass('one-page');
            }
        }
    });
    return false;
}
