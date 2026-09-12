<?php
/**
 * Appearance view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

$s = function ( $key, $default = '' ) {
	return \aiwc_get_setting( $key, $default );
};
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Appearance', 'ai-website-chatbot' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'aiwc_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Position', 'ai-website-chatbot' ); ?></th>
				<td>
					<select name="aiwc_settings[appearance][position]">
						<option value="bottom-right" <?php selected( $s( 'appearance.position' ), 'bottom-right' ); ?>><?php esc_html_e( 'Bottom right', 'ai-website-chatbot' ); ?></option>
						<option value="bottom-left" <?php selected( $s( 'appearance.position' ), 'bottom-left' ); ?>><?php esc_html_e( 'Bottom left', 'ai-website-chatbot' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_color_primary"><?php esc_html_e( 'Primary color', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="text" class="aiwc-color" id="aiwc_color_primary" name="aiwc_settings[appearance][primary_color]" value="<?php echo esc_attr( $s( 'appearance.primary_color', '#2563eb' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_color_button"><?php esc_html_e( 'Chat button color', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="text" class="aiwc-color" id="aiwc_color_button" name="aiwc_settings[appearance][button_color]" value="<?php echo esc_attr( $s( 'appearance.button_color', '#2563eb' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_color_text"><?php esc_html_e( 'Button text color', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="text" class="aiwc-color" id="aiwc_color_text" name="aiwc_settings[appearance][text_color]" value="<?php echo esc_attr( $s( 'appearance.text_color', '#ffffff' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_width"><?php esc_html_e( 'Window width (px)', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_width" name="aiwc_settings[appearance][width]" value="<?php echo (int) $s( 'appearance.width', 380 ); ?>" min="300" max="600" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_height"><?php esc_html_e( 'Window height (px)', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_height" name="aiwc_settings[appearance][height]" value="<?php echo (int) $s( 'appearance.height', 560 ); ?>" min="300" max="900" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_radius"><?php esc_html_e( 'Border radius (px)', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_radius" name="aiwc_settings[appearance][border_radius]" value="<?php echo (int) $s( 'appearance.border_radius', 12 ); ?>" min="0" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_custom_css"><?php esc_html_e( 'Custom CSS', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text code" id="aiwc_custom_css" name="aiwc_settings[appearance][custom_css]" rows="6" placeholder=".aiwc-window { }"><?php echo esc_textarea( $s( 'appearance.custom_css', '' ) ); ?></textarea></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Human handoff', 'ai-website-chatbot' ); ?></h2>
		<p class="description"><?php esc_html_e( 'When the assistant cannot help, visitors can be offered a way to reach a human.', 'ai-website-chatbot' ); ?></p>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable human handoff', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[handoff][enabled]" value="0" /><label><input type="checkbox" name="aiwc_settings[handoff][enabled]" value="1" <?php checked( (bool) $s( 'handoff.enabled', true ) ); ?> /></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_ho_phone"><?php esc_html_e( 'Support phone', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_ho_phone" name="aiwc_settings[handoff][support_phone]" value="<?php echo esc_attr( $s( 'handoff.support_phone', '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_ho_email"><?php esc_html_e( 'Support email', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_ho_email" type="email" name="aiwc_settings[handoff][support_email]" value="<?php echo esc_attr( $s( 'handoff.support_email', '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_ho_wa"><?php esc_html_e( 'WhatsApp link', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_ho_wa" type="url" name="aiwc_settings[handoff][whatsapp_link]" value="<?php echo esc_attr( $s( 'handoff.whatsapp_link', '' ) ); ?>" placeholder="https://wa.me/…" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show contact form', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[handoff][contact_form_enabled]" value="0" /><label><input type="checkbox" name="aiwc_settings[handoff][contact_form_enabled]" value="1" <?php checked( (bool) $s( 'handoff.contact_form_enabled', true ) ); ?> /> <?php esc_html_e( 'Allow visitors to leave a message via the chatbot.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Lead notifications', 'ai-website-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email notification for new leads', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[notifications][enabled]" value="0" /><label><input type="checkbox" name="aiwc_settings[notifications][enabled]" value="1" <?php checked( (bool) $s( 'notifications.enabled', false ) ); ?> /></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_note_email"><?php esc_html_e( 'Notification email', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_note_email" type="email" name="aiwc_settings[notifications][email]" value="<?php echo esc_attr( $s( 'notifications.email', get_option( 'admin_email' ) ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_note_subject"><?php esc_html_e( 'Subject', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_note_subject" name="aiwc_settings[notifications][subject]" value="<?php echo esc_attr( $s( 'notifications.subject', __( 'New lead from AI Chatbot', 'ai-website-chatbot' ) ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_note_template"><?php esc_html_e( 'Email template', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_note_template" name="aiwc_settings[notifications][template]" rows="4"><?php echo esc_textarea( $s( 'notifications.template', '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Available placeholders: {name}, {email}, {phone}, {message}', 'ai-website-chatbot' ); ?></p></td>
			</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>
</div>