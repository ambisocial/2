<div class="tie-welcome">
	<div class="tie-badge">
		<img src="<?php echo esc_attr( TIELABS_TEMPLATE_URL .'/framework/admin/assets/images/tielabs-logo-mini.png' ); ?>" alt="" />
		<?php printf( esc_html__( 'Version %s', TIELABS_TEXTDOMAIN  ), TIELABS_DB_VERSION ); ?>
	</div>

	<?php

		tie_build_theme_option(
			array(
				'title' => sprintf( esc_html__( 'Welcome to %s', TIELABS_TEXTDOMAIN ), apply_filters( 'TieLabs/theme_name', 'TieLabs' ) ),
				'id'    => 'dashboard-tab',
				'type'  => 'tab-title',
			));

		echo '<p class="tie-getting-started">';
		printf( esc_html__( 'Thank you for installing %1$s Theme! Everything in %1$s is streamlined to make your website building experience as simple and intuitive as possible. We hope you will turn it into a powerful marketing asset that brings customers to your digital doorstep.', TIELABS_TEXTDOMAIN ), apply_filters( 'TieLabs/theme_name', 'TieLabs' ) );
		echo '</p>';
	?>

	<div class="clear"></div>
</div>

<?php

// ---
$active_theme = wp_get_theme();

$theme = array(
	'name'           => $active_theme->Name,
	'version'        => TIELABS_DB_VERSION, //$parent_theme->Version,
	'version_latest' => tie_get_latest_theme_data( 'version' ),
);

if ( is_child_theme() ) {
	$parent_theme = wp_get_theme( $active_theme->Template );
	$theme['name'] = $parent_theme->Name;
}

if( empty( $theme['version_latest'] ) ){
	$theme['version_latest'] = 0;
}

// Dot Net Version
$dot_net_version = tie_get_latest_from_dot_net( 'version' );
if( ! empty( $dot_net_version ) && version_compare( $theme['version_latest'], $dot_net_version, '<' )  ){
	$theme['version_latest'] = $dot_net_version;
}

// Update Notice
if ( ! empty( $theme['version'] ) && ! empty( $theme['version_latest'] ) && version_compare( $theme['version'], $theme['version_latest'], '<' ) ) {
	tie_build_theme_option(
		array(
			'text' => '<strong style="font-size: 14px; color: #d63638;">'. esc_html__( 'Important:', TIELABS_TEXTDOMAIN ) . '</strong> <strong style="font-size: 14px;">'. sprintf( esc_html__( 'Looks like you have an outdated version of %s! It is recommended to keep the theme up to date for security reasons and new features.', TIELABS_TEXTDOMAIN ), apply_filters( 'TieLabs/theme_name', 'TieLabs' ) ) .' <a target="_blank" href="'. apply_filters( 'TieLabs/External/changelog', '' ) .'">'. esc_html__( 'Check the changelog page for the most recent version.', TIELABS_TEXTDOMAIN ) .'</a></strong>',
			'type' => 'error',
		));
}

// --
TIELABS_VERIFICATION::support_notice();

?>

<table id="tie-license-registration" class="widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="2"><?php esc_html_e( 'License Registration', TIELABS_TEXTDOMAIN ); ?> 
				<?php if( tie_get_token() ): ?>
					<a class="button button-primary" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=tie-theme-options&refresh-support' ), 'refresh-support', 'refresh_support_nonce' ) ?>"><?php esc_html_e( 'Refresh', TIELABS_TEXTDOMAIN ) ?></a>
				<?php endif; ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php esc_html_e( 'License', TIELABS_TEXTDOMAIN ); ?></td>
			<td>
				<?php
					if( tie_get_token() ){
						echo '<strong style="color: #65b70e"><span class="dashicons dashicons-yes"></span> '. esc_html__( 'Your Site is Validated', TIELABS_TEXTDOMAIN ) .'</strong>';
					?>
					<a id="manage-licenses-button" class="tie-primary-button button" target="_blank" href="<?php esc_html_e( 'https://tielabs.com/members/licenses/' ) ?>"><?php esc_html_e( 'Manage Your Licenses', TIELABS_TEXTDOMAIN ) ?></a>
					<?php
					}
					else{
						echo '<strong style="color: red"><span class="dashicons dashicons-no"></span>'. esc_html__( 'Your Site is not Validated', TIELABS_TEXTDOMAIN ) .'</strong>';
					}
				?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Version', TIELABS_TEXTDOMAIN ); ?></td>
			<td>
				<?php

					esc_html_e( $theme['version'] );

					if ( ! empty( $theme['version'] ) && ! empty( $theme['version_latest'] ) && version_compare( $theme['version'], $theme['version_latest'], '<' ) ) {
						echo ' &ndash; <a style="color:orange; text-decoration: underline; font-weight: bold;" target="_blank" href="'. tie_get_purchase_link( array( 'utm_medium' => 'version-notice' ) ) .'">'. sprintf( esc_html__( '%s is available', TIELABS_TEXTDOMAIN ), esc_html( $theme['version_latest'] ) ) . '</a>';
					}
				?>
			</td>
		</tr>
	
		<?php if( tie_get_token() ): ?>
			<tr>
				<td><?php esc_html_e( 'Support', TIELABS_TEXTDOMAIN ); ?></td>
				<td><?php TIELABS_VERIFICATION::support_compact_notice() ?></td>
			</tr>
		<?php endif; ?>

	</tbody>
</table>


<?php

do_action( 'TieLabs/Dashboard_tab/before_news' );

$new_deals = tie_get_latest_from_dot_net( 'deals' );

if( ! empty( $new_deals ) && is_array( $new_deals ) ){ ?>
	<table class="tie-deals-table widefat" cellspacing="0">
		<thead>
			<tr>
				<th colspan="3"><?php esc_html_e( 'News', TIELABS_TEXTDOMAIN ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach ( $new_deals as $single ) {
					tie_dashboard_news_deals( $single );
				}
			?>
		</tbody>
	</table>
	<?php
}

do_action( 'TieLabs/Dashboard_tab/after_news' );


// --
function tie_dashboard_news_deals( $data ){

	$data = wp_parse_args( $data, array(
		'url'         => '',
		'img'         => '',
		'button'      => '',
		'message'     => '',
		'bg_color'    => '',
		'state'       => '', 	//active, non-active, active-support, expired-support
		'version_min' => '',
		'version_max' => '',
		'start_date'  => '',
		'expire_date' => '',
	));


	// State
	if( ! empty( $data['state'] ) ){

		if( $data['state'] == 'active' && ! tie_get_token() ){
			return false;
		}
		elseif( $data['state'] == 'inactive' && tie_get_token() ){
			return false;
		}

		$support_info = tie_get_support_period_info();

		if( $data['state'] == 'active-support' && ! empty( $support_info['status'] ) && $support_info['status'] == 'expired' ){
			return false;
		}
		elseif( $data['state'] == 'expired-support' && ! empty( $support_info['status'] ) && $support_info['status'] != 'expired' ){
			return false;
		}
	}

	// Function Exists
	if( ! empty( $data['function'] ) && function_exists( $data['function'] ) ){
		return false;
	}

	// Show the message if current Version is lower than
	if( ! empty( $data['version_max'] ) && version_compare( TIELABS_DB_VERSION, $data['version_max'], '>' ) ){
		return false;
	}

	// Show the message if current Version is greater than
	if( ! empty( $data['version_min'] ) && version_compare( TIELABS_DB_VERSION, $data['version_min'], '<' ) ){
		return false;
	}

	// --
	$today = strtotime( date('Y-m-d') );

	// Start date
	if( ! empty( $data['start_date'] ) ) {
		$start_date = strtotime( $data['start_date'] );

		if( $start_date > $today ){
			return false;
		}
	}

	// Expire date
	if( ! empty( $data['expire_date'] ) ) {
		$expire_date = strtotime( $data['expire_date'] );

		if( $expire_date <= $today ){
			return false;
		}
	}

	$style = ! empty( $data['bg_color'] ) ? 'style="background-color:'. $data['bg_color'] .'"' : false;
	?>

	<tr <?php echo $style ?>>
		<td style="width: 100px">
			<a href="<?php echo esc_url( $data['url'] ); ?>" target="_blank"><img src="<?php echo esc_url( $data['img'] ); ?>" style="max-width: 100%;" alt=""></a>
		</td>
		<td class="tie-deal-message"><?php echo wp_kses_post( $data['message'] ); ?></td>
		<td><a href="<?php echo esc_url( $data['url'] ); ?>" target="_blank" class="button button-primary"><?php echo wp_kses_post( $data['button'] ); ?></a></td>
	</tr>
	<?php
}

?>


<div id="dashboard-need-help">
	<div class="col column tie-info-col">
		<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M366.05,146a46.7,46.7,0,0,1-2.42-63.42,3.87,3.87,0,0,0-.22-5.26L319.28,33.14a3.89,3.89,0,0,0-5.5,0l-70.34,70.34a23.62,23.62,0,0,0-5.71,9.24h0a23.66,23.66,0,0,1-14.95,15h0a23.7,23.7,0,0,0-9.25,5.71L33.14,313.78a3.89,3.89,0,0,0,0,5.5l44.13,44.13a3.87,3.87,0,0,0,5.26.22,46.69,46.69,0,0,1,65.84,65.84,3.87,3.87,0,0,0,.22,5.26l44.13,44.13a3.89,3.89,0,0,0,5.5,0l180.4-180.39a23.7,23.7,0,0,0,5.71-9.25h0a23.66,23.66,0,0,1,14.95-15h0a23.62,23.62,0,0,0,9.24-5.71l70.34-70.34a3.89,3.89,0,0,0,0-5.5l-44.13-44.13a3.87,3.87,0,0,0-5.26-.22A46.7,46.7,0,0,1,366.05,146Z" fill="none" stroke="#000" stroke-miterlimit="10" stroke-width="32"/><line fill="none" stroke="#000" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" x1="250.5" x2="233.99" y1="140.44" y2="123.93"/><line fill="none" stroke="#000" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" x1="294.52" x2="283.51" y1="184.46" y2="173.46"/><line fill="none" stroke="#000" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" x1="338.54" x2="327.54" y1="228.49" y2="217.48"/><line fill="none" stroke="#000" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" x1="388.07" x2="371.56" y1="278.01" y2="261.5"/></svg>		<h3><?php esc_html_e( 'Submit a Ticket', TIELABS_TEXTDOMAIN ); ?></h3>
		<p><?php esc_html_e( 'Need one-to-one assistance? Get in touch with our Support team.', TIELABS_TEXTDOMAIN ); ?></p>

		<?php
			if( tie_get_token() ){
				$support_info = tie_get_support_period_info();

				if( ! empty( $support_info['status'] ) && $support_info['status'] == 'expired' ){
					echo '<p style="font-weight:bold; color: red;">'. esc_html__( 'Your Support Period Has Expired', TIELABS_TEXTDOMAIN ) .'</p>';
				}
				else{
					?>
						<a target="_blank" class="button button-primary button-hero" href="<?php echo apply_filters( 'TieLabs/External/open_ticket', '' ); ?>"><?php esc_html_e( 'Submit a Ticket', TIELABS_TEXTDOMAIN ); ?></a>
					<?php
				}
			}
			else{

				echo '<p style="font-weight:bold; color: red;">'. esc_html__( 'You need to validate your license to access the support system.', TIELABS_TEXTDOMAIN ) .'</p>';
			}
		?>
	</div>

	<div class="col column tie-info-col">
		<svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><rect height="416" rx="48" ry="48" style="fill:none;stroke:#000;stroke-linejoin:round;stroke-width:32px" width="320" x="96" y="48"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" x1="176" x2="336" y1="128" y2="128"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" x1="176" x2="336" y1="208" y2="208"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" x1="176" x2="256" y1="288" y2="288"/></svg>
		<h3><?php esc_html_e( 'Knowledge Base', TIELABS_TEXTDOMAIN ); ?></h3>
		<p><?php esc_html_e( 'This is the place to go to reference different aspects of the theme.', TIELABS_TEXTDOMAIN ); ?></p>
		<a target="_blank" class="button button-primary" href="<?php echo apply_filters( 'TieLabs/External/knowledge_base', '' ); ?>"><?php esc_html_e( 'Start Reading', TIELABS_TEXTDOMAIN ); ?></a>
	</div>

	<div class="col column tie-info-col">
		<svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><path d="M85.57,446.25H426.43a32,32,0,0,0,28.17-47.17L284.18,82.58c-12.09-22.44-44.27-22.44-56.36,0L57.4,399.08A32,32,0,0,0,85.57,446.25Z" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"/><path d="M250.26,195.39l5.74,122,5.73-121.95a5.74,5.74,0,0,0-5.79-6h0A5.74,5.74,0,0,0,250.26,195.39Z" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"/><path d="M256,397.25a20,20,0,1,1,20-20A20,20,0,0,1,256,397.25Z"/></svg>
		<h3><?php esc_html_e( 'Troubleshooting', TIELABS_TEXTDOMAIN ); ?></h3>
		<p><?php esc_html_e( 'If something is not working as expected, Please try these common solutions.', TIELABS_TEXTDOMAIN ); ?></p>
		<a target="_blank" class="button button-primary" href="<?php echo apply_filters( 'TieLabs/External/troubleshooting', '' ); ?>"><?php esc_html_e( 'Visit The Page', TIELABS_TEXTDOMAIN ); ?></a>
	</div>
</div>


<?php TIELABS_VERIFICATION::rating_notice(); ?>

