<?php
/**
 * Plugin Name:     elancer team | ChariGame Plugin
 * Plugin URI:      https://www.elancer-team.de
 * Description:     ChariGame is a versatile fundraising plugin designed to streamline your donation campaigns with ease. With the ability to run multiple campaigns concurrently, this plugin empowers you to manage diverse fundraising initiatives effortlessly.
 * Author:          elancer team
 * Author URI:      https://www.elancer-team.de
 * Text Domain:     elancer-donate-games-plugin
 * Domain Path:     /languages
 * Version:         0.6.5.1
 *
 * @package         Elancer_Donate_Games_Plugin
 */

namespace elancer;

use DateTime;
use stdClass;
use WP_List_Table;
use WP_Query;


/**
 * Table of Content
 *
 * 0. Addon Integration
 * 1. Custom Post Type init
 * 2. BE Dashboard Admin Menu & Data Table
 * 3. FE Template Settings
 * 4. Registered Styles and Scripts
 * 5. Custom Post Type Settings
 * 6. Helper Classes (CRUD)
 * 8. Cronjob Functions
 * 9. E-Mail Function
 */

// 0. Addon Integration
// ========================================================================================================================
class EDGAddons {
	private $addons = [];

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_addons' ] );
		do_action( 'mein_plugin_addon_init' );
	}

	public function register_addon( $addon ) {
		$this->addons[] = $addon;
	}

	public function load_addons() {
		$addon_dir = plugin_dir_path( __FILE__ ) . 'addons/';
		if ( is_dir( $addon_dir ) ) {
			$addon_files = glob( $addon_dir . '*.php' );
			foreach ( $addon_files as $addon_file ) {
				include_once $addon_file;
			}
		}
	}
}

$edg_addon = new EDGAddons();

// Hook for Add-ons to register themselves
function edg_register_addon( $addon ) {
	global $edg_addon;
	if ( isset( $edg_addon ) ) {
		$edg_addon->register_addon( $addon );
	}
}


// 1. Custom Post Types
// ========================================================================================================================
require_once plugin_dir_path( __FILE__ ) . 'custom-post-types/edg-users.php';
require_once plugin_dir_path( __FILE__ ) . 'custom-post-types/edg-game-type.php';
require_once plugin_dir_path( __FILE__ ) . 'custom-post-types/edg-campaign.php';
require_once plugin_dir_path( __FILE__ ) . 'custom-post-types/edg-donation-recipients.php';
require_once plugin_dir_path( __FILE__ ) . 'custom-post-types/edg-general-settings.php';


// Construct set post types
construct_user_cpt();
construct_games_cpt();
construct_gametype_cpt();
construct_recipients_cpt();
construct_general_settings();
function get_theme_color( $color ) {
	$themecolor = get_field( $color, 'option' );
	if ( $themecolor ) {
		return $themecolor;
	} else {
		return '#000000';
	}
}

function update_tailwind_colors() {
	$primary_color   = get_theme_color( 'primary-color' );
	$secondary_color = get_theme_color( 'secondary-color' );
	$teritary_color  = get_theme_color( 'teritary-color' );

	// Das Format für die CSS-Datei vorbereiten
	$css_content = ":root {\n";
	$css_content .= "    --primary-color: $primary_color;\n";
	$css_content .= "    --secondary-color: $secondary_color;\n";
	$css_content .= "    --teritary-color: $teritary_color\n";
	$css_content .= "}\n";

	// In die Datei schreiben
	file_put_contents( plugin_dir_path( __FILE__ ) . '/tailwind-colors.css', $css_content );
}

add_action( 'acf/save_post', 'elancer\update_tailwind_colors' );

function add_edg_game_type_entry_on_activation() {

	$plugin_base_dir          = plugin_dir_path( __FILE__ );
	$game_directories_pattern = $plugin_base_dir . 'src/games/*';
	$game_directories         = glob( $game_directories_pattern, GLOB_ONLYDIR );
	$id                       = 0;
	foreach ( $game_directories as $single_game_type ) {

		if ( basename( $single_game_type ) == 'memory' ) {
			$id = 999998;
		} elseif ( basename( $single_game_type ) == 'tower' ) {
			$id = 999999;
		}

		$existing_posts = get_posts( array(
			'ID'        => $id,
			'post_type' => 'edg-game-type',
			'title'     => ucfirst( basename( $single_game_type . " " ) ),
		) );
		if ( empty( $existing_posts ) ) {
			$post_data = array(
				'import_id'   => $id,
				'post_title'  => ucfirst( basename( $single_game_type . " " ) ),
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type'   => 'edg-game-type',
			);
			wp_insert_post( $post_data );
		}
	}
}

register_activation_hook( __FILE__, 'elancer\add_edg_game_type_entry_on_activation' );
// 2. Dashboard Admin Menu
// ========================================================================================================================
add_action( 'admin_menu', 'elancer\register_parent_menu' );
function register_parent_menu(): void {

	$finished_campaigns = 1;
	$menu_title         = 'ChariGame';
	if ( $finished_campaigns > 0 ) {
		$menu_title .= ' <span class="update-plugins"><span class="update-count">' . $finished_campaigns . '</span></span>';
	}


	add_menu_page(
		'ChariGame',
		'ChariGame',
		'manage_options',
		'edg-types',
		'elancer\render_edg_data_table',
		'dashicons-games',
		1000
	);
	add_submenu_page(
		'edg-types',
		'EDG Data Table Page',
		'Data Table',
		'manage_options',
		'edg-data-table',
		'elancer\render_edg_data_table'
	);
	add_submenu_page(
		'edg-types',
		'Charigame E-mail Settings ',
		'E-Mail Settings',
		'manage_options',
		'charigame-email-settings',
		'elancer\elancer_render_email_settings'
	);
}
function elancer_render_email_settings() {
	// SMTP Optionen speichern
	if (isset($_POST['charigame_save_smtp_settings']) && check_admin_referer('charigame_save_smtp_action')) {
		$options = [
			'smtp_host'     => sanitize_text_field($_POST['smtp_host']),
			'smtp_port'     => absint($_POST['smtp_port']),
			'smtp_user'     => sanitize_text_field($_POST['smtp_user']),
			'smtp_pass'     => sanitize_text_field($_POST['smtp_pass']),
			'smtp_secure'   => sanitize_text_field($_POST['smtp_secure']),
			'smtp_from'     => sanitize_email($_POST['smtp_from']),
			'smtp_fromname' => sanitize_text_field($_POST['smtp_fromname']),
		];
		update_option('charigame_smtp_settings', $options);
		echo '<div class="notice notice-success"><p>✅ SMTP-Einstellungen gespeichert.</p></div>';
	}

	// Test-E-Mail versenden
	if (isset($_POST['charigame_send_test_mail']) && check_admin_referer('charigame_send_test_mail_action')) {
		$from = sanitize_email($_POST['sender_email']);
		$to   = sanitize_email($_POST['recipient_email']);
		$smtp = get_option('charigame_smtp_settings');

		add_action('phpmailer_init', function($phpmailer) use ($smtp, $from) {
			$phpmailer->isSMTP();
			$phpmailer->Host       = $smtp['smtp_host'];
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Port       = $smtp['smtp_port'];
			$phpmailer->Username   = $smtp['smtp_user'];
			$phpmailer->Password   = defined('WPMS_SMTP_PASS') ? WPMS_SMTP_PASS : $smtp['smtp_pass'];
			$phpmailer->SMTPSecure = $smtp['smtp_secure'];
			$phpmailer->setFrom($from, 'Charigame Plugin');
		});


		$subject = 'Charigame Test-E-Mail';
		$message = 'Dies ist eine Testmail von deinem WordPress-System.';

		if (wp_mail($to, $subject, $message)) {
			echo '<div class="notice notice-success"><p>✅ Test-E-Mail erfolgreich gesendet.</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>❌ Fehler beim Senden.</p></div>';
		}

		remove_all_actions('phpmailer_init');
	}

	// Aktuelle Optionen laden
	$smtp = get_option('charigame_smtp_settings', [
		'smtp_host'     => '',
		'smtp_port'     => 587,
		'smtp_user'     => '',
		'smtp_pass'     => '',
		'smtp_secure'   => 'tls',
		'smtp_from'     => '',
		'smtp_fromname' => '',
	]);
	?>

	<div class="wrap">
		<h2>SMTP-Einstellungen</h2>
		<form method="post">
			<?php wp_nonce_field('charigame_save_smtp_action'); ?>
			<table class="form-table">
				<tr><th><label>SMTP Host</label></th><td><input name="smtp_host" type="text" value="<?php echo esc_attr($smtp['smtp_host']); ?>" class="regular-text" /></td></tr>
				<tr><th><label>SMTP Port</label></th><td><input name="smtp_port" type="number" value="<?php echo esc_attr($smtp['smtp_port']); ?>" class="small-text" /></td></tr>
				<tr><th><label>Benutzername</label></th><td><input name="smtp_user" type="text" value="<?php echo esc_attr($smtp['smtp_user']); ?>" class="regular-text" /></td></tr>
				<tr><th><label>Passwort</label></th>
					<td>
						<?php if (defined('WPMS_SMTP_PASS')): ?>
							<em>Wird per Konstante gesetzt (WPMS_SMTP_PASS)</em>
						<?php else: ?>
							<input name="smtp_pass" type="password" value="<?php echo esc_attr($smtp['smtp_pass']); ?>" class="regular-text" />
						<?php endif; ?>
					</td>
				</tr>
				<tr><th><label>Verschlüsselung</label></th><td>
						<select name="smtp_secure">
							<option value="tls" <?php selected($smtp['smtp_secure'], 'tls'); ?>>TLS</option>
							<option value="ssl" <?php selected($smtp['smtp_secure'], 'ssl'); ?>>SSL</option>
							<option value="" <?php selected($smtp['smtp_secure'], ''); ?>>Keine</option>
						</select>
					</td></tr>
				<tr><th><label>Absender E-Mail</label></th><td><input name="smtp_from" type="email" value="<?php echo esc_attr($smtp['smtp_from']); ?>" class="regular-text" /></td></tr>
				<tr><th><label>Absender Name</label></th><td><input name="smtp_fromname" type="text" value="<?php echo esc_attr($smtp['smtp_fromname']); ?>" class="regular-text" /></td></tr>
			</table>
			<p><input type="submit" name="charigame_save_smtp_settings" class="button button-primary" value="SMTP Einstellungen speichern" /></p>
		</form>

		<h2>Test-E-Mail versenden</h2>
		<form method="post">
			<?php wp_nonce_field('charigame_send_test_mail_action'); ?>
			<table class="form-table">
				<tr><th><label>Absender E-Mail</label></th><td><input name="sender_email" type="email" value="<?php echo esc_attr($smtp['smtp_from']); ?>" class="regular-text" /></td></tr>
				<tr><th><label>Empfänger E-Mail</label></th><td><input name="recipient_email" type="email" value="" class="regular-text" /></td></tr>
			</table>
			<p><input type="submit" name="charigame_send_test_mail" class="button button-secondary" value="Testmail senden" /></p>
		</form>
	</div>
	<?php
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class EDG_Data_Table extends WP_List_Table {

	public $campaign_name;

	function __construct() {
		parent::__construct( array(
			'singular' => 'Custom Item',
			'plural'   => 'Custom Items',
			'ajax'     => false
		) );
	}

	function get_columns() {
		$columns = array(
			'email_address' => 'Email',
			'game_type'     => 'Game Type',
			'game_code'     => 'Game Code',
			'valid_from'    => 'Valid From',
			'valid_until'   => 'Valid Until',
			'code_used'     => 'Code Used',
			'last_played'   => 'Last Played',
			'highscore'     => 'Highscore',
			'recipient_1'   => 'Recipient 1',
			'recipient_2'   => 'Recipient 2',
			'recipient_3'   => 'Recipient 3',
			'email_sent'    => 'E-Mail Sent'
		);

		return $columns;
	}

	function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'edg_game_data';

		// Define column headers
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, array(), $sortable );

		// Pagination parameters
		$per_page     = 50; // Number of items per page
		$current_page = $this->get_pagenum();

		// Get total number of items
		$total_items = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE campaign_name = %s", $this->campaign_name ) );

		// Fetch data with pagination
		$offset  = ( $current_page - 1 ) * $per_page;
		$sql     = $wpdb->prepare( "SELECT * FROM $table_name WHERE campaign_name = %s LIMIT %d OFFSET %d", $this->campaign_name, $per_page, $offset );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Set the items
		$this->items = $results;

		// Set pagination arguments
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}
}

function render_edg_data_table() {

	?>
	<h1><?php echo get_admin_page_title() ?></h1>
	<?php
	$all_campaigns = get_all_campaigns();
	$tabs          = array();
	?>
	<h2>EDG Campaigns</h2>
	<div class="wrap"
		 style="background:#FFFFFF;padding:0;margin-top:0;border:1px solid #dcdcde">
		<?php
		foreach ( $all_campaigns as $index => $single_campaign ) {
			$tab_key          = 'tab' . ( $index + 1 ); // Generating tab key
			$tab_label        = ucfirst( $single_campaign->name ); // Generating tab label
			$tabs[ $tab_key ] = $tab_label; // Adding tab to the $tabs array

			add_settings_section(
				"edg-data-table_{$tab_key}_settings", // Section ID
				'<h2 style="margin-left:.5rem;">' . ucfirst( $single_campaign->name ) . ' Spendenverteilung</h2>', // Section title
				function () use ( $single_campaign ) {
					render_tab_settings_callback( $single_campaign );
				},
				"edg-data-table_{$tab_key}" // Page to which section belongs
			);
		}
		$current_tab = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? $_GET['tab'] : array_key_first( $tabs );

		// TODO: IMPORTANT---! DO IT JUST FOR THE CURRENT DATA TABLE NOT FOR ALL!
		if ( isset( $_POST['submit'] ) && $_POST['submit'] === 'Benutzerdaten aktualisieren' ) {

			$slug      = lcfirst( $tabs[ $_GET['tab'] ] );
			$post_type = 'edg-campaign';

			$post = get_page_by_path( $slug, OBJECT, $post_type );

			echo '<div class="notice notice-success"><p>Einstellugnen für die Kampagne ' . $tabs[ $_GET['tab'] ] . '  wurde erfolgreich aktualisiert!</p></div>';

			update_edg_data_table( $post->ID );

		}

		if ( isset( $_POST['delete'] ) && $_POST['delete'] === 'Benutzerdaten löschen' ) {
			echo '<div class="notice notice-warning"><p>Settings deleted successfully!</p></div>';
			delete_data_from_edg_game_data_table();
		}

		?>
		<form method="post"
			  action="<?php echo admin_url( 'admin.php?page=edg-data-table&tab=' . $current_tab ); ?>">
			<nav class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $tab => $name ) {
					$current = $tab === $current_tab ? ' nav-tab-active' : '';
					$url     = add_query_arg( array( 'page' => 'edg-data-table', 'tab' => $tab ), '' );
					echo "<a class=\"nav-tab{$current}\" href=\"{$url}\">{$name}</a>";
				}
				?>
			</nav>
			<?php
			settings_fields( "edg-data-table_{$current_tab}_settings" );
			do_settings_sections( "edg-data-table_{$current_tab}" );


			echo '<div style="display: flex; gap: 10px;">';
			submit_button( 'Benutzerdaten aktualisieren' );
			submit_button( 'Benutzerdaten löschen', 'secondary', 'delete' );
			echo '</div>';

			?>
		</form>
	</div>
	<form method="post" id="edg-user-delete-form"
		  action="<?php echo admin_url( 'admin.php?page=edg-data-table&tab=' . $current_tab ); ?>">
		<input type="hidden" name="delete_all_user" value="Alle Nutzer aus der Tabelle EDG User löschen">
		<?php submit_button( 'Alle Nutzer aus der Tabelle EDG User löschen', 'secondary', 'delete_all_user' ); ?>
	</form>

	<?php
	if ( isset( $_POST['delete_all_user'] ) && $_POST['delete_all_user'] === 'Alle Nutzer aus der Tabelle EDG User löschen' ) {
		$count = delete_edg_users_batch();

		if ( $count > 0 ) {
			// Weiterleiten zum nächsten Batch (automatischer Reload)
			echo '<div class="notice notice-warning"><p>' . $count . ' Nutzer gelöscht… Weiter mit dem nächsten Batch.</p></div>';
			echo '<script>setTimeout(function(){ document.getElementById("edg-user-delete-form").submit(); }, 1000);</script>';
		} else {
			echo '<div class="notice notice-success"><p>Alle EDG Nutzer wurden erfolgreich gelöscht!</p></div>';
		}
	}
	?>
	<?php
}

function render_tab_settings_callback( $single_campaign ) {
	$recipients = get_all_recipients_data_from_edg_game_id( $single_campaign->ID );
	$results    = get_overall_donations( $single_campaign->ID );
	$total_1    = 0;
	$total_2    = 0;
	$total_3    = 0;
	foreach ( $results as $result ) {
		$total_1 += floatval( $result->score_r1 );
		$total_2 += floatval( $result->score_r2 );
		$total_3 += floatval( $result->score_r3 );
	}
	echo '<table style="margin-left:.5rem;">';

	echo '<tr>';
	$index = 1;
	foreach ( $recipients as $recipient ) {
		echo '<th style="padding-right: 16px">' . $recipient['title'] . ' | Recipient (' . $index . ') </th>';
		$index ++;
	}
	echo '</tr>';

	echo '<tr>';
	echo '<td style="padding-right: 1rem">' . number_format( $total_1, 5 ) . ' €</td>';
	echo '<td style="padding-right: 1rem">' . number_format( $total_2, 5 ) . ' €</td>';
	echo '<td style="padding-right: 1rem">' . number_format( $total_3, 5 ) . ' €</td>';
	echo '</tr>';
	echo '</table>';
	echo '<p style="margin-left:.5rem;">Der Spendentopf gesamt beträgt: <strong>' . number_format( ( $total_1 + $total_2 + $total_3 ), 2 ) . ' €</strong></p>';
	echo '<div>';
	echo '<h2 style="padding-left:.5rem;">Data Table</h2>';
	$table                = new EDG_Data_Table();
	$table->campaign_name = $single_campaign->name;
	$table->prepare_items();
	$table->display();
	echo '</div>';
}

// 3. FE Template Settings
// ========================================================================================================================
function insert_single_game_template( string $template ): string {

	if ( 'edg-campaign' === get_post_type() ) {
		return plugin_dir_path( __FILE__ ) . 'templates/single-edg-campaign.php';
	}

	return $template;
}

add_filter( 'template_include', 'elancer\insert_single_game_template' );

// 4. Registered Styles and Scripts
// ========================================================================================================================
function my_deregister_scripts_and_styles(): void {

	if ( 'edg-campaign' === get_post_type() ) {
		global $wp_scripts, $wp_styles;

		// Ausnahmen definieren
		$script_exceptions = array(
			'borlabs-cookie-prioritize',
			'borlabs-cookie',
			// Weitere Handles von Scripts, die nicht deregistriert werden sollen
		);
		$style_exceptions  = array(
			'borlabs-cookie',
			// Weitere Handles von Styles, die nicht deregistriert werden sollen
		);
		foreach ( $wp_scripts->registered as $registered ) {
			if ( ! str_contains( $registered->src, '/wp-admin/' ) && ! in_array( $registered->handle, $script_exceptions ) ) {
				wp_deregister_script( $registered->handle );
			}
		}
		foreach ( $wp_styles->registered as $registered ) {
			if ( ! str_contains( $registered->src, '/wp-admin/' ) && ! in_array( $registered->handle, $style_exceptions ) ) {
				wp_deregister_style( $registered->handle );
			}
		}
	}
}

add_action( 'wp_enqueue_scripts', 'elancer\my_deregister_scripts_and_styles', 998 );


/**
 * Enqueue needed styles and scripts
 * style.css
 * picker.js
 * memory game.js
 * confetti.js
 * localize params for memory game.js
 */
function elancer_enqueue_styles_and_scripts(): void {


	$plugin_data    = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
	$plugin_version = $plugin_data['Version'];

	if ( 'edg-campaign' === get_post_type() ) {
		wp_enqueue_style( 'elancer-donate-games-styles', plugins_url( '/dist/styles.css', __FILE__ ), array(), $plugin_version );
		wp_enqueue_style( 'tailwind-colors', plugins_url( '/tailwind-colors.css', __FILE__ ), array(), $plugin_version );
		wp_enqueue_script(
			'jquery',
			'https://code.jquery.com/jquery-3.7.1.min.js',
			array(),
			'3.7.1'
		);
		//enqueue donation dist
		$donation_dist_settings = get_field( 'spendenverteilung_gruppe', get_the_ID() );
		$donation_dist          = $donation_dist_settings['gewinnkategorie'];
		$donation_type          = $donation_dist_settings['highscore'];
		$recipients             = get_all_recipients_data_from_edg_game_id( get_the_ID() );
		$company_settings       = get_field( 'company_settings', 'option' );
		$logo                   = get_field( 'company_settings', 'option' )['company-logo'];
		$primary_color          = get_field( 'primary-color', 'option' );
		$secondary_color        = get_field( 'secondary-color', 'option' );
		$teritary_color         = get_field( 'teritary-color', 'option' );
		wp_enqueue_script( 'elancer-helper', plugins_url( '/src/games/helper.js', __FILE__ ), array(), $plugin_version );
		wp_localize_script( 'elancer-helper', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_localize_script( 'elancer-helper', 'helper_vars', array(
			'dist'            => $donation_dist,
			'gametype'        => $donation_type,
			'recipients'      => $recipients,
			'logo'            => $logo,
			'primary_color'   => $primary_color,
			'secondary_color' => $secondary_color,
			'teritary_color'  => $teritary_color,
			'nonce'           => wp_create_nonce( 'nonce' ),
			'plugin_path'     => plugin_dir_url( __FILE__ ),
		) );
		wp_enqueue_script( 'elancer-donate-picker', plugins_url( '/src/games/picker.js', __FILE__ ), array(), $plugin_version );
		wp_enqueue_script( 'confetti-js', plugins_url( '/src/games/confetti.min.js', __FILE__ ), array(), $plugin_version );

		// Die GSAP-Core-Bibliothek + Observer
		wp_enqueue_script( 'gsap-js', plugins_url( '/js/gsap/gsap_3.12.5.min.js', __FILE__ ) );
		wp_enqueue_script( 'animation-js', plugins_url( '/js/animation.js', __FILE__ ), array( 'gsap-js' ) );
		wp_enqueue_script( 'gsap-observer', plugins_url( '/js/gsap/observer_3.12.5.min.js', __FILE__ ), array( 'gsap-js' ) );
		wp_enqueue_script( 'gsap-scrolltrigger', plugins_url( '/js/gsap/scrolltrigger_3.12.5.min.js', __FILE__ ), array( 'gsap-js' ) );
		wp_enqueue_script( 'gsap-motionpath', plugins_url( '/js/gsap/motionpathplugin_3.12.5.min.js', __FILE__ ), array( 'gsap-js' ) );
		wp_enqueue_script( 'gsap-draggable', plugins_url( '/js/gsap/draggable_3.12.5.min.js', __FILE__ ), array( 'gsap-js' ) );

		$current_game_type_id = get_field( 'game_type', get_the_ID() );
		// memory game settings || scripts and style
		if ( $current_game_type_id == 999998 ) {
			wp_enqueue_script( 'elancer-memory-game', plugins_url( '/src/games/memory/game.js', __FILE__ ), array(), $plugin_version );
			wp_localize_script( 'elancer-memory-game', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

			$memory_settings = get_field( 'memory_settings', get_the_ID() );
			$images          = $memory_settings['memory_images'];

			wp_localize_script( 'elancer-memory-game', 'my_plugin_vars', array(
				'plugin_path' => plugin_dir_url( __FILE__ ),
				'image_array' => $images,
			) );
		}

		if ( $current_game_type_id == 999999 ) {
			wp_enqueue_script( 'three-js', plugins_url( '/src/games/tower/three_r134.min.js', __FILE__ ) );
			wp_enqueue_script( 'elancer-tower-game', plugins_url( '/src/games/tower/game.js', __FILE__ ), array( 'gsap-js' ), $plugin_version );
			wp_localize_script( 'elancer-tower-game', 'game_vars', array(
				'logo' => $logo,
			) );
			wp_enqueue_style( 'elancer-tower-game-styles', plugins_url( '/src/games/tower/tower-styles.css', __FILE__ ), array(), $plugin_version );
		}


	}
}

add_action( 'wp_enqueue_scripts', 'elancer\elancer_enqueue_styles_and_scripts', 999 );

function edg_enqueue_admin_scripts(): void {
	$plugin_data    = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
	$plugin_version = $plugin_data['Version'];
	wp_enqueue_script( 'backend-js', plugin_dir_url( __FILE__ ) . 'js/backend.js', array( 'jquery' ), null, $plugin_version );
}

add_action( 'admin_enqueue_scripts', 'elancer\edg_enqueue_admin_scripts' );
function add_meta_viewport_to_head(): void {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
}

add_action( 'wp_head', 'elancer\add_meta_viewport_to_head', 1 );


// 4. Registered Styles and Scripts
// ========================================================================================================================
/**
 * Set game types to campaign dropdown
 */
function acf_set_game_type_field_to_campaign( array $field ): array {

	$field['choices'] = array();
	$game_types       = get_posts( array(
		'post_type'      => 'edg-game-type',
		'posts_per_page' => - 1,
	) );
	foreach ( $game_types as $game_type ) {
		$field['choices'][ $game_type->ID ] = $game_type->post_title;
	}

	return $field;
}

add_filter( 'acf/load_field/name=game_type', 'elancer\acf_set_game_type_field_to_campaign' );

/**
 * Set recipients to campaign dropdown
 */
function acf_set_recipients_to_campaign( array $field ): array {

	$field['choices'] = array();
	$game_types       = get_posts( array(
		'post_type'      => 'edg-donation-recipie',
		'posts_per_page' => - 1,
	) );
	foreach ( $game_types as $game_type ) {
		$field['choices'][ $game_type->ID ] = $game_type->post_title;
	}

	return $field;
}

add_filter( 'acf/load_field/name=recipient_1', 'elancer\acf_set_recipients_to_campaign' );
add_filter( 'acf/load_field/name=recipient_2', 'elancer\acf_set_recipients_to_campaign' );
add_filter( 'acf/load_field/name=recipient_3', 'elancer\acf_set_recipients_to_campaign' );

// 6. Helper Classes (CRUD)
// ========================================================================================================================

add_action( 'wp_ajax_get_current_donations', 'get_current_donations' );
add_action( 'wp_ajax_nopriv_get_current_donations', 'get_current_donations' );

function get_current_donations() {
	$single_campaign_id = intval( $_POST['campaign_id'] );
	$results            = get_overall_donations( $single_campaign_id );
	$spendenbetrag      = get_field( 'spendentopf_initial', $single_campaign_id );
	$total_1            = 0;
	$total_2            = 0;
	$total_3            = 0;

	if ( $results != null ) {
		foreach ( $results as $result ) {
			$total_1 += floatval( $result->score_r1 );
			$total_2 += floatval( $result->score_r2 );
			$total_3 += floatval( $result->score_r3 );
		}
	}

	$total_overall = $total_1 + $total_2 + $total_3;
	$total_amount  = $spendenbetrag + $total_overall;

	wp_send_json_success( array( 'total_amount' => number_format( $total_amount, 2 ) . ' €' ) );
}

function get_all_recipients_data_from_edg_game_id( int $game_id ): array {
	$recipients = array();

	for ( $i = 1; $i <= 3; $i ++ ) {
		$recipient_id = get_field( 'recipient_' . $i, $game_id );
		if ( $recipient_id ) {
			$title = get_the_title( $recipient_id );
			$desc  = get_field( 'description', $recipient_id );
			$logo  = get_field( 'logo', $recipient_id );

			$recipient_data                  = array(
				'title'       => $title,
				'description' => $desc,
				'logo'        => $logo
			);
			$recipients[ 'recipient_' . $i ] = $recipient_data;
		}
	}

	return $recipients;
}


function create_edg_game_data_table(): void {
	global $wpdb;

	$table_name = $wpdb->prefix . 'edg_game_data';

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
        campaign_name VARCHAR(255) NOT NULL,
        email_address VARCHAR(255) NOT NULL,
        game_type VARCHAR(255) NOT NULL,
        game_code VARCHAR(50) NOT NULL,
        valid_from DATE NOT NULL,
        valid_until DATE NOT NULL,
        code_used TIMESTAMP NULL,
        last_played TIMESTAMP NULL,
        highscore INT DEFAULT 0,
        recipient_1 FLOAT DEFAULT 0,
        recipient_2 FLOAT DEFAULT 0,
        recipient_3 FLOAT DEFAULT 0,
        email_sent BOOLEAN DEFAULT 0,
        PRIMARY KEY (campaign_name, email_address, game_type),
        UNIQUE (campaign_name, email_address)
    ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}

register_activation_hook( __FILE__, 'elancer\create_edg_game_data_table' );

function add_data_to_edg_game_data_table( string $campaign_name, string $email_address, string $game_type, string $game_code, string $valid_from, string $valid_until, $last_played, $code_used ): void {

	global $wpdb;

	$table_name = $wpdb->prefix . 'edg_game_data';
	if ( $code_used != null && $game_code != '' ) {
		$wpdb->update(
			$table_name,
			array(
				'code_used' => $code_used,
			),
			array(
				'game_code' => $game_code,
			)
		);

		return;
	}

	// Ensure valid date format
	$valid_from  = date( 'Y-m-d', strtotime( $valid_from ) );
	$valid_until = date( 'Y-m-d', strtotime( $valid_until ) );

	// Check if the email address with the given game name already exists
	$existing_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE email_address = %s AND game_type = %s AND campaign_name = %s", $email_address, $game_type, $campaign_name ) );
	//echo var_dump( $existing_row );
	if ( ! $existing_row ) {
		// If the record doesn't exist, insert it
		$data = array(
			'campaign_name' => $campaign_name,
			'email_address' => $email_address,
			'game_type'     => $game_type,
			'game_code'     => $game_code,
			'valid_from'    => $valid_from,
			'valid_until'   => $valid_until,
		);

		// Insert data into the table
		$wpdb->insert( $table_name, $data );
	} else if ( $existing_row->valid_from !== $valid_from ) {
		// If the valid_from date differs, update the valid_from and valid_until fields
		$wpdb->update(
			$table_name,
			array(
				'valid_from'  => $valid_from,
				'valid_until' => $valid_until,
			),
			array(
				'email_address' => $email_address,
				'game_type'     => $game_type,
				'campaign_name' => $campaign_name,
			)
		);
	}

}

function delete_data_from_edg_game_data_table(): void {
	global $wpdb;

	$table_name = $wpdb->prefix . 'edg_game_data';

	// Leere die Tabelle
	$wpdb->query( "TRUNCATE TABLE $table_name" );
}

function search_game_data_by_game_code( $game_code ) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'edg_game_data';

	// Prepare and execute the SQL query
	$query  = $wpdb->prepare( "SELECT * FROM $table_name WHERE game_code = %s", $game_code );
	$result = $wpdb->get_row( $query );

	// Return the result
	return $result;
}

function get_user_data_by_email( $email ) {

	$args = array(
		'post_type'      => 'edg-user',
		'meta_query'     => array(
			array(
				'key'     => 'email',
				'value'   => $email,
				'compare' => '=',
			),
		),
		'posts_per_page' => 1, // Limit the result to one post
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		$query->the_post();

		$user_data = (object) array(
			'ID'         => get_the_ID(),
			'first_name' => get_field( 'first-name' ),
			'last_name'  => get_field( 'last-name' ),
			'email'      => get_field( 'email' ),
			'birthday'   => get_field( 'birthday' ),
			'email_sent'   => get_field( 'email_sent' ),
		);

		wp_reset_postdata();

		return $user_data;
	} else {
		return null;
	}
}

function set_user_highscore_db() {

	if ( ! wp_verify_nonce( $_POST['nonce'], 'nonce' ) ) {
		global $wpdb;

		$table_name  = $wpdb->prefix . 'edg_game_data';
		$gamecode    = $_POST['code'];
		$highscore   = intval( $_POST['highscore'] );
		$last_played = intval( $_POST['last_played'] );
		$recipient_1 = floatval( $_POST['recipient_1'] );
		$recipient_2 = floatval( $_POST['recipient_2'] );
		$recipient_3 = floatval( $_POST['recipient_3'] );
		$gamecode    = substr( $gamecode, 4, - 4 );

		$last_played  = $last_played / 1000; // Adjusting the timestamp to seconds
		$sqlTimestamp = date( 'Y-m-d H:i:s', $last_played );

		$result = $wpdb->update(
			$table_name,
			array(
				'highscore'   => $highscore,
				'last_played' => $sqlTimestamp,
				'recipient_1' => $recipient_1,
				'recipient_2' => $recipient_2,
				'recipient_3' => $recipient_3,
			),
			array(
				'game_code' => $gamecode
			)
		);

		if ( $result !== false ) {
			$response = array(
				'message' => 'Highscore successfully ' . $gamecode . ' . ' . $result,
			);
			wp_send_json( $response );
			echo 'Highscore successfully updated for ' . $gamecode;
		} else {
			$response = array(
				'message' => 'Failed to update highscore' . $gamecode . ' . ' . $highscore . ' . ',
			);
			wp_send_json( $response );
			echo 'Failed to update highscore' . $gamecode . $gamecode . ' . ' . $highscore . ' . ';
		}
	}
	wp_die();
}

add_action( 'wp_ajax_set_user_highscore_db', 'elancer\set_user_highscore_db' );
add_action( 'wp_ajax_nopriv_set_user_highscore_db', 'elancer\set_user_highscore_db' );
function get_game_code_by_email_address( $email_address, $slug ) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'edg_game_data';
	$game_code  = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT game_code FROM $table_name WHERE email_address = %s AND campaign_name = %s",
			$email_address,
			$slug
		)
	);

	return $game_code;
}

function create_edg_user_with_acf( $first_name, $last_name, $email, $birthday ) {
	$existing_users = get_posts( array(
		'post_type'  => 'edg-user',
		'meta_query' => array(
			array(
				'key'     => 'email',
				'value'   => $email,
				'compare' => '='
			)
		)
	) );
	if ( ! empty( $existing_users ) ) {
		$existing_user = $existing_users[0];
		update_field( 'imported', 0, $existing_user->ID );
	}
	// Erstelle einen neuen EDG-Benutzerpost
	$post_id = wp_insert_post( array(
		'post_title'  => $first_name . ' ' . $last_name,
		'post_type'   => 'edg-user',
		'post_status' => 'publish',
	) );

	// Überprüfen, ob die Beitragserstellung erfolgreich war
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}
	update_field( 'first-name', $first_name, $post_id );
	update_field( 'last-name', $last_name, $post_id );
	update_field( 'email', $email, $post_id );
	update_field( 'imported', 1, $post_id );
	update_field( 'email_sent', 0, $post_id );
	$email_sent = get_field('email_sent', $post_id);
	if ($email_sent === '' || $email_sent === null) {

	}

	// Format the birthday to the 'md' format without year
	$date         = DateTime::createFromFormat( 'Y-m-d', $birthday );
	$current_year = date( 'Y' ); // Aktuelles Jahr holen

	// Setze das aktuelle Jahr für das Datum
	$date->setDate( $current_year, $date->format( 'm' ), $date->format( 'd' ) );

	// Überprüfen, ob das Datum in der Vergangenheit liegt
	if ( $date < new DateTime() ) {
		// Setze das Datum auf das nächste Jahr, wenn es in der Vergangenheit liegt
		$date->setDate( $current_year + 1, $date->format( 'm' ), $date->format( 'd' ) );
	}

	$birthday_formatted = $date->format( 'Ymd' ); // Formatiere das Datum
	update_field( 'birthday', $birthday_formatted, $post_id );


	return $post_id;
}

// Get Birthday User
function get_users_with_birthday( $is_all_user ) {

	if ( $is_all_user ) {
		$args = array(
			'post_type'      => 'edg-user',
			'posts_per_page' => - 1,
		);
	} else {
		$current_date = date( 'Ymd' );
		$args         = array(
			'post_type'      => 'edg-user',
			'meta_query'     => array(
				array(
					'key'     => 'birthday',
					'value'   => $current_date,
					'compare' => '=',
				),
			),
			'posts_per_page' => - 1,
		);
	}


	$emails = array();

	$query = new WP_Query( $args );
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			$email = get_post_meta( get_the_ID(), 'email', true );
			if ( ! empty( $email ) ) {
				$emails[] = $email;
			}


		}
	}
	// Restore original post data
	wp_reset_postdata();

	return $emails;
}

function get_all_campaigns() {

	$args = array(
		'post_type'      => 'edg-campaign',
		'posts_per_page' => - 1,
	);

	$campaigns = get_posts( $args );

	$campaigns_objects = array();

	foreach ( $campaigns as $campaign ) {
		$campaign_object       = new stdClass();
		$campaign_object->ID   = $campaign->ID;
		$campaign_object->name = $campaign->post_name;
		$campaigns_objects[]   = $campaign_object;
	}

	return $campaigns_objects;
}

function get_all_game_types() {

	$args = array(
		'post_type'      => 'edg-game-type',
		'posts_per_page' => - 1,
	);

	$campaigns = get_posts( $args );

	$campaigns_objects = array();

	foreach ( $campaigns as $campaign ) {
		$campaign_object       = new stdClass();
		$campaign_object->ID   = $campaign->ID;
		$campaign_object->name = $campaign->post_name;
		$campaigns_objects[]   = $campaign_object;
	}

	return $campaigns_objects;
}

function get_overall_donations( $single_campaign ) {

	global $wpdb;
	$spendenverteilung_gruppe = get_field( 'spendenverteilung_gruppe', $single_campaign );
	if ( $spendenverteilung_gruppe != null ) {
		$gewinnkategorie = $spendenverteilung_gruppe['gewinnkategorie'];
		$type            = $spendenverteilung_gruppe['highscore'];
		$table_name      = $wpdb->prefix . 'edg_game_data';
		$single_campaign = get_post( $single_campaign );

		$single_campaign_name = $single_campaign->post_name;
		$sql                  = $wpdb->prepare( "
    SELECT highscore, recipient_1, recipient_2, recipient_3
    FROM $table_name
    WHERE highscore > 0 AND campaign_name = %s
", $single_campaign_name );
		$results              = $wpdb->get_results( $sql );
		$scores               = array();
		$score = 0;
		foreach ( $results as $result ) {
			if ( isset( $result->highscore ) ) {

				$score_obj            = new stdClass();
				$score_obj->highscore = $result->highscore;

				foreach ( $gewinnkategorie as $kategorie ) {
					$limit         = $kategorie['limit'];
					$spendenbetrag = $kategorie['spendenbetrag'];
					if ( $type ) {
						if ( $result->highscore <= $limit ) {
							$score = $spendenbetrag;
							break;
						}
					} else {
						if ( $result->highscore >= $limit ) {
							$score = $spendenbetrag;
							break;
						}
					}
				}

				$score_r1 = ( $result->recipient_1 / 100 ) * $score;
				$score_r2 = ( $result->recipient_2 / 100 ) * $score;
				$score_r3 = ( $result->recipient_3 / 100 ) * $score;


				$score_obj->score_r1 = $score_r1;
				$score_obj->score_r2 = $score_r2;
				$score_obj->score_r3 = $score_r3;
				$scores[]            = $score_obj;
			}
		}
	} else {
		return null;
	}
	return $scores;
}

add_action( 'wp_ajax_update_donations', 'elancer\update_donations' );
add_action( 'wp_ajax_nopriv_update_donations', 'elancer\update_donations' );

function update_donations() {

	if ( ! isset( $_POST['campaign_id'] ) || ! is_numeric( $_POST['campaign_id'] ) ) {
		wp_send_json_error( [ 'message' => 'Invalid campaign ID' ] );
	}

	$campaign_id = intval( $_POST['campaign_id'] );
	$donations   = get_overall_donations( $campaign_id );
	if ( $donations ) {
		wp_send_json_success( $donations );
	} else {
		wp_send_json_error( [ 'message' => 'No donations found' ] );
	}
}

add_action( 'save_post_edg-campaign', 'elancer\update_edg_data_table', 10, 3 );
function update_edg_data_table( $post_id ) {
	$post          = get_post( $post_id );
	$campaign_name = $post->post_name;


	$user_posts = new WP_Query( array(
		'post_type'      => 'edg-user',
		'posts_per_page' => - 1,
		'post_status'    => 'publish',
	) );

	if ( $user_posts->have_posts() ) {
		while ( $user_posts->have_posts() ) {
			$user_posts->the_post();

			// Check if user got email
			$email_address = get_post_meta( get_the_ID(), 'email', true );
			$birthday      = get_post_meta( get_the_ID(), 'birthday', true );
			if ( ! empty( $email_address ) && ! empty( $birthday ) ) {
				// Get all necessary fields
				$game_type_id   = get_post_meta( $post->ID, 'game_type', true );
				$game_type_post = get_post( $game_type_id );
				$game_type      = $game_type_post->post_name;

				$valid_from_weeks = get_field( 'duration', $post->ID );

				$dispatch_date_option = get_field( 'dispatch_date_option', $post->ID );
				if ( $dispatch_date_option == 'dispatch' ) {
					$dispatch_date = get_field( 'dispatch_date', $post->ID );
					$valid_from    = $dispatch_date;
				} else {
					$valid_from = $birthday;
				}
				$valid_until = date( 'Ymd', strtotime( "+" . $valid_from_weeks . "weeks", strtotime( $valid_from ) ) );
				$game_code   = substr( str_shuffle( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789' ), 0, 12 );
				echo( $campaign_name . ' ' . $email_address . ' ' . $game_type . ' ' . $game_code . ' ' . $valid_from . ' ' . $valid_until . '<br>' );
				add_data_to_edg_game_data_table( $campaign_name, $email_address, $game_type, $game_code, $valid_from, $valid_until, null, null );
			}
		}
		wp_reset_postdata();
	}
	//}
}

function delete_edg_users_batch() {
	$batch_size = 200;
	// Hole bis zu $batch_size EDG User Beiträge
	$edg_users = get_posts( array(
		'post_type'   => 'edg-user',
		'numberposts' => $batch_size,
		'post_status' => 'any',
		'orderby'     => 'ID',
		'order'       => 'ASC',
		'fields'      => 'ids', // Nur IDs holen für bessere Performance
	) );

	foreach ( $edg_users as $edg_user_id ) {
		wp_delete_post( $edg_user_id, true );
	}

	return count( $edg_users );
}

function scan_finished_campaigns_function() {

	// 1. Get all campaigns
	// 2. check if single campaign is active
	// 3. check if campaign is ended
	// 4. set all ended with marker and singles also


	$total_finished = 0;
	$all_campaigns  = get_all_campaigns();
	foreach ( $all_campaigns as $single_campaign ) {

		$campaign_is_active = get_field( 'active', $single_campaign->ID );
		if ( ! empty( $campaign_is_active[0] ) && $campaign_is_active[0] == 'active' ) {

			$dispatch_date_option = get_field( 'dispatch_date_option', $single_campaign->ID );
			$campaign_start       = get_field( 'campaign_start', $single_campaign->ID );
			$dispatch_date        = get_field( 'dispatch_date', $single_campaign->ID );
			$duration             = get_field( 'duration', $single_campaign->ID );
			$current_date         = date( 'd.m.Y' );
			if ( $dispatch_date_option == 'birthday' ) {

				$campaign_end = new \DateTime( $campaign_start );
				$campaign_end->modify( '+1 year' );
				$formatted_date = $campaign_end->format( 'd.m.Y' );

			} else {
				$campaign_end = new \DateTime( $dispatch_date );
				$campaign_end->modify( '+' . $duration . ' weeks' );
				$formatted_date = $campaign_end->format( 'd.m.Y' );
			}

			$current_date_obj   = DateTime::createFromFormat( 'd.m.Y', $current_date );
			$formatted_date_obj = DateTime::createFromFormat( 'd.m.Y', $formatted_date );
			if ( $formatted_date_obj < $current_date_obj ) {
				$total_finished ++;

				if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'edg-campaign' ) {
					echo '<div class="notice notice-success notice-alt">

				<p>Die Kampagne <b>' . $single_campaign->name . '</b> ist seit dem ' . $formatted_date . ' abgeschlossen. <br>Bitte deaktivieren Sie die Checkbox "active" der Kampagne um diese zu beenden!</p>
				</div>';
				}
			}
		}
	}


	$menu_title = 'Campaigns';
	if ( $total_finished > 0 ) {
		$menu_title .= ' <span class="update-plugins"><span class="update-count">' . $total_finished . '</span></span>';
	}

	$post_type_object = get_post_type_object( 'edg-campaign' );
	if ( $post_type_object ) {
		$post_type_object->labels->all_items = $menu_title;
	}
}


add_action( 'init', 'elancer\scan_finished_campaigns_function', 10, 3 );


function get_top_results( $single_campaign ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'edg_game_data';

	// Retrieve the campaign slug
	$campaign_slug = get_post_field( 'post_name', $single_campaign );

	// Modify the query to filter by campaign slug
	$query   = $wpdb->prepare(
		"SELECT email_address, last_played, highscore FROM $table_name WHERE campaign_name = %s AND highscore > 0 ORDER BY highscore DESC LIMIT 10",
		$campaign_slug
	);
	$results = $wpdb->get_results( $query );

	foreach ( $results as $result ) {
		$args = array(
			'post_type'      => 'edg-user',
			'meta_query'     => array(
				array(
					'key'     => 'email',
					'value'   => $result->email_address,
					'compare' => '='
				)
			),
			'posts_per_page' => 1
		);

		$user_query = new WP_Query( $args );

		if ( $user_query->have_posts() ) {
			while ( $user_query->have_posts() ) {
				$user_query->the_post();

				$first_name           = get_field( 'first-name' );
				$last_name            = get_field( 'last-name' );
				$name_first_4         = ucfirst( substr( $first_name, 0, 4 ) );
				$name_last_4          = ucfirst( substr( $last_name, 0, 1 ) );
				$result->name_first_4 = $name_first_4;
				$result->name_last_4  = $name_last_4;
			}
		} else {
			$result->name_first_4 = 'N/A';
		}
	}
	wp_reset_postdata();

	return $results;
}

// 8. Cronjob Functions
// ========================================================================================================================


function add_cronjob_action($post_id) {

	if ( get_post_type( $post_id ) !== 'edg-campaign' ) {
		return;
	}

	$all_campaigns = get_all_campaigns();
	foreach ( $all_campaigns as $single_campaign ) {
		$campaign_is_active = get_field( 'active', $single_campaign->ID );
		if ( ! empty( $campaign_is_active[0] ) && $campaign_is_active[0] == 'active' ) {
			if ( ! has_action( 'send_email_to_user_hook_' . $single_campaign->ID, 'elancer\send_custom_email' ) ) {
				add_action( 'send_email_to_user_hook_' . $single_campaign->ID, 'elancer\send_custom_email' );
			}
		}
	}
}

add_action( 'save_post', 'elancer\add_cronjob_action', 10, 3 );


function add_cronjob_callback() {
	if ( ! has_action( 'edg_schedule_campaign', 'elancer\send_custom_email' ) ) {
		add_action( 'edg_schedule_campaign', 'elancer\send_custom_email' );
	}
}

add_action( 'init', 'elancer\add_cronjob_callback', 10, 3 );


add_action( 'updated_post_meta', 'elancer\edit_cronjob_on_dispatch_time_update', 10, 4 );

//TODO: CHANGE CRONJOB WHERE DISPATCH TIME ALSO WITH BIRTHDAY SETTING
function edit_cronjob_on_dispatch_time_update( $meta_id, $post_id, $meta_key, $meta_value ) {
	// Check if the meta key being updated is 'dispatch_time' and if the post type is 'edg-campaign'
	if ( $meta_key === 'dispatch_time' && get_post_type( $post_id ) === 'edg-campaign' ) {

		$args          = array( $post_id );
		$dispatch_date = get_post_meta( $post_id, 'dispatch_date', true );
		$dispatch_time = get_post_meta( $post_id, 'dispatch_time', true );
		$datetime_str = str_replace( '/', '-', $dispatch_date . ' ' . $dispatch_time );
		$datetime = new DateTime( $datetime_str, wp_timezone() );
		$timestamp = $datetime->getTimestamp();
		if ( $timestamp > time() ) {
			wp_clear_scheduled_hook( 'send_email_to_user_hook_' . $post_id, $args );
		} else {
			$scheduled_time = wp_next_scheduled( 'send_email_to_user_hook_' . $post_id, $args );
			if ( $scheduled_time != $timestamp ) {
				wp_clear_scheduled_hook( 'send_email_to_user_hook_' . $post_id, $args );
				wp_schedule_single_event( $timestamp, 'send_email_to_user_hook_' . $post_id, $args );
			}
		}
	}
}

function add_campaign_cronjobs( $post_id, $post, $update ) {
	// Sicherstellen, dass es sich um den richtigen Beitragstyp handelt
	if ( $post->post_type !== 'edg-campaign' || get_post_status( $post_id ) === 'trash' ) {
		return;
	}
	$all_campaigns = get_all_campaigns();
	foreach ( $all_campaigns as $single_campaign ) {
		$campaign_is_active = get_field( 'active', $single_campaign->ID );
		$args = [ $single_campaign->ID ];

		if ( ! empty( $campaign_is_active[0] ) && $campaign_is_active[0] === 'active' ) {
			$dispatch_date_option = get_field( 'dispatch_date_option', $single_campaign->ID );

			if ( $dispatch_date_option === 'dispatch' ) {
				$dispatch_date = get_field( 'dispatch_date', $single_campaign->ID );
				$dispatch_time = get_field( 'dispatch_time', $single_campaign->ID );

				if ( $dispatch_date && $dispatch_time ) {
					$datetime_str = "$dispatch_date $dispatch_time";
					$datetime = new DateTime( $datetime_str, wp_timezone() );
					$timestamp = $datetime->getTimestamp();

					if ( $timestamp > time() ) {
						wp_clear_scheduled_hook( 'edg_schedule_campaign', $args );
						wp_schedule_single_event( $timestamp, 'edg_schedule_campaign', $args );
					}
				}
			} else {
				$campaign_time = get_field( 'dispatch_time', $single_campaign->ID );

				if ( $campaign_time ) {
					list( $hour, $minute, $second ) = explode( ':', $campaign_time );
					$now = current_time( 'timestamp' );
					$next_run = mktime( $hour, $minute, $second, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );

					if ( $next_run <= $now ) {
						$next_run = strtotime( '+1 day', $next_run );
					}

					wp_clear_scheduled_hook( 'edg_schedule_campaign', $args );
					wp_schedule_event( $next_run, 'daily', 'edg_schedule_campaign', $args );
				}
			}
		} else {
			wp_clear_scheduled_hook( 'edg_schedule_campaign', $args );
		}
	}
}
add_action( 'wp_after_insert_post', 'elancer\add_campaign_cronjobs', 20, 3 );

// Verwende den wp_after_insert_post-Hook für add_campaign_cronjobs
add_action( 'wp_after_insert_post', 'elancer\add_campaign_cronjobs', 20, 3 );


add_action( 'init', 'elancer\schedule_daily_cron_job_scan_finished_campaigns' );

function schedule_daily_cron_job_scan_finished_campaigns() {
	$timestamp = strtotime( 'midnight' );
	if ( $timestamp < time() ) {
		$timestamp += 86400; // 86400 Sekunden entsprechen einem Tag
	}
	wp_schedule_event( $timestamp, 'daily', 'scan_finished_campaigns_hook' );
	// Hook-Funktion, die vom Cron-Job aufgerufen wird
	add_action( 'scan_finished_campaigns_hook', 'elancer\scan_finished_campaigns_function' );
}

add_action('wp_ajax_save_pipedrive_cron_setting', 'elancer\save_pipedrive_cron_setting');

function save_pipedrive_cron_setting() {
	check_ajax_referer('edg_nonce', 'nonce');

	$is_checked = isset($_POST['is_checked']) ? intval($_POST['is_checked']) : 0;

	// Einstellung speichern
	update_option('pipedrive_daily_import', $is_checked);

	// Cronjob registrieren oder entfernen
	if ($is_checked) {
		if (!wp_next_scheduled('pipedrive_daily_import_event')) {
			wp_schedule_event(strtotime('tomorrow 00:01'), 'daily', 'pipedrive_daily_import_event');
		}
	} else {
		$timestamp = wp_next_scheduled('pipedrive_daily_import_event');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'pipedrive_daily_import_event');
		}
	}

	wp_send_json_success();
}
add_action('pipedrive_daily_import_event', 'elancer\run_pipedrive_import');

function run_pipedrive_import() {
	$integration = new PipedriveIntegration();
	$integration->fetch_pipedrive_contacts_ajax();
}
// 9. E-Mail Functions
// ========================================================================================================================

function set_custom_smtp( $phpmailer ) {
	$phpmailer->isSMTP();
	$phpmailer->Host       = 'smtp.example.com';
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Port       = 587;
	$phpmailer->Username   = 'dein-benutzername@example.com';
	$phpmailer->Password   = 'dein-passwort';
	$phpmailer->SMTPSecure = 'tls'; // oder 'ssl'
	$phpmailer->From       = 'absender@abcd.com';
	$phpmailer->FromName   = 'Dein Name oder Firma';
}

// In send_custom_email:
//add_action( 'phpmailer_init', 'elancer\set_custom_smtp' );
function send_custom_email( $args ): void {

	$header_image_url    = plugins_url( 'assets/email/images/email-header.jpg', __FILE__ );
//	$facebook_image_url  = plugins_url( 'assets/email/images/facebook.png', __FILE__ );
//	$instagram_image_url = plugins_url( 'assets/email/images/instagram.png', __FILE__ );
//	$x_image_url         = plugins_url( 'assets/email/images/x.png', __FILE__ );

	//TODO: REPEATER FIELD GET ATTRIBUTES

	$is_all_user          = false;
	$single_campaign_id   = $args;
	$dispatch_date_option = get_field( 'dispatch_date_option', $single_campaign_id );

	if ( $dispatch_date_option == 'dispatch' ) {
		$is_all_user = true;
	}

	$emails     = get_users_with_birthday( $is_all_user );
	$utm_source = 'spendenspiel';

	foreach ( $emails as $single_email ) {

		$slug         = get_post_field( 'post_name', $single_campaign_id );
		$gamecode     = get_game_code_by_email_address( $single_email, $slug );
		$gamedata     = search_game_data_by_game_code( $gamecode );
		$email_gruppe = get_field( 'email_gruppe', $single_campaign_id );

		$single_user = get_user_data_by_email($single_email);
		$first_name = $single_user->first_name ?? '';
		$last_name = $single_user->last_name ?? '';
		$name = $first_name . ' ' . $last_name;

		$already_sent = $single_user->email_sent;
		if ($already_sent == 1) {
			continue;
		}

//		$user_query = new WP_Query(array(
//			'post_type' => 'edg-user',
//			'meta_query' => array(
//				array(
//					'key' => 'email',
//					'value' => $single_email,
//					'compare' => '='
//				)
//			),
//			'posts_per_page' => 1
//		));
//
//		if ($user_query->have_posts()) {
//			$user_query->the_post();
//			$first_name = get_post_meta(get_the_ID(), 'first-name', true);
//			$last_name = get_post_meta(get_the_ID(), 'last-name', true);
//			wp_reset_postdata();
//		} else {
//			$first_name = 'Benutzer';
//			$last_name = '';
//		}


		$primary_color   = get_field( 'primary-color', 'option' );
		$secondary_color = get_field( 'secondary-color', 'option' );

		$email_subject        = $email_gruppe['email_subject'];
		$email_header_image   = $email_gruppe['email_header_image'];
		$email_header_claim   = $email_gruppe['email_header_claim'];
		$email_headline       = $email_gruppe['email_headline'];
		$email_content        = $email_gruppe['email_content'];
		$email_info           = $email_gruppe['email_info'];
		$email_signatur       = $email_gruppe['email_signatur'];
		$social_media_options = $email_gruppe['social_media_options'];

		$company_settings = get_field( 'company_settings', 'option' );
		$company_name     = $company_settings['company_name'];
		$company_street   = $company_settings['company_street'];
		$company_city     = $company_settings['company_city'];
		// Email params
		$subject = $email_subject;
		// Message content
		$message = '<html lang="de-de"><body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">';

		$message .= '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff;">';
		$message .= '<tr>';
		$message .= '<td>';
		$message .= '<img src="' . $email_header_image . '" width="100%" style="display: block;">';
		$message .= '</td>';
		$message .= '</tr>';
		$message .= '</table>';
		$message .= '<a style="display:inline-block;color:' . $secondary_color . ';text-align: center;width: 100%;text-decoration: none;" href="' . home_url() . '/?utm_source=' . $utm_source . '&utm_campaign=' . $gamedata->campaign_name . '&utm_medium=email">' . $email_header_claim . '</a>';

		$message .= '<h1 style="text-align:center;">' . $email_headline . '</h1>';
		$message .= '<div style="padding:32px;">';
		$message .= '<p style="text-align:center;">


		Hallo '.$name.',<br><br>

		wir wünschen Ihnen von Herzen alles Gute zum Geburtstag! Möge Ihr neues Lebensjahr voller Freude, Gesundheit und inspirierender Reisemomente sein.
		<br><br>
		Zu Ihrem Ehrentag haben wir uns etwas ganz Besonderes für Sie überlegt:<br>
		🎁 Ein kleines Reise-Memory-Spiel – exklusiv für unsere Gäste.
		<br><br>
		Beim Spielen entdecken Sie traumhafte Reiseziele, wilde Tiere und Naturwunder aus aller Welt – vielleicht ist sogar ein Ort dabei, den Sie schon selbst bereist haben?
		<br><br>
		Und das Beste: Mit Ihrer Teilnahme tun Sie gleichzeitig etwas Gutes!<br>
		Denn: Für jedes gespielte Spiel stellen wir eine Spende zur Verfügung.<br>
		Sie entscheiden selbst, an welche unserer ausgewählten Projekte dieser Betrag fließt. Ganz einfach und direkt im Spiel.
		<br><br>
		👉 Jetzt spielen, erinnern & Gutes tun:	<br><br>
		<a href="' . home_url() . '/spendenspiel/' . $gamedata->campaign_name . '?code=' . $gamecode . '" target="_blank" style="display: inline-block; padding: 10px 20px; color: white; text-decoration: none; border-radius:8px; background-color:#2673AA">Zur Spendenaktion!</a>
		<br><br>
		Wir freuen uns sehr, wenn Sie mitmachen – und wünschen Ihnen noch einmal von Herzen einen wunderbaren Geburtstag!
		<br><br>
		Mit herzlichen Grüßen<br>
		Ihr Team von Natürlich Reisen 🌍

		</p>';
//		$message .= '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">';
//		$message .= '<tr>';
//		$message .= '<td align="center" style="padding: 20px;">';
//		$message .= '<table border="0" cellspacing="0" cellpadding="0">';
//		$message .= '<tr>';
//		$message .= '<td style="border-radius: 5px; background-color: ' . $secondary_color . ';">';
//		$message .= '<a href="' . home_url() . '/spendenspiel/' . $gamedata->campaign_name . '?code=' . $gamecode . '" target="_blank" style="display: inline-block; padding: 10px 20px; color: white; text-decoration: none;">Zur Spendenaktion!</a>';
//		$message .= '</td>';
//		$message .= '</tr>';
//		$message .= '</table>';
//		$message .= '</td>';
//		$message .= '</tr>';
//		$message .= '</table>';
//
//		// TODO: Anpassbar!
//		$message .= '<p style="text-align:center;">' . $email_info . '</p>';
//		$message .= '<p style="text-align:center;"><strong> ' . $gamecode . ' </strong></p>';
//		$message .= '<p style="text-align:center;">Den Code können Sie unter der folgenden Adresse eingeben:</p>';
//		$message .= '<a style="display:inline-block;color:' . $secondary_color . ';text-align: center;width: 100%;text-decoration: none;" href="' . home_url() . '/spendenspiel/' . $gamedata->campaign_name . '">' . home_url() . '/spendenspiel/' . $gamedata->campaign_name . '</a>';
//		$message .= '<p style="text-align:center;">Die Teilnahme ist exklusiv für Sie vom ' . date( 'd.m.Y', strtotime( $gamedata->valid_from ) ) . ' bis zum ' . date( 'd.m.Y', strtotime( $gamedata->valid_until ) ) . ' verfügbar.</p>';
//		$message .= '<p style="text-align:center;">Wir freuen uns auf Ihre Teilnahme!</p>';
//		$message .= '</div>';
//
//		$message .= '<p style="text-align:center;">' . $email_signatur . '</p>';
//
//		$message .= '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top: 20px;">';
//		$message .= '<tr>';
//		$message .= '<td align="center" style="padding-bottom:32px">';
//
//		if ( $social_media_options ) {
//			foreach ( $social_media_options as $option ) {
//				$social_media_name = $option['social_media_name'];
//				$social_media_link = $option['social_media_link'];
//				$social_media_icon = $option['social_media_icon'];
//
//				$message .= '<a href="' . esc_url( $social_media_link ) . '"><img style="width: 40px; height: 40px; margin-right: 10px" src="' . esc_url( $social_media_icon ) . '" alt="' . esc_attr( $social_media_name ) . '"></a>';
//			}
//		}
//		$message .= '</td>';
//		$message .= '</tr>';
//		$message .= '</table>';
		$message .= '<div style="background-color: #28333E; color: white; font-family: Arial, sans-serif; padding: 20px;">';
		$message .= '<p style="font-size: 16px; text-align:center; font-weight: bold; font-style: italic;">Impressum:</p>';
		$message .= '<p style="font-size: 14px; text-align:center;">' . $company_name . '<br>' . $company_street . '<br>' . $company_city . '</p>';
		$message .= '</div>';
		$message .= '</body></html>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		update_field('email_sent', 1, $single_user->ID);

		//add_action( 'phpmailer_init', 'elancer\set_custom_smtp' );
		wp_mail( $single_email, $subject, $message, $headers );
		//remove_action( 'phpmailer_init', 'elancer\set_custom_smtp' );
	}
}
