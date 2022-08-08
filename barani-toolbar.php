<?php

/**
 * Plugin Name: Barani - Toolbar
 * Plugin URI: https://barani.io
 * Description: This plugin will help you clear you website cache. Look out for more helpful features in the future.
 * Version: 1.2.0
 * Author: Netkant
 * Author URI: https://netkant.com
 * License: GPLv2 or later
 * Text Domain: barani-toolbar
 */

class BaraniToolbar
{
    const VERSION = '1.2.0';

    /**
     * Undocumented function
     * @return void
     */
    public function __construct()
    {
        // ...
        if (!is_user_logged_in())
            return;

        // ...
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
            return;

        // ...
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_bar_menu', [$this, 'admin_bar_menu'], 10);

        // ...
        add_action('wp_print_styles', [$this, 'wp_print_styles']);

        // ...
        add_action('wp_ajax_barani_clear_object_cache', [$this, 'wp_ajax_barani_clear_object_cache'], 10);

        // ...
        if (isset($_SERVER['BARANI_API_TOKEN'])) {
            add_action('wp_ajax_barani_clear_page_cache', [$this, 'wp_ajax_barani_clear_page_cache'], 10);
            add_action('wp_ajax_barani_clear_style_cache', [$this, 'wp_ajax_barani_clear_style_cache'], 10);
            add_action('wp_ajax_barani_clear_script_cache', [$this, 'wp_ajax_barani_clear_script_cache'], 10);
            add_action('wp_ajax_barani_clear_all_cache', [$this, 'wp_ajax_barani_clear_all_cache'], 10);
        }
    }

    /**
     * Undocumented function
     * @return void
     */
    public function enqueue_assets()
    {
        // ...
        wp_enqueue_style('barani-toolbar', plugin_dir_url(__FILE__) . 'css/styles.css', array(), $this::VERSION);

        // ...
        wp_register_script('barani-toolbar', plugin_dir_url(__FILE__) . 'js/script.js');
        wp_localize_script('barani-toolbar', 'barani_toolbar', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'post_id' => get_the_ID(),
        ));

        // ...
        wp_enqueue_script('barani-toolbar', '', array('jquery'), $this::VERSION, true);
    }

    public function wp_print_styles()
    {
        global $wp_styles;

        // ...
        $transient_name  = sprintf('_barani_toolbar_enqueued_styles_%s', get_the_ID());
        $enqueued_styles = get_transient($transient_name);
        if (!is_array($enqueued_styles))
            $enqueued_styles = [];

        // ...
        foreach ($wp_styles->queue as $handle) {
            $src = $wp_styles->registered[$handle]->src;
            if (!is_string($src))
                continue;

            $url = '';
            if (strpos($src, get_site_url()) === 0) {
                $url = $src;
            } elseif (strpos($src, '/') === 0) {
                $url = get_site_url(null, $src);
            }

            if (!in_array($url, $enqueued_styles) && strpos($url, content_url()) === 0) {
                $enqueued_styles[] = $url;
            }
        }

        // ...
        set_transient($transient_name, $enqueued_styles);
    }

    /**
     * Undocumented function
     * @param [type] $admin_bar
     * @return void
     */
    public function admin_bar_menu($admin_bar)
    {
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

        $admin_bar->add_node(array(
            'parent' => 'barani-toolbar',
            'id'     => 'barani-clear-object-cache',
            'title'  => 'Clear Object Cache',
            'href'   => '#'
        ));

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
        // ...
        if (!isset($_POST['post_id']))
            wp_send_json([], 412);

        // ...
        $post_id         = absint($_POST['post_id']);
        $transient_name  = sprintf('_barani_toolbar_enqueued_styles_%s', $post_id);
        $enqueued_styles = get_transient($transient_name);

        // ...
        $urls   = is_array($enqueued_styles) ? $enqueued_styles : [];
        $urls[] = get_permalink($post_id);

        // ...
        list($code, $data) = $this->barani_clear_cache($urls);
        wp_send_json($data, $code);
    }

    /**
     * Undocumented function
     * @return void
     */
    public function wp_ajax_barani_clear_style_cache()
    {
        global $wp_styles;

        $urls = [];
        foreach ($wp_styles->registered as $style) {

            $src = $style->src;
            if (!is_string($src))
                continue;

            $url = '';
            if (strpos($src, get_site_url()) === 0) {
                $url = $src;
            } elseif (strpos($src, '/') === 0) {
                $url = get_site_url(null, $src);
            }

            if (strpos($url, content_url()) === 0) {
                $urls[] = $url;
            }
        }

        wp_send_json($urls, 200);

        #list($code, $data) = $this->barani_clear_cache($urls);
        #wp_send_json($data, $code);
    }

    /**
     * Undocumented function
     * @return void
     */
    public function wp_ajax_barani_clear_object_cache()
    {
        wp_cache_flush();
        wp_send_json([], 200);
    }

    /**
     * Undocumented function
     * @return void
     */
    public function wp_ajax_barani_clear_all_cache()
    {
        wp_cache_flush();
        list($code, $data) = $this->barani_clear_cache();
        wp_send_json($data, $code);
    }

    /**
     * Undocumented function
     * @param array $urls
     * @return void
     */
    public function barani_clear_cache($urls = [])
    {
        // ...
        if (!isset($_SERVER['BARANI_API_TOKEN'])) {
            return [500, 'API token is missing'];
        }

        //setup the request, you can also use CURLOPT_URL
        $ch = curl_init('https://api.micusto.cloud/?r=space/clear-cache');

        // ...
        if (!empty($urls)) {
            $json = json_encode(array('urls' => $urls));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($json)));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        // Returns the data/output as a string instead of raw data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SERVER['BARANI_API_TOKEN']
        ));

        // get stringified data/output. See CURLOPT_RETURNTRANSFER
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close curl resource (to free up system resources)
        curl_close($ch);

        return [$code, json_decode($data)];
    }
}

// ...
add_action('init', function () {
    new BaraniToolbar;
});
