<?php

/* 
 * In this source file, all meta - boxes are created.
 * 
 */


wp_enqueue_style('bootstrap-css', plugins_url( '/css/bootstrap.min.css' , __FILE__ ) , 11 );
wp_enqueue_script('bootstrap-js', plugins_url( '/js/bootstrap.min.js', __FILE__ ) , 11 );
wp_enqueue_script('myScripts-js', plugins_url( '/js/db-scripts.js', __FILE__ ) , 11 );

require_once('db-validations.php');
require_once('db-send_data_to_db.php');
require_once('db-functions.php');



/* Project status meta box. */
function db_project_status_callback( $post )
{
    wp_nonce_field( 'db_save_project_status', 'db_status_meta_box_nonce');    
    $status_value = get_post_meta( $post->ID, '_db_project_status', true);
    
    ?>
        <label for=db_project_state_field_a"">Activate  </label>
        <input type="radio" name="db_project_state_field" id="db_project_state_field_a" value="activate"    <?php echo $status_value == 1 ? 'checked="checked"' : '' ?> /> <br>

        <label for=db_project_state_field_d"">Deactivate  </label>
        <input type="radio" name="db_project_state_field" id="db_project_state_field_d" value="deactivate"  <?php echo $status_value == 0 ? 'checked="checked"' : '' ?> />
    <?php
}

function db_project_status_metabox()
{
    add_meta_box(
            'db_project_status_metabox',    // Unique id of metabox.
            'Project Status',               // Displayed metabox title.
            'db_project_status_callback',   // Callback function.
            'donationboxes',                // On which page it will appear.
            'side'                          // In which position.
            );
}

add_action('add_meta_boxes' , 'db_project_status_metabox' , 1 );





/* Target amount meta box. */

function db_target_amount_callback( $post )
{
    // For current amount :
    wp_nonce_field( 'db_save_current_amount', 'db_current_amount_meta_box_nonce');
    $current_amount_value = get_post_meta( $post->ID , '_db_project_current_amount', true);
    
    // For target amount :
    wp_nonce_field(
            'db_save_target_amount',
            'db_target_amount_meta_box_nonce'
            );
    $target_amount_value = get_post_meta(
                                $post->ID,
                                '_db_project_target_amount',
                                true);
    
    ?>
    <div class="form-field form-required" >
        <label for="db_project_current_amount_field">Current amount </label>
        <input type="text" disabled="disabled" name="db_project_current_amount_field" id="db_project_current_amount_field" value="<?php echo ($current_amount_value > 0 ? esc_attr($current_amount_value) : '0') ?>" aria-required="true" required="required" /> <br>
    </div>
    <div class="form-field form-required" >
        <label for="db_project_target_amount_field">Target amount </label>
        <input type="number" name="db_project_target_amount_field" id="db_project_target_amount_field" value="<?php echo esc_attr($target_amount_value) ?>"  aria-required="true" required="required" min="1" /> <br>
    </div>
    <?php
    
}

function db_project_target_amount_metabox()
{
    add_meta_box(
            'db_amount_metabox',            // Unique id of metabox.
            'Donation money',               // Displayed metabox title.
            'db_target_amount_callback',    // Callback function.
            'donationboxes',                // On which page it will appear.
            'side',                         // In which position.
            'high'                          // Priority.

            );
}

add_action('add_meta_boxes' , 'db_project_target_amount_metabox', 1 );





function db_preview_callback( $post )
{
    $preview_page = '/wp-content/plugins/donationBox/templates/template-portrait_mode.php';
    $preview_page .= '?db_preview_id=' . get_the_ID();

    $category_detail = get_the_category( get_the_ID() ); 
    
    
    ?>
    <p>
        <button type="submit" class="btn btn-primary" id="db_preview_button" name="<?php echo get_the_ID() ?> ">
            <span class="glyphicon glyphicon-eye-open"></span> Donation Box Preview
        </button>
    </p>
    <?php
}

function db_project_preview_metabox()
{
    add_meta_box(
            'db_preview_metabox',   // Unique id of metabox.
            'Project Preview',      // Displayed metabox title. 
            'db_preview_callback',  // Callback function.
            'donationboxes',        // On which page it will appear.
            'side',                 // In which position.
            'high'                  // Priority.
            );
}

add_action('add_meta_boxes' , 'db_project_preview_metabox' , 1 );





/* Upload style sheet file metaboxe. */
function db_style_callback( $post )
{
    wp_nonce_field( 'db_save_stylesheet_file', 'db_upload_stylesheet_file_meta_box_nonce');
    $theFILE = get_post_meta( $post->ID , '_db_project_stylesheet_file', true );

    echo '<p id="current_css_file" class="description">';

    if ( count($theFILE) > 0  &&  is_array($theFILE) )
    {
        ?>
        Current stylesheet file URL : <?php echo $theFILE[0]['url'] ?>
        <a href="#" title="Remove" id="rm_css" onClick="return false;"><span class="glyphicon glyphicon-remove text-danger" aria-hidden="true">   </span> </a>
        <?php
    }
    else
    {
        ?>
        If you want, upload a stylesheet (css) file.
        <br>
        <input id="db_project_css_file_field" title="select file" name="db_project_css_file_field" size="25" type="file" accept="text/css" value="" />
        <?php
    }

    echo '</p>';

}

function db_project_style_metabox()
{
    if ( current_user_can('upload_files') ) // Only for users who can upload files - Essentially only the administrator -
    {
        add_meta_box(
                'db_style_metabox',     // Unique id of metabox.
                'Project Style',        // Displayed metabox title. 
                'db_style_callback',    // Callback function.
                'donationboxes',        // On which page it will appear.
                'normal',               // In which position.
                'high'                  // Priority.
                );
    }
}

add_action('add_meta_boxes' , 'db_project_style_metabox' , 1 );





function db_video_callback( $post )
{
    wp_nonce_field( 'db_save_video_file', 'db_upload_video_file_meta_box_nonce');
    $theFILE = get_post_meta( $post->ID , '_db_project_video_file', true );

    echo '<p id="current_video_file" class="description">';

    if ( count($theFILE) > 0  &&  is_array($theFILE) )
    {
        ?>
        Current video file URL : <?php echo $theFILE[0]['url'] ?>
        <a href="#" title="Remove" id="rm_video" onClick="return false;"><span class="glyphicon glyphicon-remove text-danger" aria-hidden="true">   </span> </a>
        <?php
    }
    else
    {
        ?>
        If you want, upload a video file.
        <br>
        <input id="db_project_video_field" title="select file" name="db_project_video_field" size="25" type="file" accept="video/mp4" />
        <?php
    }

    echo '</p>';
}

function db_project_video()
{
    add_meta_box(
        'db_video_metabox',     // Unique id of metabox.
        'Project Video',        // Displayed metabox title. 
        'db_video_callback',    // Callback function.
        'donationboxes',        // On which page it will appear.
        'normal',               // In which position.
        'high'                  // Priority.
        );

}

add_action('add_meta_boxes', 'db_project_video');





function db_image_callback( $post )
{
    wp_nonce_field( 'db_save_image_file', 'db_upload_image_file_meta_box_nonce');
    $theFILE = get_post_meta( $post->ID , '_db_project_image_file', true );

    echo '<p id="current_image_file" class="description">';

    if ( count($theFILE) > 0  &&  is_array($theFILE) )
    {
        ?>
        Current image file URL : <?php echo $theFILE[0]['url'] ?>
        <a href="#" title="Remove" id="rm_image" onClick="return false;"><span class="glyphicon glyphicon-remove text-danger" aria-hidden="true">   </span> </a>
        <?php
    }
    else
    {
        ?>
        If you want, upload a image file.
        <br>
        <input id="db_project_image_field" title="select file" name="db_project_image_field" size="25" type="file" accept="image/jpeg, image/png" value="" />
        <?php
    }

    echo '</p>';
}

function db_project_image()
{
    add_meta_box(
        'db_image_metabox',     // Unique id of metabox.
        'Project Image',        // Displayed metabox title. 
        'db_image_callback',    // Callback function.
        'donationboxes',        // On which page it will appear.
        'normal',               // In which position.
        'high'                  // Priority.
        );

}

add_action('add_meta_boxes', 'db_project_image');





// update_edit_form  : Very important function for uploading a file.
add_action('post_edit_form_tag', 'update_edit_form');

function update_edit_form()
{
  echo 'enctype="multipart/form-data"';
}


// Save meta boxes data.

function db_save_metaboxes_data( $post_id )
{
    // Validations for stylesheet file.
    if ( db_css_file_validations() )
    {

        if( ! empty( $_FILES['db_project_css_file_field']['name'] ) )
        {
            $flag = 0;

            if( !empty($_FILES['db_project_css_file_field']['name']) )
            {
                $flag = 1;
                $upload[] = wp_upload_bits(
                                            $_FILES['db_project_css_file_field']['name'],
                                            null,
                                            file_get_contents( $_FILES['db_project_css_file_field']['tmp_name'] )
                                        );
            }

            if ( $flag == 1 )
            {
                update_post_meta( $post_id, '_db_project_stylesheet_file', $upload);
                unset($upload);
            }
        }
    }
    
    
    // For delete css file. Maybe it's necessary to canmore validations here..
    if (  isset( $_POST['remove_css'] ) )
    {
        db_delete_css_file($post_id);
    }
    
    
    //  Validations for video file.
    if ( db_video_file_validations() )
    {
        if( ! empty( $_FILES['db_project_video_field']['name'] ) )
        {
            $flag = 0;

            if( !empty($_FILES['db_project_video_field']['name']) )
            {
                $flag = 1;
                $upload[] = wp_upload_bits(
                                            $_FILES['db_project_video_field']['name'],
                                            null,
                                            file_get_contents( $_FILES['db_project_video_field']['tmp_name'] )
                                        );
            }

            if ( $flag == 1 )
            {
                update_post_meta( $post_id, '_db_project_video_file', $upload);
                unset($upload);
            }
        }
    }
    
    // For delete video file.
    if (  isset( $_POST['remove_video'] ) )
    {
        db_delete_video_file($post_id);
    }
    
    
    //  Validations for image file.
    if ( db_image_file_validations() )
    {
        if( ! empty( $_FILES['db_project_image_field']['name'] ) )
        {
            $flag = 0;

            if( !empty($_FILES['db_project_image_field']['name']) )
            {
                $flag = 1;
                $upload[] = wp_upload_bits(
                                            $_FILES['db_project_image_field']['name'],
                                            null,
                                            file_get_contents( $_FILES['db_project_image_field']['tmp_name'] )
                                        );
            }

            if ( $flag == 1 )
            {
                update_post_meta( $post_id, '_db_project_image_file', $upload);
                unset($upload);
            }
        }
    }
    
    // For delete image file.
    if (  isset( $_POST['remove_image'] ) )
    {
        db_delete_image_file($post_id);
    }
    
    
    // Validations for status:
    if ( db_status_validations() )
    {
        if ( isset( $_POST['db_project_state_field'] ) )
        {
            $status_data = esc_attr( sanitize_text_field( $_POST['db_project_state_field'] ) ) ;
            $status_data_int = 0;

            if ( strcmp($status_data, 'activate') == 0 )
            {
                $status_data_int =  1;
            }

            else if ( strcmp($status_data, 'deactivate') == 0 )
            {
                $status_data_int = 0;
            }
            settype($status_data_int, 'integer');
            update_post_meta( $post_id, '_db_project_status', $status_data_int );
        }
    }
    
    // For current amount :
    // The value 0 is set by default. All new projects will begin with 0 initial amount.
    // The user will NEVER be able to change this value.
    if ( get_post_status( $post_id) == 'auto-draft' || get_post_status( $post_id ) == 'draft' )
    {
        update_post_meta( $post_id, '_db_project_current_amount', 0 );
    }
    

    // Validations for target amount :
    if ( db_target_amount_validations() )
    {
        if ( isset( $_POST['db_project_target_amount_field'] ) )
        {
            $target_amount_data = esc_attr( sanitize_text_field( $_POST['db_project_target_amount_field'] ) ) ;
            settype($target_amount_data, 'integer'); // For more more more secure!!!
            update_post_meta(
                    $post_id,
                    '_db_project_target_amount',
                    $target_amount_data
                    );
        }
    }

}

add_action('save_post_donationboxes' , 'db_save_metaboxes_data');








/*
 * 
 */

//function db_move_to_trash( $post_id )
//{
//    echo '<script> alert("Op op op!"); </script>';
//    echo '<script> confirm("Press a button!"); </scirpt>';
//    die();
//}
//
//add_action('wp_trash_post', 'db_move_to_trash');





function db_redirect_post_location( $location, $post_id )
{
    global $db_error;
    global $untrashed_posts;

    if ( $db_error['have'] )
    {
        $location = add_query_arg( 'message', $db_error['message_code'], get_edit_post_link( $post_id, 'url' ) );
    }
    
    if ( $untrashed_posts ) // If i have untrashed posts.
    {
        $location = add_query_arg( 'ids', $untrashed_posts[0], $location );
        var_dump($untrashed_posts);
//        wp_die();
    }
    
    return $location;
}

add_filter( 'redirect_post_location', 'db_redirect_post_location' , 10, 2 );





// Create me own admin notice for fail save post!
function db_admin_notices( $post_id )
{

    global $untrashed_posts;


    switch ( $_GET['message'] )
    {
        case 1: // Update post.
            db_send_data_to_donationBox_database( $_GET['post'] );
            break;
        case 6: // Create new post.
            db_send_data_to_donationBox_database( $_GET['post'] );
            break;
        case 101 : 
            db_print_user_error( '101', 'Problem with css file [UPLOAD_ERR_NO_FILE].' );
            break;
        case 102 : 
            db_print_user_error( '102', 'Problem with css file [UPLOAD_ERR_INI_SIZE] || [UPLOAD_ERR_FORM_SIZE].' );
            break;
        case 103 : 
            db_print_user_error( '103', 'Problem with css file [Something went wrong].' );
            break;
        case 201 : 
            db_print_user_error( '201', 'Problem with video file [UPLOAD_ERR_NO_FILE].' );
            break;
        case 202 : 
            db_print_user_error( '202', 'Problem with video file [UPLOAD_ERR_INI_SIZE] || [UPLOAD_ERR_FORM_SIZE].' );
            break;
        case 203 : 
            db_print_user_error( '203', 'Problem with video file [Something went wrong]. ' );
            break;
        case 301 : 
            db_print_user_error( '301', 'Problem with image file [UPLOAD_ERR_NO_FILE].' );
            break;
        case 302 : 
            db_print_user_error( '302', 'Problem with image file [UPLOAD_ERR_INI_SIZE] || [UPLOAD_ERR_FORM_SIZE].' );
            break;
        case 303 : 
            db_print_user_error( '303', 'Problem with image file [Something went wrong].' );
            break;
    }

    if ( isset( $_GET['trashed'] ) ) // If he sends donation projects in the trash.
    {
        if ( isset( $_GET['ids'] ) )
        {
            db_delete_data_from_donationBox_database( $_GET['ids'] );
        }
    }
    
    if ( isset( $_GET['untrashed'] ) ) // If he sends donation projects in the trash.
    {
        var_dump($untrashed_posts);
//        if ( $_GET['ids'] == 'untrash' )
//        {
//            db_delete_data_from_donationBox_database( $_GET['ids'] );
//        }
        if ( isset( $_GET['ids'] ) )
        {
            db_print_user_error( 'Untrashed posts is : ', $_GET['ids'] );
        }
//        wp_die();
    }
        
}

add_action( 'admin_notices', 'db_admin_notices' )




