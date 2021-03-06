<?php
/**
 * @package donationBox
 */
/*
Plugin Name: DonationBox
Plugin URI: https://github.com/eellak/gsoc17-donationbox/tree/master/plugins
Description: WordPress plugin for the Donation-Box Network.
Version: 0.9.3
Author: Tas-sos
Author URI: https://github.com/Tas-sos
License: GPLv2
Text Domain: donationBox
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyleft 2017 ~ Google Summer of Code! :)
*/

/* For Security Reasons. */
if ( !defined( 'ABSPATH' ) )
{
    exit;
}

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);

require_once( plugin_dir_path(__FILE__) . 'admin/db-functions.php');
require_once( plugin_dir_path(__FILE__) . 'admin/db-validations.php');
require_once( plugin_dir_path(__FILE__) . 'admin/db-send_data_to_db.php');



/*
 * Definition of Global Variables for handling errors.
 */

global $db_error;

$db_error = array(
            'have'  => false,
            'message_code'  => '0'
                );


/*
 * Plugin Activation.
 * 
 * What to do when the plugin activated.
 */

register_activation_hook( __FILE__, 'db_plugin_activation');


function db_plugin_activation()
{
    
    $capabilities = array(
        'read'                      => true,
        'edit_posts'                => true,
        'edit_others_posts'         => false,
        'edit_private_posts'        => true,
        'edit_published_posts'      => true,
        'delete_posts'              => true,
        'delete_others_posts'       => false,
        'delete_published_posts'    => false,
        'publish_posts'             => true
    );
    
    add_role('project_creator', 'Project Creator', $capabilities);
    
    // My WordPress Cron Job to update the donation projects.
    db_creat_cron_job();
    
}





/*
 * Plugin Deactivation.
 * 
 * What to do when the plugin deactivated.
 */

register_deactivation_hook( __FILE__, 'db_delete_role_for_users');
function db_delete_role_for_users()
{
    remove_role('project_creator');
}


//register_unistall_hook() : 




// Create plugin menu.
function db_create_plugin_menu()
{
    require_once( plugin_dir_path(__FILE__) . 'admin/db-donation-boxes-menu.php' );
}

add_action('plugins_loaded', 'db_create_plugin_menu');





//Add my custom endpoints to the REST WordPress API.
/**
 * Grab latest post title by an author!
 *
 * @param array $request all options - arguments from the request.
 * @return string|null Post title for the latest,  * or null if none.
 * 
 */

function db_create_endpoint_REST_API( $request ) 
{
    
    $date_param = $request->get_param( 'date' );
    $time_param = $request->get_param( 'time' );
    
    // Search args :
    $args = array(
                'post_type' => 'donationboxes'
                );
    
    // Run query :
    $posts = get_posts( $args );

    if ( empty( $posts ) ) 
    {
        return new WP_Error( 'awesome_no_author', 'Invalid author', array( 'status' => 404 ) );
    }
    
    $data = array();
    
    for ( $i = 0; $i < count($posts); $i++ )
    {
        // Because post_modified is like "2017-06-20 13:50:08", that's very good! :)
        
        $request_date = new DateTime( $date_param .' '.$time_param );
        $currnet_post_time = new DateTime($posts[$i]->post_modified);
        
        if ( $currnet_post_time > $request_date )
        {
            $data[$i]['ID'] = $posts[$i]->ID;
            $data[$i]['post_modified'] = $posts[$i]->post_modified;
        }
    }
    
    if ( empty($data) )
    {
        $data = null;
    }

    $response = new WP_REST_Response( $data );

    // Add a custom status code
    $response->set_status( 201 );

    // Add a custom header
//    $response->header( 'Location', 'http://example.com/' );
    
    return $response ;
}


/*
 * To make this available via the API, we need to register a route. 
 * This tells the API to respond to a given request with our function.
 * 
 * “Route” is the URL, whereas “endpoint” is the function behind it that 
 * corresponds to a method and a URL.
 * 
 * If your site domain is example.com and you’ve kept the API path of wp-json,
 * then the full URL would be :
 * http://example.com/wp-json/myplugin/v1/author/(?P\d+).
 * for my example : 
 * http://localhost:8000/wp-json/myplugin/v1/author/1
 * 
 */

function db_donationboxes_updated_rest_route()
{
    register_rest_route( 
            'wp/v2/donationboxes',          /* Namespace - Route. */
            '/updated/(?P<date>([0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])))/(?P<time>(([01]?[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])))',  /* Endpoint with parameters. */
            array(
                'methods' => 'GET',
                'callback' => 'db_create_endpoint_REST_API',
                'args' => array(
                            'date' => array(
                                        'validate_callback' => function($param, $request, $key) 
                                        {
                                            return preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $param );
                                        }
                                        ),
                            'time' => array(
                                        'validate_callback' => function($param, $request, $key) 
                                        {
                                            return preg_match("/^([01]?[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])?$/", $param );
                                        }
                                        )
                                ),

                )
    );
}

add_action( 'rest_api_init', 'db_donationboxes_updated_rest_route');










function db_view_donation_projects_rest($request)
{

    // Search args :
    $args = array(
                'post_type' => 'donationboxes'
                );
    
    // Run query :
    $posts = get_posts( $args );

    if ( empty( $posts ) ) 
    {
        return new WP_Error( 'awesome_no_donation_projects', 'No donation projects', array( 'status' => 404 ) );
    }
    
    $data = array();
    
    for ( $i = 0; $i < count($posts); $i++ )
    {
            $data[$i]['ID'] = $posts[$i]->ID;
            $data[$i]['Title'] =  $posts[$i]->post_title ;
    }
    
    if ( empty($data) )
    {
        $data = null;
    }

    $response = new WP_REST_Response( $data );

    $response->set_status( 201 );

    
    return $response ;
}


function db_donationboxes_projects_rest_route()
{
    register_rest_route( 
            'wp/v2/donationboxes',
            '/projects',
            array(
                'methods' => 'GET',
                'callback' => 'db_view_donation_projects_rest',
                )
    );
}

add_action( 'rest_api_init', 'db_donationboxes_projects_rest_route');




function my_bulk_post_updated_messages_filter( $bulk_messages, $bulk_counts )
{
    $singular = 'donation project';
    $plural = 'donation projects';
    
    $bulk_messages['donationboxes'] = array(
        'published' => _n( '%s ' . $singular . ' published.', '%s ' . $plural . ' published.', $bulk_counts['published'] ),
        'updated'   => _n( '%s ' . $singular . ' updated.', '%s ' . $plural . ' updated.', $bulk_counts['updated'] ),
        'locked'    => _n( '%s ' . $singular . ' not updated, somebody is editing it.', '%s ' . $plural . ' not updated, somebody is editing them.', $bulk_counts['locked'] ),
        'deleted'   => _n( '%s ' . $singular . ' permanently deleted.', '%s ' . $plural . ' permanently deleted.', $bulk_counts['deleted'] ),
        'trashed'   => _n( '%s ' . $singular . ' moved to the Trash.', '%s ' . $plural . ' moved to the Trash.', $bulk_counts['trashed'] ),
        'untrashed' => _n( '%s ' . $singular . ' restored from the Trash.', '%s ' . $plural . ' restored from the Trash.', $bulk_counts['untrashed'] ),
    );

    return $bulk_messages;

}

add_filter( 'bulk_post_updated_messages', 'my_bulk_post_updated_messages_filter', 10, 2 );




/**
 * Function - WordPress Filter where it adding a new timer for the WordPress cron
 * jobs.
 * 
 * @param Array $schedules : Array where containing the current possible timers.
 * 
 * @return Array : The array he received as an argument adding another one timer to him.
 * 
 */
 
function db_add_cron_interval( $schedules )
{
    if ( !isset( $schedules['30min'] ) )
    {
        $schedules['30min'] = array(
            'interval' => 30 * 60 , // 30min * 60sec
            'display'  => esc_html__( 'Once, every half hour.' ),
        );
    }
    
    return $schedules;
}

add_filter( 'cron_schedules', 'db_add_cron_interval' );





/**
 * Function where it creating the WordPress Cron Job where it will update the
 * local database from the remote, every half hour.
 * 
 */

function db_creat_cron_job()
{
    
    if ( ! wp_next_scheduled ( 'db_update_local_db_event') )
    {
        wp_schedule_event(time(), '30min', 'db_update_local_db_event');
    }
    
}

add_action('db_update_local_db_event', 'db_update_local_current_amount');





/**
 * Function that removes from the list of Bulk actions the ability to restore
 * or permanently delete donation projects in the "Trash" folder.
 * The above features are removed if a user other than the administrator can
 * get into the "Trash" folder. 
 * 
 * Obviously a long time ago, forbidden/blocked the access to this page to all users
 * except the system administrator.
 * But in this function, we offer an additional level of security!
 * 
 * @param Array $actions : The WordPress Bulk actions that the user can do.
 * 
 * @return Array : 
 *              1) If the user is the system administrator :
 *                  All the WordPress Bulk actions that the user can do.
 *              2) If the user is not the system administrator :
 *                  Τhe WordPress Bulk actions that the user can do, except 
 *                  for restoring and permanent deletion!
 * 
 */

function db_remove_dropdown_list_bulk_actions($actions)
{
    unset( $actions['untrash'] );   // Restore
            
    if ( ! current_user_can('administrator') ) // Only administrator can access to actions!
    {
        unset( $actions['delete'] );    // Delete Permanently
    }
    
    return $actions;
}

add_filter('bulk_actions-edit-donationboxes','db_remove_dropdown_list_bulk_actions');





/**
 * A function that removes from the "subsubsub" menu, the menu (link) to enter
 * the trash folder.
 * 
 * Attention! : It does not forbid access! It just does not show this menu to the user.
 * Obviously a long time ago, forbidden/blocked the access to this page to all users
 * except the system administrator.
 * But in this function, we offer an additional level of security!
 * 
 * @param Array $views : All the "subsubsub" menu.
 * 
 * @return Array : 
 *              1) If the user is the system administrator :
 *                  All the WordPress "subsubsub" menu.
 *              2) If the user is not the system administrator :
 *                  All the WordPress "subsubsub" menu, except the 
 *                  "Trash" link!
 * 
 */

function db_remove_from_subsubmenu( $views )
{
    if ( ! current_user_can('administrator') ) // Only administrator can access to this menu!
    {
        unset($views['trash']);
    }

    return $views;
}

add_filter('views_edit-donationboxes','db_remove_from_subsubmenu');





/**
 * A function that subtracts from the "row" actions of post list, the ability
 * to quickly edit and in the trash folder or if the user is not the administrator
 * and is in the "Trash" folder, the menu link to restore or permanently delete
 * donation project.
 * 
 * Attention! : It does not forbid access! It just does not show this menu to the user.
 * In this function, we offer an additional level of security!
 * 
 * @param Array $actions : The WordPress "row" actions that the user can do.
 * 
 * @param Array $post : The global WordPress variable "$post.
 * 
 * @return Array : 
 *              1)  Τhe WordPress "row" actions that the user can do, except the
 *                  "Quick edit".
 * 
 *              2a) If the user is the system administrator and is located
 *                  inside the "Tras" folder:
 *                  Τhe WordPress "row" actions that the user can do, except the
 *                  "Quick edit" menu link.
 *              2b) If the user is not the system administrator and is located
 *                  inside the "Tras" folder:
 *                  Τhe WordPress "row" actions that the user can do, except the
 *                  "Quick edit", "Restore", and "Permanent Delete" row menu links.
 * 
 */

function db_remove_actions_from_row( $actions, $post )
{
    if ( $post->post_type == "donationboxes" )
    {
        unset( $actions['inline hide-if-no-js'] );  //   Quick edit
        unset( $actions['untrash'] );               //   Restore
        
        // In Trash folder.
        if ( ! current_user_can('administrator') ) // Only administrator can access to these actions!
        {
            unset( $actions['delete'] ); //    Delete Permanently
        }
    }
    
    return $actions;
}

add_filter('post_row_actions','db_remove_actions_from_row', 10, 2 );



