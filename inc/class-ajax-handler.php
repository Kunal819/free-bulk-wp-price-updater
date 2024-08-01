<?php

class Bulk_Price_Editor_Ajax_Handler {

    public static function handle_ajax() {
        if ( isset($_POST['action']) && $_POST['action'] === 'fbpe_update_prices' ) {
            check_ajax_referer( 'fbpe_nonce', 'nonce' );
            
            if ( !current_user_can('manage_options') ) {
                wp_send_json_error(array('message' => 'You do not have sufficient permissions.'));
                return;
            }
            
            if ( class_exists('Free_Bulk_Price_Editor') ) {
                $editor = Free_Bulk_Price_Editor::get_instance();
                if ( method_exists($editor, 'update_prices') ) {
                    $editor->update_prices();
                } else {
                    wp_send_json_error(array('message' => 'Update prices method not found.'));
                }
            } else {
                wp_send_json_error(array('message' => 'Bulk_Price_Editor class not found.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Invalid action.'));
        }
    }
}

add_action( 'wp_ajax_fbpe_update_prices', array( 'Bulk_Price_Editor_Ajax_Handler', 'handle_ajax' ) );
add_action( 'wp_ajax_nopriv_fbpe_update_prices', array( 'Bulk_Price_Editor_Ajax_Handler', 'handle_ajax' ) ); // Optional for non-logged-in users
