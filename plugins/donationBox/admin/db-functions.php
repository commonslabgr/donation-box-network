<?php

/* 
 * Useful functions used by the plugin-in.
 */


define('kB', 1024);
define('MB', 1024 * 1024);
define('GB', 1024 * 1024 * 1024);





/*
 * Function which return the user's IP address..
 * @return : The user ip address.
 */

function get_user_ip()
{
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
    {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) 
    {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}





/*
 * Function that is targeted to delete the stylesheet file.
 * Deletes from a specific post, the stylesheet file, if it exists.
 * 
 * @param post_id : The donation project for which to delete (if exist) the stylesheet
 * file.
 * 
 */

function db_delete_css_file( $post_id )
{
    $css = get_post_meta($post_id, '_db_project_stylesheet_file', true );
    if ( count($css) > 0  &&  is_array($css) )
    {
        wp_delete_file( $css[0]['file'] );
        delete_post_meta($post_id, '_db_project_stylesheet_file');
    }
}





/*
 * Function that is targeted to delete the video file.
 * Deletes from a specific post, the video file, if it exists.
 * 
 * @param post_id : The donation project for which to delete (if exist) the video
 * file.
 * 
 */

function db_delete_video_file( $post_id )
{
    $video = get_post_meta($post_id, '_db_project_video_file', true);
    if ( count($video) > 0 && is_array($video) )
    {
        wp_delete_file( $video[0]['file'] );
        delete_post_meta($post_id, '_db_project_video_file');
    }
}





/*
 * Function that is targeted to delete the image file.
 * Deletes from a specific post, the image file, if it exists.
 * 
 * @param post_id : The donation project for which to delete (if exist) the image
 * file.
 * 
 */

function db_delete_image_file( $post_id )
{
    $image = get_post_meta($post_id, '_db_project_image_file', true);
    if ( count($image) > 0  &&  is_array($image) )
    {
        wp_delete_file( $image[0]['file'] );
        delete_post_meta($post_id, '_db_project_image_file');
    }
}





/*
 * Function returning if the post is 'donationboxes' post type.
 * 
 * @param post_id : The donation project id for which it will check if it is post type
 * 'donationboxes'.
 * 
 * @return : True if it is, or false if its not.
 * 
 */

function db_post_type_is_donationboxes( $post_id )
{
    return get_post_type($post_id) == "donationboxes";
}





/*
 * Function returning if the post status is 'draft' or no.
 * 
 * @param post_id : The donation project id for which it will check if it is draft.
 * 
 * @return : True if it is, or false if its not.
 * 
 */

function db_post_status_is_draft( $post_id )
{
    return get_post_status( $post_id ) == 'auto-draft' || get_post_status( $post_id ) == 'draft';
}





/*
 * Function that is responsible for displaying the error code ($code)
 * and the error message to the user in the right area.
 * 
 * Reference : https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
 */

function db_print_user_error( $code , $message )
{
    $class = 'notice notice-error is-dismissible';
    $message_title = __( 'Failed saving/updating.' , 'sample-text-domain' );

    echo '<div class="'.$class.'"><p><b>Failed!</b> '.$message_title.'<br>'.$code.': '.$message.' </p></div>';
}





/*
 * The following php function, will be executed upon request via AJAX for 
 * action "db_is_project_creator".
 * 
 * Its purpose to check two things:
 *      - If the user belongs to the category (has the role) "project_creator"
 *      - If it is a page which has post_type "donationboxes".
 * 
 * @return : If it meets the above requirements, true or false if he doesn't.
 * 
 */

function db_ajax_remove_trash_and_remove()
{
    $url = wp_get_referer();
    $parts = parse_url($url);
    parse_str($parts['query'], $query);

    if ( current_user_can('project_creator') && $query['post_type'] == 'donationboxes' )
    {
        echo TRUE;
    }
    else
    {
        echo FALSE;
    }

    wp_die();
}

add_action('wp_ajax_db_is_project_creator', 'db_ajax_remove_trash_and_remove');
add_action('wp_ajax_nopriv_db_is_project_creator', 'db_ajax_remove_trash_and_remove');






/*
 * 
 */

function db_cron_exists( $id )
{
    $all_cron_jobs = _get_cron_array();

    foreach ($all_cron_jobs as $cron_job) 
    {
        if ( $cron_job['db_cron_hook_insert_update'] || $cron_job['db_cron_hook_delete'])
        {
            foreach ($cron_job as $value)
            {
                foreach ($value as $temp )
                {
                    if ( $temp['args'][0] == $id ) 
                    {
                        return TRUE;
                    }
                }
            }
        }
    }
    return FALSE;    
}






function db_delete_cron_job( $id )
{
    $delete = FALSE;
    
    $all_cron_jobs = _get_cron_array();
    var_dump($all_cron_jobs);
    echo '<br> ------------------- <br>';

    foreach ($all_cron_jobs as $cron_job) 
    {
        if ( $cron_job['db_cron_hook_insert_update'] || $cron_job['db_cron_hook_delete'])
        {
            foreach ($cron_job as $value)
            {               
                foreach ($value as $temp )
                {
                    if ( $temp['args'][0] == $id ) 
                    {
                        $delete = TRUE;
                    }
                }
                
                if ( $delete )
                {
                    var_dump( $cron_job );
                    echo '<br>';
                    var_dump( key($cron_job) );
                    echo '<br> --> ' . $all_cron_jobs[ key($cron_job) ] ;
                    echo '<br>';
                    var_dump($value);
                    echo '<br>'.$all_cron_jobs[ key($cron_job) ][ key($value) ] .'<br>';
                    unset( $all_cron_jobs[ key($value)] );
                    return;
                }
                
            }
        }
    }
    
    _set_cron_array($all_cron_jobs);
    
    
//    $crons = _get_cron_array();
//    foreach ($crons as $key=>$job)
//    {
//        echo $crons[$key] ;
//        
//        if ( $job['args'][0] == $id )
//        {
//            unset( $crons[$key] );
//        }
//    }
//    
//    _set_cron_array($crons);
    
}








