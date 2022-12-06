<?php
/*
Plugin Name: WP All Import YMM Search Add-On
Description: Imports configured template data into the tables for the YMM Search Plug-in (Year, Make, and Model).
Version: 1.0
Author: Nicholas Alipaz
*/

include "rapid-addon.php";


final class YMM_Search_Add_On {

    protected static $instance;
    
    protected $ymm_db;
    protected $ymm_config;

    protected $add_on;

    static public function get_instance() {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        
        // Define the add-on
        $this->add_on = new RapidAddon('YMM Search Add-On', 'wpai_ymm_search_add_on');
        
        // Add UI elements to the import template
        $this->add_on->add_field('ymm_search_restrictions', 'YMM Search', 'textarea');

        $this->add_on->set_import_function([$this, 'import']);
        add_action('admin_init', [$this, 'admin_init']);
        
        if (!is_plugin_active('ymm-search/ymm-search.php')) {
            include_once(Pektsekye_YMM()->getPluginPath() . 'Model/Db.php');
            $this->ymm_db = new Pektsekye_Ymm_Model_Db();
        }
    }

    // Check if YMM Search is installed and activate
    public function admin_init() {
        if (function_exists('is_plugin_active')) {
            
            // Display this notice if YMM Search is not active.
            if (!is_plugin_active('ymm-search/ymm-search.php')) {
                // Specify a custom admin notice.
                $this->add_on->admin_notice(
                    'The YMM Search Add-On requires WP All Import <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a> and the <a href="https://wordpress.org/plugins/ymm-search/">YMM Search</a> plugin.'
                );
            }
            
            // Only run this add-on if the YMM Search plugin is active.
            if (is_plugin_active('ymm-search/ymm-search.php')) {
                $this->add_on->run();
            }
        }
    }
    
    public function getFormatExplanationMessage() {
        return __('Correct format is four columns in a row (three commas). Then a new line. Example:<br> Daihatsu, Altis, 1990, 2005<br>Toyota, Caldina, 1997, 2008 <br>Toyota, Camry, 1993, 2000<br><br>All models of one make:<br>Daihatsu, , 0, 0<br><br>All makes and models:<br> , , 0, 0', 'ymm-search');
    }

    // Check if the user has allowed these fields to be updated, and then import data to them
    public function import($post_id, $data, $import_options) {
        if ($this->add_on->can_update_meta('_ymm_search_restrictions', $import_options)) {
            if (isset($data['ymm_search_restrictions'])) {
                $restriction = sanitize_textarea_field(stripslashes($data['ymm_search_restrictions']));

                try {
                    $this->ymm_db->saveProductRestrictionText($post_id, $restriction);
                }
                catch (Exception $e) {
                    $logger = function($m) { printf("[%s] $m", date("H:i:s")); flush(); };
                    call_user_func($logger, 'YMM restriction was not saved.' . ' ' . $this->getFormatExplanationMessage()); 
                }
            }
        }
    }
}

YMM_Search_Add_On::get_instance();
