=== DocsBot ===
Contributors: uglyrobot
Tags: artificial intelligence, chatbot, customer support, documentation, knowledge base
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Give WordPress visitors instant answers with DocsBot. Match your brand and control exactly where and for whom the widget appears.

== Description ==

Put the DocsBot support agent trained on your content where visitors need it. No embed code to paste. No theme file to edit.

Connect your account, choose a bot, and manage the widget experience from **Settings > DocsBot**. Update the welcome message, match your site's design, turn on actions, preview changes live, and decide which pages and visitors get chat.

This plugin requires a DocsBot account and API key. Already using DocsBot? Connect in a few steps. New to DocsBot? [Create your free account](https://docsbot.ai/register?utm_source=wordpress.org&utm_medium=plugin&utm_campaign=docsbot_wordpress&utm_content=description_signup).

= Put useful answers where visitors need them =

* Answer common questions with the DocsBot agent trained on your content.
* Match the launcher, chat window, colors, greeting, labels, logo, and avatar to your site.
* Let visitors upload images or ask questions by voice when you enable those options.
* Add feedback, human handoff, web search, booking, custom buttons, and advanced integrations through DocsBot Skills and MCP servers.
* Preview the real widget as you work, then enable it when it looks right.

= Show the right widget to the right audience =

* Include or exclude page paths, from an entire documentation section to a single account area.
* Show chat only to logged-in visitors, selected WordPress roles, or eligible members.
* Support private DocsBot bots without exposing signing credentials in the browser.
* Choose whether to share a visitor's display name, email, or pseudonymous site user ID with the conversation.

= Supported membership integrations =

Use WooCommerce Memberships, MemberPress, Paid Memberships Pro, Restrict Content Pro, WP-Members, or approved Ultimate Member roles to control who sees the widget. If the selected membership integration becomes unavailable, the widget stays hidden.

= Made for WordPress =

Choose custom launcher icons, bot avatars, and header logos from the WordPress Media Library. The plugin keeps every control under **Settings > DocsBot** and adds no sitewide notices or nags.

= Privacy and security =

Public conversation identity is off by default. If you enable it, the plugin sends the visitor's display name, email address, and/or pseudonymous site user ID only after the visitor passes your access rules. It never sends membership, role, password, payment, or subscription data.

Private bots have one exception: a signed request for a logged-in visitor automatically includes the numeric WordPress user ID as private `priv_user_id` metadata. Guest requests do not include a user ID. DocsBot keeps `priv_*` metadata out of chat history and AI model context while making it available to authorized integrations.

WordPress keeps the DocsBot API key on the server and uses private-bot signature keys only to create short-lived tokens. The plugin encrypts both credentials before database storage. Site owners may instead supply them through `wp-config.php` constants.

= External service =

This plugin connects to DocsBot, a third-party service:

* WordPress calls `https://docsbot.ai/api` when an administrator connects an account, reads the authenticated user's current team, lists teams or bots, reads settings, or saves supported bot settings.
* DocsBot receives the entered prompt when an administrator requests a custom action button draft. The plugin saves that draft only when the administrator saves Actions.
* Eligible visitors' browsers load `https://widget.docsbot.ai/chat.js` after an administrator enables the widget.
* DocsBot processes visitor chat messages and any identity fields the administrator chooses to share.

The [DocsBot Privacy Policy](https://docsbot.ai/privacy?utm_source=wordpress.org&utm_medium=plugin&utm_campaign=docsbot_wordpress&utm_content=privacy) and [Terms of Service](https://docsbot.ai/terms?utm_source=wordpress.org&utm_medium=plugin&utm_campaign=docsbot_wordpress&utm_content=terms) govern use of the service. Learn more in the [DocsBot widget documentation](https://docsbot.ai/documentation/developer/embeddable-chat-widget?utm_source=wordpress.org&utm_medium=plugin&utm_campaign=docsbot_wordpress&utm_content=widget_docs).

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` or install the ZIP from **Plugins > Add New Plugin > Upload Plugin**.
2. Activate **DocsBot**.
3. Open **Settings > DocsBot > Connection**.
4. Get your user API key from the [DocsBot API Keys page](https://docsbot.ai/app/api?utm_source=wordpress.org&utm_medium=plugin&utm_campaign=docsbot_wordpress&utm_content=installation_api_key), paste it into WordPress, and connect. DocsBot selects your current accessible team when available.
5. Choose a team, save, then choose a bot.
6. Configure Content, Design, and Actions.
7. Open Deploy, choose your audience and pages, then enable the widget.

WordPress supplies the cryptography used to encrypt saved credentials, so no optional PHP encryption extension is required.

For a private bot, select the bot normally. The plugin retrieves its signing key in the authorized server-side bot response, encrypts it immediately, and never renders it in WordPress.

== Frequently Asked Questions ==

= Does the plugin expose my DocsBot API key? =

No. WordPress makes admin API requests on the server. The plugin encrypts the key before database storage and never includes it in frontend HTML or JavaScript.

= How does the plugin support private bots? =

WordPress checks your access rules before creating a short-lived, server-signed access token. The signing key stays on your server, and the token response is private and never cached.

= Does the plugin send WordPress user data automatically? =

Public identity sharing is off by default. You choose whether to share a visitor's display name, email address, or pseudonymous site user ID. For private bots, signed requests automatically include the logged-in visitor's numeric WordPress user ID as private metadata. Guest requests include no user ID.

= What happens if my membership plugin is disabled? =

The widget stays hidden until the selected membership integration is available again or you change the restriction.

= Can I show the widget only in my documentation or account area? =

Yes. Add one literal path prefix per line, such as `/docs/` or `/account/`. Excluded paths always take precedence.

Page-path rules decide where the widget appears; they do not protect access by themselves. To protect chat, also require a login, WordPress role, or membership under Audience. WordPress checks those rules before sharing identity data or issuing a private-bot token.

= Why can I see a create-bot link even if I cannot create bots? =

DocsBot does not expose a public create-permission flag. When a selected team has no bots, the plugin links to the DocsBot bot page. DocsBot then applies your current role and plan.

== Support ==

Need help connecting a bot or configuring the widget? Start with the [DocsBot widget documentation](https://docsbot.ai/documentation/developer/embeddable-chat-widget?utm_source=wordpress.org&utm_medium=plugin&utm_campaign=docsbot_wordpress&utm_content=support_docs) or open a topic in the WordPress.org support forum.

== Screenshots ==

1. Connect a DocsBot API key and select an accessible team and bot.
2. Edit the bot's conversation content and labels.
3. Match the chat widget to your site design.
4. Enable supported feedback, escalation, search, booking, and custom actions.
5. Deploy by URL path, user role, membership, metadata, and private signing.

== Changelog ==

= 1.0.0 =
* Added WordPress Media Library selection for custom launcher icons, bot avatars, and header logos.
* Added expanded dashboard action categories for Scheduling Tools, Custom Buttons, Skills, and MCP Servers, plus image uploads, voice input, and matching preview controls.
* Moved support settings into Human Support Escalation and content-related options into Content.
* Removed editable domain restrictions and now preserves unrestricted bots while transparently adding the WordPress hostname to existing allowlists.
* Added bundled translations for Spanish, German, French, Brazilian Portuguese, Japanese, and Simplified Chinese.
* Added import-ready WordPress.org listing translation catalogs for the same locales.
* Added the complete WordPress.org banner and icon asset set.
* Added dashboard-matched Content, Design, Actions, Deploy, and live widget preview screens.
* Added automatic private-bot signing-key retrieval and encrypted server-side storage.
* Added role, membership, page-prefix, and opt-in conversation identity controls.
* Added release validation and installable ZIP packaging with GitHub Actions.
* Standardized the plugin slug, text domain, PHP symbols, options, hooks, REST namespace, and package files on DocsBot.
* Updated product naming to DocsBot.
* Initial public release.
