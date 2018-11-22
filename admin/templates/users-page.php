<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Users', 'ct-admin-list' ); ?></h1>

    <hr class="wp-header-end">

    <h2 class="screen-reader-text"><?php esc_html_e( 'Filter users list', 'ct-admin-list' ); ?></h2>

    <form method="get">

        <div class="tablenav top">

            <ul class="subsubsub">
                <li class="all">Filters: </li>
                <li class="all"><a href="users.php" class="current" aria-current="page">All <span class="count">(<?php echo $this->total_found; ?>)</span></a> |</li>
                <li class="administrator"><a href="users.php?role=administrator">Administrator <span class="count">(12)</span></a> |</li>
                <li class="editor"><a href="users.php?role=editor">Editor <span class="count">(11)</span></a> |</li>
                <li class="author"><a href="users.php?role=author">Author <span class="count">(11)</span></a> |</li>
                <li class="contributor"><a href="users.php?role=contributor">Contributor <span class="count">(11)</span></a> |</li>
                <li class="subscriber"><a href="users.php?role=subscriber">Subscriber <span class="count">(11)</span></a></li>
            </ul>

            <h2 class="screen-reader-text">Users list navigation</h2>
            <div class="tablenav-pages">
                <?php echo $this->paginator(); ?>
            </div>
            <br class="clear">
        </div>

        <h2 class="screen-reader-text">Users list</h2>
        <table class="wp-list-table widefat fixed striped users">
            <thead>
            <tr>
                <th scope="col" id="username" class="manage-column column-username column-primary sortable desc">
                    <a href="users.php?orderby=login&amp;order=asc"><span><?php esc_html_e('Username', ''); ?></span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" id="name" class="manage-column column-name">Name</th>
                <th scope="col" id="email" class="manage-column column-email">Email</th>
                <th scope="col" id="role" class="manage-column column-role">Role</th>
            </tr>
            </thead>

            <tbody id="list_table_body">

            <?php if( $this->found_items ) : ?>

            <?php foreach( $this->found_items as $user ) : ?>

            <tr>
                <td><strong><a href="<?php echo get_edit_user_link($user->ID); ?>"><?php echo $user->user_login; ?></a></strong></td>
                <td><?php echo $user->display_name; ?></td>
                <td><?php echo $user->user_email; ?></td>
                <td><?php echo $this->format_roles($user->roles); ?></td>
            </tr>

            <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

        <div class="tablenav bottom">

            <div class="tablenav-pages"><span class="displaying-num"></span>
                <?php echo $this->paginator(); ?>
            <br class="clear">
        </div>
    </form>

    <br class="clear">
</div>
