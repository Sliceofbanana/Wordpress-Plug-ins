<?php
/**
 * Plugin Name: Newsletter Collector
 * Description: A simple plugin to collect newsletter emails and view them in the WordPress dashboard.
 * Version: 2.0
 * Author: Brent and Genesis Jr
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ✅ Create database table on activation
register_activation_hook( __FILE__, 'nc_create_table' );
function nc_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsletter_emails';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// ✅ Shortcode for subscription form
add_shortcode( 'newsletter_form', 'nc_form_shortcode' );
function nc_form_shortcode() {
    ob_start(); ?>
    <form method="post" class="newsletter-form">
        <input type="email" name="nc_email" placeholder="Enter your email" class="newsletter-input" required>
        <button type="submit" name="nc_submit" class="newsletter-button">Subscribe</button>
    </form>
    <?php
    return ob_get_clean();
}

// ✅ Handle form submission
add_action( 'init', 'nc_handle_form' );
function nc_handle_form() {
    if ( isset($_POST['nc_submit']) && ! empty($_POST['nc_email']) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsletter_emails';
        $email = sanitize_email( $_POST['nc_email'] );

        if ( is_email( $email ) ) {
            $wpdb->insert(
                $table_name,
                array( 'email' => $email ),
                array( '%s' )
            );
        }
    }
}

// ✅ Add admin menu
add_action( 'admin_menu', 'nc_admin_menu' );
function nc_admin_menu() {
    add_menu_page(
        'Newsletter Emails',
        'Newsletter Emails',
        'manage_options',
        'newsletter-collector',
        'nc_admin_page',
        'dashicons-email-alt',
        20
    );
}

// ✅ Admin page to display emails
function nc_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsletter_emails';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    echo "<div class='wrap'><h1>Collected Emails</h1>";

    if ( $results ) {
        echo "<table class='widefat fixed'>";
        echo "<thead><tr><th>ID</th><th>Email</th><th>Date</th></tr></thead><tbody>";
        foreach ( $results as $row ) {
            echo "<tr><td>{$row->id}</td><td>{$row->email}</td><td>{$row->created_at}</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No emails collected yet.</p>";
    }

    echo "<br><a href='" . admin_url('admin-post.php?action=nc_export_csv') . "' class='button button-primary'>Export CSV</a>";
    echo "</div>";
}

// ✅ Export emails as CSV
add_action( 'admin_post_nc_export_csv', 'nc_export_csv' );
function nc_export_csv() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsletter_emails';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=newsletter-emails.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Email', 'Date'));

    foreach ( $results as $row ) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
