=== DocsBot ===
Contributors: uglyrobot
Tags: artificial intelligence, chatbot, customer support, documentation, live chat
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add your DocsBot support agent to WordPress, customize the widget, and control exactly where and for whom it appears.

== Description ==

DocsBot for WordPress gives site administrators a secure, native way to connect a DocsBot account and deploy an AI support widget.

Connect your DocsBot API key, choose a team and bot you can access, then configure the most useful widget content, design, and action settings without leaving WordPress. The Deploy tab controls page paths, logged-in users, WordPress roles, popular membership systems, optional conversation identity, and private-bot signing.

= Built for real WordPress sites =

* Choose from every team and bot available to your DocsBot user.
* Configure the greeting, labels, support link, branding, color, icons, and placement.
* Enable feedback, escalation, web search, booking, and custom button integrations already configured for your bot.
* Include or exclude literal URL path prefixes.
* Restrict chat by login, WordPress role, or a supported membership plugin.
* Share display name, email, and a pseudonymous site user ID through separate opt-in controls.
* Use private DocsBot bots with short-lived server-signed JWTs.
* Require server-verified login, role, or membership rules when chat access must be protected.
* Keep identity and signatures out of full-page caches.
* Avoid dashboard clutter: the plugin shows no sitewide notices or nags.

= Supported membership integrations =

The plugin uses public APIs from WooCommerce Memberships, MemberPress, Paid Memberships Pro, Restrict Content Pro, and WP-Members. Ultimate Member is supported through approved WordPress roles. The selected integration fails closed if it becomes unavailable.

= Privacy and security =

Identity sharing is off by default. If enabled, the selected fields are sent to DocsBot only after the visitor passes the configured access rules. The plugin never sends membership, role, password, payment, or subscription data.

The DocsBot API key remains on the WordPress server. Private-bot signature keys are used only to create short-lived tokens. Both credentials are encrypted before database storage, or may be supplied through `wp-config.php` constants.

= External service =

This plugin connects to DocsBot, a third-party service:

* `https://docsbot.ai/api` is called from the WordPress server when an administrator connects an account, lists teams or bots, reads settings, or saves supported bot settings.
* `https://widget.docsbot.ai/chat.js` is loaded for eligible visitors when the widget is enabled.
* Visitor chat messages and any administrator-enabled identity fields are processed by DocsBot.

Use of DocsBot is subject to the [DocsBot Privacy Policy](https://docsbot.ai/privacy) and [Terms of Service](https://docsbot.ai/terms). Learn more in the [DocsBot widget documentation](https://docsbot.ai/documentation/developer/embeddable-chat-widget).

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` or install the ZIP from **Plugins > Add New Plugin > Upload Plugin**.
2. Activate **DocsBot**.
3. Open **DocsBot > Connection**.
4. Get your user API key from the [DocsBot API Keys page](https://docsbot.ai/app/api), paste it into WordPress, and connect.
5. Choose a team, save, then choose a bot.
6. Configure Content, Design, and Actions.
7. Open Deploy, choose your audience and pages, then enable the widget.

For a private bot, copy its signature key from the bot's Widget Embed page and save it under Deploy before enabling the widget.

== Frequently Asked Questions ==

= Does the plugin expose my DocsBot API key? =

No. Admin API requests are made by WordPress on the server. The key is encrypted before database storage and is never included in frontend HTML or JavaScript.

= How are private bots supported? =

WordPress creates a short-lived HS256 JWT using the bot signature key. Access rules run first, and the token is returned through a private, no-store response. The signature key never leaves the server.

= Does the plugin send WordPress user data automatically? =

No. Display name, email address, and a pseudonymous site user ID are three separate settings and all are off by default.

= What happens if my membership plugin is disabled? =

The restriction fails closed. The widget stays hidden until the selected integration is available again or the restriction is changed.

= Can I show the widget only in my documentation or account area? =

Yes. Add one literal path prefix per line, such as `/docs/` or `/account/`. Excluded paths always take precedence.

Path rules control where the embed is placed; they are not an authorization boundary. For protected chat, also enable a logged-in, WordPress role, or membership restriction under Audience. Those rules are verified by WordPress before identity or a private-bot token is returned.

= Why can I see a create-bot link even if I cannot create bots? =

DocsBot does not expose a public create-permission flag. When a selected team has no bots, the plugin links to the DocsBot bot page, where your current role and plan are enforced.

== Screenshots ==

1. Connect a DocsBot API key and select an accessible team and bot.
2. Edit the bot's conversation content and labels.
3. Match the chat widget to your site design.
4. Enable supported feedback, escalation, search, booking, and custom actions.
5. Deploy by URL path, user role, membership, metadata, and private signing.

== Changelog ==

= 1.0.1 =
* Updated product naming to DocsBot.

= 1.0.0 =
* Initial public release.
