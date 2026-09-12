=== AI Website Chatbot ===
Contributors: aiwebsitechatbot
Tags: chatbot, ai, openai, chat, support, wooCommerce, knowledge base
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An AI-powered website chatbot that answers questions from your own content, FAQs and WooCommerce store.

== Description ==

AI Website Chatbot adds a modern, responsive AI chat assistant to your WordPress website. It answers visitor questions using your own indexed content, FAQs and (optionally) your WooCommerce products.

= Features =

* Floating chat widget (bottom-right / bottom-left), fully responsive
* Website knowledge base with automatic and manual indexing of pages, posts, custom post types and products
* FAQ manager with high-priority retrieval
* WooCommerce product answers: prices, stock, categories, links
* Lead generation with name, email and phone capture plus CSV export
* Conversation history storage with retention controls
* Configurable AI system prompt, fallback messages and business information
* Multiple language responses with a configurable default language
* Source links shown when answers come from website content
* Human handoff (phone, email, WhatsApp, contact form)
* Basic analytics: conversations, messages, popular and unanswered questions
* Rate limiting and abuse protection
* Privacy controls: disable storage, retention period, delete on uninstall
* Extensible provider architecture (OpenAI included) for future providers and SaaS

= Requirements =

* WordPress 6.4+ (PHP 8.1+)
* Openai API key with credits
* WooCommerce is optional

= Shortcode =

`[ai_chatbot]` renders the widget, or use `do_action( 'ai_chatbot_render' );` in a template.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP via Plugins → Add New.
2. Activate "AI Website Chatbot".
3. Open the new "AI Chatbot" menu in the WordPress admin.
4. Add your OpenAI API key on the AI Configuration page.
5. Go to Knowledge Base and click "Index Website".
6. Optionally enable the WooCommerce integration and add FAQs.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No. WooCommerce integration is optional and is disabled automatically when WooCommerce is not installed.

= Is my API key exposed? =

No. The API key is stored in a separate option, never printed into HTML, never passed through REST responses and never loaded into frontend JavaScript.

= Does the plugin store visitor IPs? =

No. Rate limiting uses expiring, hashed visitor fingerprints. Raw IP addresses are never stored.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
* Initial release.