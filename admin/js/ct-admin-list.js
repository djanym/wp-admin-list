$ = jQuery.noConflict();

var ct_current_role = '';
var ct_current_orderby = '';
var ct_current_order = '';
var ct_current_page = 1;
var ct_total_pages = 1;

$(function(){
    // Makes request after page selector changed.
    $('.tablenav-pages select').on('change', function(){
        // Assigns to all select tags the same new value
        $('.tablenav-pages select').val($(this).val());
        load_users_ajax(ct_current_role, ct_current_orderby, ct_current_order, $(this).val());
    });

    // Changes page number.
    $('.tablenav-pages .first-page').on('click', function(){
        if( $(this).attr('disabled') ){
            return false;
        }
        ct_current_page = 1;
    });

    // Changes page number.
    $('.tablenav-pages .prev-page').on('click', function(){
        if( $(this).attr('disabled') ){
            return false;
        }
        ct_current_page -= 1;
    });

    // Changes page number.
    $('.tablenav-pages .next-page').on('click', function(){
        if( $(this).attr('disabled') ){
            return false;
        }
        ct_current_page += 1;
    });

    // Changes page number.
    $('.tablenav-pages .last-page').on('click', function(){
        if( $(this).attr('disabled') ){
            return false;
        }
        ct_current_page = ct_total_pages;
    });

    // Makes request after paginator navigation clicked.
    $('.tablenav-pages .first-page, .tablenav-pages .prev-page, .tablenav-pages .next-page, .tablenav-pages .last-page').on('click', function(){
        if( $(this).attr('disabled') ){
            return false;
        }

        // Assigns to all select tags the same new value
        $('.tablenav-pages select').val(ct_current_page);
        load_users_ajax(ct_current_role, ct_current_orderby, ct_current_order, ct_current_page);
        return false;
    });

    // Makes request after role navigation clicked.
    $('a[data-filter-role]').click(function(){
        $('.roles-list-nav a').removeClass('current');
        $(this).addClass('current');

        load_users_ajax($(this).attr('data-filter-role'), ct_current_orderby, ct_current_order, 1);
        return false;
    });

    // Makes request after role navigation clicked.
    $('a[data-sort-orderby]').click(function(){
        // Remove focus outlines to fix visual bug with sorting indicator.
        $(this).blur();

        // If order by field changes to another field, then we should set default order direction.
        if( $(this).parent('th').hasClass('sortable') ){
            // Change previous sorting field to sortable state
            $('.users-list-table th.sorted')
                .removeClass('sorted asc')
                .addClass('sortable desc');

            $(this).attr('data-sort-order', 'asc');
        }

        load_users_ajax(ct_current_role, $(this).attr('data-sort-orderby'), $(this).attr('data-sort-order'), ct_current_page);

        // Set new order direction for the next request.
        var new_order_dir = $(this).attr('data-sort-order') === 'asc' ? 'desc' : 'asc';
        $(this).attr('data-sort-order', new_order_dir);

        // Change/toggle visual part
        $(this).parent('th')
            .removeClass('sortable')
            .addClass('sorted')
            .toggleClass('asc desc');

        return false;
    });

});

/**
 * Makes an ajax query with filter/sorting options. Then generates results HTML.
 *
 * @param role
 * @param orderby
 * @param order
 * @param paged
 *
 * @returns {boolean}
 */
function load_users_ajax( role, orderby, order, paged ){
    var postData = {
        'action': 'load_users',
        'role': role,
        'orderby': orderby,
        'order': order,
        'paged': paged
    };

    // Set passed arguments as current query options
    ct_current_role = role;
    ct_current_orderby = orderby;
    ct_current_order = order;
    ct_current_page = parseInt(paged);

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: ajaxurl,
        data: postData,
        success: function( res ){
            var container = $('#list_table_body');
            container.html('');

            if( !res || !res.total_found || !res.found_items.length ){
                var row = $($('#user_table_noresults').html());
                container.append(row);
                return;
            }

            ct_total_pages = res.total_pages;

            // Create table row for each result item
            $.each(res.found_items, function( i, item ){
                var row = $($('#user_table_row').html());
                row.find('#user_name_link').text(item.user_name).prop('href', item.user_link);
                row.find('#display_name').html(item.display_name);
                row.find('#user_email').text(item.user_email);
                row.find('#user_roles').html(item.user_roles);
                container.append(row);
            })

            // If total page number changed, then we should regenerate select options
            if( res.total_pages && $('.tablenav-pages select:first option').length !== res.total_pages ){
                $('.tablenav-pages select').empty();

                for(var x = 1; x <= parseInt(res.total_pages); x++){
                    $('.tablenav-pages select').append($('<option/>').prop('value', x).text(x));
                }
            }

            // Disable/enable first page link depending on current page
            if( ct_current_page <= 2 ){
                $('.tablenav-pages .first-page').attr('disabled', 'disabled');
            } else{
                $('.tablenav-pages .first-page').removeAttr('disabled');
            }

            // Disable/enable previous page status depending on current page
            if( ct_current_page <= 1 ){
                $('.tablenav-pages .prev-page').attr('disabled', 'disabled');
            } else{
                $('.tablenav-pages .prev-page').removeAttr('disabled');
            }

            // Disable/enable next page link depending on current page & total pages
            if( ct_current_page + 1 > res.total_pages ){
                $('.tablenav-pages .next-page').attr('disabled', 'disabled');
            } else{
                $('.tablenav-pages .next-page').removeAttr('disabled');
            }

            // Disable/enable last page link depending on current page & total pages
            if( ct_current_page + 2 > res.total_pages ){
                $('.tablenav-pages .last-page').attr('disabled', 'disabled');
            } else{
                $('.tablenav-pages .last-page').removeAttr('disabled');
            }

            // Change labels to new total found count
            $('.tablenav-pages .displaying-num').text(res.total_found_formatted);
            $('.tablenav-pages .total-pages').text(res.total_pages_formatted);

            // Hide paginator if only one page.
            if( res.total_pages < 2 ){
                $('.tablenav-pages').addClass('one-page');
            } else{
                $('.tablenav-pages').removeClass('one-page');
            }
        }
    });
    return false;
}
