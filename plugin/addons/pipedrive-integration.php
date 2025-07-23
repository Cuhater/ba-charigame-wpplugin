<?php

namespace elancer;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PipedriveIntegration {
	public function __construct() {
		add_action( 'pipedrive_integration_addon_init', [ $this, 'init' ] );
		add_action( 'admin_menu', [ $this, 'add_submenu' ] );
		add_action( 'wp_ajax_fetch_pipedrive_contacts_ajax', [ $this, 'fetch_pipedrive_contacts_ajax' ] );
		add_action( 'wp_ajax_nopriv_fetch_pipedrive_contacts_ajax', [ $this, 'fetch_pipedrive_contacts_ajax' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'edg_enqueue_admin_pipedrive_scripts' ] );

	}

	public function init() {
		edg_register_addon( $this );
	}

	public function add_submenu() {
		add_submenu_page(
			'edg-types', // Slug des Hauptmenüs
			'Pipedrive Integration',
			'Pipedrive Integration',
			'manage_options',
			'pipedrive-integration',
			[ $this, 'render_submenu_page' ]
		);
	}

	public function render_submenu_page() {
		echo '<h1>Pipedrive Integration</h1>';

		?>

		<div id="pipedrive-content">
			<div class="postbox">
				<div class="inside">
					<h2>Pipedrive API-Import</h2>
					<div><p class="">Bitte beachte, dass die folgenden Einstellungen mit sorgfalt verwendet werden müssen. <br>Die Abfrage bei
									 Pipedrive kann einige Zeit in Anspruch nehmen!</p></div>

					<label for="checkbox_id">
						<input name="checkbox_id"
							   type="checkbox"
							   id="checkbox_id"
							   value="1">
						Ich bin sicher, dass ich die Daten aus Pipedrive manuell importieren möchte.
					</label>
					<div id="progress-bar-container"
						 style="width: 100%; background-color: #f3f3f3; margin: 20px 0;">
						<div id="progress-bar"
							 style="width: 0; height: 30px; background-color: #4caf50;justify-content: center;display: flex;align-items: center;color: white;border-radius:5px;"></div>
					</div>
					<div id="progress-batch"></div>
					<div id="progress-current"></div>
					<div id="progress-total"></div>
					<div id="progress-pagination"></div>
					<div id="progress-pagination-total"></div>
					<div id="progress"></div>

					<button class="button button-small"
							id="startBtn">Benutzerdaten aus Pipedrive importieren
					</button>
					<br>
					<br>
					<?php
					$is_checked = get_option('pipedrive_daily_import', 0);
					?>
					<label for="checkbox_id2">
						<input name="checkbox_id2"
							   type="checkbox"
							   id="checkbox_id2"
							   value="1"
							<?php echo $is_checked ? 'checked' : ''; ?>>
						Die Nutzer sollen täglich aus der Pipedrive importiert werden.
					</label>
					<br>
					<br>
					<button class="button button-small button-primary"
							id="saveBtn">Einstellung speichern
					</button>

				</div>
			</div>
		</div>



		<?php
	}


	public function fetch_pipedrive_contacts_ajax() {

		// 1) Grundvoraussetzungen prüfen
		if ( ! defined( 'PIPEDRIVE_API_TOKEN' ) || ! defined( 'PIPEDRIVE_COMPANY_DOMAIN' ) ) {
			wp_send_json_error( [ 'error' => 'Pipedrive‑Konstanten fehlen.' ] );
			wp_die();
		}
		check_ajax_referer( 'edg_nonce', 'nonce' );

		// 2) Eingaben & Defaults
		$apiToken        = PIPEDRIVE_API_TOKEN;
		$companyDomain   = PIPEDRIVE_COMPANY_DOMAIN;
		$created_users   = [];

		$start           = isset( $_POST['start'] ) ? (int) $_POST['start'] : 0;
		$limit           = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 100;
		$paginationCount = isset( $_POST['paginationCount'] ) ? (int) $_POST['paginationCount'] : 0;

		// 3) Gesamtzahl der Kontakte holen (nur beim allerersten Aufruf)
		if ( empty( $_POST['totalContacts'] ) ) {
			$totalContactsFetched = $this->get_total_pipedrive_contacts( $apiToken, $companyDomain );

			if ( empty( $totalContactsFetched['success'] ) ) {
				wp_send_json_error( [ 'error' => 'Gesamtzahl konnte nicht ermittelt werden.' ] );
				wp_die();
			}
			$total_count = (int) ( $totalContactsFetched['additional_data']['summary']['total_count'] ?? 0 );
		} else {
			$total_count = (int) $_POST['totalContacts'];
		}

		$currentContacts = isset( $_POST['currentContacts'] ) ? (int) $_POST['currentContacts'] : 0;

		// 4) Personen batch‑weise abrufen
		$response = $this->fetchPersons( $apiToken, $companyDomain, $start, $limit );

		if ( empty( $response ) || empty( $response['success'] ) ) {
			wp_send_json_error( [ 'error' => 'API‑Antwort leer oder Fehler.' ] );
			wp_die();
		}

		$persons     = $response['data'] ?? [];
		$batchCount  = count( $persons );
		$currentContacts += $batchCount;
		$moreItems   = $response['additional_data']['pagination']['more_items_in_collection'] ?? false;

		// 5) Nur Personen mit Won‑Deal + E‑Mail + Geburtstag
		$personsWithWonDeals = [];
		foreach ( $persons as $person ) {
			if (
				( $person['won_deals_count'] ?? 0 ) >= 1 &&
				! empty( $person['primary_email'] ) &&
				! empty( $person['6a63bc9d4920221168bd057fc05822bdd54b1b6a'] &&
				!$person['5c4c362c2ad9a99eb7d0654c6fe40ae6cb17f3b5']) //exclude users with pipedrive-flag
			) {
				$personsWithWonDeals[] = $person;
			}
		}

		// 6) WP‑User anlegen – nur wenn noch nicht vorhanden
		foreach ( $personsWithWonDeals as $person ) {
			$first_name = $person['first_name'];
			$last_name  = $person['last_name'];
			$email      = $person['primary_email'];
			$birthday   = $person['6a63bc9d4920221168bd057fc05822bdd54b1b6a'];


			if ( ! $this->edg_user_exists_by_email( $email ) ) {
				$created_users[] = $email;
				create_edg_user_with_acf( $first_name, $last_name, $email, $birthday );
			}
		}

		// 7) Erfolgs‑Antwort
		wp_send_json_success( [
			'currentBatchCount' => $batchCount,
			'currentContacts'   => $currentContacts,
			'totalContacts'     => $total_count,
			'paginationCount'   => $paginationCount + 1,
			'moreItems'         => $moreItems,
			'createdUsersCount' => count( $created_users ),
			'createdUsers'      => $created_users,
		] );
		wp_die();
	}

	public function get_total_pipedrive_contacts( $apiToken, $companyDomain ) {
		$url = "https://{$companyDomain}.pipedrive.com/v1/persons?api_token={$apiToken}&start=0&limit=1&get_summary=1&filter_id=504";
		$ch  = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $response, true );
	}

	public function fetchPersons( $apiToken, $companyDomain, $start = 0, $limit = 500 ) {
		$url = "https://{$companyDomain}.pipedrive.com/v1/persons?api_token={$apiToken}&start={$start}&limit={$limit}&filter_id=504";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $response, true );
	}
	public function edg_user_exists_by_email($email) {
		$args = array(
			'post_type'  => 'edg-user',
			'meta_query' => array(
				array(
					'key'   => 'email',
					'value' => $email,
				),
			),
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		);
		$query = new \WP_Query($args);
		return $query->have_posts();
	}

	public function edg_enqueue_admin_pipedrive_scripts() {
		wp_enqueue_script( 'edg-ajax-script', plugin_dir_url( __FILE__ ) . '/pipedrive-ajax.js', array( 'jquery' ), null, true );

		wp_localize_script( 'edg-ajax-script', 'edgAjax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'edg_nonce' )
		) );
	}
}

new PipedriveIntegration();
