$ = jQuery.noConflict();

var ct_current_role = '';
var ct_current_orderby = '';
var ct_current_order = '';
var ct_current_page = 1;

$(function(){
    $('a[data-filter-role]').click(function(){
        $('.roles-list-nav a').removeClass('current');
        $(this).addClass('current');

        load_users_ajax($(this).attr('data-filter-role'), ct_current_orderby, ct_current_order, ct_current_page);
        return false;
    });

    $('a[data-sort-orderby]').click(function(){
        load_users_ajax(ct_current_role, $(this).attr('data-sort-orderby'), $(this).attr('data-sort-order'), ct_current_page);
        return false;
    });
});

function load_users_ajax(role, orderby, order, paged){
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
                row.find('#user_name_link').text(item.user_name).prop( 'href', item.user_link);
                row.find('#display_name').text(item.display_name);
                row.find('#user_email').text(item.user_email);
                row.find('#user_roles').text(item.user_roles);
                container.append(row);
            })
        }
    });
    return false;
}
