<?php
/**
 * Enquiries: no lead is ever lost, and no key is ever in the page.
 *
 * Pipeline: honeypot → rate limit → nonce (recorded) → validate → SAVE → email → retry → purge.
 * The form posts to admin-post.php. Forwarding to a CRM happens on the server through the
 * {{PREFIX}}_enquiry_saved action, with keys from wp-config.php, never from the browser.
 *
 * Settings (wp-config.php): {{PREFIX_UC}}_ENQUIRY_TO (inbox), {{PREFIX_UC}}_MAIL_FROM (sender),
 * {{PREFIX_UC}}_ENQUIRY_RETENTION_MONTHS (default 24; say the same in the privacy notice).
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Private store for enquiries. Admins only; nobody can create one by hand.
 *
 * @return void
 */
function {{PREFIX}}_register_enquiry_type() {
	register_post_type(
		'{{PREFIX}}_enquiry',
		array(
			'labels'              => array(
				'name'          => __( 'Enquiries', '{{SLUG}}-core' ),
				'singular_name' => __( 'Enquiry', '{{SLUG}}-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-email-alt',
			'supports'            => array( 'title' ),
			'map_meta_cap'        => false,
			'capabilities'        => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'delete_posts'       => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
				'create_posts'       => 'do_not_allow',
			),
		)
	);
}
add_action( 'init', '{{PREFIX}}_register_enquiry_type' );

/**
 * Where the visitor lands after sending: a "thank-you" page if it exists (good for
 * conversion tracking), else the contact page with ?enquiry=sent.
 *
 * @return string
 */
function {{PREFIX}}_enquiry_thank_you_url() {
	$page = get_page_by_path( 'thank-you', OBJECT, array( 'page' ) ); // An array, so a media file named "thank-you" never matches.
	$url  = $page ? get_permalink( $page ) : add_query_arg( 'enquiry', 'sent', home_url( '/contact/' ) );
	return (string) apply_filters( '{{PREFIX}}_enquiry_thank_you_url', $url );
}

/**
 * The consent sentence. The form shows it, and each enquiry stores it word for word with the
 * privacy notice URL (GDPR Art. 7: be able to show what the person agreed to).
 *
 * @return string
 */
function {{PREFIX}}_consent_text() {
	return __( 'I agree that you keep my details to answer this enquiry.', '{{SLUG}}-core' );
}

/**
 * Print the enquiry form. Templates call this; they never build their own form.
 *
 * @param array $args context (what the visitor was looking at, e.g. an offer slug).
 * @return void
 */
function {{PREFIX}}_render_enquiry_form( $args = array() ) {
	$args    = wp_parse_args( $args, array( 'context' => '' ) );
	$privacy = get_privacy_policy_url();
	?>
	<form class="enquiry-form" id="enquiry" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="{{PREFIX}}_enquiry">
		<?php wp_nonce_field( '{{PREFIX}}_enquiry', '{{PREFIX}}_enquiry_nonce' ); ?>
		<input type="hidden" name="context" value="<?php echo esc_attr( $args['context'] ); ?>">
		<input type="hidden" name="lang" value="<?php echo esc_attr( determine_locale() ); ?>">
		<p class="enquiry-form__trap" aria-hidden="true" style="position:absolute;left:-9999px">
			<label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
		</p>
		<p>
			<label for="{{PREFIX}}-name"><?php esc_html_e( 'Your name', '{{SLUG}}-core' ); ?></label>
			<input id="{{PREFIX}}-name" name="name" type="text" required maxlength="100" autocomplete="name">
		</p>
		<p>
			<label for="{{PREFIX}}-email"><?php esc_html_e( 'Email', '{{SLUG}}-core' ); ?></label>
			<input id="{{PREFIX}}-email" name="email" type="email" required maxlength="190" autocomplete="email">
		</p>
		<p>
			<label for="{{PREFIX}}-message"><?php esc_html_e( 'Message', '{{SLUG}}-core' ); ?></label>
			<textarea id="{{PREFIX}}-message" name="message" rows="6" maxlength="5000"></textarea>
		</p>
		<p>
			<label>
				<input type="checkbox" name="consent" value="1" required>
				<?php echo esc_html( {{PREFIX}}_consent_text() ); ?>
				<?php if ( $privacy ) : ?>
					<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy notice', '{{SLUG}}-core' ); ?></a>
				<?php endif; ?>
			</label>
		</p>
		<p><button type="submit"><?php esc_html_e( 'Send enquiry', '{{SLUG}}-core' ); ?></button></p>
	</form>
	<?php
}

/**
 * Print a status line after a redirect back (?enquiry=sent).
 *
 * @return void
 */
function {{PREFIX}}_render_enquiry_notice() {
	$state = isset( $_GET['enquiry'] ) ? sanitize_key( wp_unslash( $_GET['enquiry'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	if ( 'sent' === $state ) {
		printf( '<p class="enquiry-notice" role="status">%s</p>', esc_html__( 'Thank you. We have your message and will reply soon.', '{{SLUG}}-core' ) );
	}
}

/**
 * Handle a submitted enquiry.
 *
 * A failed nonce does NOT reject the enquiry: a cached contact page can carry an expired
 * nonce, and a 403 would drop a real customer's lead. The record is saved and flagged.
 *
 * @return void
 */
function {{PREFIX}}_handle_enquiry() {
	$thank_you = {{PREFIX}}_enquiry_thank_you_url();

	// 1. Honeypot: bots fill every field. Pretend success, keep nothing.
	if ( ! empty( $_POST['website'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- discarded, nothing processed.
		wp_safe_redirect( $thank_you );
		exit;
	}

	// 2. Rate limit per public IP and hour. Above the soft limit the lead is still saved but held
	// (not emailed), so people sharing one network (a hotel, an office) are never lost. Above the
	// hard limit it is a flood: dropped quietly. Private or proxy IPs are not limited (they would
	// lump every visitor together).
	$ip      = {{PREFIX}}_client_ip();
	$soft    = (int) apply_filters( '{{PREFIX}}_enquiry_rate_limit', {{PREFIX}}_is_production() ? 5 : 100 );
	$hard    = max( $soft, (int) apply_filters( '{{PREFIX}}_enquiry_rate_limit_hard', 30 ) );
	$limited = false;
	if ( {{PREFIX}}_is_public_ip( $ip ) ) {
		$rate_key = '{{PREFIX}}_enq_rl_' . md5( $ip );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= $hard ) {
			wp_safe_redirect( $thank_you );
			exit;
		}
		$limited = $count >= $soft;
		set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );
	}

	// 3. Nonce: recorded, not enforced (see the docblock).
	$nonce_ok = isset( $_POST['{{PREFIX}}_enquiry_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['{{PREFIX}}_enquiry_nonce'] ) ), '{{PREFIX}}_enquiry' );

	// 4. Read and clean the fields.
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- recorded in step 3.
	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$context = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '';
	$lang    = isset( $_POST['lang'] ) ? sanitize_text_field( wp_unslash( $_POST['lang'] ) ) : '';
	$consent = ! empty( $_POST['consent'] );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( '' === $name || ! is_email( $email ) || ! $consent ) {
		wp_die(
			esc_html__( 'Please give your name, a valid email address and your consent, then send the form again.', '{{SLUG}}-core' ),
			esc_html__( 'Missing information', '{{SLUG}}-core' ),
			array(
				'response'  => 400,
				'back_link' => true,
			)
		);
	}

	// 5. Save first: the lead is in our database before anything is forwarded.
	$id = wp_insert_post(
		array(
			'post_type'   => '{{PREFIX}}_enquiry',
			'post_status' => 'private',
			'post_title'  => sprintf( '%s (%s)', $name, gmdate( 'Y-m-d H:i' ) ),
			'meta_input'  => array(
				'_{{PREFIX}}_name'          => $name,
				'_{{PREFIX}}_email'         => $email,
				'_{{PREFIX}}_message'       => $message,
				'_{{PREFIX}}_context'       => $context,
				'_{{PREFIX}}_lang'          => $lang,
				'_{{PREFIX}}_ip_hash'       => '' !== $ip ? wp_hash( $ip ) : '',
				'_{{PREFIX}}_nonce_ok'      => $nonce_ok ? '1' : '0',
				'_{{PREFIX}}_consent_text'  => {{PREFIX}}_consent_text(),
				'_{{PREFIX}}_privacy_url'   => (string) get_privacy_policy_url(),
				'_{{PREFIX}}_rate_limited'  => $limited ? '1' : '0',
				'_{{PREFIX}}_mail_sent'     => '0',
				'_{{PREFIX}}_mail_attempts' => 0,
			),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		// Never pretend success when the lead is not stored.
		wp_die(
			esc_html__( 'Sorry, we could not save your message. Please email us directly.', '{{SLUG}}-core' ),
			esc_html__( 'Not sent', '{{SLUG}}-core' ),
			array(
				'response'  => 500,
				'back_link' => true,
			)
		);
	}

	/**
	 * Fires after an enquiry is stored. Forward to a CRM or chat app here, server-side.
	 *
	 * @param int $id Enquiry post ID.
	 */
	do_action( '{{PREFIX}}_enquiry_saved', $id );

	// 6. Email the inbox (unless held by the rate limit). A failed send is retried by cron.
	if ( ! $limited ) {
		{{PREFIX}}_forward_enquiry( $id );
	}

	wp_safe_redirect( $thank_you );
	exit;
}
add_action( 'admin_post_nopriv_{{PREFIX}}_enquiry', '{{PREFIX}}_handle_enquiry' );
add_action( 'admin_post_{{PREFIX}}_enquiry', '{{PREFIX}}_handle_enquiry' );

/**
 * Email one stored enquiry to the inbox and record the result.
 *
 * @param int $id Enquiry post ID.
 * @return bool Whether the mail server accepted it.
 */
function {{PREFIX}}_forward_enquiry( $id ) {
	$name  = (string) get_post_meta( $id, '_{{PREFIX}}_name', true );
	$email = (string) get_post_meta( $id, '_{{PREFIX}}_email', true );
	$to    = defined( '{{PREFIX_UC}}_ENQUIRY_TO' ) && is_email( constant( '{{PREFIX_UC}}_ENQUIRY_TO' ) ) ? constant( '{{PREFIX_UC}}_ENQUIRY_TO' ) : get_option( 'admin_email' );
	$lines = array(
		/* translators: %s: customer name. */
		sprintf( __( 'Name: %s', '{{SLUG}}-core' ), $name ),
		/* translators: %s: customer email. */
		sprintf( __( 'Email: %s', '{{SLUG}}-core' ), $email ),
		/* translators: %s: page or offer the visitor came from. */
		sprintf( __( 'From: %s', '{{SLUG}}-core' ), (string) get_post_meta( $id, '_{{PREFIX}}_context', true ) ),
		/* translators: %s: visitor's language code. */
		sprintf( __( 'Language: %s', '{{SLUG}}-core' ), (string) get_post_meta( $id, '_{{PREFIX}}_lang', true ) ),
		'',
		(string) get_post_meta( $id, '_{{PREFIX}}_message', true ),
		'',
		/* translators: %d: enquiry ID. */
		sprintf( __( 'Stored safely as enquiry #%d.', '{{SLUG}}-core' ), $id ),
	);
	/* translators: %s: customer name. */
	$subject = sprintf( __( 'New enquiry: %s', '{{SLUG}}-core' ), $name );
	$sent    = wp_mail( $to, $subject, implode( "\n", $lines ), array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email ) );

	update_post_meta( $id, '_{{PREFIX}}_mail_attempts', (int) get_post_meta( $id, '_{{PREFIX}}_mail_attempts', true ) + 1 );
	update_post_meta( $id, '_{{PREFIX}}_mail_sent', $sent ? '1' : '0' );
	return $sent;
}

/**
 * Hourly: retry enquiries whose email failed (up to 3 attempts each).
 *
 * @return void
 */
function {{PREFIX}}_retry_enquiry_mail() {
	$ids = get_posts(
		array(
			'post_type'      => '{{PREFIX}}_enquiry',
			'post_status'    => 'private',
			'fields'         => 'ids',
			'posts_per_page' => 20,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small private table, hourly.
				'relation' => 'AND',
				array(
					'key'   => '_{{PREFIX}}_mail_sent',
					'value' => '0',
				),
				array(
					'key'     => '_{{PREFIX}}_mail_attempts',
					'value'   => 3,
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => '_{{PREFIX}}_rate_limited',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		)
	);
	foreach ( $ids as $id ) {
		{{PREFIX}}_forward_enquiry( $id );
	}
}
add_action( '{{PREFIX}}_retry_enquiry_mail', '{{PREFIX}}_retry_enquiry_mail' );

/**
 * Daily: delete enquiries older than the retention period (GDPR: keep only as long as needed).
 *
 * @return void
 */
function {{PREFIX}}_purge_old_enquiries() {
	$months = defined( '{{PREFIX_UC}}_ENQUIRY_RETENTION_MONTHS' ) ? max( 1, (int) constant( '{{PREFIX_UC}}_ENQUIRY_RETENTION_MONTHS' ) ) : 24;
	$old    = get_posts(
		array(
			'post_type'      => '{{PREFIX}}_enquiry',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded daily batch.
			'date_query'     => array( array( 'before' => $months . ' months ago' ) ),
		)
	);
	foreach ( $old as $id ) {
		wp_delete_post( $id, true );
	}
}
add_action( '{{PREFIX}}_purge_old_enquiries', '{{PREFIX}}_purge_old_enquiries' );

/**
 * Schedule the retry and purge jobs once (must-use plugins have no activation hook).
 * Not during `wp core install`: the options table may not exist yet, and the next request schedules them.
 *
 * @return void
 */
function {{PREFIX}}_schedule_enquiry_jobs() {
	if ( wp_installing() ) {
		return;
	}
	if ( ! wp_next_scheduled( '{{PREFIX}}_retry_enquiry_mail' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', '{{PREFIX}}_retry_enquiry_mail' );
	}
	if ( ! wp_next_scheduled( '{{PREFIX}}_purge_old_enquiries' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', '{{PREFIX}}_purge_old_enquiries' );
	}
}
add_action( 'init', '{{PREFIX}}_schedule_enquiry_jobs' );

/**
 * Sender address: the site's own domain, so its SPF and DKIM apply.
 *
 * The default wordpress@<host> on a subdomain has no SPF, and Gmail rejects it (550 5.7.26).
 *
 * @return string
 */
function {{PREFIX}}_mail_from() {
	return defined( '{{PREFIX_UC}}_MAIL_FROM' ) && is_email( constant( '{{PREFIX_UC}}_MAIL_FROM' ) ) ? constant( '{{PREFIX_UC}}_MAIL_FROM' ) : 'noreply@{{DOMAIN}}';
}
add_filter( 'wp_mail_from', '{{PREFIX}}_mail_from' );

/**
 * Sender name: the site's name, decoded (so "&" isn't sent as "&amp;").
 *
 * @return string
 */
function {{PREFIX}}_mail_from_name() {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}
add_filter( 'wp_mail_from_name', '{{PREFIX}}_mail_from_name' );

/**
 * Align the envelope sender (Return-Path) with From, so SPF is checked against our domain.
 *
 * Only for PHP's mail() transport and only when nothing set a sender: an SMTP plugin or
 * provider manages its own envelope sender, and overriding it breaks their authentication.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
 * @return void
 */
function {{PREFIX}}_align_envelope_sender( $phpmailer ) {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property names.
	if ( 'mail' === $phpmailer->Mailer && empty( $phpmailer->Sender ) ) {
		$phpmailer->Sender = $phpmailer->From;
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
}
add_action( 'phpmailer_init', '{{PREFIX}}_align_envelope_sender' );

/**
 * Show the stored fields on the enquiry screen.
 *
 * @return void
 */
function {{PREFIX}}_enquiry_meta_box() {
	add_meta_box( '{{PREFIX}}-enquiry-details', __( 'Enquiry', '{{SLUG}}-core' ), '{{PREFIX}}_render_enquiry_details', '{{PREFIX}}_enquiry', 'normal', 'high' );
}
add_action( 'add_meta_boxes_{{PREFIX}}_enquiry', '{{PREFIX}}_enquiry_meta_box' );

/**
 * Print the details table.
 *
 * @param WP_Post $post Enquiry.
 * @return void
 */
function {{PREFIX}}_render_enquiry_details( $post ) {
	$fields = array(
		'name'          => __( 'Name', '{{SLUG}}-core' ),
		'email'         => __( 'Email', '{{SLUG}}-core' ),
		'context'       => __( 'From page', '{{SLUG}}-core' ),
		'lang'          => __( 'Language', '{{SLUG}}-core' ),
		'message'       => __( 'Message', '{{SLUG}}-core' ),
		'mail_sent'     => __( 'Email sent (1 = yes)', '{{SLUG}}-core' ),
		'mail_attempts' => __( 'Email attempts', '{{SLUG}}-core' ),
		'rate_limited'  => __( 'Held by the rate limit, not emailed (1 = yes)', '{{SLUG}}-core' ),
		'nonce_ok'      => __( 'Form token valid (0 = old cached page or a bot)', '{{SLUG}}-core' ),
		'consent_text'  => __( 'Consent given to', '{{SLUG}}-core' ),
		'privacy_url'   => __( 'Privacy notice at the time', '{{SLUG}}-core' ),
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $fields as $key => $label ) {
		printf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $label ),
			nl2br( esc_html( (string) get_post_meta( $post->ID, '_{{PREFIX}}_' . $key, true ) ) )
		);
	}
	echo '</tbody></table>';
}

/**
 * Columns on the Enquiries list, so the owner sees who and whether it was emailed at a glance.
 *
 * @param array $columns List columns.
 * @return array
 */
function {{PREFIX}}_enquiry_columns( $columns ) {
	$date = isset( $columns['date'] ) ? $columns['date'] : __( 'Date', '{{SLUG}}-core' );
	unset( $columns['date'] );
	$columns['{{PREFIX}}_email']   = __( 'Email', '{{SLUG}}-core' );
	$columns['{{PREFIX}}_context'] = __( 'From page', '{{SLUG}}-core' );
	$columns['{{PREFIX}}_status']  = __( 'Emailed', '{{SLUG}}-core' );
	$columns['date']         = $date;
	return $columns;
}
add_filter( 'manage_{{PREFIX}}_enquiry_posts_columns', '{{PREFIX}}_enquiry_columns' );

/**
 * Fill the extra Enquiries columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Enquiry ID.
 * @return void
 */
function {{PREFIX}}_enquiry_column_value( $column, $post_id ) {
	if ( '{{PREFIX}}_email' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_{{PREFIX}}_email', true ) );
	} elseif ( '{{PREFIX}}_context' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_{{PREFIX}}_context', true ) );
	} elseif ( '{{PREFIX}}_status' === $column ) {
		if ( '1' === get_post_meta( $post_id, '_{{PREFIX}}_mail_sent', true ) ) {
			esc_html_e( 'Yes', '{{SLUG}}-core' );
		} elseif ( '1' === get_post_meta( $post_id, '_{{PREFIX}}_rate_limited', true ) ) {
			esc_html_e( 'Held (rate limit): read it here', '{{SLUG}}-core' );
		} else {
			esc_html_e( 'NOT YET: read it here', '{{SLUG}}-core' );
		}
	}
}
add_action( 'manage_{{PREFIX}}_enquiry_posts_custom_column', '{{PREFIX}}_enquiry_column_value', 10, 2 );

/**
 * Warn admins in wp-admin when enquiries were not emailed (failed 3 times, or held).
 *
 * @return void
 */
function {{PREFIX}}_unsent_enquiries_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$count = get_transient( '{{PREFIX}}_unsent_enquiries' );
	if ( false === $count ) {
		$ids   = get_posts(
			array(
				'post_type'      => '{{PREFIX}}_enquiry',
				'post_status'    => 'private',
				'fields'         => 'ids',
				'posts_per_page' => 100,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small private table, cached 10 minutes.
					'relation' => 'AND',
					array(
						'key'   => '_{{PREFIX}}_mail_sent',
						'value' => '0',
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => '_{{PREFIX}}_rate_limited',
							'value' => '1',
						),
						array(
							'key'     => '_{{PREFIX}}_mail_attempts',
							'value'   => 3,
							'compare' => '>=',
							'type'    => 'NUMERIC',
						),
					),
				),
			)
		);
		$count = count( $ids );
		set_transient( '{{PREFIX}}_unsent_enquiries', $count, 10 * MINUTE_IN_SECONDS );
	}
	if ( (int) $count > 0 ) {
		printf(
			'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
			/* translators: %d: number of enquiries. */
			esc_html( sprintf( _n( '%d enquiry was not emailed to you.', '%d enquiries were not emailed to you.', (int) $count, '{{SLUG}}-core' ), (int) $count ) ),
			esc_url( admin_url( 'edit.php?post_type={{PREFIX}}_enquiry' ) ),
			esc_html__( 'Read them in Enquiries', '{{SLUG}}-core' )
		);
	}
}
add_action( 'admin_notices', '{{PREFIX}}_unsent_enquiries_notice' );

/**
 * GDPR: let Tools > Export Personal Data find enquiries by email.
 *
 * @param array $exporters Registered exporters.
 * @return array
 */
function {{PREFIX}}_register_privacy_exporter( $exporters ) {
	$exporters['{{PREFIX}}-enquiries'] = array(
		'exporter_friendly_name' => __( 'Enquiries', '{{SLUG}}-core' ),
		'callback'               => '{{PREFIX}}_export_enquiries',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', '{{PREFIX}}_register_privacy_exporter' );

/**
 * GDPR: let Tools > Erase Personal Data delete enquiries by email.
 *
 * @param array $erasers Registered erasers.
 * @return array
 */
function {{PREFIX}}_register_privacy_eraser( $erasers ) {
	$erasers['{{PREFIX}}-enquiries'] = array(
		'eraser_friendly_name' => __( 'Enquiries', '{{SLUG}}-core' ),
		'callback'             => '{{PREFIX}}_erase_enquiries',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', '{{PREFIX}}_register_privacy_eraser' );

/**
 * Enquiry IDs for one email address.
 *
 * @param string $email Email address.
 * @return int[]
 */
function {{PREFIX}}_enquiries_for_email( $email ) {
	return get_posts(
		array(
			'post_type'      => '{{PREFIX}}_enquiry',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 100,
			'meta_key'       => '_{{PREFIX}}_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- rare admin request.
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- rare admin request.
		)
	);
}

/**
 * Personal data exporter.
 *
 * @param string $email Email address.
 * @return array
 */
function {{PREFIX}}_export_enquiries( $email ) {
	$data = array();
	foreach ( {{PREFIX}}_enquiries_for_email( $email ) as $id ) {
		$data[] = array(
			'group_id'    => '{{PREFIX}}-enquiries',
			'group_label' => __( 'Enquiries', '{{SLUG}}-core' ),
			'item_id'     => 'enquiry-' . $id,
			'data'        => array(
				array(
					'name'  => __( 'Date', '{{SLUG}}-core' ),
					'value' => get_the_date( 'Y-m-d H:i', $id ),
				),
				array(
					'name'  => __( 'Name', '{{SLUG}}-core' ),
					'value' => (string) get_post_meta( $id, '_{{PREFIX}}_name', true ),
				),
				array(
					'name'  => __( 'Message', '{{SLUG}}-core' ),
					'value' => (string) get_post_meta( $id, '_{{PREFIX}}_message', true ),
				),
			),
		);
	}
	return array(
		'data' => $data,
		'done' => true,
	);
}

/**
 * Personal data eraser: deletes that person's enquiries.
 *
 * @param string $email Email address.
 * @return array
 */
function {{PREFIX}}_erase_enquiries( $email ) {
	$ids = {{PREFIX}}_enquiries_for_email( $email );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	return array(
		'items_removed'  => count( $ids ),
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}
