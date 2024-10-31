<?php
/**
 * Plugin Name: PopNotifi
 * Plugin URI: http://popnotifi.com/wordpress
 * Description: PopNotifi makes it super quick & easy to send mass notifications to your users on mobile and desktop, you will be up and running in less than 5 minutes.
 * Version: 1.0
 * Author: PopNotifi
 * Author URI: http://popnotifi.com
 * License: GPL2
 */

add_action( 'wp_enqueue_scripts', 'popnotifi_inject_script' );
add_action( 'admin_menu', 'popnotifi_plugin_menu' );
add_action( 'admin_init', 'popnotifi_register_mysettings' );
add_action( 'admin_notices','popnotifi_warn_nosettings');
add_action( 'publish_post', 'popnotifi_process_post');

function popnotifi_inject_script(){
    $popnotifi_url_key = get_option('popnotifi_url_key');

    if($popnotifi_url_key) {
        wp_enqueue_script('popnotifi', 'https://popnotifi-cdn.com/lib/' . $popnotifi_url_key . '/integration.js', array(), false, false);
    }
}

function popnotifi_process_post($post_ID){
    $notificationSent = get_post_meta($post_ID, 'pop_notifi_notification_sent', true);
    if($notificationSent != "yes") {
        $title = sanitize_text_field(get_bloginfo('name'));
        $body = sanitize_text_field(get_the_title($post_ID));
        $url = get_permalink($post_ID);
        $thumbnail = "";
        if (has_post_thumbnail()) {
            if (function_exists("get_the_post_thumbnail_url")) {
                $thumbnail = get_the_post_thumbnail_url($post_ID, 'thumbnail');
            } else {
                $thumb_id = get_post_thumbnail_id($post_ID);
                $thumb_url = wp_get_attachment_image_src($thumb_id, 'thumbnail-size', true);
                if (count($thumb_url) > 0) {
                    $thumbnail = $thumb_url[0];
                }
            }
        }

        $apiKey = get_option("popnotifi_api_key");
        if ($title <> "" && $body <> "" && $apiKey <> "") {
            $apiUrl = 'https://api.popnotifi.com/send';
            $fields = array(
                'notification_title' => urlencode($title),
                'notification_description' => urlencode($body),
                'notification_url' => urlencode($url),
                'notification_icon' => urlencode($thumbnail),
            );

            $args = array(
                'body' => $fields,
                'timeout' => '30',
                'redirection' => '30',
                'httpversion' => '1.0',
                'blocking' => true,
                'cookies' => array(),
                'headers' => array(
                    'token' => $apiKey,
                ),
            );
            $response = wp_remote_post($apiUrl, $args );
            add_post_meta($post_ID, 'pop_notifi_notification_sent', 'yes');
        }
    }
}

function popnotifi_plugin_menu() {
    add_options_page('PopNotifi', 'PopNotifi', 'create_users', 'popnotifi_options', 'popnotifi_options_page');
}

function popnotifi_register_mysettings(){
    register_setting('popnotifi_options','popnotifi_url_key');
    register_setting('popnotifi_options','popnotifi_api_key');
    register_setting('popnotifi_options','popnotifi_native_enabled');
}

function popnotifi_options_page() {
    echo '<div class="wrap">';?>
    <h2>PopNotifi</h2>
    <p>You need to have a <a target="_blank" href="https://popnotifi.com">PopNotifi</a> account in order to use this plugin. This plugin inserts the neccessary code into your Wordpress site automatically without you having to touch anything. In order to use the plugin, you need to enter your PopNotifi Website ID (Your Website ID (a string of characters) can be found in your Dashboard area after you <a target="_blank" href="https://popnotifi.com/login">login</a> into your PopNotifi account.)</p>

    <p>If you want to setup notification sending from your wordpress website you will have to enter an api key in the field labeled "PopNotifi API Key" below, you can find/generate an api key by going to your dashboard, select the corresponding website in the dashboard and than select API Keys from the left menu</p>
    <form method="post" action="options.php">
    <?php settings_fields( 'popnotifi_options' ); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Your PopNotifi Website ID</th>
            <td><input type="text" name="popnotifi_url_key" value="<?php echo get_option('popnotifi_url_key'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row">PopNotifi API Key</th>
            <td><input type="text" name="popnotifi_api_key" value="<?php echo get_option('popnotifi_api_key'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row">Add Native Tags</th>
            <td>
                <select name="popnotifi_native_enabled">
                    <option value="0" <?php if(get_option('popnotifi_native_enabled') == 0){?>selected<?php } ?>>No</option>
                    <option value="1" <?php if(get_option('popnotifi_native_enabled') == 1){?>selected<?php } ?>>Yes</option>
                </select>
            </td>
        </tr>
    </table>

    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
    <p>PopNotifi lets you send / schedule notifications to your users, push notifications work great in driving users back to your website. It is expected that in a couple of years Push Notifications will replace email as the primary source of communication with your users.</p>
    <br /><br />
    <?php
    echo '</div>';
}

function popnotifi_warn_nosettings(){
    if (!is_admin())
        return;

    $option = get_option("popnotifi_url_key");
    if (!$option){
        echo "<div class='updated fade'><p><strong>PopNotifi is almost ready.</strong> You must <a target=\"_blank\" href=\"https://app.popnotifi.com/websites\">enter your Website ID</a> for it to work.</p></div>";
    }

    $option = get_option("popnotifi_api_key");
    if (!$option){
        echo "<div class='updated fade'><p><strong>In order to send notifications from your website, you need to enter an api key, you can generate one from your website dashboard > API Keys</p></div>";
    }
}
?>