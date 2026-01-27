<?php
/**
 * Plugin Name: Simple Review Collector
 * Description: A simple plugin to collect and display reviews with ratings. Works with any page builder or classic editor.
 * Version: 1.0
 * Author: Genesis Jr
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ✅ Create database table on activation
register_activation_hook( __FILE__, 'src_create_table' );
function src_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'review_collector';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        reviewer_name varchar(255) NOT NULL,
        rating tinyint(1) NOT NULL,
        review_text text NOT NULL,
        photo_url varchar(500) DEFAULT NULL,
        reviewer_ip varchar(100) DEFAULT NULL,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY page_id (page_id),
        KEY status (status)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// ✅ Enqueue styles and scripts
add_action( 'wp_enqueue_scripts', 'src_enqueue_assets' );
function src_enqueue_assets() {
    wp_enqueue_style( 'src-style', plugins_url( 'assets/css/style.css', __FILE__ ), array(), '1.0' );
    wp_enqueue_script( 'src-script', plugins_url( 'assets/js/script.js', __FILE__ ), array('jquery'), '1.0', true );
    
    wp_localize_script( 'src-script', 'srcAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'src_nonce' )
    ));
}

// ✅ Shortcode for review form and display
add_shortcode( 'review_form', 'src_form_shortcode' );
function src_form_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'layout' => 'card', // card or slider
        'show_form' => 'yes',
        'order' => 'newest', // newest or highest
        'per_page' => 10
    ), $atts );
    
    ob_start();
    
    // Get current page ID
    $page_id = get_the_ID();
    
    // Display review form
    if ( $atts['show_form'] === 'yes' ) {
        src_display_form( $page_id );
    }
    
    // Display reviews
    src_display_reviews( $page_id, $atts );
    
    return ob_get_clean();
}

// ✅ Display review form
function src_display_form( $page_id ) {
    ?>
    <div class="src-review-form-wrapper">
        <h3>Leave a Review</h3>
        <form id="src-review-form" class="src-review-form" enctype="multipart/form-data">
            <input type="hidden" name="page_id" value="<?php echo esc_attr( $page_id ); ?>">
            <input type="hidden" name="action" value="src_submit_review">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'src_nonce' ); ?>">
            
            <!-- Honeypot for spam protection -->
            <input type="text" name="website" style="display:none;">
            
            <div class="src-form-group">
                <label for="reviewer_name">Your Name *</label>
                <input type="text" id="reviewer_name" name="reviewer_name" required>
            </div>
            
            <div class="src-form-group">
                <label>Your Rating *</label>
                <div class="src-star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1">★</label>
                </div>
            </div>
            
            <div class="src-form-group">
                <label for="review_text">Your Review *</label>
                <textarea id="review_text" name="review_text" rows="5" required></textarea>
            </div>
            
            <div class="src-form-group">
                <label for="review_photo">Add Photo (Optional)</label>
                <input type="file" id="review_photo" name="review_photo" accept="image/*">
            </div>
            
            <button type="submit" class="src-submit-btn">Submit Review</button>
            <div class="src-message"></div>
        </form>
    </div>
    <?php
}

// ✅ Display reviews
function src_display_reviews( $page_id, $atts ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'review_collector';
    
    // Get order
    $orderby = ( $atts['order'] === 'highest' ) ? 'rating DESC, created_at' : 'created_at';
    
    // Get reviews
    $reviews = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE page_id = %d AND status = 'approved' 
        ORDER BY $orderby DESC 
        LIMIT %d",
        $page_id,
        $atts['per_page']
    ));
    
    // Get stats
    $stats = $wpdb->get_row( $wpdb->prepare(
        "SELECT COUNT(*) as total, AVG(rating) as average 
        FROM $table_name 
        WHERE page_id = %d AND status = 'approved'",
        $page_id
    ));
    
    if ( ! $reviews ) {
        echo '<p class="src-no-reviews">No reviews yet. Be the first to leave a review!</p>';
        return;
    }
    
    // Display stats
    ?>
    <div class="src-review-stats">
        <div class="src-average-rating">
            <span class="src-rating-number"><?php echo number_format( $stats->average, 1 ); ?></span>
            <div class="src-stars"><?php echo src_render_stars( $stats->average ); ?></div>
            <span class="src-total-reviews"><?php echo $stats->total; ?> reviews</span>
        </div>
    </div>
    
    <div class="src-reviews-container src-layout-<?php echo esc_attr( $atts['layout'] ); ?>">
        <?php foreach ( $reviews as $review ) : ?>
            <div class="src-review-card">
                <?php if ( $review->photo_url ) : ?>
                    <div class="src-review-photo">
                        <img src="<?php echo esc_url( $review->photo_url ); ?>" alt="Review photo">
                    </div>
                <?php endif; ?>
                
                <div class="src-review-header">
                    <div class="src-reviewer-name"><?php echo esc_html( $review->reviewer_name ); ?></div>
                    <div class="src-review-rating"><?php echo src_render_stars( $review->rating ); ?></div>
                    <div class="src-review-date"><?php echo date( 'F j, Y', strtotime( $review->created_at ) ); ?></div>
                </div>
                
                <div class="src-review-text">
                    <?php echo nl2br( esc_html( $review->review_text ) ); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

// ✅ Render star rating
function src_render_stars( $rating ) {
    $full_stars = floor( $rating );
    $half_star = ( $rating - $full_stars ) >= 0.5 ? 1 : 0;
    $empty_stars = 5 - $full_stars - $half_star;
    
    $output = str_repeat( '★', $full_stars );
    if ( $half_star ) $output .= '☆';
    $output .= str_repeat( '☆', $empty_stars );
    
    return $output;
}

// ✅ Handle AJAX form submission
add_action( 'wp_ajax_src_submit_review', 'src_submit_review' );
add_action( 'wp_ajax_nopriv_src_submit_review', 'src_submit_review' );
function src_submit_review() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'src_nonce' ) ) {
        wp_send_json_error( 'Security check failed' );
    }
    
    // Honeypot check
    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( 'Spam detected' );
    }
    
    // Validate fields
    if ( empty( $_POST['reviewer_name'] ) || empty( $_POST['rating'] ) || empty( $_POST['review_text'] ) ) {
        wp_send_json_error( 'Please fill all required fields' );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'review_collector';
    
    // Sanitize data
    $page_id = intval( $_POST['page_id'] );
    $reviewer_name = sanitize_text_field( $_POST['reviewer_name'] );
    $rating = intval( $_POST['rating'] );
    $review_text = sanitize_textarea_field( $_POST['review_text'] );
    $reviewer_ip = $_SERVER['REMOTE_ADDR'];
    
    // Handle photo upload
    $photo_url = null;
    if ( ! empty( $_FILES['review_photo']['name'] ) ) {
        $upload = wp_handle_upload( $_FILES['review_photo'], array( 'test_form' => false ) );
        if ( ! isset( $upload['error'] ) ) {
            $photo_url = $upload['url'];
        }
    }
    
    // Insert review
    $result = $wpdb->insert(
        $table_name,
        array(
            'page_id' => $page_id,
            'reviewer_name' => $reviewer_name,
            'rating' => $rating,
            'review_text' => $review_text,
            'photo_url' => $photo_url,
            'reviewer_ip' => $reviewer_ip,
            'status' => 'pending' // Requires admin approval
        ),
        array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
    );
    
    if ( $result ) {
        // Send email notification to admin
        $admin_email = get_option( 'admin_email' );
        $subject = 'New Review Submitted';
        $message = "A new review has been submitted on " . get_the_title( $page_id ) . "\n\n";
        $message .= "Name: $reviewer_name\n";
        $message .= "Rating: $rating stars\n";
        $message .= "Review: $review_text\n\n";
        $message .= "Approve it here: " . admin_url( 'admin.php?page=review-collector' );
        
        wp_mail( $admin_email, $subject, $message );
        
        wp_send_json_success( 'Thank you for your review! It will be published after approval.' );
    } else {
        wp_send_json_error( 'Failed to submit review. Please try again.' );
    }
}

// ✅ Add admin menu
add_action( 'admin_menu', 'src_admin_menu' );
function src_admin_menu() {
    add_menu_page(
        'Review Collector',
        'Reviews',
        'manage_options',
        'review-collector',
        'src_admin_page',
        'dashicons-star-filled',
        25
    );
}

// ✅ Admin page to manage reviews
function src_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'review_collector';
    
    // Handle actions
    if ( isset( $_GET['action'] ) && isset( $_GET['review_id'] ) ) {
        $review_id = intval( $_GET['review_id'] );
        
        if ( $_GET['action'] === 'approve' ) {
            $wpdb->update( $table_name, array( 'status' => 'approved' ), array( 'id' => $review_id ), array( '%s' ), array( '%d' ) );
        } elseif ( $_GET['action'] === 'reject' ) {
            $wpdb->update( $table_name, array( 'status' => 'rejected' ), array( 'id' => $review_id ), array( '%s' ), array( '%d' ) );
        } elseif ( $_GET['action'] === 'delete' ) {
            $wpdb->delete( $table_name, array( 'id' => $review_id ), array( '%d' ) );
        }
    }
    
    // Get filter
    $status_filter = isset( $_GET['status'] ) ? $_GET['status'] : 'all';
    $where = ( $status_filter !== 'all' ) ? $wpdb->prepare( "WHERE status = %s", $status_filter ) : '';
    
    $reviews = $wpdb->get_results( "SELECT * FROM $table_name $where ORDER BY created_at DESC" );
    
    ?>
    <div class="wrap">
        <h1>Review Management</h1>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select onchange="window.location.href='?page=review-collector&status='+this.value">
                    <option value="all" <?php selected( $status_filter, 'all' ); ?>>All Reviews</option>
                    <option value="pending" <?php selected( $status_filter, 'pending' ); ?>>Pending</option>
                    <option value="approved" <?php selected( $status_filter, 'approved' ); ?>>Approved</option>
                    <option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>>Rejected</option>
                </select>
            </div>
        </div>
        
        <?php if ( $reviews ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Page</th>
                        <th>Name</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Photo</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $reviews as $review ) : ?>
                        <tr>
                            <td><?php echo $review->id; ?></td>
                            <td><a href="<?php echo get_permalink( $review->page_id ); ?>"><?php echo get_the_title( $review->page_id ); ?></a></td>
                            <td><?php echo esc_html( $review->reviewer_name ); ?></td>
                            <td><?php echo src_render_stars( $review->rating ); ?> (<?php echo $review->rating; ?>)</td>
                            <td><?php echo esc_html( substr( $review->review_text, 0, 100 ) ); ?>...</td>
                            <td><?php if ( $review->photo_url ) echo '<a href="' . esc_url( $review->photo_url ) . '" target="_blank">View</a>'; ?></td>
                            <td><span class="src-status-<?php echo $review->status; ?>"><?php echo ucfirst( $review->status ); ?></span></td>
                            <td><?php echo date( 'M j, Y', strtotime( $review->created_at ) ); ?></td>
                            <td>
                                <?php if ( $review->status === 'pending' ) : ?>
                                    <a href="?page=review-collector&action=approve&review_id=<?php echo $review->id; ?>" class="button button-small">Approve</a>
                                    <a href="?page=review-collector&action=reject&review_id=<?php echo $review->id; ?>" class="button button-small">Reject</a>
                                <?php endif; ?>
                                <a href="?page=review-collector&action=delete&review_id=<?php echo $review->id; ?>" class="button button-small" onclick="return confirm('Delete this review?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No reviews found.</p>
        <?php endif; ?>
    </div>
    
    <style>
        .src-status-pending { color: orange; font-weight: bold; }
        .src-status-approved { color: green; font-weight: bold; }
        .src-status-rejected { color: red; font-weight: bold; }
    </style>
    <?php
}