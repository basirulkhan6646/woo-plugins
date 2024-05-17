<?php

/*
 * Plugin Name: Base64 Image Upload using Product API
 * Plugin URI: https://github.com/basirulkhan6646
 * Description:Plugin for woocommerce product image upload using base64 url using product api
 * Author: Basirul Khan
 * Version: 1.0
 * Text Domain: bkwoo
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'aec_woocommerce_api_customize');

function aec_woocommerce_api_customize()
{

    add_action( 'woocommerce_rest_insert_product_object', 'custom_product_api_image_upload', 10, 2 );
}


function custom_product_api_image_upload( $product, $request){

    if (isset($request['images_base64_urls']) && is_array($request['images_base64_urls'])) {
        foreach ($request['images_base64_urls']  as $key => $image_base64_url) {
            if (!empty($image_base64_url)) {
                $image_data = base64_decode($image_base64_url);
                $upload_dir = wp_upload_dir();
                $image_filename = wp_unique_filename($upload_dir['path'], 'product_image_' . uniqid() . '.png');
                $image_path = $upload_dir['path'] . '/' . $image_filename;

                if (file_put_contents($image_path, $image_data) !== false) {
                    $attachment = array(
                        'post_title'     => sanitize_file_name($image_filename),
                        'post_mime_type' => 'image/png',
                        'post_status'    => 'inherit'
                    );

                    $attachment_id = wp_insert_attachment($attachment, $image_path, $product->get_id());
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $image_path);
                        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
                        if ($key === 0) {
                            $product->set_image_id($attachment_id);
                        } else {
                            $gallery_image_ids = $product->get_gallery_image_ids();
                            $gallery_image_ids[] = $attachment_id;
                            $product->set_gallery_image_ids($gallery_image_ids);
                        }
                    } else {
                        error_log('Error inserting attachment: ' . $attachment_id->get_error_message());
                    }
                } else {
                    error_log('Error writing image file to disk');
                }
            }
        }
        $product->save();
    }
}
