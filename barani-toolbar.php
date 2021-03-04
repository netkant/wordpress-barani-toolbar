<?php
/**
 * Plugin Name: Barani - Toolbar
 * Plugin URI: https://barani.io
 * Description: This plugin will help you clear you website cache. Look out for more helpful features in the future.
 * Version: 1.0.0
 * Author: Netkant
 * Author URI: https://netkant.com
 * License: GPLv2 or later
 * Text Domain: barani-toolbar
 */

class BaraniToolbar
{
    const VERSION = '1.0.0';
    
    /**
     * Undocumented function
     * @return void
     */
    public static function init()
    {
        // ...
        if (!is_user_logged_in())
            return;
        
        // ...
        $user = wp_get_current_user();
        if (!$user->has_cap('edit_posts') && !$user->has_cap('edit_pages'))
            return;

        // ...
        add_action('wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ]);
        add_action('admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ]);
        add_action('admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 10);

        // ...
        if (isset($_SERVER['BARANI_API_TOKEN'])) {
            add_action('wp_ajax_barani_clear_page_cache', [ __CLASS__, 'wp_ajax_barani_clear_page_cache' ], 10);
            add_action('wp_ajax_barani_clear_all_cache', [ __CLASS__, 'wp_ajax_barani_clear_all_cache' ], 10);
        }
    }

    /**
     * Undocumented function
     * @return void
     */
    public function enqueue_assets()
    {
        // ...
        wp_enqueue_style('barani-toolbar', plugin_dir_url( __FILE__ ) . 'css/styles.css', array(), self::VERSION);

        // ...
        wp_register_script('barani-toolbar', plugin_dir_url( __FILE__ ) . 'js/script.js');
        wp_localize_script('barani-toolbar', 'barani_toolbar', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));

        // ...
        wp_enqueue_script('barani-toolbar', '', array('jquery'), self::VERSION, true);
    }
     
    /**
     * Undocumented function
     * @param [type] $admin_bar
     * @return void
     */
    public function admin_bar_menu($admin_bar) {         
        $admin_bar->add_node(array(
            'parent' => 'top-secondary',
            'id'     => 'barani-toolbar',
            'title'  => 'Barani',
            'href'   => '#'
        ));

        if (!is_admin() && isset($_SERVER['BARANI_API_TOKEN'])) {
            $admin_bar->add_node(array(
                'parent' => 'barani-toolbar',
                'id'     => 'barani-clear-page-cache',
                'title'  => 'Clear Page Cache',
                'href'   => '#'
            ));
        }

        if (isset($_SERVER['BARANI_API_TOKEN'])) {
            $admin_bar->add_node(array(
                'parent' => 'barani-toolbar',
                'id'     => 'barani-clear-all-cache',
                'title'  => 'Clear All Cache',
                'href'   => '#'
            ));
        }
    }

    /**
     * Undocumented function
     * @return void
     */
    public function wp_ajax_barani_clear_page_cache()
    {
        list($code, $data) = self::barani_clear_cache(isset($_SERVER['HTTP_REFERER']) ? [ $_SERVER['HTTP_REFERER'] ] : []);
        wp_send_json($data, $code);
    }

    /**
     * Undocumented function
     * @return void
     */
    public function wp_ajax_barani_clear_all_cache()
    {
        list($code, $data) = self::barani_clear_cache();
        wp_send_json($data, $code);
    }

    /**
     * Undocumented function
     * @param array $urls
     * @return void
     */
    public static function barani_clear_cache($urls = [])
    {
        // ...
        if (!isset($_SERVER['BARANI_API_TOKEN'])) {
            return [ 500, 'API token is missing' ];
        }

        //setup the request, you can also use CURLOPT_URL
        $ch = curl_init('https://api.micusto.cloud/?r=space/clear-cache');

        // ...
        if (!empty($urls)) {
            $json = json_encode(array('urls' => $urls ));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($json)));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        // Returns the data/output as a string instead of raw data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $_SERVER['BARANI_API_TOKEN']
        ));

        // get stringified data/output. See CURLOPT_RETURNTRANSFER
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close curl resource (to free up system resources)
        curl_close($ch);

        return [ $code, json_decode($data) ];
    }
}

// ...
add_action('init', [ 'BaraniToolbar', 'init' ]);
