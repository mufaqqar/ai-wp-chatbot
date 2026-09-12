<?php
/**
 * Dashboard view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

use AIWebsiteChatbot\Analytics;

$stats = Analytics::stats();
$configured = \AIWebsiteChatbot\Settings::is_ai_configured();
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'AI Website Chatbot', 'ai-website-chatbot' ); ?></h1>
	<p class="aiwc-subtitle">
		<?php esc_html_e( 'AI-powered website chatbot', 'ai-website-chatbot' ); ?>
		&middot; <?php echo esc_html( AIWC_VERSION ); ?>
		&middot; DB <?php echo esc_html( get_option( 'aiwc_db_version', '' ) ); ?>
	</p>

	<?php if ( ! $configured ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php echo wp_kses_post( __( 'Configure your <a href="' . esc_url( admin_url( 'admin.php?page=aiwc-ai' ) ) . '">AI provider</a> and <a href="' . esc_url( admin_url( 'admin.php?page=aiwc-knowledge' ) ) . '">knowledge base</a> to activate AI-powered responses.', 'ai-website-chatbot' ) ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-success">
			<p><?php esc_html_e( 'Plugin active and AI configured.', 'ai-website-chatbot' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="aiwc-cards">
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['total_conversations']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Total conversations', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['conversations_today']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Today', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['conversations_week']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'This week', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['conversations_month']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'This month', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['total_messages']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Total messages', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['leads']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Leads generated', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['answered']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Questions answered', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['failed']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Questions not answered', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo esc_html( $stats['avg_length'] ); ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Avg conversation length', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo esc_html( $stats['avg_response_time'] ); ?>s</span><span class="aiwc-card-label"><?php esc_html_e( 'Avg response time', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value"><?php echo (int) $stats['tokens']; ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Estimated AI usage (tokens)', 'ai-website-chatbot' ); ?></span></div>
		<div class="aiwc-card"><span class="aiwc-card-value">$<?php echo esc_html( number_format( (float) $stats['estimated_cost'], 4 ) ); ?></span><span class="aiwc-card-label"><?php esc_html_e( 'Estimated AI cost', 'ai-website-chatbot' ); ?></span></div>
	</div>
</div>