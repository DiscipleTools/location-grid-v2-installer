<?php
/**
 * Plugin Name: Location Grid V2 Installer
 * Plugin URI: https://github.com/Pray4Movement/location-grid-v2-installer
 * Description: Small utility to add the latest location grid database and overwrite the current one. (386k+)
 * Version:  2.4
 * Author URI: https://github.com/Pray4Movement
 * GitHub Plugin URI: https://github.com/Pray4Movement/location-grid-v2-installer
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.5
 *
 * @package Disciple_Tools
 * @link    https://github.com/Pray4Movement
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 0.1 Working installer
 *          0.2 Table replacement with refactoring.
 *          0.3 Populations for colorado and slovenia
 *          0.4 Added US populations to admin2
 *          2.4 Matched version number to the v2 LG naming
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

add_action( 'after_setup_theme', function (){
    // must be in admin area
    if ( ! is_admin() ) {
        return false;
    }

    $required_dt_theme_version = '1.4.0';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;
    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $required_dt_theme_version, "<" ) ) {
        add_action('admin_notices', function () {
            ?>
            <div class="notice notice-error notice-location_grid_v2_installer is-dismissible" data-notice="location_grid_v2_installer">Disciple
                Tools Theme not active or not latest version for this plugin.
            </div><?php
        });
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }
    /*
     * Don't load the plugin on every rest request. Only those with the 'sample' namespace
     */
    $is_rest = dt_is_rest();
    if ( !$is_rest ){
        return Location_Grid_Full_DB_Updater::instance();
    }
    return false;
} );


/**
 * Class Location_Grid_Full_DB_Updater
 */
class Location_Grid_Full_DB_Updater {

    public $version = 2.4;
    public $token = 'upgrade_lgdb';
    public $title = 'Location Grid v2 Installer';
    public $permissions = 'manage_dt';


    /**  Singleton */
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        $this->title = $this->title . ' (' . $this->version . ')';

        if ( is_admin() ) {
            add_action( "admin_menu", [ $this, "register_menu" ] );
        }

    } // End __construct()

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', $this->title, $this->title, $this->permissions, $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( $this->permissions ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        ?>
        <div class="wrap">
            <h2><?php echo esc_html( $this->title ) ?></h2>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->main_column(); ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                        <div id="postbox-container-1" class="postbox-container">
                            <!-- Right Column -->

                            <?php $this->right_column(); ?>

                            <!-- End Right Column -->
                        </div><!-- postbox-container 1 -->
                        <div id="postbox-container-2" class="postbox-container">
                        </div><!-- postbox-container 2 -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
        </div><!-- End wrap -->

        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th><p style="max-width:450px"></p>
                    <p><a class="button" id="upgrade_button" href="<?php echo esc_url( trailingslashit( admin_url() ) ) ?>admin.php?page=<?php echo esc_attr( $this->token ) ?>&loop=true" disabled="true">Upgrade Away!</a></p>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            /* disable button */
            if ( ! isset( $_GET['loop'] ) ) {
                ?>
                <script>
                    jQuery(document).ready(function(){
                        jQuery('#upgrade_button').removeAttr('disabled')
                    })

                </script>
                <?php
            }
            /* Start loop & add spinner */
            if ( isset( $_GET['loop'] ) && ! isset( $_GET['step'] ) ) {
                ?>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px" alt="spinner" /></td>
                </tr>
                <script type="text/javascript">
                    <!--
                    function nextpage() {
                        location.href = "<?php echo admin_url() ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&loop=true&step=1&nonce=<?php echo wp_create_nonce( 'loop'.get_current_user_id() ) ?>";
                    }
                    setTimeout( "nextpage()", 1500 );
                    //-->
                </script>
                <?php
            }

            /* Loop */
            if ( isset( $_GET['loop'], $_GET['step'], $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'loop'.get_current_user_id() ) ) {
                $step = sanitize_text_field( wp_unslash( $_GET['step'] ) );
                $this->run_loop( $step );
            }

            ?>
            </tbody>
        </table>
        <?php

    }

    public function run_loop( $step ){
        $func = 'process_step_' . $step;
        $result = false;
        if ( method_exists( $this, $func ) ){
            $result = $this->$func();
        }
        if ( $result ) {
            ?>
            <?php foreach( $this->steps as $index => $label ) :
                if ( $index <= $step ) : ?>
                    <tr>
                        <td><?php echo esc_html( $label ) ?></td>
                    </tr>
                <?php endif; endforeach; ?>
            <?php if ( $step < count( $this->steps ) - 1 ) : ?>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px" alt="spinner" /></td>
                </tr>
                <script type="text/javascript">
                    <!--
                    function nextpage() {
                        location.href = "<?php echo admin_url() ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&loop=true&step=<?php echo esc_attr( $step + 1 ) ?>&nonce=<?php echo wp_create_nonce( 'loop'.get_current_user_id() ) ?>";
                    }
                    setTimeout( "nextpage()", 1500 );
                    //-->
                </script>
            <?php endif; ?>
            <?php
        } else {
            ?>
            Ooops. Error found. Step <?php echo esc_html( $step ); ?><br>
            <?php
            print_r($result);
        }
    }

    public $steps = [
        '', // 0
        'Create staging table', // 1
        'Upload batch 1 of 12 records',
        'Upload batch 2 of 12 records',
        'Upload batch 3 of 12 records',
        'Upload batch 4 of 12 records',
        'Upload batch 5 of 12 records',
        'Upload batch 6 of 12 records',
        'Upload batch 7 of 12 records',
        'Upload batch 8 of 12 records',
        'Upload batch 9 of 12 records',
        'Upload batch 10 of 12 records',
        'Upload batch 11 of 12 records',
        'Upload batch 12 of 12 records',
        'Migrate custom records',
        'Swap live with new database',
        'Update post records',
        'Finish'
    ];

    public function process_step_1() {
        global $wpdb;
        dt_write_log(__METHOD__);
        $wpdb->query("
        DROP TABLE IF EXISTS `{$wpdb->prefix}dt_location_grid_upgrade`
        ");
        $result = $wpdb->query("
        CREATE TABLE `{$wpdb->prefix}dt_location_grid_upgrade` (
              `grid_id` bigint(20) NOT NULL AUTO_INCREMENT,
              `name` varchar(200) NOT NULL DEFAULT '',
              `level` float DEFAULT NULL,
              `level_name` varchar(7) DEFAULT NULL,
              `country_code` varchar(10) DEFAULT NULL,
              `admin0_code` varchar(10) DEFAULT NULL,
              `parent_id` bigint(20) DEFAULT NULL,
              `admin0_grid_id` bigint(20) DEFAULT NULL,
              `admin1_grid_id` bigint(20) DEFAULT NULL,
              `admin2_grid_id` bigint(20) DEFAULT NULL,
              `admin3_grid_id` bigint(20) DEFAULT NULL,
              `admin4_grid_id` bigint(20) DEFAULT NULL,
              `admin5_grid_id` bigint(20) DEFAULT NULL,
              `longitude` float DEFAULT NULL,
              `latitude` float DEFAULT NULL,
              `north_latitude` float DEFAULT NULL,
              `south_latitude` float DEFAULT NULL,
              `east_longitude` float DEFAULT NULL,
              `west_longitude` float DEFAULT NULL,
              `population` bigint(20) NOT NULL DEFAULT '0',
              `modification_date` date DEFAULT NULL,
              `alt_name` varchar(200) DEFAULT NULL,
              `alt_population` bigint(20) DEFAULT '0',
              `is_custom_location` tinyint(1) NOT NULL DEFAULT '0',
              `alt_name_changed` tinyint(1) NOT NULL DEFAULT '0',
              PRIMARY KEY (`grid_id`),
              KEY `level` (`level`),
              KEY `latitude` (`latitude`),
              KEY `longitude` (`longitude`),
              KEY `admin0_code` (`admin0_code`),
              KEY `parent_id` (`parent_id`),
              KEY `country_code` (`country_code`),
              KEY `north_latitude` (`north_latitude`),
              KEY `south_latitude` (`south_latitude`),
              KEY `west_longitude` (`east_longitude`),
              KEY `east_longitude` (`west_longitude`),
              KEY `admin0_grid_id` (`admin0_grid_id`),
              KEY `admin1_grid_id` (`admin1_grid_id`),
              KEY `admin2_grid_id` (`admin2_grid_id`),
              KEY `admin3_grid_id` (`admin3_grid_id`),
              KEY `admin4_grid_id` (`admin4_grid_id`),
              KEY `admin5_grid_id` (`admin5_grid_id`),
              KEY `level_name` (`level_name`),
              KEY `population` (`population`),
              FULLTEXT KEY `name` (`name`),
              FULLTEXT KEY `alt_name` (`alt_name`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1003867580 DEFAULT CHARSET=utf8;
        ");

        if ( $result === false ) {
            return new WP_Error(__METHOD__, 'Did not create table', ['error', $result ] );
        }

        return true;
    }
    public function process_step_2() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file(trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_0.tsv' );
    }
    public function process_step_3() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_1.tsv' );
    }
    public function process_step_4() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_2.tsv' );
    }
    public function process_step_5() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_3.tsv' );
    }
    public function process_step_6() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_4.tsv' );
    }
    public function process_step_7() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_5.tsv' );
    }
    public function process_step_8() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_6.tsv' );
    }
    public function process_step_9() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_7.tsv' );
    }
    public function process_step_10() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_8.tsv' );
    }
    public function process_step_11() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_9.tsv' );
    }
    public function process_step_12() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_10.tsv' );
    }
    public function process_step_13() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_11.tsv' );
    }
    public function process_step_14() {
        dt_write_log(__METHOD__);
        return $this->install_upgrade_db_file( trailingslashit( plugin_dir_path(__FILE__) ) . 'files/dt_full_location_grid_12.tsv' );
    }
    public function process_step_15() {
        dt_write_log(__METHOD__);
        /* @todo Migrate custom table records to new database table*/

        return true;
    }
    public function process_step_16() {
        dt_write_log(__METHOD__);
        /**
         * Swap upgrade table as new table
         */
        global $wpdb;
        // drop current dt_location_grid table
        $wpdb->query("
        DROP TABLE IF EXISTS `{$wpdb->prefix}dt_location_grid`
        ");
        // rename upgrade table to dt_location_grid
        $wpdb->query("
        RENAME TABLE `{$wpdb->prefix}dt_location_grid_upgrade` TO `{$wpdb->prefix}dt_location_grid`;
        ");
        return true;
    }
    public function process_step_17() {
        dt_write_log(__METHOD__);
        /**
         * @todo Update post records with new grid information
         * This should only apply to lowest level, mapbox geolocations that were place or city, and can now be geocoded to a lower level.
         */
        return true;
    }

    public function install_upgrade_db_file( $file ) {
        global $wpdb;
        $fp = fopen( $file, 'r' );

        $query = "INSERT IGNORE INTO `{$wpdb->prefix}dt_location_grid_upgrade` VALUES ";

        $count = 0;
        while ( ! feof( $fp ) ) {
            $line = fgets( $fp, 2048 );
            $count++;

            $data = str_getcsv( $line, "\t" );

            $data_sql = $this->dt_array_to_sql( $data );

            if ( isset( $data[24] ) ) {
                $query .= " ( $data_sql ), ";
            }
            if ( $count === 500 ) {
                $query .= ';';
                $query = str_replace( ", ;", ";", $query ); //remove last comma
                $result = $wpdb->query( $query );  //phpcs:ignore
                if ( $result === false ) {
                    return new WP_Error(__METHOD__ . ': Inside 500 Count', 'Failed query', ['error', $result ] );
                }
                $query = "INSERT IGNORE INTO `{$wpdb->prefix}dt_location_grid_upgrade` VALUES ";
                $count = 0;
            }
        }
        if ( strpos($query, '(' ) !== false ) {
            //add the last queries
            $query .= ';';
            $query = str_replace( ", ;", ";", $query ); //remove last comma
            $result = $wpdb->query( $query );  //phpcs:ignore
            if ( $result === false ) {
                return new WP_Error(__METHOD__, 'Failed query 2', ['error', $result ] );
            }
        }

        return true;
    }

    public function dt_array_to_sql( $values) {
            if (empty( $values )) {
                return 'NULL';
            }
            foreach ($values as &$val) {
                if ('\N' === $val) {
                    $val = 'NULL';
                } else {
                    $val = "'" . esc_sql( trim( $val ) ) . "'";
                }
            }
        return implode( ',', $values );
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr><th>WARNING!!</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    This plugin installs the full location grid database, migrates the current location-grid, and replaces it.
                    The process can take a few minutes. Do not interrupt or navigate away from the page. Errors will be displayed.
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {

    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return $this->token;
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     *
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        // @codingStandardsIgnoreLine
        _doing_it_wrong( __FUNCTION__, esc_html('Whoah, partner!'), '0.1' );
        unset( $method, $args );
        return null;
    }
}

// Register activation hook.
register_activation_hook( __FILE__, [ 'Location_Grid_Full_DB_Updater', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'Location_Grid_Full_DB_Updater', 'deactivation' ] );

add_action( 'plugins_loaded', function (){
    if ( is_admin() ){
        // Check for plugin updates
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' )){
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ){
            Puc_v4_Factory::buildUpdateChecker(
                'https://raw.githubusercontent.com/Pray4Movement/location-grid-v2-installer/master/version-control.json',
                __FILE__,
                'location-grid-v2-installer'
            );

        }
    }
} );
