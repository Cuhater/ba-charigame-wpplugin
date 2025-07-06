<?php
/*
Template Name: elancer Game Template
Template Post Type: edg-campaign
*/

namespace elancer;

use DateTime;
use DateTimeZone;
wp_head();
$bg_image = get_field( 'background-image', 'option' );

$spendenverteilung_gruppe = get_field( 'spendenverteilung_gruppe', get_the_ID() );
if ( $spendenverteilung_gruppe != null ) {
	$highscore       = $spendenverteilung_gruppe['highscore'];
	$gewinnkategorie = $spendenverteilung_gruppe['gewinnkategorie'];
	//var_dump($gewinnkategorie);

	if ( ! $highscore ) {
		$spendenbetrag = $gewinnkategorie[0]['spendenbetrag'];
	} else {
		$spendenbetrag = $gewinnkategorie[ count( $gewinnkategorie ) - 1 ]['spendenbetrag'];
	}
} else {
	$spendenbetrag = 0;
}
//echo $spendenbetrag;
//date_default_timezone_set('Europe/Berlin');
//$campaign_time  = get_field( 'dispatch_time', get_the_ID() );
//list($hour, $minute, $second) = explode(':', $campaign_time);
//$now = current_time('timestamp'); // lokale Zeit!
//$next_run = mktime($hour, $minute, $second, date('n', $now), date('j', $now), date('Y', $now));
//
//// Falls die Uhrzeit heute schon vorbei ist, auf morgen setzen
//if ($next_run <= $now) {
//	echo "NEXT RUN";
//	echo "<br>";
//	$next_run = strtotime('+1 day', $next_run);
//	$run = date('Y-m-d H:i:s', $next_run);
//	echo $run;
//	echo "<br>";
//}
//else{
//	echo "THIS RUN";
//	echo "<br>";
//	$next_run = strtotime('+1 day', $next_run);
//	$run = date('Y-m-d H:i:s', $next_run);
//	echo $run;
//
//}

?>
	<style>
		.template-background {
			background-image: url("<?php echo $bg_image ?>");
			background-size:cover;
		}
	</style>

	<div id="primary"
		 class="content-area">
		<main id="main"
			  class="site-main overflow-hidden" data-campaign="<?php echo get_the_ID() ?>">
			<?php
			date_default_timezone_set( 'UTC' );
			$current_date      = date( 'Y-m-d' );
			$current_timestamp = new DateTime( 'now' );
			$current_timestamp->setTimezone( new DateTimeZone( 'Europe/Berlin' ) );
			$currentDateTimeGMT1 = $current_timestamp->format( 'Y-m-d H:i:s' );

			$form_islocked = true;
			$gamedata      = null;
			$code_provided = false;

			if ( isset( $_POST['unlock_code'] ) ) {
				$gamedata      = search_game_data_by_game_code( sanitize_text_field( $_POST['unlock_code'] ) );
				$code_provided = true;
			} elseif ( isset( $_GET['code'] ) ) {
				$gamedata      = search_game_data_by_game_code( sanitize_text_field( $_GET['code'] ) );
				$code_provided = true;
			} else {
				display_unlock_form();
			}

			if ( $gamedata != null ) {
				//update the gamedata "code last used" for specific game_code
				add_data_to_edg_game_data_table( '', '', '', $gamedata->game_code, '', '', null, $currentDateTimeGMT1 );
				$campaign_slug = get_post_field( 'post_name', get_the_ID() );
				if ( $current_date >= $gamedata->valid_from && $current_date <= $gamedata->valid_until && $gamedata->campaign_name == $campaign_slug ) {
					display_game_content( $gamedata );
				} else if ( $gamedata->campaign_name != $campaign_slug ) {

					$redirect = home_url() . '/spendenspiel/' . $gamedata->campaign_name . '?code=' . $gamedata->game_code;
					wp_redirect( $redirect );
					exit();
				} else if ( $current_date <= $gamedata->valid_from || $current_date >= $gamedata->valid_until ) {
					display_error( $gamedata->valid_from, $gamedata->game_code );
				}


			} elseif ( $code_provided ) {
				$given_code = '';
				if ( isset ( $_POST['code'] ) ) {
					$given_code = sanitize_text_field( $_GET['code'] );
				} elseif ( isset( $_POST['unlock_code'] ) ) {
					$given_code = sanitize_text_field( $_POST['unlock_code'] );
				}
				display_error( '', $given_code );
			} elseif ( $code_provided && ! $form_islocked ) {
				display_game_content( $gamedata );
			}
			?>
		</main>

		<footer>
			<?php
			$company_settings = get_field( 'company_settings', 'option' );
			$agb_page         = $company_settings['agb_page'];
			$datenschutz      = $company_settings['datenschutz_page'];
			$impressum        = $company_settings['impressum_page'];
			?>
			<div class="bg-[#28333E] w-full p-12">
				<div class="z-10 container relative sm:max-w-6xl sm:mx-auto">
					<div class="flex flex-col sm:flex-row gap-4 justify-end">
						<?php if ( $impressum != '' ) { ?>
							<a target="_blank" href="<?php echo $impressum; ?>"
							   class="text-white">Impressum</a>
						<?php } ?>
						<?php if ( $datenschutz != '' ) { ?>
							<a target="_blank" href="<?php echo $datenschutz; ?>"
							   class="text-white">Datenschutz</a>
						<?php } ?>
						<?php if ( $agb_page != '' ) { ?>
							<a target="_blank" href="<?php echo $agb_page; ?>"
							   class="text-white">AGB</a>
						<?php } ?>
<!--						<a href="#"-->
<!--						   class="text-white">Cookie-Einstellungen</a>-->
					</div>
				</div>
			</div>
		</footer>
		<div class="max-sm:text-base"></div>
	</div><!-- #primary -->

	<?php
function display_error( $valid_until, $code ) {
	$torn_top         = get_field( 'section-primary', 'option' );
	$response = wp_remote_get( $torn_top );
	$torn_top = wp_remote_retrieve_body( $response );
	$company_settings = get_field( 'company_settings', 'option' );
	$logo             = $company_settings['company-logo'];
	?>

	<section>
		<div class="template-background">
			<div class="min-h-screen py-6 flex flex-col justify-center sm:py-12">

				<div class="z-10 container relative py-3 sm:max-w-xl sm:mx-auto">
					<div
						class="mt-16 z-[-1] absolute inset-0 bg-gradient-to-r from-secondary to-primary shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
					<a class="block w-max"
					   href="<?php echo home_url(); ?>">
						<img alt="Logo"
							 class="mb-8 w-16 l-16 rounded-full"
							 src="<?php echo $logo; ?>">
					</a>
					<div
						 class="rotate-180 -mb-1 w-full max-h-16 seperator-white">
					<?php echo $torn_top; ?>
					</div>
					<div class="bg-white p-8">
						<?php if ( $valid_until != '' ) { ?>
							<h1 class="pb-10 text-4xl">Code ist abgelaufen</h1>
							<p class="text-xl info-text">Leider ist Ihr Code (<?php echo $code ?>)
														 am <?php echo date( 'd.m.Y', strtotime( $valid_until ) ); ?> abgelaufen.</p>


						<?php } else { ?>
							<h1 class="pb-10 text-4xl">Code ist ungültig</h1>
							<p class="text-xl info-text">Leider der eingegebene Code (<?php echo $code ?>)
														 ungültig.</p>


						<?php } ?>

						<a href="<?php echo get_permalink( get_the_ID() ); ?>">
							<button class="flex flex-row items-center justify-between mt-8  px-4 xs:px-8 py-3 xs:py-[1.125rem] w-max bg-secondary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-secondary hover:ring-2
                hover:ring-secondary cursor-pointer">Zurück zum Login
							</button>
						</a>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php
}

?>

	<?php
function display_unlock_form() {
	$torn_top         = get_field( 'section-primary', 'option' );
	$response = wp_remote_get( $torn_top );
	$torn_top = wp_remote_retrieve_body( $response );

	$torn_bottom        = get_field( 'section-secondary', 'option' );
	$response_bottom = wp_remote_get( $torn_bottom );
	$torn_bottom = wp_remote_retrieve_body( $response_bottom );

	$company_settings = get_field( 'company_settings', 'option' );
	$logo             = $company_settings['company-logo'];
	$template_title   = get_field( 'login-form-headline', 'option' );
	?>

	<section class="bg-gradient-to-t from-primary to-secondary">
		<div class="custom-shape-divider-top-1721205319 absolute">
			<div class="-mb-1 w-full min-h-[90px] seperator-white opacity-30">
			<?php echo $torn_bottom; ?>
			</div>
		</div>
		<div class="template-background h-full opacity-10"></div>
		<div class="min-h-screen pt-12 flex flex-col justify-between sm:pt-12 absolute h-screen top-0 w-full">

			<div class="z-10 container relative py-3 sm:max-w-xl sm:mx-auto">
				<div
					class="mt-16 z-[-1] absolute inset-0 bg-gradient-to-r from-secondary to-primary shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
				<a class="block w-max"
				   href="<?php echo home_url(); ?>"><img alt="Logo"
														 class="mb-8 w-16 l-16 rounded-full"
														 src="<?php echo $logo; ?>"></a>
				<div class="rotate-180 -mb-1 w-full max-h-12 seperator-white">
					<?php echo $torn_top; ?></div>
				<div class="bg-white p-8">
					<h1 class="pb-10 text-4xl"><?php echo $template_title; ?></h1>
					<p class="text-xl info-text">Bitte geben Sie Ihren Zugangscode ein, den Sie per Mail erhalten haben.</p>
					<form method="post"
						  action="">
						<input
							class="shadow my-4 appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
							type="text"
							id="unlock_code"
							name="unlock_code"
							required>
						<div class="relative text-white hover:text-secondary w-max rounded-lg">
							<input class="flex flex-row items-center justify-between px-4 xs:px-8 py-3 xs:py-[1.125rem] w-max bg-secondary hover:bg-white rounded-lg text-secondary font-medium text-lg xs:text-xl hover:text-white hover:ring-2
                    hover:ring-secondary cursor-pointer"
								   type="submit"
								   value="Unlock">
							<svg class="w-12 h-12 absolute top-0 left-5 pointer-events-none"
								 fill="none"
								 stroke="currentColor"
								 viewBox="0 0 24 24"
								 xmlns="http://www.w3.org/2000/svg">
								<path stroke-linecap="round"
									  stroke-linejoin="round"
									  stroke-width="2"
									  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
							</svg>
						</div>
					</form>
				</div>
			</div>
			<div class="bg-transparent fill-[#27333E] rotate-180 w-full h-auto">
				<?php
				$svg_url  = get_field( 'section-primary', 'option' );
				$svg_code = file_get_contents( $svg_url );
				echo $svg_code;
				?>
			</div>
		</div>

	</section>
	<?php
}

function display_game_content( $gamedata ) {
	//Get Images
	$torn_top         = get_field( 'section-primary', 'option' );
	$response = wp_remote_get( $torn_top );
	$torn_top = wp_remote_retrieve_body( $response );

	$torn_bottom        = get_field( 'section-secondary', 'option' );
	$response_bottom = wp_remote_get( $torn_bottom );
	$torn_bottom = wp_remote_retrieve_body( $response_bottom );

	$logo_lg          = plugins_url( 'assets/images/logo-lg-white.webp', __DIR__ );
	$logo_lg          = get_field( 'company-logo', 'option' );
	$bg_video         = plugins_url( 'assets/videos/background-video.webm', __DIR__ );
	$bg_video         = get_field( 'video-mp4', 'option' );
	$company_settings = get_field( 'company_settings', 'option' );
	$logo             = $company_settings['company-logo'];

	//Get DB Data
	$template_title = get_field( 'login-form-headline', 'option' );
	$email_address  = $gamedata->email_address;
	$campaign_name  = $gamedata->campaign_name;
	$game_code      = $gamedata->game_code;
	$valid_from     = $gamedata->valid_from;
	$valid_until    = $gamedata->valid_until;
	$last_played    = $gamedata->last_played;

	//Get CPT User Data
	$user_data  = get_user_data_by_email( $email_address );
	$birthday   = $user_data->birthday;
	$first_name = $user_data->first_name;
	$last_name  = $user_data->last_name;

	//Get CPT GameType Data
	$current_game_type_id = get_field( 'game_type', get_the_ID() );

	$game_type    = get_post( $current_game_type_id );
	$spendensumme = get_field( "description", $game_type->ID );

	$game_form_gruppe   = get_field( 'game_form_gruppe', get_the_ID() );
	$campaign_title     = $game_form_gruppe['campaign_title'];
	$campaign_desc_text = $game_form_gruppe['campaign_desc_text'];

	?>
	<section id="intro"
			 class="bg-gradient-to-t from-primary to-secondary relative">
		<div class="template-background rounded-b-3xl h-full w-full absolute top-0 opacity-10 bg-cover"></div>


		<div class="z-10 container relative py-12 sm:max-w-2xl sm:mx-auto">
			<a class="absolute block w-max"
			   href="<?php echo home_url(); ?>">
				<img alt="Logo"
					 class="mb-8 w-16 l-16 rounded-full"
					 src="<?php echo $logo; ?>">
			</a>
			<div
				class="backplate mt-20 mb-10 z-[-1] absolute inset-0 bg-gradient-to-r from-secondary to-primary shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
			<div class="bg-white mt-20 p-8 rounded-t-3xl">
				<div class="container sm:max-w-xl sm:mx-auto">

					<h1 class="text-4xl pb-10 flex justify-center"><?php echo $campaign_title ?></h1>
					<div class="flex flex-col">
						<?php echo $campaign_desc_text; ?>
					</div>
					<div class="flex justify-center mt-4">
						<p class="text-center"><strong>Wir spielen <?php echo $game_type->post_title; ?>!</strong></p>
						<p class="pt-4">
							<?php echo $game_type->post_excerpt; ?>
						</p>
					</div>

				</div>
				<div class="flex justify-center">
					<a href="#spiel">
						<button class="flex flex-row items-center justify-between mt-8 px-4 xs:px-8 py-3 xs:py-[1.125rem] w-max bg-secondary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-secondary hover:ring-2
                hover:ring-secondary cursor-pointer">Zum Spiel
						</button>
					</a>
				</div>
				<p class="text-center mt-4 -mb-4 font-light">Die Teilnahme an dieser Aktion ist exklusiv für Sie bis
															 zum <?php echo date( 'd.m.Y', strtotime( $valid_until ) ); ?> möglich.</p>

			</div>
			<div class="intro-seperator -mt-1 w-full max-h-8 seperator-white">
			<?php echo $torn_bottom; ?>
			</div>
		</div>
		<div class="section fill-whitegrey rotate-180 !important">
			<?php

			echo $torn_top;
			?>
		</div>
	</section>

	<section id="recipients" class="bg-gray-100">
		<div class="z-10 container relative py-12 sm:max-w-6xl sm:mx-auto">
			<h2 class="font-main text-secondary text-4xl pb-10 flex justify-center text-center mb-8 max-sm:px-2">Wir spenden an diese Organisationen</h2>
			<div class="flex lg:flex-row flex-col gap-4">
				<?php
				$all_recipients_data = get_all_recipients_data_from_edg_game_id( get_the_ID() );
				$index               = 0;
				if ( ! empty( $all_recipients_data ) ) {
					foreach ( $all_recipients_data as $recipient ) { ?>
						<div id="<?php echo "recipient-" . $index ?>"
							 class="recipient basis-1/3 overflow-hidden mx-8 lg:mx-0 bg-gradient-to-r from-primary via-teritary2 to-secondary p-0.5 rounded-t-3xl shadow-xl max-sm:mb-4">
							<div class="bg-white p-8 flex flex-col items-center lg:-mb-1 rounded-t-3xl shadow-sm border border-gray-100 h-full">
								<img alt="Charity Logo"
									 class="aspect-square max-w-48 mb-4 object-contain"
									 src="<?php echo $recipient['logo']; ?>">
								<?php ?><h3
									class="font-main text-secondary text-2xl pb-10 flex justify-center text-center min-h-20"><?php echo $recipient['title']; ?></h3>
								<?php
								echo $recipient['description'];
								?>
							</div>
<!--							<div class="-mb-1 min-w-[1000px] w-full -ml-16 seperator-white">-->
<!--								--><?php //echo $torn_bottom; ?>
<!--							</div>-->
						</div>
						<?php
						$index ++;
					}

				} else {
					echo 'No recipients found.';
				}
				?>
			</div>
		</div>
	</section>
	<section id="cta-end" class="hidden bg-gray-200 relative pb-8">

		<?php
		$ctas = get_field( 'ctas', get_the_ID() );
		?>
		<?php
		if($ctas){
			foreach ($ctas as $cta) {
				$cta_title      = $cta['cta_headline'];
				$cta_desc       = $cta['cta_text'];
				$cta_button_text      = $cta['cta_button_text'];
				$cta_button_link       = $cta['cta_button_link'];
				$cta_type       = $cta['type'];
				if ( $cta_type == 'primary' ) {
					$cta_class = 'bg-primary';
					?>

					<div class="z-10 container relative py-12 sm:max-w-4xl sm:mx-auto">
						<a class="absolute block w-max"
						   href="<?php echo home_url(); ?>">
							<img alt="Logo"
								 class="mb-8 w-16 l-16 rounded-full"
								 src="<?php echo $logo; ?>">
						</a>
						<div
							class="backplate mt-20 mb-10 z-[-1] absolute inset-0 bg-gradient-to-r from-secondary to-primary shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl"></div>
						<div class="bg-white mt-20 p-8 rounded-t-3xl">
							<div class="container sm:max-w-2xl sm:mx-auto">

								<h1 class="text-4xl pb-10 flex justify-center"><?php echo $cta_title ?></h1>

								<div class="flex flex-col">
									<?php echo $cta_desc; ?>
								</div>
							</div>
							<div class="flex flex-col items-center md:flex-row justify-around">
								<a href="<?php echo $cta_button_link; ?>">
									<button class="flex flex-row items-center justify-between mt-8 px-4 xs:px-8 py-3 xs:py-[1.125rem] w-max bg-secondary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-secondary hover:ring-2
                hover:ring-secondary cursor-pointer"><?php echo $cta_button_text ?>
									</button>
								</a>
								<a href="https://natuerlich.reisen/">
									<button class="flex flex-row items-center justify-between mt-8 px-4 xs:px-8 py-3 xs:py-[1.125rem] w-max bg-primary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-primary hover:ring-2
                hover:ring-primary cursor-pointer">Hier die nächste Traumreise finden
									</button>
								</a>
							</div>
						</div>

					</div>


					<?php
				} else {
					$cta_class = 'bg-secondary';
				}
				?>
			<?php }
		}
		?>

	</section>
	<section class="relative overflow-hidden min-h-[350px]">

		<video id="bgVideo"
			   class="min-h-[500px] max-h-[500px] max-sm:min-h-[700px] max-sm:max-h-[700px] object-cover max-w-none w-full bg-gradient-to-r from-secondary to-primary py-8"
			   preload
			   autoplay
			   loop
			   muted>
			<source src="<?php echo $bg_video ?>"
					type="video/webm"/>
		</video>
		<div class="z-10 container absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
			<h2 class="font-main text-white text-4xl pb-10 text-center">Der aktuelle Spendenstand im Überblick</h2>
			<div class="text-center text-white">Aktueller Spendentopf insgesamt:</div>
			<p class="text-center text-white text-2xl">
				<?php
				//TODO: Save total in database for every campaign
				$single_campaign = get_post( get_the_ID() );
				$results         = get_overall_donations( $single_campaign->ID );
				$total_1         = 0;
				$total_2         = 0;
				$total_3         = 0;
				$spendenbetrag = get_field('spendentopf_initial', get_the_ID());
				//$total_array	 = array();
				if ( $results != null ) {
					foreach ( $results as $result ) {
						$total_1 += floatval( $result->score_r1 );
						$total_2 += floatval( $result->score_r2 );
						$total_3 += floatval( $result->score_r3 );

						$total_array[0] = $total_1;
						$total_array[1] = $total_2;
						$total_array[2] = $total_3;
					}

					$total_overall = $total_1 + $total_2 + $total_3;
					if($spendenbetrag != 0){
						echo number_format( $spendenbetrag, 2) . ' € + ';
					}
					?>
					<span id="donation-display"><?php echo number_format( $total_1 + $total_2 + $total_3, 2 ) ?></span> €
					<?php

				}
				else if($results == null && $spendenbetrag != 0){
					$total_overall = 0;
					echo number_format( $spendenbetrag, 2) . ' € + ';

					?>
					<span id="donation-display"><?php echo number_format( $total_1 + $total_2 + $total_3, 2 ) ?></span> €
					<?php

					$total_array[0] = 0.33;
					$total_array[1] = 0.33;
					$total_array[2] = 0.33;
				}
				else{
					$total_overall = 0;
					echo 'Noch keine Spende vorhanden';
				}
				?>
			</p>
			<?php if ( $total_overall > 0 || $results == null && $spendenbetrag != 0) { ?>
				<?php
				if($results == null && $spendenbetrag != 0){
					$total_array[0] = 0.33;
					$total_array[1] = 0.33;
					$total_array[2] = 0.33;
					$total_overall = 1;
				}
				?>
				<div class="z-10 container relative py-3 sm:max-w-xl sm:mx-auto">
					<h3 class="font-main text-white text-2xl pb-8 text-center">Die Spendenverteilung im Überblick</h3>
					<?php
					$index               = 0;
					$all_recipients_data = get_all_recipients_data_from_edg_game_id( get_the_ID() );

					if ( ! empty( $all_recipients_data ) ) {
						foreach ( $all_recipients_data as $recipient ) {

							if ( $total_array[ $index ] >= 0 ) { ?>

								<div class="flex max-sm:mx-8 max-sm:mb-4">
									<img alt="Charity Logo"
										 class="aspect-square max-w-12 mb-4 object-contain rounded-lg bg-white max-sm:h-[50px] max-sm:w-[50px]"
										 src="<?php echo $recipient['logo']; ?>">
									<div class="grow ml-4">
										<div class="flex justify-between mb-1">
											<span class="text-base font-medium text-white"><?php echo $recipient['title']; ?></span>
											<span id="recipient-num-<?php echo $index ?>" class="text-sm font-medium text-white max-sm:min-w-[75px] max-sm:text-right"><?php echo number_format( ( $total_array[ $index ] * 100 / $total_overall ), 0 ); ?> %</span>
										</div>
										<div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
											<div id="recipient-bar-<?php echo $index ?>" class="bg-primary h-2.5 rounded-full"
												 style="width:<?php echo number_format( ( $total_array[ $index ] * 100 / $total_overall ), 0 ); ?>%;"></div>
										</div>
									</div>
								</div>

								<?php
								$index ++;
							}
						}
					} ?>
				</div>
			<?php } ?>


		</div>

	</section>

	<section id="how-to-play"
			 class="bg-white pb-10 how-to-play overflow-hidden">
		<div>
			<div class="z-10 container relative pb-12 sm:max-w-6xl sm:mx-auto flex justify-center items-baseline">
			<?php
			$how_to_play = get_field('how_to_play_group', $game_type->ID);
			if ($how_to_play && isset($how_to_play['how_to_play_headline'])) {
				echo '<h2 class="font-main text-secondary text-4xl pt-10 text-center">' . esc_html($how_to_play['how_to_play_headline']) . '</h2>';
			}
			?>
		</div>
		<?php
		if (!empty($how_to_play['how_to_play_steps'])) {
			echo '<div class="flex gap-4 pb-4 sm:max-w-6xl sm:mx-auto justify-center items-baseline max-sm:flex-col">';
			foreach ($how_to_play['how_to_play_steps'] as $step) {
				if ( $step['step_color'] == 'primary' ) {
					$step_color = 'bg-primary';
				} else if ( $step['step_color'] == 'secondary' ) {
					$step_color = 'bg-secondary';
				}
				else if ( $step['step_color'] == 'teritary' ) {
					$step_color = 'bg-teritary';
				}

				echo '<div class="flex items-center gap-4 flex-1 flex-col">';
				echo '<span class="shrink-0 rounded-lg '. $step_color .' p-4">';
				if (!empty($step['step_icon'])) {
					echo '<img class="w-6 h-6" src="' . esc_url($step['step_icon']) . '" alt="Icon"> ';
				}
				echo '</span>';
				// Text ausgeben, falls vorhanden
				if (!empty($step['step_headline'])) {
					echo '<span class="text-lg font-bold text-center">' . esc_html($step['step_headline']) . '</span>';
				}

				if (!empty($step['step_text'])) {
					echo '<span class="mt-1 text-sm text-gray-600 text-center mx-8">' . esc_html($step['step_text']) . '</span>';
				}
				echo '</div>';

			}
			echo '</div>';
		}
		?>
		</div>

	</section>
	<section id="spiel"
			 class="overflow-hidden bg-secondary">
		<div id="bottom-seperator" class="seperator-white">
		<?php echo $torn_bottom; ?>
		</div>
		<h2 id="gamesection-headline"
			class="font-main text-white text-4xl text-center mb-10 max-sm:mx-4">Das Spiel</h2>

		<?php
		$game_code_pre = substr( str_shuffle( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789' ), 0, 4 );
		$game_code_end = substr( str_shuffle( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789' ), 0, 4 );
		?>
		<span id="hidden-code"
			  class="hidden"
			  data-game-code="<?php echo $game_code_pre ?><?php echo $game_code ?><?php echo $game_code_end ?>"></span>
		<?php

		$current_game_type_id = get_field( 'game_type', get_the_ID() );
		// memory game settings || scripts and styl
		if ( $current_game_type_id == 999998 ) {
			require_once plugin_dir_path( __DIR__ ) . 'src/games/memory/game.php';
		} else if ( $current_game_type_id == 999999 ) {
			require_once plugin_dir_path( __DIR__ ) . 'src/games/tower/game.php';
		}
		?>

		<div id="picker-container"
			 class="lg:my-0 bg-gray-100 max-sm:w-[80%] max-sm:mx-auto">
			<div class="lg:w-1/3 lg:mx-auto">
				<div id="picker"
					 class="picker">
				</div>
			</div>
			<div class="flex justify-center">
				<button id="btn-submit-score"
						class="hidden flex-row items-center justify-between mt-8 px-4 xs:px-8 py-3 xs:py-[1.125rem] w-max bg-secondary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-secondary hover:ring-2
                hover:ring-secondary cursor-pointer max-sm:mt-40"
						title="Spende verteilen">
					Spende verteilen
				</button>
			</div>
		</div>
		<?php
		$spendenverteilung_gruppe = get_field( 'spendenverteilung_gruppe', get_the_ID() );
		if ( $spendenverteilung_gruppe != null ) {
			$highscore       = $spendenverteilung_gruppe['highscore'];
			$gewinnkategorie = $spendenverteilung_gruppe['gewinnkategorie'];
			if ( !$highscore ) {
				$spendenbetrag = $gewinnkategorie[0]['spendenbetrag'];
			} else {
				$spendenbetrag = $gewinnkategorie[ count( $gewinnkategorie) - 1 ]['spendenbetrag'];
			}
		} else {
			$spendenbetrag = 0;
		}
		?>
		<div id="btn-play-again-end-container"
			 class="flex justify-center items-center flex-col hidden">
			<div class="flex justify-center"><p class="text-center">Sehr gut. Versuchen Sie den maximalen Spendenbetrag
								  von <?php echo number_format( $spendenbetrag, 2 ); ?>€
								  zu erreichen!</p></div>
			<button id="btn-play-again-end"
					class="btn-play-again-end flex justify-center w-[305px] px-4 xs:px-8 py-3 xs:py-[1.125rem] my-4 text-secondary bg-white hover:bg-secondary hover:text-white rounded-lg font-medium text-lg xs:text-xl ring-2
                ring-secondary cursor-pointer"> Noch mal versuchen
			</button>
			<p class=" text-sm text-center mb-8">Beachten Sie, dass Ihr erspieltes Ergebnis überschrieben wird.
<!--				<br>Wenn Sie zufrieden sind mit-->
<!--														   Ihrem Ergebnis, können Sie jederzeit <a class="text-[#28333E]"-->
<!--																				  href="--><?php //echo home_url(); ?><!--">unsere Website besuchen</a>-->
			</p>
			<div class="bg-white w-full hidden">
				<?php $results = get_top_results( get_the_ID() );
				if ( $results ) { ?>

					<h2 class="font-main text-white text-4xl text-center mb-10 mt-10">Die Bestenliste</h2>
					<div class="flex justify-center">
						<table class="table-auto w-2/3">
							<thead>
							<tr class="text-left">
								<th class="font-main tracking-wider text-white text-2xl">Name</th>
								<th class="font-main tracking-wider text-white text-2xl">zuletzt gespielt</th>
								<th class="font-main tracking-wider text-white text-2xl">Highscore</th>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ( $results as $result ) {
								$user_first_name = $result->name_first_4;
								$user_last_name  = $result->name_last_4;
								$score           = $result->highscore;
								$played          = $result->last_played;
								$formattedDate   = date( "d.m.Y", strtotime( $played ) );
								?>
								<tr class="text-white">
									<td><?php echo $user_first_name . '. ' . $user_last_name . '.'; ?></td>
									<td><?php echo $formattedDate; ?></td>
									<td><?php echo $score; ?></td>
								</tr>
								<?php
							}
							?>
							</tbody>
						</table>
					</div>
				<?php } ?>
				<div class="my-8 text-white text-center">Deine erspielte Punktzahl in dieser Runde:</div>
				<div class="mb-4 text-white text-center font-main text-4xl font-extrabold"><span id="game-points-end"></span></div>
			</div>
		</div>
	</section>
	<!-- TODO: Anpassen bzw ändern nach Vorgabe -->

	<div id="distribution"
		 class="hidden mt-8">
		<?php if ( $total_overall > 0 ) { ?>
			<div class="z-10 container relative mb-12 py-3 sm:max-w-xl sm:mx-auto">
				<h3 class="font-main text-white text-2xl pb-10 text-center">Die aktuelle Spendenverteilung im Überblick</h3>
				<?php
				$index               = 0;
				$all_recipients_data = get_all_recipients_data_from_edg_game_id( get_the_ID() );
				if ( ! empty( $all_recipients_data ) ) {
					foreach ( $all_recipients_data as $recipient ) {

						if ( $total_array[ $index ] >= 0 ) { ?>


							<div class="flex justify-between mb-1">
								<span class="text-base font-medium text-blue-700 dark:text-white"><?php echo $recipient['title']; ?></span>
								<span class="text-sm font-medium text-blue-700 dark:text-white"><?php echo number_format( ( $total_array[ $index ] * 100 / $total_overall ), 0 ); ?> %</span>
							</div>
							<div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
								<div class="bg-primary h-2.5 rounded-full"
									 style="width:<?php echo number_format( ( $total_array[ $index ] * 100 / $total_overall ), 0 ); ?>%;"></div>
							</div>

							<?php
							$index ++;
						}
					}
				} ?>
			</div>
		<?php } ?>
<!--		<img alt="Seperator"-->
<!--			 class="rotate-180 -mt-1 w-full max-h-16"-->
<!--			 src="--><?php //echo $torn_top; ?><!--">-->
		<?php echo $torn_bottom; ?>

	</div>

	<div id="modal-game-end"
		 class="bg-gradient-to-t from-secondary to-primary fixed z-50 inset-0 hidden rounded-b-3xl overflow-y-hidden">
		<div
			 class="intro-seperator -mt-1 w-full max-h-8 seperator-white"
		>
		<?php echo $torn_bottom; ?>
	</div>
		<div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
			<div
				class="bg-white border-t-8 border-t-primary inline-block align-bottom rounded-b-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 container my-auto mx-auto px-6 max-w-5xl"
				role="dialog"
				aria-modal="true"
				aria-labelledby="modal-headline">
				<div class="bg-dark px-4 pt-5 sm:p-6 sm:pb-4">
					<div class="">
						<div class="pt-6 text-center sm:mt-0">
							<h3 class="text-3xl sm:text-6xl"
								id="popup-game-finished">
								Vielen Dank für Ihre Teilnahme!
							</h3>
							<div class="mt-6 sm:mt-12">
								<p class="text-xl sm:text-3xl">
									Sie haben insgesamt <span id="game-points"
															  class="text-secondary">x</span> <span id="game-objectives">Karten umgedreht</span> und
									<span id="game-time"
										  class="text-secondary">x</span>
									<span id="game-time-unit">O</span> benötigt.
								</p>
								<p id="scored" class="text-xl sm:text-3xl">
									Der Spendentopf erhöht sich damit um <span id="personal-bonus"
																			   class="text-secondary">x</span>€.
								</p>
								<p id="not-scored" class="text-xl sm:text-3xl pt-4">
									Der Spendentopf konnte diesmal nicht erhöht werden.<br>
									Geben Sie nicht auf – versuchen Sie es gleich nochmal!
								</p>
							</div>
						</div>
					</div>
				</div>
				<div class="px-4 sm:px-6 pt-3 lg:pt-12 pb-10 lg:pb-14 flex flex-col justify-center items-center">
					<button id="show-donation-triangle"
							class="btn-show-donation flex justify-center w-[305px] mt-8 px-4 xs:px-8 py-3 xs:py-[1.125rem] bg-secondary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-secondary hover:ring-2
                hover:ring-secondary cursor-pointer">Spendentopf erhöhen & verteilen
					</button>
					<button id="btn-play-again"
							class="btn-play-again flex justify-center w-[305px] mt-8 px-4 xs:px-8 py-3 xs:py-[1.125rem] bg-teritary hover:bg-white rounded-lg text-white font-medium text-lg xs:text-xl hover:text-teritary hover:ring-2
                hover:ring-teritary cursor-pointer"> Noch mal versuchen
					</button>

				</div>

			</div>
		</div>
	</div>

	<?php
}
