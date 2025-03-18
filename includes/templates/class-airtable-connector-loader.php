<?php
/**
 * Loader class to manage all plugin components
 */
class Airtable_Connector_Loader {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Store instances of plugin components
     */
    public $api = null;
    public $cache = null;
    public $shortcode = null;
    public $admin = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct creation
     */
    private function __construct() {
        $this->load_dependencies();
        $this->initialize_components();
    }
    
    /**
     * Load all required files
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-airtable-connector-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-airtable-connector-cache.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-airtable-connector-shortcode.php';
        
        if (is_admin()) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-airtable-connector-admin.php';
        }
    }
    
    /**
     * Initialize all components
     */
    private function initialize_components() {
        // Create instances in correct order (dependency first)
        $this->api = new Airtable_Connector_API();
        $this->cache = new Airtable_Connector_Cache();
        $this->shortcode = new Airtable_Connector_Shortcode($this->api, $this->cache);
        
        if (is_admin()) {
            $this->admin = new Airtable_Connector_Admin($this->api, $this->cache);
        }
    }
}