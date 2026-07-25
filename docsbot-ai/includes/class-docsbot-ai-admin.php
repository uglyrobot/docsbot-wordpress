<?php
/**
 * Branded WordPress admin experience.
 *
 * @package DocsBot_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and processes the plugin settings screens.
 */
final class DocsBot_AI_Admin {

	const PAGE_SLUG = 'docsbot-ai';

	/**
	 * DocsBot API client.
	 *
	 * @var DocsBot_AI_API
	 */
	private $api;

	/**
	 * Membership service.
	 *
	 * @var DocsBot_AI_Memberships
	 */
	private $memberships;

	/**
	 * Constructor.
	 *
	 * @param DocsBot_AI_API         $api         API client.
	 * @param DocsBot_AI_Memberships $memberships Membership service.
	 */
	public function __construct( $api, $memberships ) {
		$this->api         = $api;
		$this->memberships = $memberships;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DOCSBOT_AI_FILE ), array( $this, 'plugin_action_links' ) );

		add_action( 'admin_post_docsbot_ai_connect', array( $this, 'save_connection' ) );
		add_action( 'admin_post_docsbot_ai_select', array( $this, 'save_selection' ) );
		add_action( 'admin_post_docsbot_ai_remote_content', array( $this, 'save_remote_content' ) );
		add_action( 'admin_post_docsbot_ai_remote_design', array( $this, 'save_remote_design' ) );
		add_action( 'admin_post_docsbot_ai_actions', array( $this, 'save_actions' ) );
		add_action( 'admin_post_docsbot_ai_deploy', array( $this, 'save_deploy' ) );
	}

	/**
	 * Add the plugin menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'DocsBot', 'docsbot-ai' ),
			__( 'DocsBot', 'docsbot-ai' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-format-chat',
			58
		);
	}

	/**
	 * Load assets only on the DocsBot page.
	 *
	 * @param string $hook_suffix Admin hook.
	 * @return void
	 */
	public function admin_assets( $hook_suffix ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'docsbot-ai-admin',
			DOCSBOT_AI_URL . 'assets/css/admin.css',
			array(),
			DOCSBOT_AI_VERSION
		);
		wp_enqueue_script(
			'docsbot-ai-admin',
			DOCSBOT_AI_URL . 'assets/js/admin.js',
			array(),
			DOCSBOT_AI_VERSION,
			true
		);
	}

	/**
	 * Add a direct settings link on the Plugins screen.
	 *
	 * @param array<int,string> $links Existing links.
	 * @return array<int,string>
	 */
	public function plugin_action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'docsbot-ai' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Render the selected tab.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs     = $this->tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a tab does not change state.
		$raw_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'connection';
		$tab      = isset( $tabs[ $raw_tab ] ) ? $raw_tab : 'connection';
		$settings = DocsBot_AI_Plugin::settings();
		$feedback = $this->consume_feedback();
		?>
		<div class="wrap docsbot-admin">
			<header class="docsbot-header">
				<div class="docsbot-brand">
					<span class="docsbot-brand__mark" aria-hidden="true">
						<img src="<?php echo esc_url( DOCSBOT_AI_URL . 'assets/images/docsbot-icon.svg' ); ?>" alt="">
					</span>
					<div>
						<h1><?php esc_html_e( 'DocsBot for WordPress', 'docsbot-ai' ); ?></h1>
						<p><?php esc_html_e( 'Configure your AI support experience without leaving WordPress.', 'docsbot-ai' ); ?></p>
					</div>
				</div>
				<div class="docsbot-connection-pill <?php echo empty( $settings['bot_id'] ) ? 'is-idle' : 'is-connected'; ?>">
					<span aria-hidden="true"></span>
					<?php echo empty( $settings['bot_id'] ) ? esc_html__( 'Setup needed', 'docsbot-ai' ) : esc_html__( 'Bot connected', 'docsbot-ai' ); ?>
				</div>
			</header>

			<nav class="docsbot-tabs" aria-label="<?php esc_attr_e( 'DocsBot settings', 'docsbot-ai' ); ?>">
				<?php foreach ( $tabs as $tab_id => $label ) : ?>
					<a
						href="<?php echo esc_url( $this->tab_url( $tab_id ) ); ?>"
						class="<?php echo $tab_id === $tab ? 'is-active' : ''; ?>"
						<?php echo $tab_id === $tab ? 'aria-current="page"' : ''; ?>
					>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( $feedback ) : ?>
				<div class="docsbot-inline-message docsbot-inline-message--<?php echo esc_attr( $feedback['type'] ); ?>" role="status">
					<p><?php echo esc_html( $feedback['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<main class="docsbot-layout">
				<section class="docsbot-main">
					<?php
					switch ( $tab ) {
						case 'content':
							$this->render_content( $settings );
							break;
						case 'design':
							$this->render_design( $settings );
							break;
						case 'actions':
							$this->render_actions( $settings );
							break;
						case 'deploy':
							$this->render_deploy( $settings );
							break;
						default:
							$this->render_connection( $settings );
					}
					?>
				</section>
				<aside class="docsbot-sidebar">
					<div class="docsbot-card docsbot-summary">
						<p class="docsbot-eyebrow"><?php esc_html_e( 'Current bot', 'docsbot-ai' ); ?></p>
						<h2><?php echo esc_html( $settings['bot_name'] ? $settings['bot_name'] : __( 'Not selected', 'docsbot-ai' ) ); ?></h2>
						<?php if ( $settings['team_name'] ) : ?>
							<p><?php echo esc_html( $settings['team_name'] ); ?></p>
						<?php endif; ?>
						<dl>
							<div><dt><?php esc_html_e( 'Widget', 'docsbot-ai' ); ?></dt><dd><?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Live', 'docsbot-ai' ) : esc_html__( 'Paused', 'docsbot-ai' ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Privacy', 'docsbot-ai' ); ?></dt><dd><?php echo esc_html( ucfirst( (string) $settings['bot_privacy'] ) ); ?></dd></div>
						</dl>
					</div>
					<div class="docsbot-card docsbot-help">
						<h2><?php esc_html_e( 'Need a hand?', 'docsbot-ai' ); ?></h2>
						<p><?php esc_html_e( 'Find setup guidance and widget documentation from the DocsBot team.', 'docsbot-ai' ); ?></p>
						<a href="https://docsbot.ai/documentation/developer/embeddable-chat-widget" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Read widget docs', 'docsbot-ai' ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'docsbot-ai' ); ?></span>
						</a>
					</div>
				</aside>
			</main>
		</div>
		<?php
	}

	/**
	 * Render connection setup.
	 *
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_connection( $settings ) {
		$constant_key = $this->has_api_key_constant();
		$has_key      = $constant_key || '' !== (string) $settings['api_key'];
		$teams        = $has_key ? $this->cached_teams() : array();
		$bots         = $has_key && $settings['team_id'] ? $this->cached_bots( $settings['team_id'] ) : array();
		?>
		<div class="docsbot-card docsbot-card--hero">
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Step 1', 'docsbot-ai' ); ?></p>
			<h2><?php esc_html_e( 'Connect your DocsBot account', 'docsbot-ai' ); ?></h2>
			<p><?php esc_html_e( 'Your API key is used only by your WordPress server to load teams, bots, and settings. It is never sent to site visitors.', 'docsbot-ai' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="docsbot_ai_connect">
				<?php wp_nonce_field( 'docsbot_ai_connect' ); ?>
				<div class="docsbot-field">
					<label for="docsbot-api-key"><?php esc_html_e( 'DocsBot API key', 'docsbot-ai' ); ?></label>
					<div class="docsbot-secret">
						<input
							type="password"
							id="docsbot-api-key"
							name="api_key"
							value=""
							autocomplete="new-password"
							placeholder="<?php echo $has_key ? esc_attr__( 'Saved securely — enter a new key to replace it', 'docsbot-ai' ) : esc_attr__( 'Paste your API key', 'docsbot-ai' ); ?>"
							<?php echo $constant_key ? 'disabled' : ''; ?>
						>
						<button type="button" class="button docsbot-reveal" data-reveal="docsbot-api-key" data-show-label="<?php esc_attr_e( 'Show', 'docsbot-ai' ); ?>" data-hide-label="<?php esc_attr_e( 'Hide', 'docsbot-ai' ); ?>" aria-controls="docsbot-api-key" aria-pressed="false"><?php esc_html_e( 'Show', 'docsbot-ai' ); ?></button>
					</div>
					<p class="description">
						<a href="https://docsbot.ai/app/api" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Get an API key from DocsBot', 'docsbot-ai' ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'docsbot-ai' ); ?></span>
						</a>
					</p>
				</div>
				<?php if ( $has_key && ! $constant_key ) : ?>
					<label class="docsbot-check"><input type="checkbox" name="remove_key" value="1"> <span><?php esc_html_e( 'Disconnect and remove the saved API key', 'docsbot-ai' ); ?></span></label>
				<?php endif; ?>
				<?php submit_button( $has_key ? __( 'Update connection', 'docsbot-ai' ) : __( 'Connect account', 'docsbot-ai' ), 'primary', 'submit', false ); ?>
			</form>
		</div>

		<?php if ( is_wp_error( $teams ) ) : ?>
			<?php $this->inline_error( $teams->get_error_message() ); ?>
		<?php elseif ( $has_key ) : ?>
			<div class="docsbot-card">
				<p class="docsbot-eyebrow"><?php esc_html_e( 'Step 2', 'docsbot-ai' ); ?></p>
				<h2><?php esc_html_e( 'Choose a team and bot', 'docsbot-ai' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="docsbot_ai_select">
					<?php wp_nonce_field( 'docsbot_ai_select' ); ?>
					<div class="docsbot-grid docsbot-grid--2">
						<div class="docsbot-field">
							<label for="docsbot-team"><?php esc_html_e( 'Team', 'docsbot-ai' ); ?></label>
							<select id="docsbot-team" name="team_id">
								<option value=""><?php esc_html_e( 'Select a team', 'docsbot-ai' ); ?></option>
								<?php foreach ( $teams as $team ) : ?>
									<option value="<?php echo esc_attr( $team['id'] ); ?>" <?php selected( $settings['team_id'], $team['id'] ); ?>>
										<?php echo esc_html( $team['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="docsbot-field">
							<label for="docsbot-bot"><?php esc_html_e( 'Bot', 'docsbot-ai' ); ?></label>
							<select id="docsbot-bot" name="bot_id" <?php disabled( empty( $settings['team_id'] ) ); ?>>
								<option value=""><?php esc_html_e( 'Select a bot', 'docsbot-ai' ); ?></option>
								<?php if ( is_array( $bots ) ) : ?>
									<?php foreach ( $bots as $bot ) : ?>
										<option value="<?php echo esc_attr( $bot['id'] ); ?>" <?php selected( $settings['bot_id'], $bot['id'] ); ?>>
											<?php echo esc_html( $bot['name'] ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>
					<p class="description">
						<?php
						echo $settings['bot_id']
							? esc_html__( 'Change the team and save to refresh its available bots.', 'docsbot-ai' )
							: esc_html__( 'Choose a team first and save to load its bots.', 'docsbot-ai' );
						?>
					</p>
					<?php submit_button( __( 'Use selected bot', 'docsbot-ai' ), 'primary', 'submit', false ); ?>
				</form>
				<?php if ( $settings['team_id'] && is_array( $bots ) && empty( $bots ) ) : ?>
					<div class="docsbot-empty">
						<h3><?php esc_html_e( 'No bots found in this team', 'docsbot-ai' ); ?></h3>
						<p><?php esc_html_e( 'If your DocsBot role and plan allow bot creation, create one in DocsBot and return here.', 'docsbot-ai' ); ?></p>
						<a class="button button-secondary" href="https://docsbot.ai/app/bots" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Create a bot in DocsBot', 'docsbot-ai' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render content fields.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private function render_content( $settings ) {
		$bot = $this->current_bot_or_prompt( $settings );
		if ( ! is_array( $bot ) ) {
			return;
		}
		$labels = isset( $bot['labels'] ) && is_array( $bot['labels'] ) ? $bot['labels'] : array();
		?>
		<div class="docsbot-card">
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Content', 'docsbot-ai' ); ?></p>
			<h2><?php esc_html_e( 'Shape the conversation', 'docsbot-ai' ); ?></h2>
			<p><?php esc_html_e( 'These settings are saved to the selected bot in DocsBot and used anywhere that bot is embedded.', 'docsbot-ai' ); ?></p>
			<?php $this->form_open( 'docsbot_ai_remote_content' ); ?>
				<div class="docsbot-field">
					<label for="docsbot-name"><?php esc_html_e( 'Bot name', 'docsbot-ai' ); ?></label>
					<input type="text" id="docsbot-name" name="name" maxlength="100" value="<?php echo esc_attr( $bot['name'] ?? '' ); ?>" required>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-description"><?php esc_html_e( 'Description', 'docsbot-ai' ); ?></label>
					<textarea id="docsbot-description" name="description" rows="3" maxlength="500"><?php echo esc_textarea( $bot['description'] ?? '' ); ?></textarea>
				</div>
				<div class="docsbot-grid docsbot-grid--2">
					<?php $this->text_field( 'first_message', __( 'First message', 'docsbot-ai' ), $labels['firstMessage'] ?? '', 500 ); ?>
					<?php $this->text_field( 'input_placeholder', __( 'Input placeholder', 'docsbot-ai' ), $labels['inputPlaceholder'] ?? '', 120 ); ?>
					<?php $this->text_field( 'floating_button', __( 'Floating button label', 'docsbot-ai' ), $labels['floatingButton'] ?? '', 80 ); ?>
					<?php $this->text_field( 'support_label', __( 'Support button label', 'docsbot-ai' ), $labels['getSupport'] ?? '', 80 ); ?>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-footer-message"><?php esc_html_e( 'Footer message', 'docsbot-ai' ); ?></label>
					<textarea id="docsbot-footer-message" name="footer_message" rows="3" maxlength="1000"><?php echo esc_textarea( $labels['footerMessage'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'A good place for privacy or acceptable-use guidance. Basic Markdown is supported by the widget.', 'docsbot-ai' ); ?></p>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-support-link"><?php esc_html_e( 'Support URL', 'docsbot-ai' ); ?></label>
					<input type="url" id="docsbot-support-link" name="support_link" value="<?php echo esc_attr( $bot['supportLink'] ?? '' ); ?>" placeholder="https://example.com/support">
				</div>
				<div class="docsbot-check-grid">
					<?php $this->checkbox( 'show_button_label', __( 'Show support button', 'docsbot-ai' ), ! empty( $bot['showButtonLabel'] ) ); ?>
					<?php $this->checkbox( 'show_copy_button', __( 'Let visitors copy answers', 'docsbot-ai' ), ! empty( $bot['showCopyButton'] ) ); ?>
					<?php $this->checkbox( 'hide_sources', __( 'Hide answer sources', 'docsbot-ai' ), ! empty( $bot['hideSources'] ) ); ?>
				</div>
				<?php submit_button( __( 'Save content', 'docsbot-ai' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render design fields.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private function render_design( $settings ) {
		$bot = $this->current_bot_or_prompt( $settings );
		if ( ! is_array( $bot ) ) {
			return;
		}
		?>
		<div class="docsbot-card">
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Design', 'docsbot-ai' ); ?></p>
			<h2><?php esc_html_e( 'Match your site', 'docsbot-ai' ); ?></h2>
			<p><?php esc_html_e( 'Use your brand color, placement, and imagery while keeping the widget accessible.', 'docsbot-ai' ); ?></p>
			<?php $this->form_open( 'docsbot_ai_remote_design' ); ?>
				<div class="docsbot-grid docsbot-grid--3">
					<div class="docsbot-field">
						<label for="docsbot-color"><?php esc_html_e( 'Accent color', 'docsbot-ai' ); ?></label>
						<div class="docsbot-color">
							<input type="color" id="docsbot-color-picker" value="<?php echo esc_attr( $bot['color'] ?? '#0891b8' ); ?>" aria-label="<?php esc_attr_e( 'Choose accent color', 'docsbot-ai' ); ?>">
							<input type="text" id="docsbot-color" name="color" value="<?php echo esc_attr( $bot['color'] ?? '#0891b8' ); ?>" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" required>
						</div>
					</div>
					<?php $this->select_field( 'icon', __( 'Button icon', 'docsbot-ai' ), array( 'default' => __( 'Comments', 'docsbot-ai' ), 'comments' => __( 'Comments (classic)', 'docsbot-ai' ), 'robot' => __( 'Robot', 'docsbot-ai' ), 'life-ring' => __( 'Life ring', 'docsbot-ai' ), 'question' => __( 'Question', 'docsbot-ai' ), 'book' => __( 'Book', 'docsbot-ai' ) ), $bot['icon'] ?? 'default' ); ?>
					<?php $this->select_field( 'alignment', __( 'Button side', 'docsbot-ai' ), array( 'right' => __( 'Right', 'docsbot-ai' ), 'left' => __( 'Left', 'docsbot-ai' ) ), $bot['alignment'] ?? 'right' ); ?>
					<?php $this->select_field( 'bot_icon', __( 'Bot avatar', 'docsbot-ai' ), array( '' => __( 'None', 'docsbot-ai' ), 'comment' => __( 'Comment', 'docsbot-ai' ), 'robot' => __( 'Robot', 'docsbot-ai' ), 'life-ring' => __( 'Life ring', 'docsbot-ai' ), 'info' => __( 'Info', 'docsbot-ai' ), 'book' => __( 'Book', 'docsbot-ai' ) ), $bot['botIcon'] ?? '' ); ?>
					<?php $this->select_field( 'header_alignment', __( 'Header alignment', 'docsbot-ai' ), array( 'center' => __( 'Center', 'docsbot-ai' ), 'left' => __( 'Left', 'docsbot-ai' ) ), $bot['headerAlignment'] ?? $settings['header_alignment'] ); ?>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-logo"><?php esc_html_e( 'Header logo URL', 'docsbot-ai' ); ?></label>
					<input type="url" id="docsbot-logo" name="logo" value="<?php echo esc_attr( is_string( $bot['logo'] ?? '' ) ? $bot['logo'] : '' ); ?>" placeholder="https://example.com/logo.png">
					<p class="description"><?php esc_html_e( 'Use a public HTTPS image URL. A maximum displayed height of 36px works best.', 'docsbot-ai' ); ?></p>
				</div>
				<div class="docsbot-check-grid">
					<?php $this->checkbox( 'branding', __( 'Show DocsBot branding', 'docsbot-ai' ), ! isset( $bot['branding'] ) || ! empty( $bot['branding'] ) ); ?>
					<?php $this->checkbox( 'link_safety_enabled', __( 'Confirm external links', 'docsbot-ai' ), ! empty( $bot['linkSafetyEnabled'] ) || ! empty( $settings['link_safety_enabled'] ) ); ?>
				</div>
				<?php submit_button( __( 'Save design', 'docsbot-ai' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render runtime action overrides.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private function render_actions( $settings ) {
		?>
		<div class="docsbot-card">
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Actions', 'docsbot-ai' ); ?></p>
			<h2><?php esc_html_e( 'Choose what the widget can do', 'docsbot-ai' ); ?></h2>
			<p><?php esc_html_e( 'These WordPress embed options activate features already enabled and configured for your bot and plan in DocsBot.', 'docsbot-ai' ); ?></p>
			<?php $this->form_open( 'docsbot_ai_actions' ); ?>
				<div class="docsbot-option-list">
					<?php $this->option_toggle( 'use_feedback', __( 'Answer feedback', 'docsbot-ai' ), __( 'Let visitors rate answers as helpful or unhelpful.', 'docsbot-ai' ), $settings['use_feedback'] ); ?>
					<?php $this->option_toggle( 'use_escalation', __( 'Human support escalation', 'docsbot-ai' ), __( 'Show escalation when it is configured for the selected bot.', 'docsbot-ai' ), $settings['use_escalation'] ); ?>
					<?php $this->option_toggle( 'use_web_search', __( 'Web search', 'docsbot-ai' ), __( 'Allow agent-mode web search when your plan and bot support it.', 'docsbot-ai' ), $settings['use_web_search'] ); ?>
					<?php $this->option_toggle( 'use_calendly', __( 'Calendly booking', 'docsbot-ai' ), __( 'Allow the configured Calendly scheduling action.', 'docsbot-ai' ), $settings['use_calendly'] ); ?>
					<?php $this->option_toggle( 'use_calcom', __( 'Cal.com booking', 'docsbot-ai' ), __( 'Allow the configured Cal.com scheduling action.', 'docsbot-ai' ), $settings['use_calcom'] ); ?>
					<?php $this->option_toggle( 'use_tidycal', __( 'TidyCal booking', 'docsbot-ai' ), __( 'Allow the configured TidyCal scheduling action.', 'docsbot-ai' ), $settings['use_tidycal'] ); ?>
					<?php $this->option_toggle( 'use_custom_buttons', __( 'Custom action buttons', 'docsbot-ai' ), __( 'Allow custom button tools configured in DocsBot.', 'docsbot-ai' ), $settings['use_custom_buttons'] ); ?>
					<?php $this->option_toggle( 'show_agent_activity', __( 'Agent activity', 'docsbot-ai' ), __( 'Show concise thinking and tool progress while the agent works.', 'docsbot-ai' ), $settings['show_agent_activity'] ); ?>
				</div>
				<?php submit_button( __( 'Save actions', 'docsbot-ai' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render deployment, identity, membership, and signing settings.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private function render_deploy( $settings ) {
		global $wp_roles;
		$roles              = is_object( $wp_roles ) ? $wp_roles->get_names() : array();
		$providers          = $this->memberships->providers();
		$constant_signature = $this->has_signature_key_constant();
		?>
		<div class="docsbot-card docsbot-deploy-hero">
			<div>
				<p class="docsbot-eyebrow"><?php esc_html_e( 'Deploy', 'docsbot-ai' ); ?></p>
				<h2><?php esc_html_e( 'Put your bot to work', 'docsbot-ai' ); ?></h2>
				<p><?php esc_html_e( 'Choose exactly where the widget appears and who can access it.', 'docsbot-ai' ); ?></p>
			</div>
			<span class="docsbot-status-badge <?php echo ! empty( $settings['enabled'] ) ? 'is-live' : ''; ?>">
				<?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Live', 'docsbot-ai' ) : esc_html__( 'Paused', 'docsbot-ai' ); ?>
			</span>
		</div>
		<form class="docsbot-deploy-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-unsaved-message="<?php esc_attr_e( 'You have unsaved deployment changes. Leave this page anyway?', 'docsbot-ai' ); ?>">
			<input type="hidden" name="action" value="docsbot_ai_deploy">
			<?php wp_nonce_field( 'docsbot_ai_deploy' ); ?>
			<div class="docsbot-sticky-save" hidden>
				<span aria-live="polite"><?php esc_html_e( 'Deployment changes are not saved yet.', 'docsbot-ai' ); ?></span>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save deployment', 'docsbot-ai' ); ?></button>
			</div>
			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Widget availability', 'docsbot-ai' ); ?></h2>
				<?php $this->option_toggle( 'enabled', __( 'Enable the widget', 'docsbot-ai' ), __( 'Turn this off to pause the WordPress embed without changing your DocsBot bot.', 'docsbot-ai' ), $settings['enabled'] ); ?>
				<div class="docsbot-grid docsbot-grid--2">
					<div class="docsbot-field">
						<label for="docsbot-includes"><?php esc_html_e( 'Only show on URL prefixes', 'docsbot-ai' ); ?></label>
						<textarea id="docsbot-includes" name="include_prefixes" rows="5" placeholder="/docs/&#10;/account/"><?php echo esc_textarea( $settings['include_prefixes'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One path prefix per line. These control placement, not authorization; use the Audience controls below to protect access.', 'docsbot-ai' ); ?></p>
					</div>
					<div class="docsbot-field">
						<label for="docsbot-excludes"><?php esc_html_e( 'Never show on URL prefixes', 'docsbot-ai' ); ?></label>
						<textarea id="docsbot-excludes" name="exclude_prefixes" rows="5" placeholder="/checkout/&#10;/privacy/"><?php echo esc_textarea( $settings['exclude_prefixes'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Exclusions always win over inclusions.', 'docsbot-ai' ); ?></p>
					</div>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-domains"><?php esc_html_e( 'Allowed embed domains', 'docsbot-ai' ); ?></label>
					<textarea id="docsbot-domains" name="allowed_domains" rows="3" placeholder="<?php echo esc_attr( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>"><?php echo esc_textarea( $settings['allowed_domains'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One hostname per line, without a scheme or wildcard. Saved to DocsBot as defense in depth.', 'docsbot-ai' ); ?></p>
				</div>
			</div>

			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Audience', 'docsbot-ai' ); ?></h2>
				<?php $this->checkbox( 'logged_in_only', __( 'Only show to logged-in WordPress users', 'docsbot-ai' ), $settings['logged_in_only'] ); ?>
				<fieldset class="docsbot-fieldset">
					<legend><?php esc_html_e( 'Allowed WordPress roles', 'docsbot-ai' ); ?></legend>
					<p class="description"><?php esc_html_e( 'Leave every role unchecked to allow all roles.', 'docsbot-ai' ); ?></p>
					<div class="docsbot-check-grid">
						<?php foreach ( $roles as $role_id => $role_name ) : ?>
							<label class="docsbot-check"><input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr( $role_id ); ?>" <?php checked( in_array( $role_id, (array) $settings['allowed_roles'], true ) ); ?>> <span><?php echo esc_html( translate_user_role( $role_name ) ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<div class="docsbot-grid docsbot-grid--2">
					<?php $this->select_field( 'membership_provider', __( 'Membership integration', 'docsbot-ai' ), $providers, $settings['membership_provider'] ); ?>
					<div class="docsbot-field">
						<label for="docsbot-membership-rule"><?php esc_html_e( 'Plan, product, or role IDs', 'docsbot-ai' ); ?></label>
						<input type="text" id="docsbot-membership-rule" name="membership_rule" value="<?php echo esc_attr( $settings['membership_rule'] ); ?>" placeholder="gold, 42">
						<p class="description"><?php esc_html_e( 'Comma-separated. Leave empty for any active membership; Ultimate Member requires role slugs.', 'docsbot-ai' ); ?></p>
					</div>
				</div>
				<?php if ( 'none' !== $settings['membership_provider'] && ! $this->memberships->is_available( $settings['membership_provider'] ) ) : ?>
					<div class="docsbot-inline-message docsbot-inline-message--warning" role="status"><p><?php esc_html_e( 'The selected membership integration is not currently available. The widget will fail closed until it is active.', 'docsbot-ai' ); ?></p></div>
				<?php endif; ?>
			</div>

			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Conversation identity', 'docsbot-ai' ); ?></h2>
				<p><?php esc_html_e( 'Sharing identity makes DocsBot conversations non-anonymous. Each field is optional and is sent only after the visitor passes your access rules.', 'docsbot-ai' ); ?></p>
				<div class="docsbot-check-grid">
					<?php $this->checkbox( 'share_name', __( 'Share display name', 'docsbot-ai' ), $settings['share_name'] ); ?>
					<?php $this->checkbox( 'share_email', __( 'Share email address', 'docsbot-ai' ), $settings['share_email'] ); ?>
					<?php $this->checkbox( 'share_user_id', __( 'Share pseudonymous site user ID', 'docsbot-ai' ), $settings['share_user_id'] ); ?>
				</div>
				<p class="description"><?php esc_html_e( 'Review the suggested DocsBot text under Settings → Privacy before enabling identity fields.', 'docsbot-ai' ); ?></p>
			</div>

			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Private bot signing', 'docsbot-ai' ); ?></h2>
				<?php if ( 'private' === $settings['bot_privacy'] ) : ?>
					<p><?php esc_html_e( 'Private bots require the signature key from the bot’s Widget Embed page. The key stays encrypted on your server; visitors receive only short-lived signed tokens.', 'docsbot-ai' ); ?></p>
					<div class="docsbot-field">
						<label for="docsbot-signature-key"><?php esc_html_e( 'Bot signature key', 'docsbot-ai' ); ?></label>
						<div class="docsbot-secret">
							<input type="password" id="docsbot-signature-key" name="signature_key" value="" autocomplete="new-password" placeholder="<?php echo $settings['signature_key'] || $constant_signature ? esc_attr__( 'Saved securely — enter a new key to replace it', 'docsbot-ai' ) : esc_attr__( 'Paste the bot signature key', 'docsbot-ai' ); ?>" <?php echo $constant_signature ? 'disabled' : ''; ?>>
							<button type="button" class="button docsbot-reveal" data-reveal="docsbot-signature-key" data-show-label="<?php esc_attr_e( 'Show', 'docsbot-ai' ); ?>" data-hide-label="<?php esc_attr_e( 'Hide', 'docsbot-ai' ); ?>" aria-controls="docsbot-signature-key" aria-pressed="false"><?php esc_html_e( 'Show', 'docsbot-ai' ); ?></button>
						</div>
					</div>
					<?php if ( $settings['signature_key'] && ! $constant_signature ) : ?>
						<label class="docsbot-check"><input type="checkbox" name="remove_signature_key" value="1"> <span><?php esc_html_e( 'Remove the saved signature key', 'docsbot-ai' ); ?></span></label>
					<?php endif; ?>
					<div class="docsbot-field docsbot-field--short">
						<label for="docsbot-jwt-ttl"><?php esc_html_e( 'Token lifetime (seconds)', 'docsbot-ai' ); ?></label>
						<input type="number" id="docsbot-jwt-ttl" name="jwt_ttl" min="300" max="3600" step="60" value="<?php echo esc_attr( $settings['jwt_ttl'] ); ?>">
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'The selected bot is public, so no signature key is needed or retained.', 'docsbot-ai' ); ?></p>
				<?php endif; ?>
				<a href="https://docsbot.ai/app/bots" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open bot embed settings in DocsBot', 'docsbot-ai' ); ?></a>
			</div>
			<?php submit_button( __( 'Save deployment', 'docsbot-ai' ), 'primary docsbot-save-large', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Save or remove the API key.
	 *
	 * @return void
	 */
	public function save_connection() {
		$this->guard( 'docsbot_ai_connect' );
		check_admin_referer( 'docsbot_ai_connect' );

		if ( $this->has_api_key_constant() ) {
			$this->redirect_feedback( 'connection', 'success', __( 'The connection is managed through wp-config.php.', 'docsbot-ai' ) );
		}

		if ( ! empty( $_POST['remove_key'] ) ) {
			DocsBot_AI_Plugin::update_settings(
				array(
					'api_key'       => '',
					'team_id'       => '',
					'team_name'     => '',
					'bot_id'        => '',
					'bot_name'      => '',
					'bot_privacy'   => 'public',
					'signature_key' => '',
					'enabled'       => false,
				)
			);
			$this->clear_cache();
			$this->redirect_feedback( 'connection', 'success', __( 'DocsBot has been disconnected.', 'docsbot-ai' ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( '' === $api_key ) {
			$this->redirect_feedback( 'connection', 'error', __( 'Enter an API key to connect DocsBot.', 'docsbot-ai' ) );
		}

		$teams = $this->api->list_teams( $api_key );
		if ( is_wp_error( $teams ) ) {
			$this->redirect_feedback( 'connection', 'error', $teams->get_error_message() );
		}

		$encrypted = DocsBot_AI_Crypto::encrypt( $api_key );
		if ( is_wp_error( $encrypted ) ) {
			$this->redirect_feedback( 'connection', 'error', $encrypted->get_error_message() );
		}

		DocsBot_AI_Plugin::update_settings(
			array(
				'api_key'       => $encrypted,
				'team_id'       => '',
				'team_name'     => '',
				'bot_id'        => '',
				'bot_name'      => '',
				'bot_privacy'   => 'public',
				'signature_key' => '',
				'enabled'       => false,
			)
		);
		$this->clear_cache();
		set_transient( 'docsbot_ai_teams', $teams, 5 * MINUTE_IN_SECONDS );
		$this->redirect_feedback( 'connection', 'success', __( 'DocsBot connected. Now choose a team and bot.', 'docsbot-ai' ) );
	}

	/**
	 * Save selected team/bot and pull the bot settings.
	 *
	 * @return void
	 */
	public function save_selection() {
		$this->guard( 'docsbot_ai_select' );
		check_admin_referer( 'docsbot_ai_select' );

		$team_id = isset( $_POST['team_id'] ) ? sanitize_text_field( wp_unslash( $_POST['team_id'] ) ) : '';
		$bot_id  = isset( $_POST['bot_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bot_id'] ) ) : '';
		$teams   = $this->api->list_teams();
		if ( is_wp_error( $teams ) ) {
			$this->redirect_feedback( 'connection', 'error', $teams->get_error_message() );
		}

		$team = $this->find_by_id( $teams, $team_id );
		if ( ! $team ) {
			$this->redirect_feedback( 'connection', 'error', __( 'Select a valid DocsBot team.', 'docsbot-ai' ) );
		}

		$bots = $this->api->list_bots( $team_id );
		if ( is_wp_error( $bots ) ) {
			$this->redirect_feedback( 'connection', 'error', $bots->get_error_message() );
		}

		if ( '' === $bot_id ) {
			DocsBot_AI_Plugin::update_settings(
				array(
					'team_id'       => $team_id,
					'team_name'     => sanitize_text_field( $team['name'] ),
					'bot_id'        => '',
					'bot_name'      => '',
					'bot_privacy'   => 'public',
					'signature_key' => '',
					'enabled'       => false,
				)
			);
			$this->clear_cache();
			set_transient( 'docsbot_ai_bots_' . md5( $team_id ), $bots, 5 * MINUTE_IN_SECONDS );
			$this->redirect_feedback( 'connection', 'success', empty( $bots ) ? __( 'Team selected. Create a bot in DocsBot to continue.', 'docsbot-ai' ) : __( 'Team selected. Now choose a bot.', 'docsbot-ai' ) );
		}

		$listed_bot = $this->find_by_id( $bots, $bot_id );
		if ( ! $listed_bot ) {
			$this->redirect_feedback( 'connection', 'error', __( 'Select a valid DocsBot bot.', 'docsbot-ai' ) );
		}

		$bot = $this->api->get_bot( $team_id, $bot_id );
		if ( is_wp_error( $bot ) ) {
			$this->redirect_feedback( 'connection', 'error', $bot->get_error_message() );
		}

		$settings = DocsBot_AI_Plugin::settings();
		$changes  = array(
			'team_id'         => $team_id,
			'team_name'       => sanitize_text_field( $team['name'] ),
			'bot_id'          => $bot_id,
			'bot_name'        => sanitize_text_field( $bot['name'] ?? $listed_bot['name'] ),
			'bot_privacy'     => in_array( $bot['privacy'] ?? 'public', array( 'public', 'private' ), true ) ? $bot['privacy'] : 'public',
			'allowed_domains' => $this->lines_from_array( $bot['allowedDomains'] ?? array() ),
		);
		if ( 'public' === $changes['bot_privacy'] ) {
			$changes['signature_key'] = '';
		}
		if ( $bot_id !== $settings['bot_id'] ) {
			$changes['signature_key'] = '';
			$changes['enabled']       = false;
		}
		DocsBot_AI_Plugin::update_settings( $changes );
		$this->clear_cache();
		set_transient( 'docsbot_ai_bot_' . md5( $team_id . '|' . $bot_id ), $bot, 5 * MINUTE_IN_SECONDS );
		$this->redirect_feedback( 'connection', 'success', __( 'Bot connected and settings loaded.', 'docsbot-ai' ) );
	}

	/**
	 * Save remote content settings.
	 *
	 * @return void
	 */
	public function save_remote_content() {
		$this->guard( 'docsbot_ai_remote_content' );
		check_admin_referer( 'docsbot_ai_remote_content' );
		$settings = DocsBot_AI_Plugin::settings();
		$labels   = array(
			'firstMessage'     => $this->post_textarea( 'first_message', 500 ),
			'inputPlaceholder' => $this->post_text( 'input_placeholder', 120 ),
			'floatingButton'   => $this->post_text( 'floating_button', 80 ),
			'getSupport'       => $this->post_text( 'support_label', 80 ),
			'footerMessage'    => $this->post_textarea( 'footer_message', 1000 ),
		);
		$result   = $this->api->update_bot(
			$settings['team_id'],
			$settings['bot_id'],
			array(
				'name'            => $this->post_text( 'name', 100 ),
				'description'     => $this->post_textarea( 'description', 500 ),
				'supportLink'     => $this->post_url( 'support_link' ),
				'showButtonLabel' => $this->posted_bool( 'show_button_label' ),
				'showCopyButton'  => $this->posted_bool( 'show_copy_button' ),
				'hideSources'     => $this->posted_bool( 'hide_sources' ),
				'labels'          => $labels,
			)
		);
		$this->finish_remote_save( 'content', $result, __( 'Content saved to DocsBot.', 'docsbot-ai' ) );
	}

	/**
	 * Save remote design settings and local embed-only design overrides.
	 *
	 * @return void
	 */
	public function save_remote_design() {
		$this->guard( 'docsbot_ai_remote_design' );
		check_admin_referer( 'docsbot_ai_remote_design' );
		$settings         = DocsBot_AI_Plugin::settings();
		$color            = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '';
		$alignment        = $this->posted_enum( 'alignment', array( 'left', 'right' ), 'right' );
		$header_alignment = $this->posted_enum( 'header_alignment', array( 'left', 'center' ), 'center' );
		$icon             = $this->posted_enum( 'icon', array( 'default', 'comments', 'robot', 'life-ring', 'question', 'book' ), 'default' );
		$bot_icon         = $this->posted_enum( 'bot_icon', array( '', 'comment', 'robot', 'life-ring', 'info', 'book' ), '' );
		$logo             = $this->post_url( 'logo' );

		if ( ! $color ) {
			$this->redirect_feedback( 'design', 'error', __( 'Enter a valid six-digit hex color.', 'docsbot-ai' ) );
		}

		$result = $this->api->update_bot(
			$settings['team_id'],
			$settings['bot_id'],
			array(
				'color'             => $color,
				'icon'              => $icon,
				'alignment'         => $alignment,
				'botIcon'           => '' === $bot_icon ? false : $bot_icon,
				'logo'              => '' === $logo ? false : $logo,
				'headerAlignment'   => $header_alignment,
				'branding'          => $this->posted_bool( 'branding' ),
				'linkSafetyEnabled' => $this->posted_bool( 'link_safety_enabled' ),
			)
		);

		if ( ! is_wp_error( $result ) ) {
			DocsBot_AI_Plugin::update_settings(
				array(
					'header_alignment'    => $header_alignment,
					'link_safety_enabled' => $this->posted_bool( 'link_safety_enabled' ),
				)
			);
		}
		$this->finish_remote_save( 'design', $result, __( 'Design saved to DocsBot.', 'docsbot-ai' ) );
	}

	/**
	 * Save local action overrides.
	 *
	 * @return void
	 */
	public function save_actions() {
		$this->guard( 'docsbot_ai_actions' );
		check_admin_referer( 'docsbot_ai_actions' );
		$fields  = array( 'use_feedback', 'use_escalation', 'use_web_search', 'use_calendly', 'use_calcom', 'use_tidycal', 'use_custom_buttons', 'show_agent_activity' );
		$changes = array();
		foreach ( $fields as $field ) {
			$changes[ $field ] = $this->posted_bool( $field );
		}
		DocsBot_AI_Plugin::update_settings( $changes );
		$this->redirect_feedback( 'actions', 'success', __( 'Widget actions saved.', 'docsbot-ai' ) );
	}

	/**
	 * Save deployment and optionally sync domain restrictions.
	 *
	 * @return void
	 */
	public function save_deploy() {
		$this->guard( 'docsbot_ai_deploy' );
		check_admin_referer( 'docsbot_ai_deploy' );
		$settings    = DocsBot_AI_Plugin::settings();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Every role is allowlisted below.
		$roles       = isset( $_POST['allowed_roles'] ) ? (array) wp_unslash( $_POST['allowed_roles'] ) : array();
		$valid_roles = array_keys( wp_roles()->roles );
		$roles       = array_values( array_intersect( array_map( 'sanitize_key', $roles ), $valid_roles ) );
		$provider    = isset( $_POST['membership_provider'] ) ? sanitize_key( wp_unslash( $_POST['membership_provider'] ) ) : 'none';
		$providers   = $this->memberships->providers();
		$provider    = isset( $providers[ $provider ] ) ? $provider : 'none';
		$signature   = isset( $_POST['signature_key'] ) ? sanitize_text_field( wp_unslash( $_POST['signature_key'] ) ) : '';
		$constant_signature = $this->has_signature_key_constant();
		$remove_key         = ! empty( $_POST['remove_signature_key'] ) && ! $constant_signature;
		$changes     = array(
			'enabled'             => $this->posted_bool( 'enabled' ),
			'include_prefixes'    => $this->sanitize_prefix_lines( isset( $_POST['include_prefixes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['include_prefixes'] ) ) : '' ),
			'exclude_prefixes'    => $this->sanitize_prefix_lines( isset( $_POST['exclude_prefixes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['exclude_prefixes'] ) ) : '' ),
			'allowed_domains'     => $this->sanitize_domain_lines( isset( $_POST['allowed_domains'] ) ? sanitize_textarea_field( wp_unslash( $_POST['allowed_domains'] ) ) : '' ),
			'logged_in_only'      => $this->posted_bool( 'logged_in_only' ),
			'allowed_roles'       => $roles,
			'membership_provider' => $provider,
			'membership_rule'     => $this->post_text( 'membership_rule', 500 ),
			'share_name'          => $this->posted_bool( 'share_name' ),
			'share_email'         => $this->posted_bool( 'share_email' ),
			'share_user_id'       => $this->posted_bool( 'share_user_id' ),
			'jwt_ttl'             => min( 3600, max( 300, absint( isset( $_POST['jwt_ttl'] ) ? wp_unslash( $_POST['jwt_ttl'] ) : 3600 ) ) ),
		);

		if ( empty( $settings['team_id'] ) || empty( $settings['bot_id'] ) ) {
			$changes['enabled'] = false;
		}

		if ( $remove_key || 'public' === $settings['bot_privacy'] ) {
			$changes['signature_key'] = '';
		}

		if ( '' !== $signature && ! $constant_signature ) {
			$encrypted = DocsBot_AI_Crypto::encrypt( $signature );
			if ( is_wp_error( $encrypted ) ) {
				$this->redirect_feedback( 'deploy', 'error', $encrypted->get_error_message() );
			}
			$changes['signature_key'] = $encrypted;
		}

		$has_signature = $constant_signature
			|| '' !== $signature
			|| ( ! $remove_key && ! empty( $settings['signature_key'] ) );
		if ( ! empty( $changes['enabled'] ) && 'private' === $settings['bot_privacy'] && ! $has_signature ) {
			$changes['enabled'] = false;
			DocsBot_AI_Plugin::update_settings( $changes );
			$this->redirect_feedback( 'deploy', 'error', __( 'Add the bot signature key before enabling a private bot.', 'docsbot-ai' ) );
		}

		if ( 'none' !== $provider ) {
			$changes['logged_in_only'] = true;
		}

		DocsBot_AI_Plugin::update_settings( $changes );

		if ( $settings['team_id'] && $settings['bot_id'] ) {
			$domains = array_values( array_filter( preg_split( '/\r\n|\r|\n/', $changes['allowed_domains'] ) ) );
			$result  = $this->api->update_bot( $settings['team_id'], $settings['bot_id'], array( 'allowedDomains' => $domains ) );
			if ( is_wp_error( $result ) ) {
				/* translators: %s: DocsBot API error message. */
				$this->redirect_feedback( 'deploy', 'warning', sprintf( __( 'Deployment saved, but DocsBot domain restrictions were not updated: %s', 'docsbot-ai' ), $result->get_error_message() ) );
			}
			$this->cache_bot( $settings['team_id'], $settings['bot_id'], $result );
		}

		$this->redirect_feedback( 'deploy', 'success', __( 'Deployment settings saved.', 'docsbot-ai' ) );
	}

	/**
	 * Security guard for every mutation.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage DocsBot.', 'docsbot-ai' ),
				'',
				array( 'response' => 403 )
			);
		}
		check_admin_referer( $action );
	}

	/**
	 * Check whether wp-config.php provides a usable API key.
	 *
	 * @return bool
	 */
	private function has_api_key_constant() {
		return defined( 'DOCSBOT_AI_API_KEY' ) && '' !== trim( (string) DOCSBOT_AI_API_KEY );
	}

	/**
	 * Check whether wp-config.php provides a usable signature key.
	 *
	 * @return bool
	 */
	private function has_signature_key_constant() {
		return defined( 'DOCSBOT_AI_SIGNATURE_KEY' ) && '' !== trim( (string) DOCSBOT_AI_SIGNATURE_KEY );
	}

	/**
	 * Cache and finish a remote bot save.
	 *
	 * @param string                        $tab     Return tab.
	 * @param array<string,mixed>|WP_Error $result  API result.
	 * @param string                        $success Success message.
	 * @return void
	 */
	private function finish_remote_save( $tab, $result, $success ) {
		if ( is_wp_error( $result ) ) {
			$this->redirect_feedback( $tab, 'error', $result->get_error_message() );
		}
		$settings = DocsBot_AI_Plugin::settings();
		$privacy = in_array( $result['privacy'] ?? $settings['bot_privacy'], array( 'public', 'private' ), true )
			? ( $result['privacy'] ?? $settings['bot_privacy'] )
			: 'public';
		$changes = array(
			'bot_name'    => sanitize_text_field( $result['name'] ?? $settings['bot_name'] ),
			'bot_privacy' => $privacy,
		);
		if ( 'public' === $privacy ) {
			$changes['signature_key'] = '';
		}
		DocsBot_AI_Plugin::update_settings( $changes );
		$this->cache_bot( $settings['team_id'], $settings['bot_id'], $result );
		$this->redirect_feedback( $tab, 'success', $success );
	}

	/**
	 * Return current bot or render an inline connection prompt.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,mixed>|null
	 */
	private function current_bot_or_prompt( $settings ) {
		if ( empty( $settings['team_id'] ) || empty( $settings['bot_id'] ) ) {
			$this->connection_prompt();
			return null;
		}
		$bot = $this->cached_bot( $settings['team_id'], $settings['bot_id'] );
		if ( is_wp_error( $bot ) ) {
			$this->inline_error( $bot->get_error_message() );
			return null;
		}
		return $bot;
	}

	/**
	 * Render a selection prompt.
	 *
	 * @return void
	 */
	private function connection_prompt() {
		?>
		<div class="docsbot-card docsbot-empty docsbot-empty--large">
			<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
			<h2><?php esc_html_e( 'Connect a bot first', 'docsbot-ai' ); ?></h2>
			<p><?php esc_html_e( 'Choose a DocsBot team and bot before configuring this section.', 'docsbot-ai' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( $this->tab_url( 'connection' ) ); ?>"><?php esc_html_e( 'Go to connection', 'docsbot-ai' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Cached teams.
	 *
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	private function cached_teams() {
		$cached = get_transient( 'docsbot_ai_teams' );
		if ( false !== $cached ) {
			return $cached;
		}
		$teams = $this->api->list_teams();
		if ( ! is_wp_error( $teams ) ) {
			set_transient( 'docsbot_ai_teams', $teams, 5 * MINUTE_IN_SECONDS );
		}
		return $teams;
	}

	/**
	 * Cached bots.
	 *
	 * @param string $team_id Team ID.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	private function cached_bots( $team_id ) {
		$key    = 'docsbot_ai_bots_' . md5( $team_id );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$bots = $this->api->list_bots( $team_id );
		if ( ! is_wp_error( $bots ) ) {
			set_transient( $key, $bots, 5 * MINUTE_IN_SECONDS );
		}
		return $bots;
	}

	/**
	 * Cached current bot.
	 *
	 * @param string $team_id Team ID.
	 * @param string $bot_id  Bot ID.
	 * @return array<string,mixed>|WP_Error
	 */
	private function cached_bot( $team_id, $bot_id ) {
		$key    = 'docsbot_ai_bot_' . md5( $team_id . '|' . $bot_id );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$bot = $this->api->get_bot( $team_id, $bot_id );
		if ( ! is_wp_error( $bot ) ) {
			set_transient( $key, $bot, 5 * MINUTE_IN_SECONDS );
		}
		return $bot;
	}

	/**
	 * Cache bot response.
	 *
	 * @param string              $team_id Team ID.
	 * @param string              $bot_id  Bot ID.
	 * @param array<string,mixed> $bot     Bot object.
	 * @return void
	 */
	private function cache_bot( $team_id, $bot_id, $bot ) {
		set_transient( 'docsbot_ai_bot_' . md5( $team_id . '|' . $bot_id ), $bot, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Clear plugin response caches.
	 *
	 * @return void
	 */
	private function clear_cache() {
		delete_transient( 'docsbot_ai_teams' );
		$settings = DocsBot_AI_Plugin::settings();
		if ( $settings['team_id'] ) {
			delete_transient( 'docsbot_ai_bots_' . md5( $settings['team_id'] ) );
		}
		if ( $settings['team_id'] && $settings['bot_id'] ) {
			delete_transient( 'docsbot_ai_bot_' . md5( $settings['team_id'] . '|' . $settings['bot_id'] ) );
		}
	}

	/**
	 * Find object by ID.
	 *
	 * @param array<int,array<string,mixed>> $items Items.
	 * @param string                         $id    ID.
	 * @return array<string,mixed>|null
	 */
	private function find_by_id( $items, $id ) {
		foreach ( $items as $item ) {
			if ( isset( $item['id'] ) && hash_equals( (string) $item['id'], (string) $id ) ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Tab definitions.
	 *
	 * @return array<string,string>
	 */
	private function tabs() {
		return array(
			'connection' => __( 'Connection', 'docsbot-ai' ),
			'content'    => __( 'Content', 'docsbot-ai' ),
			'design'     => __( 'Design', 'docsbot-ai' ),
			'actions'    => __( 'Actions', 'docsbot-ai' ),
			'deploy'     => __( 'Deploy', 'docsbot-ai' ),
		);
	}

	/**
	 * Tab URL.
	 *
	 * @param string $tab Tab ID.
	 * @return string
	 */
	private function tab_url( $tab ) {
		return add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => $tab ), admin_url( 'admin.php' ) );
	}

	/**
	 * Start a protected form.
	 *
	 * @param string $action Action ID.
	 * @return void
	 */
	private function form_open( $action ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
			<?php wp_nonce_field( $action ); ?>
		<?php
	}

	/**
	 * Render a checkbox.
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param bool   $checked Checked state.
	 * @return void
	 */
	private function checkbox( $name, $label, $checked ) {
		?>
		<label class="docsbot-check">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>>
			<span><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render a detailed toggle row.
	 *
	 * @param string $name        Field name.
	 * @param string $label       Label.
	 * @param string $description Description.
	 * @param bool   $checked     Checked state.
	 * @return void
	 */
	private function option_toggle( $name, $label, $description, $checked ) {
		?>
		<label class="docsbot-option">
			<span class="docsbot-option__copy"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $description ); ?></span></span>
			<span class="docsbot-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>><span aria-hidden="true"></span></span>
		</label>
		<?php
	}

	/**
	 * Render a text input.
	 *
	 * @param string $name   Field name.
	 * @param string $label  Label.
	 * @param string $value  Value.
	 * @param int    $length Maximum length.
	 * @return void
	 */
	private function text_field( $name, $label, $value, $length ) {
		?>
		<div class="docsbot-field">
			<label for="docsbot-<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" id="docsbot-<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>" name="<?php echo esc_attr( $name ); ?>" maxlength="<?php echo esc_attr( $length ); ?>" value="<?php echo esc_attr( $value ); ?>">
		</div>
		<?php
	}

	/**
	 * Render a select.
	 *
	 * @param string               $name    Name.
	 * @param string               $label   Label.
	 * @param array<string,string> $options Options.
	 * @param string               $value   Selected value.
	 * @return void
	 */
	private function select_field( $name, $label, $options, $value ) {
		$id = 'docsbot-' . str_replace( '_', '-', $name );
		?>
		<div class="docsbot-field">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( (string) $value, (string) $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render an inline error.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function inline_error( $message ) {
		?>
		<div class="docsbot-inline-message docsbot-inline-message--error" role="alert"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	}

	/**
	 * Store feedback and redirect back to a safe plugin tab.
	 *
	 * @param string $tab     Tab.
	 * @param string $type    Feedback type.
	 * @param string $message Message.
	 * @return never
	 */
	private function redirect_feedback( $tab, $type, $message ) {
		$token = wp_generate_password( 12, false, false );
		set_transient(
			'docsbot_ai_feedback_' . get_current_user_id() . '_' . $token,
			array(
				'type'    => in_array( $type, array( 'success', 'error', 'warning' ), true ) ? $type : 'success',
				'message' => sanitize_text_field( $message ),
			),
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( add_query_arg( 'feedback', $token, $this->tab_url( $tab ) ) );
		exit;
	}

	/**
	 * Consume one-time page feedback.
	 *
	 * @return array<string,string>|null
	 */
	private function consume_feedback() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The random token is user-bound and only consumes a status message.
		$token = isset( $_GET['feedback'] ) ? sanitize_key( wp_unslash( $_GET['feedback'] ) ) : '';
		if ( ! $token ) {
			return null;
		}
		$key      = 'docsbot_ai_feedback_' . get_current_user_id() . '_' . $token;
		$feedback = get_transient( $key );
		delete_transient( $key );
		return is_array( $feedback ) ? $feedback : null;
	}

	/**
	 * Read a boolean checkbox.
	 *
	 * @param string $name Name.
	 * @return bool
	 */
	private function posted_bool( $name ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify their action nonce before using submitted values.
		return isset( $_POST[ $name ] ) && '1' === sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
	}

	/**
	 * Read an enum.
	 *
	 * @param string            $name    Name.
	 * @param array<int,string> $allowed Allowed values.
	 * @param string            $default Default.
	 * @return string
	 */
	private function posted_enum( $name, $allowed, $default ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify their action nonce before using submitted values.
		$value = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : $default;
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Read bounded single-line text.
	 *
	 * @param string $name   Name.
	 * @param int    $length Maximum length.
	 * @return string
	 */
	private function post_text( $name, $length ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify their action nonce before using submitted values.
		$value = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}

	/**
	 * Read bounded textarea text.
	 *
	 * @param string $name   Name.
	 * @param int    $length Maximum length.
	 * @return string
	 */
	private function post_textarea( $name, $length ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify their action nonce before using submitted values.
		$value = isset( $_POST[ $name ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}

	/**
	 * Read a URL.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private function post_url( $name ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify their action nonce before using submitted values.
		return isset( $_POST[ $name ] ) ? esc_url_raw( wp_unslash( $_POST[ $name ] ), array( 'https' ) ) : '';
	}

	/**
	 * Sanitize newline-delimited path prefixes.
	 *
	 * @param mixed $raw Raw input.
	 * @return string
	 */
	private function sanitize_prefix_lines( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$out   = array();
		foreach ( is_array( $lines ) ? $lines : array() as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$path = wp_parse_url( $line, PHP_URL_PATH );
			if ( is_string( $path ) && false === strpos( $line, '://' ) ) {
				$out[] = '/' . ltrim( $path, '/' );
			}
		}
		return implode( "\n", array_unique( $out ) );
	}

	/**
	 * Sanitize newline-delimited hostnames.
	 *
	 * @param mixed $raw Raw input.
	 * @return string
	 */
	private function sanitize_domain_lines( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$out   = array();
		foreach ( is_array( $lines ) ? $lines : array() as $line ) {
			$host = strtolower( trim( $line ) );
			if ( $host && 1 === preg_match( '/^(?=.{1,253}$)(?!-)(?:[a-z0-9-]{1,63}\.)*[a-z0-9-]{1,63}$/', $host ) ) {
				$out[] = $host;
			}
		}
		return implode( "\n", array_unique( $out ) );
	}

	/**
	 * Convert an array to newline-delimited scalar values.
	 *
	 * @param mixed $values Values.
	 * @return string
	 */
	private function lines_from_array( $values ) {
		return implode( "\n", array_map( 'sanitize_text_field', is_array( $values ) ? $values : array() ) );
	}
}
