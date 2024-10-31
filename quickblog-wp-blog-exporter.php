<?php
/*
Plugin Name: Quickblog WP Blog Exporter
Description: Exports existing posts to Google Sheets
Version:     1.4.3
Author:      Quickblog
Author URI:  https://www.quickblog.co
Text Domain: cpe2-google-spreadsheet
*/

defined( 'ABSPATH' ) or die;

define( 'CPE2_GOOGLE_SPREADSHEET_VER', '1.4.0' );

if ( ! class_exists( 'CPE2_Google_Spreadsheet' ) ) {
	class CPE2_Google_Spreadsheet {
		public static function getInstance() {
			if ( self::$instance == null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private static $instance = null;

		private function __clone() { }

		private function __wakeup() { }

		private function __construct() {
			$this->ajax_loader_url = plugins_url( 'images/ajax-loader.gif', __FILE__ );
			$this->logo_url = plugins_url( 'images/logo.png', __FILE__ );
			$this->seo_titles = '';

			// Actions
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'wp_ajax_cpe2gs_export', array( $this, 'ajax_export_posts' ) );
		}

		public function admin_enqueue_scripts( $hn ) {
			if ( $hn != 'toplevel_page_cpe2-google-spreadsheet' ) return;
			wp_enqueue_script( 'cpe2-google-spreadsheet', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), CPE2_GOOGLE_SPREADSHEET_VER, true );
			wp_localize_script( 'cpe2-google-spreadsheet', 'CPE2GSAdminData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n' => array(
					'NSError' => __( 'Network/Server Error', 'cpe2-google-spreadsheet' ),
					'done' => __( 'Done', 'cpe2-google-spreadsheet' )
				)
			) );
		}

		public function add_admin_menu() {
			add_menu_page(
				__( 'Quickblog WPBE', 'cpe2-google-spreadsheet' ),
				__( 'Quickblog WPBE', 'cpe2-google-spreadsheet' ),
				'manage_options',
				'cpe2-google-spreadsheet',
				array( $this, 'render_dashboard' ),
				'dashicons-database-export'
			);
		}

		public function render_dashboard() {
			require( __DIR__ . '/dashboard.php' );
		}

		public function ajax_export_posts() {
			$share_url = isset( $_POST['share_url'] ) ? sanitize_text_field( $_POST['share_url'] ) : '';
			$sheet_name     = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';

			if ( $share_url == '' ) wp_send_json_error( __( 'Empty share URL', 'cpe2-google-spreadsheet' ) );
			if ( $sheet_name == '' ) wp_send_json_error( __( 'Empty sheet name', 'cpe2-google-spreadsheet' ) );

			if ( ! preg_match( '/https?:\/\/docs\.google\.com\/spreadsheets\/d\/([^\/]+)/i', $share_url, $matches ) ) {
				wp_send_json_error( __( 'Invalid share URL', 'cpe2-google-spreadsheet' ) );
			} else {
				$spreadsheet_id = $matches[1];
			}

			try {
				error_reporting( E_ERROR | E_PARSE );

				require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );

				$http = new GuzzleHttp\Client( array( 'verify' => dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'cacert.pem' ) );

				$client = new Google_Client();
				$client->setHttpClient( $http );
				$client->setApplicationName( 'Custom Post Export to Google Spreadsheet' );
				$client->setScopes( Google_Service_Sheets::SPREADSHEETS );
				$client->setAuthConfig( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'credentials.json' );
				$client->setAccessType( 'offline' );
				$headingRange = $sheet_name . '!A1:L1';
				$valueRangeHeading = new Google_Service_Sheets_ValueRange();
				$valueRangeHeading->setValues( array(array('Title','Header Image URL','Date','Content','Button Text','Author','Category','SEO Primary Keyword','SEO Other Keyword','SEO Post title (60 chars)','SEO Meta Description (160 chars)', 'URL Slug','Status')));

				
				
				
				$range = $sheet_name . '!A2:L';
				$valueRange = new Google_Service_Sheets_ValueRange();
				$conf = array( 'valueInputOption' => 'RAW' );
				$service = new Google_Service_Sheets( $client );
				$data = $this->get_data();
				$valueRange->setValues( $data );
				$response = $service->spreadsheets_values->get($spreadsheet_id, $headingRange);
				$existHeadingvalues = $response->getValues();
			    if (empty($existHeadingvalues)) {
				   $service->spreadsheets_values->append( $spreadsheet_id, $headingRange, $valueRangeHeading, $conf );
				}
				$service->spreadsheets_values->append( $spreadsheet_id, $range, $valueRange, $conf );

				wp_send_json_success();
			} catch( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		private function get_data() {
			$posts = get_posts( array(
				'numberposts' => -1,
				'post_status' => 'any',
				'post_type' => 'post'
			) );

			$this->seo_titles = get_option( 'wpseo_titles', array() );

			$data = array();

			foreach( $posts as $post ) {
				$user_data = get_userdata( $post->post_author )->data;
				$image_url = get_the_post_thumbnail_url( $post->ID );
				$categories = $this->getCategories($post->ID);

				array_push( $data, array(
					$post->post_title, // Title
					$image_url ? $image_url : '', // Header Image URL
					$post->post_date_gmt, // Date (GMT)
					$this->filter_content( $post->post_content ), // Content
					'Read More',				
					$user_data->display_name,
					$categories,
					get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true ), // SEO Primary Keyword
					get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true ), // SEO Other Keyword
					$this->get_post_title( $post ), // SEO Post Title (60 chars)
					$this->get_post_description( $post ), // SEO Meta Description (160 chars),
					$post->post_name,
					$post->post_status, 
				) );
			}

			return $data;
		}
        
		private function getCategories($postid) {
			$catNames = '';
			$categories = get_the_category($postid); //$post->ID
			
			foreach($categories as $key=>$cd) {
				if($key == 0) {		
					$catNames .=$cd->cat_name;						
				} else {
					$catNames .=','.$cd->cat_name;
				}							    
			}
			return $catNames;
		}
	

		private function get_post_title( $post ) {
			$yoast_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
			if ( empty( $yoast_title ) ) {
				$wpseo_titles = $this->seo_titles;
				$yoast_title  = isset( $wpseo_titles[ 'title-' . $post->post_type ] ) ? $wpseo_titles[ 'title-' . $post->post_type ] : get_the_title();
			}

			return function_exists( 'wpseo_replace_vars' ) ? wpseo_replace_vars( $yoast_title, $post ) : $yoast_title;
		}

		private function get_post_description( $post ) {
			$yoast_post_description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			if ( empty( $yoast_post_description ) ) {
				$wpseo_titles           = $this->seo_titles;
				$yoast_post_description = isset( $wpseo_titles[ 'metadesc-' . $post->post_type ] ) ? $wpseo_titles[ 'metadesc-' . $post->post_type ] : '';
			}

			return function_exists( 'wpseo_replace_vars' ) ? wpseo_replace_vars( $yoast_post_description, $post ) : $yoast_post_description;
		}

		private function get_gsp_editor_email() {
			$data = json_decode( file_get_contents( __DIR__ . '/credentials.json' ) );
			return $data->client_email;
		}

		private function filter_content( $content ) {
			$content = preg_replace( '/<\!--\s*wp\:paragraph\s*-->\n?\r?/', '', $content );
			$content = preg_replace( '/\n\r?<\!--\s*\/wp\:paragraph\s*-->/', '', $content );
			return $content;
		}
	}
}
CPE2_Google_Spreadsheet::getInstance();