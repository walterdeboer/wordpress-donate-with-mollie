<?php

/**
 * Toont de lijst met donaties
 */
class Dmm_List_Table extends WP_List_Table
{
    public function __construct() {
        parent::__construct(
            array(
                'singular' => __('donation', 'doneren-met-mollie'),
                'plural'   => __('donations', 'doneren-met-mollie'),
                'ajax'     => false
            )
        );
    }

    function get_columns(){
        $dmm_fields = get_option('dmm_form_fields');

        $columns = array();
        $columns['cb'] = '<input type="checkbox">';
        $columns['time'] = __('Date/time', 'doneren-met-mollie');

        if (isset($dmm_fields['Name']['active']) && $dmm_fields['Name']['active'])
            $columns['dm_name'] = __('Name', 'doneren-met-mollie');

        if (isset($dmm_fields['Company name']['active']) && $dmm_fields['Company name']['active'])
            $columns['dm_company'] = __('Company name', 'doneren-met-mollie');

        if (isset($dmm_fields['Email address']['active']) && $dmm_fields['Email address']['active'])
            $columns['dm_email'] = __('Email address', 'doneren-met-mollie');

        $columns['dm_amount'] = __('Amount', 'doneren-met-mollie');

        if (isset($dmm_fields['Project']['active']) && $dmm_fields['Project']['active'])
            $columns['dm_project'] = __('Project', 'doneren-met-mollie');

        $columns['dm_status'] = __('Status', 'doneren-met-mollie');
        $columns['donation_id'] = __('Donation ID', 'doneren-met-mollie');

        return $columns;
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            'dmm_donation',
            $item['id']
        );
    }

    function column_donation_id($item){
        $url_refund = wp_nonce_url('?page=doneren-met-mollie&action=refund&payment=' . $item['payment_id'], 'refund-donation_' . $item['payment_id']);
        $url_delete = wp_nonce_url('?page=doneren-met-mollie&action=delete&payment=' . $item['payment_id'], 'delete-donation_' . $item['payment_id']);
        $url_view = '?page=doneren-met-mollie-donatie&id=' . $item['id'];

        $actions = array();
        $actions['view'] = sprintf('<a href="%s">' . esc_html__('View', 'doneren-met-mollie') . '</a>', $url_view);

        if ($item['dm_status'] === 'paid' && $item['dm_amount'] > 0.30)
            $actions['refund'] = sprintf('<a href="%s" style="color:#a00;" onclick="return confirm(\'' . __('Are you sure?', 'doneren-met-mollie') . '\')">' . esc_html__('Refund', 'doneren-met-mollie') . '</a>', $url_refund);

        $actions['delete'] = sprintf('<a href="%s" style="color:#a00;" onclick="return confirm(\'' . __('Are you sure?', 'doneren-met-mollie') . '\')">' . esc_html__('Delete', 'doneren-met-mollie') . '</a>', $url_delete);

        //Return the title contents
        return sprintf('%1$s %2$s',
            $item['donation_id'],
            $this->row_actions($actions)
        );
    }

    function prepare_items() {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $where = '';
        if (isset($_GET['subscription'])) {
            $where .= ' WHERE subscription_id="' . esc_sql(sanitize_title_for_query($_GET['subscription'])) . '"';
        }

        if (isset($_GET['search'])) {
            $search = sanitize_title_for_query($_GET['search']);
            $where .= ($where ? ' AND' : ' WHERE') . ' (dm_name LIKE "%' . esc_sql($search) . '%" OR dm_email LIKE "%' . esc_sql($search) . '%" OR dm_company LIKE "%' . esc_sql($search) . '%" OR donation_id LIKE "%' . esc_sql($search) . '%" OR payment_id LIKE "%' . esc_sql($search) . '%")';
        }

        $donations = $wpdb->get_results("SELECT * FROM " . DMM_TABLE_DONATIONS . $where . " ORDER BY time DESC", ARRAY_A);

        $per_page = 25;
        $current_page = $this->get_pagenum();
        $total_items = count($donations);

        $d = array_slice($donations,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
        $this->items = $d;

        $this->process_bulk_action();
    }

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );

        return $actions;
    }

    function process_bulk_action() {
        global $wpdb;

        if ('delete' === $this->current_action()) {
            foreach ($_POST['dmm_donation'] as $donation) {
                $wpdb->query($wpdb->prepare("DELETE FROM " . DMM_TABLE_DONATIONS . " WHERE id = %d",
                    $donation
                ));
            }

            wp_redirect('?page=' . sanitize_text_field($_REQUEST['page']) . '&msg=delete-ok');
        }
    }

    function statusName( $status ) {
        switch( $status ) {
            case 'open':
                return __('Open', 'doneren-met-mollie');
            case 'canceled':
            case 'cancelled':
                return __('Cancelled', 'doneren-met-mollie');
            case 'pending':
                return __('Pending', 'doneren-met-mollie');
            case 'expired':
                return __('Expired', 'doneren-met-mollie');
            case 'paid':
                return __('Paid', 'doneren-met-mollie');
            case 'paidout':
                return __('Paid out', 'doneren-met-mollie');
            case 'refunded':
                return __('Refunded', 'doneren-met-mollie');
            case 'charged_back':
                return __('Charged back', 'doneren-met-mollie');
            default:
                return $status;
        }
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'dm_amount':
                return ($item['payment_method'] ? '<img valign="top" src="https://www.mollie.com/images/payscreen/methods/' . $item['payment_method'] . '.png" width="18"> ' : '') . dmm_get_currency_symbol($item['dm_currency']) . ' ' . number_format($item[ $column_name ], dmm_get_currencies($item['dm_currency']), ',', '') . ' ' . (isset($item['customer_id']) && $item['customer_id'] ? '<small>(recurring)</small>' : '');
                break;
            case 'time':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item[ $column_name ]));
            case 'dm_status':
                return $this->statusName($item[ $column_name ]) . ($item['payment_mode'] == 'test' ? ' <small>(' . $item['payment_mode'] . ')</small>' : '');
            case 'dm_email':
            case 'dm_name':
            case 'dm_company':
            case 'dm_project':
            case 'donation_id':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }

    public function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <?php $this->pagination( $which );?>
            <br class="clear" />
        </div>
        <?php
    }
}
