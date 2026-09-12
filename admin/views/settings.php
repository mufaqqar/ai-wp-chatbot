<?php
/**
 * General settings view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

$s = function ( $key, $default = '' ) {
	return \aiwc_get_setting( $key, $default );
};
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'AI Chatbot Settings', 'ai-website-chatbot' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'aiwc_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Chatbot enabled', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[general][enabled]" value="0" /><label><input type="checkbox" name="aiwc_settings[general][enabled]" value="1" <?php checked( (bool) $s( 'general.enabled', true ) ); ?> /> <?php esc_html_e( 'Show the chatbot widget on the website.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_bot_name"><?php esc_html_e( 'Bot name', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_bot_name" name="aiwc_settings[general][bot_name]" value="<?php echo esc_attr( $s( 'general.bot_name', 'AI Assistant' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_welcome"><?php esc_html_e( 'Welcome message', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_welcome" name="aiwc_settings[general][welcome_message]" value="<?php echo esc_attr( $s( 'general.welcome_message', '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_fallback"><?php esc_html_e( 'Fallback message', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_fallback" name="aiwc_settings[general][fallback_message]" rows="3"><?php echo esc_textarea( $s( 'general.fallback_message', '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Shown when the assistant cannot find an answer.', 'ai-website-chatbot' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_language"><?php esc_html_e( 'Default language', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<select id="aiwc_language" name="aiwc_settings[general][default_language]">
						<option value="en" <?php selected( $s( 'general.default_language' ), 'en' ); ?>><?php esc_html_e( 'English', 'ai-website-chatbot' ); ?></option>
						<option value="ur" <?php selected( $s( 'general.default_language' ), 'ur' ); ?>><?php esc_html_e( 'Urdu', 'ai-website-chatbot' ); ?></option>
						<option value="ar" <?php selected( $s( 'general.default_language' ), 'ar' ); ?>><?php esc_html_e( 'Arabic', 'ai-website-chatbot' ); ?></option>
						<option value="fr" <?php selected( $s( 'general.default_language' ), 'fr' ); ?>><?php esc_html_e( 'French', 'ai-website-chatbot' ); ?></option>
						<option value="de" <?php selected( $s( 'general.default_language' ), 'de' ); ?>><?php esc_html_e( 'German', 'ai-website-chatbot' ); ?></option>
						<option value="es" <?php selected( $s( 'general.default_language' ), 'es' ); ?>><?php esc_html_e( 'Spanish', 'ai-website-chatbot' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Collect leads', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[general][collect_leads]" value="0" /><label><input type="checkbox" name="aiwc_settings[general][collect_leads]" value="1" <?php checked( (bool) $s( 'general.collect_leads', true ) ); ?> /> <?php esc_html_e( 'Allow visitors to leave their name, email and phone.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_lead_prompt"><?php esc_html_e( 'Lead prompt message', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_lead_prompt" name="aiwc_settings[general][lead_prompt]" value="<?php echo esc_attr( $s( 'general.lead_prompt', '' ) ); ?>" /></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Business information', 'ai-website-chatbot' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Used to build the assistant system prompt.', 'ai-website-chatbot' ); ?></p>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_biz_name"><?php esc_html_e( 'Business name', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_biz_name" name="aiwc_settings[business][business_name]" value="<?php echo esc_attr( $s( 'business.business_name', get_bloginfo( 'name' ) ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_biz_desc"><?php esc_html_e( 'Business description', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_biz_desc" name="aiwc_settings[business][business_description]" rows="3"><?php echo esc_textarea( $s( 'business.business_description', '' ) ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_biz_addr"><?php esc_html_e( 'Address', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_biz_addr" name="aiwc_settings[business][business_address]" rows="2"><?php echo esc_textarea( $s( 'business.business_address', '' ) ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_biz_phone"><?php esc_html_e( 'Phone', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_biz_phone" name="aiwc_settings[business][phone]" value="<?php echo esc_attr( $s( 'business.phone', '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_biz_email"><?php esc_html_e( 'Email', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_biz_email" type="email" name="aiwc_settings[business][email]" value="<?php echo esc_attr( $s( 'business.email', '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_biz_url"><?php esc_html_e( 'Website URL', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_biz_url" type="url" name="aiwc_settings[business][website_url]" value="<?php echo esc_attr( $s( 'business.website_url', home_url( '/' ) ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_biz_hours"><?php esc_html_e( 'Business hours', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_biz_hours" name="aiwc_settings[business][business_hours]" rows="2"><?php echo esc_textarea( $s( 'business.business_hours', '' ) ); ?></textarea></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Instructions', 'ai-website-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_instructions"><?php esc_html_e( 'Custom instructions', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_instructions" name="aiwc_settings[instructions][custom_instructions]" rows="4"><?php echo esc_textarea( $s( 'instructions.custom_instructions', '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Appended to the system prompt.', 'ai-website-chatbot' ); ?></p></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Privacy', 'ai-website-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Store conversation history', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[privacy][store_conversations]" value="0" /><label><input type="checkbox" name="aiwc_settings[privacy][store_conversations]" value="1" <?php checked( (bool) $s( 'privacy.store_conversations', true ) ); ?> /> <?php esc_html_e( 'Persist conversations and messages in the database.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_retention"><?php esc_html_e( 'Data retention (days)', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_retention" name="aiwc_settings[privacy][retention_days]" value="<?php echo (int) $s( 'privacy.retention_days', 0 ); ?>" min="0" /> <span class="description"><?php esc_html_e( '0 keeps conversations forever. Conversations (and related leads) older than this are deleted hourly.', 'ai-website-chatbot' ); ?></span></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[privacy][delete_on_uninstall]" value="0" /><label><input type="checkbox" name="aiwc_settings[privacy][delete_on_uninstall]" value="1" <?php checked( (bool) $s( 'privacy.delete_on_uninstall', false ) ); ?> /> <?php esc_html_e( 'Warning: removes all tables and options when the plugin is uninstalled.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Rate limits', 'ai-website-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_rl_min"><?php esc_html_e( 'Messages per minute', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_rl_min" name="aiwc_settings[rate_limits][messages_per_minute]" value="<?php echo (int) $s( 'rate_limits.messages_per_minute', 10 ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_rl_hour"><?php esc_html_e( 'Messages per hour', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_rl_hour" name="aiwc_settings[rate_limits][messages_per_hour]" value="<?php echo (int) $s( 'rate_limits.messages_per_hour', 100 ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_rl_len"><?php esc_html_e( 'Maximum message length', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_rl_len" name="aiwc_settings[rate_limits][max_message_length]" value="<?php echo (int) $s( 'rate_limits.max_message_length', 1000 ); ?>" min="10" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_rl_hist"><?php esc_html_e( 'Max conversation history messages', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_rl_hist" name="aiwc_settings[rate_limits][max_history_messages]" value="<?php echo (int) $s( 'rate_limits.max_history_messages', 12 ); ?>" min="2" max="50" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_rl_day"><?php esc_html_e( 'Daily message limit', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_rl_day" name="aiwc_settings[rate_limits][daily_message_limit]" value="<?php echo (int) $s( 'rate_limits.daily_message_limit', 500 ); ?>" min="1" /></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Logging', 'ai-website-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Debug logging', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[logging][debug_logging]" value="0" /><label><input type="checkbox" name="aiwc_settings[logging][debug_logging]" value="1" <?php checked( (bool) $s( 'logging.debug_logging', false ) ); ?> /> <?php esc_html_e( 'Record plugin events in the log table.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>
</div>