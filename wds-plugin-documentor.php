<?php
/**
 * Plugin Name: WDS Plugin Documentor
 * Plugin URI:  http://webdevstudios.com/
 * Description: Allows developers to provide information to their clients about installed plugins.  Multi-site compatible.
 * Version:     0.1.0
 * Author:      WebDevStudios, Jay Wood
 * Author URI:  http://webdevstudios.com
 * License:     GPLv2+
 * Text Domain: wds_plugin_documentor
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 WebDevStudios, Jay Wood
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

/**
 * Main initiation class
 */
class WDS_Plugin_Documentor {

	const VERSION = '0.1.0';
	protected $cpt = 'wds-plugin-doc';

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 */
	public function __construct() {
	}

	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_hooks' ) );
	}

	/**
	 * Init hooks
	 * @since  0.1.0
	 * @return null
	 */
	public function init() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wds_plugin_documentor' );
		load_textdomain( 'wds_plugin_documentor', WP_LANG_DIR . '/wds_plugin_documentor/wds_plugin_documentor-' . $locale . '.mo' );
		load_plugin_textdomain( 'wds_plugin_documentor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		$this->add_new_capability = apply_filters( 'wds-plugin-documentor-add-new-capability', 'manage_options' );
		$this->can_see_capability = apply_filters( 'wds-plugin-documentor-visible-capability', 'manage_options' );

		$labels = array(
			'name'                => __( 'Plugin Info', 'wds_plugin_documentor' ),
			'singular_name'       => __( 'Plugin Info', 'wds_plugin_documentor' ),
			'add_new'             => _x( 'Add Plugin Notes', 'wds_plugin_documentor', 'wds_plugin_documentor' ),
			'add_new_item'        => __( 'Add Plugin Notes', 'wds_plugin_documentor' ),
			'edit_item'           => __( 'Edit Plugin Info', 'wds_plugin_documentor' ),
			'new_item'            => __( 'New Plugin Info', 'wds_plugin_documentor' ),
			'view_item'           => __( 'View Plugin Info', 'wds_plugin_documentor' ),
			'search_items'        => __( 'Search Plugin Info', 'wds_plugin_documentor' ),
			'not_found'           => __( 'No Plugins found', 'wds_plugin_documentor' ),
			'not_found_in_trash'  => __( 'No Plugins found in Trash', 'wds_plugin_documentor' ),
			'parent_item_colon'   => __( 'Parent Plugins:', 'wds_plugin_documentor' ),
			'menu_name'           => __( 'Plugin Info', 'wds_plugin_documentor' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'A CPT that houses notes about installed plugins.', 'wds_plugin_documentor' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'query_var'           => false,
			'rewrite'             => false,
			'supports'            => array( 'title', 'editor', 'revisions' ),
		);

		register_post_type( $this->cpt, apply_filters( 'wds-plugin-documentor-cpt-args', $args ) );
	}

	/**
	 * Hooks for the Admin
	 * @since  0.1.0
	 * @return null
	 */
	public function admin_hooks() {
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_notes' ), 10, 3 );
		add_filter( 'enter_title_here', array( $this, 'filter_enter_title_here' ), 10, 2 );

		add_action( 'after_plugin_row', array( $this, 'add_plugin_notes_row' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_admin_scripts' ) );

		add_filter( 'post_updated_messages', array( $this, 'filter_udpate_messages' ) );
		add_filter( 'save_post', array( $this, 'maybe_redirect' ), 99, 2 );
		if ( isset( $_GET['return_uri'] ) ) {
			add_action( 'edit_form_top', array( $this, 'hidden_return_uri' ) );
		}
		add_action( 'delete_post', array( $this, 'maybe_fix_delete_notes_redirect' ) );
		add_filter( 'wp_redirect', array( $this, 'update_delete_notes_redirect' ) );
		add_action( 'all_admin_notices', array( $this, 'notice_for_deleting_notes' ) );

		add_action( 'add_meta_boxes_' . $this->cpt, function(){
			remove_meta_box( 'submitdiv', $this->cpt, 'side' );
			add_meta_box( 'updateBox', 'Save', array( $this, 'submit_meta_box' ), $this->cpt, 'side', 'core', array( '__back_compat_meta_box' => true ) );
		} );
	}

	public function add_plugin_notes( $plugin_meta, $plugin_file, $plugin_data ){

		if ( ! current_user_can( $this->can_see_capability ) || ! isset( $plugin_data['Name'] ) ) {
			return $plugin_meta;
		}

		$post_info = $this->getPluginNotePost( $plugin_data['Name'] ?? '' );

		if ( empty( $post_info ) && current_user_can( $this->add_new_capability ) ) {

			$add_post_url  = $this->add_return_uri( admin_url( 'post-new.php' ), array(
				'post_type'    => $this->cpt,
				'plugin_title' => urlencode( $plugin_data['Name'] ),
			) );

			$new_post_link = apply_filters( 'wds-plugin-documentor-new-post-link', $add_post_url );

			$plugin_meta[] = '<a href="' . $new_post_link . '" title="' . __( 'Add notes for this plugin.', 'wds_plugin_documentor' ) . '" class="wds-plugin-doc"><span class="dashicons dashicons-plus-alt"></span> ' . __( 'Add Plugin Notes', 'wds_plugin_documentor' ) . '</a>';

		} else if ( ! empty( $post_info ) ) {

			$plugin_meta[] = '<a href="#" class="wds-plugin-doc has_info" data-post="' . sanitize_title( $plugin_data['Name'] ) . '">' . __( 'Toggle Plugin Notes', 'wds_plugin_documentor' ) . '</a>';

		}

		return $plugin_meta;
	}

	public function add_plugin_notes_row( $plugin_file, $plugin_data ) {
		$post_info = $this->getPluginNotePost( $plugin_data['Name'] ?? '' );

		if ( empty( $post_info->post_content ) || empty( $plugin_data['Name'] ) ) {
			return;
		}

		$output  = '<tr class="wds-plugin-doc info ' . sanitize_title( $plugin_data['Name'] ) . ' hidden "><td colspan="3"><div class="slider">';

		$output .= apply_filters( 'the_content', $post_info->post_content );

		if ( current_user_can( $this->add_new_capability ) ) {
			$delete_link = get_delete_post_link( $post_info->ID, null, true );
			$edit_link = $this->add_return_uri( get_edit_post_link( $post_info->ID ) );
			$output .= '<hr><p class="edit-plugin-notes submitbox">';
			$output .= '<a href="' . $edit_link . '" title="' . __( 'Edit this plugin\'s Notes', 'wds_plugin_documentor' ) . '" class="wds-plugin-doc"><span class="dashicons dashicons-edit"></span> ' . __( 'Edit Plugin Notes', 'wds_plugin_documentor' ) . '</a>';
			$output .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
			$output .= '<a href="' . $delete_link . '" title="' . __( 'Delete this plugin\'s Notes', 'wds_plugin_documentor' ) . '" class="wds-plugin-doc submitdelete"><span class="dashicons dashicons-trash"></span> ' . __( 'Delete Plugin Notes', 'wds_plugin_documentor' ) . '</a>';
			$output .= '</p>';
		}

		$output .= '</div></td></tr>';

		echo apply_filters( 'wds-plugin-documentor-notes', $output, $plugin_data, $post_info );
	}

	public function filter_enter_title_here( $placeholder, $post ){
		if ( isset( $post->post_type ) && $this->cpt == $post->post_type ){
			// Hack to pre-set the title of the post if the get variable is present AND the post title isn't already set.
			if ( empty( $post->post_title ) && isset( $_GET['plugin_title'] ) ){
				$post->post_title = esc_attr( urldecode( $_GET['plugin_title'] ) );
			}
			return __( 'Enter the Plugin Name', 'wds_plugin_documentor' );
		}
		return $placeholder;
	}

	public function setup_admin_scripts(){
		$screen = get_current_screen();
		if ( ! isset( $screen->base, $screen->id ) ) {
			return;
		}

		if ( ! in_array( $screen->base, array( 'plugins-network', 'plugins' ) ) && 'wds-plugin-doc' != $screen->id ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( 'wds-plugin-doc', $this->url( "assets/css/wds-plugin-documentor{$min}.css" ), null, self::VERSION );
		wp_enqueue_script( 'wds-plugin-doc', $this->url( "assets/js/wds-plugin-documentor{$min}.js" ), array( 'jquery' ), self::VERSION, true );
		// Send plugins admin_url to JS
		wp_localize_script( 'wds-plugin-doc', 'WDS_Plugin_Documentor', array(
			'plugins_url' => admin_url( 'plugins.php' ),
		) );
	}

	public function filter_udpate_messages( $messages ) {
		$messages[ $this->cpt ][6] = $messages[ $this->cpt ][1] = __( 'Plugin notes updated.', 'wds_plugin_documentor' );
		return $messages;
	}

	public function maybe_redirect( $post_id, $post ) {

		// Only redirect our post-type
		if ( ! isset( $post->post_type ) || $post->post_type != $this->cpt ) {
			return;
		}

		// Don't redirect autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Only redirect if user has correct permissions
		if ( 'page' == $post->post_type ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Only redirect if post is published
		if ( 'publish' == $post->post_status && isset( $_POST['return_uri'] ) ) {
			wp_redirect( $_POST['return_uri'] );
			exit();
		}

	}

	public function hidden_return_uri() {
		// Use wp_get_referer instead
		$referrer = wp_get_referer();
		echo '<input name="return_uri" type="hidden" value="'. esc_attr( $referrer ) .'"/>';
	}

	public function maybe_fix_delete_notes_redirect( $post_id ) {
		$this->deleting_notes = $this->cpt == get_post_type( $post_id ) ? get_the_title( $post_id ) : false;
	}

	public function update_delete_notes_redirect( $location ) {
		if (
			isset( $this->deleting_notes )
			&& $this->deleting_notes
			&& false !== strpos( $location, 'plugins.php' )
			&& false !== strpos( $location, 'deleted' )
		) {
			$location = add_query_arg( 'deleted-note', urlencode( $this->deleting_notes ), remove_query_arg( 'deleted', $location ) );
		}

		return $location;
	}

	public function notice_for_deleting_notes() {
		if ( isset( $_GET['deleted-note'] ) ) {
			echo '<div id="message" class="updated"><p>'. sprintf( __( 'Plugin notes for <strong>%s</strong> have been deleted.', 'wds_plugin_documentor' ), esc_attr( urldecode( $_GET['deleted-note'] ) ) ) .'</p></div>';
		}
	}

	public function submit_meta_box( $post ) {
		global $action;

		$post_id          = (int) $post->ID;
		$isAutoDraft      = $post->post_status == 'auto-draft';
		?>
<div class="submitbox" id="submitpost">

<div id="minor-publishing">

	<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key. ?>
	<div style="display:none;">
		<?php submit_button( __( 'Save' ), '', 'save' ); ?>
	</div>

	<div id="misc-publishing-actions">
		<?php
		$date_string = __( '%1$s at %2$s' );
		$date_format = _x( 'M j, Y', 'publish box date format' );
		$time_format = _x( 'H:i', 'publish box time format' );

		if ( $isAutoDraft ) {
			$stamp = __( 'Publish <b>immediately</b>' );
			$date  = sprintf(
				$date_string,
				date_i18n( $date_format, strtotime( current_time( 'mysql' ) ) ),
				date_i18n( $time_format, strtotime( current_time( 'mysql' ) ) )
			);
		} else {
			$stamp = __( 'Published on: %s' );
			$date = sprintf(
				$date_string,
				date_i18n( $date_format, strtotime( $post->post_date ) ),
				date_i18n( $time_format, strtotime( $post->post_date ) )
			);
		}
		?>
		<div class="misc-pub-section curtime misc-pub-curtime">
			<span id="timestamp">
				<?php printf( $stamp, '<b>' . $date . '</b>' ); ?>
			</span>
			<fieldset id="timestampdiv" class="hide-if-js">
				<legend class="screen-reader-text">
					<?php
					/* translators: Hidden accessibility text. */
					_e( 'Date and time' );
					?>
				</legend>
				<?php touch_time( ( 'edit' === $action ), 1 ); ?>
			</fieldset>
			<?php if ( ! $isAutoDraft ) : ?>
				<div>
					<span id="timestamp">
						Last Modified: <b><?php echo date_i18n( $date_format, strtotime( $post->post_modified ) ); ?></b>
					</span>
				</div>
			<?php endif; ?>
		</div>
		<?php

		?>
	</div>
	<div class="clear"></div>
</div>

<div id="major-publishing-actions">
	<div id="delete-action">
		<?php
		if ( current_user_can( 'delete_post', $post_id ) ) {
			?>
			<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post_id, '', true ); ?>"><?php _e( 'Delete permanently' ); ?></a>
			<?php
		}
		?>
	</div>

	<div id="publishing-action">
		<span class="spinner"></span>
		<?php submit_button( __( 'Update' ), 'primary large', 'save', false, array( 'id' => 'publish' ) ); ?>
	</div>
	<div class="clear"></div>
</div>

</div>
		<?php
	}

	public function getPluginNotePost( $pluginName ) {
		if ( empty( $pluginName ) ) {
			return false;
		}

		$query = new WP_Query( array(
			'post_type' => $this->cpt,
			'title'     => sanitize_text_field( $pluginName ),
			'posts_per_page' => 1,
		) );
		return $query->post;
	}

	public function add_return_uri( $url, $args = array() ) {
		// Pass true, instead of the actual URL
		$args['return_uri'] = true;
		return add_query_arg( $args, $url );
	}

	/**
	 * Include a file from the includes directory
	 * @since  0.1.0
	 * @param  string $filename Name of the file to be included
	 */
	public static function include_file( $filename ) {
		$file = self::dir( 'includes/'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
	}

	/**
	 * This plugin's directory
	 * @since  0.1.0
	 * @param  string $path (optional) appended path
	 * @return string       Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url
	 * @since  0.1.0
	 * @param  string $path (optional) appended path
	 * @return string       URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $field
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'url':
			case 'path':
				return self::$field;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

}

// init our class
$WDS_Plugin_Documentor = new WDS_Plugin_Documentor();
$WDS_Plugin_Documentor->hooks();

