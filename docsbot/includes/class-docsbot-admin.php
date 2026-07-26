<?php
/**
 * Branded WordPress admin experience.
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and processes the plugin settings screens.
 */
final class DocsBot_Admin {

	const PAGE_SLUG = 'docsbot';

	/**
	 * DocsBot API client.
	 *
	 * @var DocsBot_API
	 */
	private $api;

	/**
	 * Membership service.
	 *
	 * @var DocsBot_Memberships
	 */
	private $memberships;

	/**
	 * Constructor.
	 *
	 * @param DocsBot_API         $api         API client.
	 * @param DocsBot_Memberships $memberships Membership service.
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
		add_filter( 'plugin_action_links_' . plugin_basename( DOCSBOT_FILE ), array( $this, 'plugin_action_links' ) );

		add_action( 'admin_post_docsbot_connect', array( $this, 'save_connection' ) );
		add_action( 'admin_post_docsbot_select', array( $this, 'save_selection' ) );
		add_action( 'admin_post_docsbot_remote_content', array( $this, 'save_remote_content' ) );
		add_action( 'admin_post_docsbot_remote_design', array( $this, 'save_remote_design' ) );
		add_action( 'admin_post_docsbot_actions', array( $this, 'save_actions' ) );
		add_action( 'admin_post_docsbot_deploy', array( $this, 'save_deploy' ) );
		add_action( 'wp_ajax_docsbot_custom_button_draft', array( $this, 'ajax_custom_button_draft' ) );
	}

	/**
	 * Add the plugin menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'DocsBot Settings', 'docsbot' ),
			__( 'DocsBot', 'docsbot' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Load assets only on the DocsBot page.
	 *
	 * @param string $hook_suffix Admin hook.
	 * @return void
	 */
	public function admin_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'docsbot-admin',
			DOCSBOT_URL . 'assets/css/admin.css',
			array(),
			DOCSBOT_VERSION
		);
		wp_enqueue_media();
		wp_enqueue_script(
			'docsbot-admin',
			DOCSBOT_URL . 'assets/js/admin.js',
			array( 'media-editor' ),
			DOCSBOT_VERSION,
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
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'docsbot' ) . '</a>'
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

		$tabs = $this->tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a tab does not change state.
		$raw_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'connection';
		$tab      = isset( $tabs[ $raw_tab ] ) ? $raw_tab : 'connection';
		$settings = DocsBot_Plugin::settings();
		$feedback = $this->consume_feedback();
		?>
		<div class="wrap docsbot-admin">
			<header class="docsbot-header">
				<div class="docsbot-brand">
					<span class="docsbot-brand__mark" aria-hidden="true">
						<img src="<?php echo esc_url( DOCSBOT_URL . 'assets/images/docsbot-icon.svg' ); ?>" alt="">
					</span>
					<div>
						<h1><?php esc_html_e( 'DocsBot for WordPress', 'docsbot' ); ?></h1>
					</div>
				</div>
				<div class="docsbot-connection-pill <?php echo empty( $settings['bot_id'] ) ? 'is-idle' : 'is-connected'; ?>">
					<span aria-hidden="true"></span>
					<?php echo empty( $settings['bot_id'] ) ? esc_html__( 'Setup needed', 'docsbot' ) : esc_html__( 'Bot connected', 'docsbot' ); ?>
				</div>
			</header>

			<nav class="docsbot-tabs" aria-label="<?php esc_attr_e( 'DocsBot settings', 'docsbot' ); ?>">
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
					<?php if ( in_array( $tab, array( 'content', 'design', 'actions' ), true ) && $settings['bot_id'] ) : ?>
						<?php $this->render_widget_preview( $settings ); ?>
					<?php endif; ?>
					<div class="docsbot-card docsbot-summary">
						<p class="docsbot-eyebrow"><?php esc_html_e( 'Current bot', 'docsbot' ); ?></p>
						<h2><?php echo esc_html( $settings['bot_name'] ? $settings['bot_name'] : __( 'Not selected', 'docsbot' ) ); ?></h2>
						<?php if ( $settings['team_name'] ) : ?>
							<p><?php echo esc_html( $settings['team_name'] ); ?></p>
						<?php endif; ?>
						<dl>
							<div><dt><?php esc_html_e( 'Widget', 'docsbot' ); ?></dt><dd><?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Live', 'docsbot' ) : esc_html__( 'Paused', 'docsbot' ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Privacy', 'docsbot' ); ?></dt><dd><?php echo esc_html( ucfirst( (string) $settings['bot_privacy'] ) ); ?></dd></div>
						</dl>
					</div>
					<div class="docsbot-card docsbot-help">
						<h2><?php esc_html_e( 'Need a hand?', 'docsbot' ); ?></h2>
						<p><?php esc_html_e( 'Find setup guidance and widget documentation from the DocsBot team.', 'docsbot' ); ?></p>
						<a href="https://docsbot.ai/documentation/developer/embeddable-chat-widget" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Read widget docs', 'docsbot' ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'docsbot' ); ?></span>
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
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Step 1', 'docsbot' ); ?></p>
			<h2><?php esc_html_e( 'Connect your DocsBot account', 'docsbot' ); ?></h2>
			<p><?php esc_html_e( 'Your API key is used only by your WordPress server to load teams, bots, and settings. It is never sent to site visitors.', 'docsbot' ); ?></p>
			<?php if ( $has_key && ! is_wp_error( $teams ) ) : ?>
				<div class="docsbot-connected-account">
					<span class="docsbot-connected-account__icon" aria-hidden="true">✓</span>
					<div>
						<strong><?php esc_html_e( 'Connected securely', 'docsbot' ); ?></strong>
						<span><?php echo $constant_key ? esc_html__( 'The API key is managed in wp-config.php.', 'docsbot' ) : esc_html__( 'The encrypted API key is stored by WordPress.', 'docsbot' ); ?></span>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( ! $constant_key ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="docsbot_connect">
					<input type="hidden" name="connection_intent" value="connect">
					<?php wp_nonce_field( 'docsbot_connect' ); ?>
					<div id="docsbot-reconnect-panel" class="docsbot-field<?php echo $has_key ? ' docsbot-reconnect-panel' : ''; ?>" <?php echo $has_key ? 'hidden' : ''; ?>>
						<label for="docsbot-api-key"><?php echo $has_key ? esc_html__( 'New DocsBot API key', 'docsbot' ) : esc_html__( 'DocsBot API key', 'docsbot' ); ?></label>
						<div class="docsbot-secret">
							<input type="password" id="docsbot-api-key" name="api_key" value="" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Paste your API key', 'docsbot' ); ?>">
							<button type="button" class="button docsbot-reveal" data-reveal="docsbot-api-key" data-show-label="<?php esc_attr_e( 'Show', 'docsbot' ); ?>" data-hide-label="<?php esc_attr_e( 'Hide', 'docsbot' ); ?>" aria-controls="docsbot-api-key" aria-pressed="false"><?php esc_html_e( 'Show', 'docsbot' ); ?></button>
						</div>
						<p class="description">
							<a href="https://docsbot.ai/app/api" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Get an API key from DocsBot', 'docsbot' ); ?>
								<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'docsbot' ); ?></span>
							</a>
						</p>
						<?php $this->save_action( $has_key ? __( 'Save new API key', 'docsbot' ) : __( 'Connect account', 'docsbot' ) ); ?>
					</div>
				</form>
				<?php if ( $has_key ) : ?>
					<div class="docsbot-connection-actions">
						<button type="button" class="button docsbot-reconnect" aria-expanded="false" aria-controls="docsbot-reconnect-panel"><?php esc_html_e( 'Reconnect with a different key', 'docsbot' ); ?></button>
						<form class="docsbot-disconnect-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="docsbot_connect">
							<input type="hidden" name="connection_intent" value="disconnect">
							<?php wp_nonce_field( 'docsbot_connect' ); ?>
							<button type="submit" class="docsbot-disconnect" data-confirm="<?php esc_attr_e( 'Disconnect DocsBot and remove the saved API key, bot selection, and signing key?', 'docsbot' ); ?>"><?php esc_html_e( 'Disconnect account', 'docsbot' ); ?></button>
						</form>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<?php if ( is_wp_error( $teams ) ) : ?>
			<?php $this->inline_error( $teams->get_error_message() ); ?>
		<?php elseif ( $has_key ) : ?>
			<div class="docsbot-card">
				<p class="docsbot-eyebrow"><?php esc_html_e( 'Step 2', 'docsbot' ); ?></p>
				<h2><?php esc_html_e( 'Choose a team and bot', 'docsbot' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="docsbot_select">
					<?php wp_nonce_field( 'docsbot_select' ); ?>
					<div class="docsbot-grid docsbot-grid--2">
						<div class="docsbot-field">
							<label for="docsbot-team"><?php esc_html_e( 'Team', 'docsbot' ); ?></label>
							<select id="docsbot-team" name="team_id">
								<option value=""><?php esc_html_e( 'Select a team', 'docsbot' ); ?></option>
								<?php foreach ( $teams as $team ) : ?>
									<option value="<?php echo esc_attr( $team['id'] ); ?>" <?php selected( $settings['team_id'], $team['id'] ); ?>>
										<?php echo esc_html( $team['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="docsbot-field">
							<label for="docsbot-bot"><?php esc_html_e( 'Bot', 'docsbot' ); ?></label>
							<select id="docsbot-bot" name="bot_id" <?php disabled( empty( $settings['team_id'] ) ); ?>>
								<option value=""><?php esc_html_e( 'Select a bot', 'docsbot' ); ?></option>
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
							? esc_html__( 'Change the team and save to refresh its available bots.', 'docsbot' )
							: esc_html__( 'Choose a team first and save to load its bots.', 'docsbot' );
						?>
					</p>
					<?php $this->save_action( __( 'Use selected bot', 'docsbot' ) ); ?>
				</form>
				<?php if ( $settings['team_id'] && is_array( $bots ) && empty( $bots ) ) : ?>
					<div class="docsbot-empty">
						<h3><?php esc_html_e( 'No bots found in this team', 'docsbot' ); ?></h3>
						<p><?php esc_html_e( 'If your DocsBot role and plan allow bot creation, create one in DocsBot and return here.', 'docsbot' ); ?></p>
						<a class="button button-secondary" href="https://docsbot.ai/app/bots" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Create a bot in DocsBot', 'docsbot' ); ?>
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
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Content', 'docsbot' ); ?></p>
			<h2><?php esc_html_e( 'Shape the conversation', 'docsbot' ); ?></h2>
			<p><?php esc_html_e( 'These settings are saved to the selected bot in DocsBot and used anywhere that bot is embedded.', 'docsbot' ); ?></p>
			<?php $this->form_open( 'docsbot_remote_content' ); ?>
				<div class="docsbot-field">
					<label for="docsbot-name"><?php esc_html_e( 'Bot name', 'docsbot' ); ?></label>
					<input type="text" id="docsbot-name" name="name" maxlength="100" value="<?php echo esc_attr( $bot['name'] ?? '' ); ?>" required>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-description"><?php esc_html_e( 'Description', 'docsbot' ); ?></label>
					<textarea id="docsbot-description" name="description" rows="3" maxlength="500"><?php echo esc_textarea( $bot['description'] ?? '' ); ?></textarea>
				</div>
				<div class="docsbot-grid docsbot-grid--2">
					<?php $this->text_field( 'first_message', __( 'First message', 'docsbot' ), $labels['firstMessage'] ?? '', 500 ); ?>
					<?php $this->text_field( 'input_placeholder', __( 'Input placeholder', 'docsbot' ), $labels['inputPlaceholder'] ?? '', 120 ); ?>
					<?php $this->text_field( 'floating_button', __( 'Floating button label', 'docsbot' ), $labels['floatingButton'] ?? '', 80 ); ?>
				</div>
				<div class="docsbot-field">
					<label for="docsbot-footer-message"><?php esc_html_e( 'Footer message', 'docsbot' ); ?></label>
					<textarea id="docsbot-footer-message" name="footer_message" rows="3" maxlength="1000"><?php echo esc_textarea( $labels['footerMessage'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'A good place for privacy or acceptable-use guidance. Basic Markdown is supported by the widget.', 'docsbot' ); ?></p>
				</div>
				<div class="docsbot-toggle-grid">
					<?php $this->checkbox( 'show_button_label', __( 'Show floating button text', 'docsbot' ), ! empty( $bot['showButtonLabel'] ) ); ?>
					<?php $this->checkbox( 'show_copy_button', __( 'Let visitors copy answers', 'docsbot' ), ! empty( $bot['showCopyButton'] ) ); ?>
					<?php $this->checkbox( 'hide_sources', __( 'Hide answer sources', 'docsbot' ), ! empty( $bot['hideSources'] ) ); ?>
					<?php $this->checkbox( 'link_safety_enabled', __( 'Confirm external links', 'docsbot' ), ! empty( $settings['link_safety_enabled'] ) ); ?>
					<?php $this->checkbox( 'show_agent_activity', __( 'Agent activity', 'docsbot' ), ! empty( $settings['show_agent_activity'] ) ); ?>
					<?php $this->checkbox( 'use_image_upload', __( 'Image uploads', 'docsbot' ), ! empty( $settings['use_image_upload'] ) ); ?>
					<?php $this->checkbox( 'use_audio_upload', __( 'Voice input', 'docsbot' ), ! empty( $settings['use_audio_upload'] ) ); ?>
				</div>
				<?php $this->save_action( __( 'Save content', 'docsbot' ) ); ?>
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
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Design', 'docsbot' ); ?></p>
			<h2><?php esc_html_e( 'Match your site', 'docsbot' ); ?></h2>
			<p><?php esc_html_e( 'Use your brand color, placement, and imagery while keeping the widget accessible.', 'docsbot' ); ?></p>
			<?php $this->form_open( 'docsbot_remote_design' ); ?>
				<div class="docsbot-grid docsbot-grid--2">
					<div class="docsbot-field">
						<label for="docsbot-color"><?php esc_html_e( 'Accent color', 'docsbot' ); ?></label>
						<div class="docsbot-color">
							<input type="color" id="docsbot-color-picker" value="<?php echo esc_attr( $bot['color'] ?? '#0891b8' ); ?>" aria-label="<?php esc_attr_e( 'Choose accent color', 'docsbot' ); ?>">
							<input type="text" id="docsbot-color" name="color" value="<?php echo esc_attr( $bot['color'] ?? '#0891b8' ); ?>" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" required>
						</div>
					</div>
					<?php
					$this->select_field(
						'alignment',
						__( 'Button side', 'docsbot' ),
						array(
							'right' => __( 'Right', 'docsbot' ),
							'left'  => __( 'Left', 'docsbot' ),
						),
						$bot['alignment'] ?? 'right'
					);
					?>
					<?php
					$this->select_field(
						'header_alignment',
						__( 'Header alignment', 'docsbot' ),
						array(
							'center' => __( 'Center', 'docsbot' ),
							'left'   => __( 'Left', 'docsbot' ),
						),
						$settings['header_alignment']
					);
					?>
				</div>
				<?php
				$launcher_icons = array(
					'default'   => __( 'Comments', 'docsbot' ),
					'comments'  => __( 'Classic', 'docsbot' ),
					'robot'     => __( 'Robot', 'docsbot' ),
					'life-ring' => __( 'Life ring', 'docsbot' ),
					'question'  => __( 'Question', 'docsbot' ),
					'book'      => __( 'Book', 'docsbot' ),
				);
				$avatar_icons   = array(
					''          => __( 'None', 'docsbot' ),
					'comment'   => __( 'Comment', 'docsbot' ),
					'robot'     => __( 'Robot', 'docsbot' ),
					'life-ring' => __( 'Life ring', 'docsbot' ),
					'info'      => __( 'Info', 'docsbot' ),
					'book'      => __( 'Book', 'docsbot' ),
				);
				$this->icon_choice_field( 'icon', __( 'Button icon', 'docsbot' ), $launcher_icons, $bot['icon'] ?? 'default', 'docsbot-icon-custom' );
				$this->icon_choice_field( 'bot_icon', __( 'Bot avatar', 'docsbot' ), $avatar_icons, $bot['botIcon'] ?? '', 'docsbot-bot-icon-custom' );
				?>
				<div class="docsbot-field">
					<label for="docsbot-logo"><?php esc_html_e( 'Header logo URL', 'docsbot' ); ?></label>
					<div class="docsbot-media-field">
						<input type="url" id="docsbot-logo" name="logo" value="<?php echo esc_attr( is_string( $bot['logo'] ?? '' ) ? $bot['logo'] : '' ); ?>" placeholder="https://example.com/logo.png">
						<button type="button" class="button docsbot-choose-image" data-media-target="docsbot-logo" data-media-title="<?php esc_attr_e( 'Choose widget header logo', 'docsbot' ); ?>" data-media-button="<?php esc_attr_e( 'Use this image', 'docsbot' ); ?>"><?php esc_html_e( 'Choose image', 'docsbot' ); ?></button>
					</div>
					<p class="description"><?php esc_html_e( 'Use a public HTTPS image URL. A maximum displayed height of 36px works best.', 'docsbot' ); ?></p>
				</div>
				<div class="docsbot-toggle-grid">
					<?php $this->checkbox( 'branding', __( 'Show DocsBot branding', 'docsbot' ), ! isset( $bot['branding'] ) || ! empty( $bot['branding'] ) ); ?>
				</div>
				<?php $this->save_action( __( 'Save design', 'docsbot' ) ); ?>
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
		$bot = $this->current_bot_or_prompt( $settings );
		if ( ! is_array( $bot ) ) {
			return;
		}
		$bot_id   = isset( $bot['id'] ) && is_string( $bot['id'] ) ? $bot['id'] : (string) $settings['bot_id'];
		$draft    = get_transient( $this->actions_draft_key() );
		if ( is_array( $draft ) && hash_equals( $bot_id, (string) ( $draft['bot_id'] ?? '' ) ) ) {
			if ( isset( $draft['tools'] ) && is_array( $draft['tools'] ) ) {
				$bot['tools'] = $draft['tools'];
			}
			if ( isset( $draft['labels'] ) && is_array( $draft['labels'] ) ) {
				$bot['labels'] = $draft['labels'];
			}
			if ( isset( $draft['supportLink'] ) ) {
				$bot['supportLink'] = (string) $draft['supportLink'];
			}
			if ( isset( $draft['settings'] ) && is_array( $draft['settings'] ) ) {
				$settings = array_merge( $settings, $draft['settings'] );
			}
		}
		$labels   = isset( $bot['labels'] ) && is_array( $bot['labels'] ) ? $bot['labels'] : array();
		$bot_root = 'https://docsbot.ai/app/bots/' . rawurlencode( $bot_id ) . '/configure/';
		$skills   = $this->cached_skills( $settings['team_id'], $bot_id );
		$skills   = is_wp_error( $skills ) ? $skills : $this->api->available_widget_skills( $skills );
		$servers  = $this->api->available_mcp_servers( $bot['mcpServers'] ?? array() );
		$tools    = isset( $bot['tools'] ) && is_array( $bot['tools'] ) ? $bot['tools'] : array();
		$buttons  = isset( $tools['customButtons'] ) && is_array( $tools['customButtons'] ) ? array_values( $tools['customButtons'] ) : array();
		?>
		<div class="docsbot-card">
			<p class="docsbot-eyebrow"><?php esc_html_e( 'Actions', 'docsbot' ); ?></p>
			<h2><?php esc_html_e( 'Choose what the widget can do', 'docsbot' ); ?></h2>
			<p><?php esc_html_e( 'These WordPress embed options activate features already enabled and configured for your bot and plan in DocsBot.', 'docsbot' ); ?></p>
			<?php $this->form_open( 'docsbot_actions' ); ?>
				<div class="docsbot-option-list docsbot-action-list docsbot-action-list--built-in">
					<?php $this->option_toggle( 'use_feedback', __( 'Collect Feedback', 'docsbot' ), __( 'Collect ratings (CSAT) from users after they interact with the bot.', 'docsbot' ), $settings['use_feedback'], 'feedback' ); ?>
					<div>
						<?php $this->option_toggle( 'use_escalation', __( 'Human Support Escalation', 'docsbot' ), __( 'Allow the bot to detect when a user needs to speak to a human.', 'docsbot' ), $settings['use_escalation'], 'support' ); ?>
						<div class="docsbot-action-nested" data-depends-on="use_escalation">
							<?php $this->text_field( 'support_label', __( 'Button Label', 'docsbot' ), $labels['getSupport'] ?? '', 80 ); ?>
							<div class="docsbot-field">
								<label for="docsbot-support-link"><?php esc_html_e( 'Button Link', 'docsbot' ); ?></label>
								<input type="url" id="docsbot-support-link" name="support_link" value="<?php echo esc_attr( is_string( $bot['supportLink'] ?? '' ) ? $bot['supportLink'] : '' ); ?>" placeholder="https://example.com/support/">
							</div>
						</div>
					</div>
				</div>

				<section class="docsbot-action-category" aria-labelledby="docsbot-scheduling-title">
					<div class="docsbot-action-category__heading">
						<h3 id="docsbot-scheduling-title"><?php esc_html_e( 'Scheduling Tools', 'docsbot' ); ?><span class="docsbot-new-badge"><?php esc_html_e( 'New!', 'docsbot' ); ?></span></h3>
						<p><?php esc_html_e( 'Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.', 'docsbot' ); ?></p>
					</div>
					<div class="docsbot-action-editors" data-booking-actions>
						<?php $this->booking_action_editor( 'calendly', 'Calendly', $tools['calendly'] ?? array() ); ?>
						<?php $this->booking_action_editor( 'calcom', 'Cal.com', $tools['calcom'] ?? array() ); ?>
						<?php $this->booking_action_editor( 'tidycal', 'TidyCal', $tools['tidycal'] ?? array() ); ?>
					</div>
				</section>

				<section class="docsbot-action-category" aria-labelledby="docsbot-buttons-title">
					<div class="docsbot-action-category__heading">
						<h3 id="docsbot-buttons-title"><?php esc_html_e( 'Custom Buttons', 'docsbot' ); ?><span class="docsbot-new-badge"><?php esc_html_e( 'New!', 'docsbot' ); ?></span></h3>
						<p><?php esc_html_e( 'Let your bot show a configured button when its instructions match.', 'docsbot' ); ?></p>
					</div>
					<div class="docsbot-action-editors" data-custom-buttons data-next-index="<?php echo esc_attr( (string) count( $buttons ) ); ?>" data-max-buttons="20">
						<?php foreach ( $buttons as $index => $button ) : ?>
							<?php $this->custom_button_editor( $index, is_array( $button ) ? $button : array() ); ?>
						<?php endforeach; ?>
					</div>
					<template id="docsbot-custom-button-template"><?php $this->custom_button_editor( '__INDEX__', array() ); ?></template>
					<button type="button" class="docsbot-action-add" data-add-custom-button aria-expanded="false" aria-controls="docsbot-custom-button-prompt-panel"><span aria-hidden="true">+</span><?php esc_html_e( 'Add custom button', 'docsbot' ); ?></button>
					<div
						id="docsbot-custom-button-prompt-panel"
						class="docsbot-custom-button-prompt"
						data-custom-button-prompt
						data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'docsbot_custom_button_draft' ) ); ?>"
						data-loading-label="<?php esc_attr_e( 'Generating…', 'docsbot' ); ?>"
						data-error-label="<?php esc_attr_e( 'Unable to generate a custom button draft.', 'docsbot' ); ?>"
						data-limit-label="<?php esc_attr_e( 'A bot can have at most 20 custom buttons.', 'docsbot' ); ?>"
						hidden
					>
						<div class="docsbot-field">
							<label for="docsbot-custom-button-prompt"><?php esc_html_e( 'What should this button do?', 'docsbot' ); ?></label>
							<textarea id="docsbot-custom-button-prompt" rows="4" maxlength="2000" data-custom-button-prompt-input placeholder="<?php esc_attr_e( 'Send visitors to our pricing page when they ask about plans or cost.', 'docsbot' ); ?>"></textarea>
						</div>
						<p class="docsbot-action-load-error" data-custom-button-prompt-error role="alert" hidden></p>
						<span class="screen-reader-text" data-custom-button-prompt-status role="status" aria-live="polite"></span>
						<div class="docsbot-custom-button-prompt__actions">
							<button type="button" class="button button-primary" data-generate-custom-button><?php esc_html_e( 'Generate button', 'docsbot' ); ?></button>
							<button type="button" class="button" data-cancel-custom-button><?php esc_html_e( 'Cancel', 'docsbot' ); ?></button>
						</div>
					</div>
				</section>

				<section class="docsbot-action-category" aria-labelledby="docsbot-skills-title">
					<div class="docsbot-action-category__heading">
						<h3 id="docsbot-skills-title"><?php esc_html_e( 'Skills', 'docsbot' ); ?><span class="docsbot-new-badge"><?php esc_html_e( 'New!', 'docsbot' ); ?></span></h3>
						<p><?php esc_html_e( 'Enable bot skills to give your bot special abilities.', 'docsbot' ); ?></p>
					</div>
					<?php if ( is_wp_error( $skills ) ) : ?>
						<p class="docsbot-action-load-error" role="status"><?php echo esc_html( $skills->get_error_message() ); ?></p>
					<?php else : ?>
						<div class="docsbot-action-available">
							<?php foreach ( $skills as $skill ) : ?>
								<?php
								$skill_name = $skill['displayName'] ?? ( $skill['name'] ?? ( $skill['skillName'] ?? '' ) );
								if ( ! is_string( $skill_name ) || '' === trim( $skill_name ) ) {
									continue;
								}
								?>
								<div class="docsbot-action-available__item">
									<span class="docsbot-action-available__icon" aria-hidden="true"><?php echo $this->action_icon( 'skills' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static allowlisted SVG. ?></span>
									<strong><?php echo esc_html( $skill_name ); ?></strong>
									<button type="submit" class="button-link-delete docsbot-action-remove" name="remove_skill" value="<?php echo esc_attr( (string) ( $skill['id'] ?? '' ) ); ?>"><?php esc_html_e( 'Remove', 'docsbot' ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<a class="docsbot-action-add" href="<?php echo esc_url( $bot_root . 'skills' ); ?>" target="_blank" rel="noopener noreferrer">
						<span aria-hidden="true">+</span><?php esc_html_e( 'Add skill in DocsBot', 'docsbot' ); ?>
					</a>
				</section>

				<section class="docsbot-action-category" aria-labelledby="docsbot-mcp-title">
					<div class="docsbot-action-category__heading">
						<h3 id="docsbot-mcp-title"><?php esc_html_e( 'MCP Servers', 'docsbot' ); ?><span class="docsbot-new-badge"><?php esc_html_e( 'New!', 'docsbot' ); ?></span></h3>
						<p><?php esc_html_e( 'Connect your bot to external tools and data from your services.', 'docsbot' ); ?></p>
					</div>
					<div class="docsbot-action-available">
						<?php foreach ( $servers as $server ) : ?>
							<?php
							$server_name = $server['serverLabel'] ?? ( $server['name'] ?? '' );
							if ( ! is_string( $server_name ) || '' === trim( $server_name ) ) {
								continue;
							}
							?>
							<div class="docsbot-action-available__item">
								<span class="docsbot-action-available__icon" aria-hidden="true"><?php echo $this->action_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static allowlisted SVG. ?></span>
								<strong><?php echo esc_html( $server_name ); ?></strong>
								<button type="submit" class="button-link-delete docsbot-action-remove" name="remove_mcp" value="<?php echo esc_attr( (string) ( $server['id'] ?? '' ) ); ?>"><?php esc_html_e( 'Remove', 'docsbot' ); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<a class="docsbot-action-add" href="<?php echo esc_url( $bot_root . 'mcp-connections' ); ?>" target="_blank" rel="noopener noreferrer">
						<span aria-hidden="true">+</span><?php esc_html_e( 'Add MCP server in DocsBot', 'docsbot' ); ?>
					</a>
				</section>

				<div class="docsbot-action-footer">
					<?php $this->option_toggle( 'use_web_search', __( 'Web Search', 'docsbot' ), __( 'Allow agent-mode web search when your plan and bot support it.', 'docsbot' ), $settings['use_web_search'], 'globe' ); ?>
				</div>
				<?php $this->save_action( __( 'Save actions', 'docsbot' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a complete scheduling provider editor.
	 *
	 * @param string              $provider Provider key.
	 * @param string              $label    Provider label.
	 * @param array<string,mixed> $action   Provider settings.
	 * @return void
	 */
	private function booking_action_editor( $provider, $label, $action ) {
		$enabled      = true === ( $action['enabled'] ?? false );
		$instructions = is_string( $action['instructions'] ?? '' ) ? $action['instructions'] : '';
		$url          = is_string( $action['url'] ?? '' ) ? $action['url'] : '';
		$logo         = DOCSBOT_URL . 'assets/images/scheduling-icons/' . $provider . '.svg';
		?>
		<div class="docsbot-action-editor <?php echo esc_attr( $enabled ? 'is-enabled' : '' ); ?>" data-booking-action>
			<div class="docsbot-action-editor__header">
				<img src="<?php echo esc_url( $logo ); ?>" alt="" width="36" height="36">
				<span><strong><?php echo esc_html( $label ); ?></strong><small><?php esc_html_e( 'Booking action', 'docsbot' ); ?></small></span>
				<span class="docsbot-switch"><input type="checkbox" name="booking[<?php echo esc_attr( $provider ); ?>][enabled]" value="1" data-booking-toggle <?php checked( $enabled ); ?>><span aria-hidden="true"></span></span>
			</div>
			<div class="docsbot-action-editor__body">
				<div class="docsbot-field">
					<label for="docsbot-<?php echo esc_attr( $provider ); ?>-url"><?php echo esc_html( $label ); ?> <?php esc_html_e( 'URL', 'docsbot' ); ?></label>
					<input type="text" id="docsbot-<?php echo esc_attr( $provider ); ?>-url" name="booking[<?php echo esc_attr( $provider ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" maxlength="500" placeholder="https://<?php echo esc_attr( 'calendly' === $provider ? 'calendly.com/your-name/meeting' : ( 'calcom' === $provider ? 'cal.com/your-name/meeting' : 'tidycal.com/your-name/meeting' ) ); ?>">
				</div>
				<div class="docsbot-field">
					<label for="docsbot-<?php echo esc_attr( $provider ); ?>-instructions"><?php esc_html_e( 'When to trigger', 'docsbot' ); ?></label>
					<textarea id="docsbot-<?php echo esc_attr( $provider ); ?>-instructions" name="booking[<?php echo esc_attr( $provider ); ?>][instructions]" rows="3" maxlength="2000"><?php echo esc_textarea( $instructions ); ?></textarea>
				</div>
				<div class="docsbot-toggle-grid">
					<?php $this->checkbox( 'booking[' . $provider . '][hideEventDetails]', 'tidycal' === $provider ? __( 'Hide the profile avatar', 'docsbot' ) : __( 'Hide event details', 'docsbot' ), ! empty( $action['hideEventDetails'] ) ); ?>
					<?php if ( 'calendly' === $provider ) : ?>
						<?php $this->checkbox( 'booking[calendly][hideCookieBanner]', __( 'Hide the cookie banner', 'docsbot' ), ! empty( $action['hideCookieBanner'] ) ); ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a complete custom button editor.
	 *
	 * @param int|string          $index  Array index or template placeholder.
	 * @param array<string,mixed> $button Button settings.
	 * @return void
	 */
	private function custom_button_editor( $index, $button ) {
		$prefix = 'custom_buttons[' . $index . ']';
		$id     = 'docsbot-custom-button-' . $index . '-';
		$name   = is_string( $button['name'] ?? '' ) ? $button['name'] : '';
		$icons  = $this->custom_button_icon_options();
		$icon   = isset( $icons[ $button['icon'] ?? '' ] ) ? $button['icon'] : 'LinkIcon';
		?>
		<div class="docsbot-action-editor docsbot-custom-button-editor <?php echo ! empty( $button['enabled'] ) ? 'is-enabled' : ''; ?>" data-new-title="<?php esc_attr_e( 'New custom button', 'docsbot' ); ?>">
			<div class="docsbot-action-editor__header">
				<span class="docsbot-action-available__icon" aria-hidden="true" data-custom-button-header-icon><?php echo $this->custom_button_icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static allowlisted SVG. ?></span>
				<span><strong data-custom-button-title><?php echo esc_html( $name ? $name : __( 'New custom button', 'docsbot' ) ); ?></strong><small><?php esc_html_e( 'Custom button', 'docsbot' ); ?></small></span>
				<span class="docsbot-switch"><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[enabled]" value="1" <?php checked( ! empty( $button['enabled'] ) ); ?>><span aria-hidden="true"></span></span>
			</div>
			<div class="docsbot-action-editor__body">
				<div class="docsbot-grid docsbot-grid--2">
					<div class="docsbot-field"><label for="<?php echo esc_attr( $id . 'name' ); ?>"><?php esc_html_e( 'Name', 'docsbot' ); ?></label><input id="<?php echo esc_attr( $id . 'name' ); ?>" type="text" name="<?php echo esc_attr( $prefix ); ?>[name]" value="<?php echo esc_attr( $name ); ?>" maxlength="100" data-custom-button-name></div>
					<div class="docsbot-field"><label for="<?php echo esc_attr( $id . 'key' ); ?>"><?php esc_html_e( 'Key', 'docsbot' ); ?></label><div class="docsbot-input-prefix"><span>button_</span><input id="<?php echo esc_attr( $id . 'key' ); ?>" type="text" name="<?php echo esc_attr( $prefix ); ?>[functionKey]" value="<?php echo esc_attr( preg_replace( '/^button_/', '', is_string( $button['functionKey'] ?? '' ) ? $button['functionKey'] : '' ) ); ?>" maxlength="64" pattern="[a-z0-9]+(?:_[a-z0-9]+)*"></div></div>
				</div>
				<div class="docsbot-field"><label for="<?php echo esc_attr( $id . 'instructions' ); ?>"><?php esc_html_e( 'When to use', 'docsbot' ); ?></label><textarea id="<?php echo esc_attr( $id . 'instructions' ); ?>" name="<?php echo esc_attr( $prefix ); ?>[instructions]" rows="3" maxlength="2000"><?php echo esc_textarea( is_string( $button['instructions'] ?? '' ) ? $button['instructions'] : '' ); ?></textarea></div>
				<div class="docsbot-grid docsbot-grid--2">
					<div class="docsbot-field"><label for="<?php echo esc_attr( $id . 'text' ); ?>"><?php esc_html_e( 'Button text', 'docsbot' ); ?></label><input id="<?php echo esc_attr( $id . 'text' ); ?>" type="text" name="<?php echo esc_attr( $prefix ); ?>[buttonText]" value="<?php echo esc_attr( is_string( $button['buttonText'] ?? '' ) ? $button['buttonText'] : '' ); ?>" maxlength="100"></div>
					<div class="docsbot-field">
						<label id="<?php echo esc_attr( $id . 'icon-label' ); ?>"><?php esc_html_e( 'Icon', 'docsbot' ); ?></label>
						<div class="docsbot-icon-picker" data-icon-picker>
							<button type="button" class="docsbot-icon-picker__trigger" data-icon-picker-trigger aria-haspopup="listbox" aria-expanded="false" aria-labelledby="<?php echo esc_attr( $id . 'icon-label ' . $id . 'icon-value' ); ?>" aria-controls="<?php echo esc_attr( $id . 'icon-options' ); ?>">
								<span data-icon-picker-preview><?php echo $this->custom_button_icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static allowlisted SVG. ?></span>
								<span id="<?php echo esc_attr( $id . 'icon-value' ); ?>" data-icon-picker-label><?php echo esc_html( $icons[ $icon ] ); ?></span>
								<span aria-hidden="true">⌄</span>
							</button>
							<div id="<?php echo esc_attr( $id . 'icon-options' ); ?>" class="docsbot-icon-picker__options" data-icon-picker-options role="listbox" aria-labelledby="<?php echo esc_attr( $id . 'icon-label' ); ?>" hidden>
								<?php foreach ( $icons as $option_icon => $icon_label ) : ?>
									<button type="button" role="option" data-icon-picker-option data-icon="<?php echo esc_attr( $option_icon ); ?>" data-label="<?php echo esc_attr( $icon_label ); ?>" aria-selected="<?php echo $option_icon === $icon ? 'true' : 'false'; ?>" tabindex="<?php echo $option_icon === $icon ? '0' : '-1'; ?>">
										<?php echo $this->custom_button_icon_svg( $option_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static allowlisted SVG. ?>
										<span><?php echo esc_html( $icon_label ); ?></span>
									</button>
								<?php endforeach; ?>
							</div>
							<select name="<?php echo esc_attr( $prefix ); ?>[icon]" data-icon-picker-select hidden aria-hidden="true" tabindex="-1"><?php foreach ( $icons as $option_icon => $icon_label ) : ?><option value="<?php echo esc_attr( $option_icon ); ?>" <?php selected( $icon, $option_icon ); ?>><?php echo esc_html( $icon_label ); ?></option><?php endforeach; ?></select>
						</div>
					</div>
				</div>
				<div class="docsbot-field"><label for="<?php echo esc_attr( $id . 'url' ); ?>"><?php esc_html_e( 'URL', 'docsbot' ); ?></label><input id="<?php echo esc_attr( $id . 'url' ); ?>" type="text" name="<?php echo esc_attr( $prefix ); ?>[url]" value="<?php echo esc_attr( is_string( $button['url'] ?? '' ) ? $button['url'] : '' ); ?>" maxlength="2048" pattern="[A-Za-z][A-Za-z0-9+.-]*:.+" placeholder="https://example.com/"></div>
				<button type="button" class="button-link-delete docsbot-remove-custom-button"><?php esc_html_e( 'Remove button', 'docsbot' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Return the same Heroicon allowlist accepted by the DocsBot dashboard.
	 *
	 * @return array<string,string>
	 */
	private function custom_button_icon_options() {
		static $out = null;
		if ( is_array( $out ) ) {
			return $out;
		}

		$icons = array(
			'AcademicCapIcon', 'AdjustmentsHorizontalIcon', 'ArchiveBoxIcon', 'ArrowTrendingUpIcon', 'ArrowPathRoundedSquareIcon',
			'AtSymbolIcon', 'BanknotesIcon', 'BeakerIcon', 'BoltIcon', 'BellIcon', 'BookOpenIcon', 'BookmarkIcon', 'BriefcaseIcon',
			'BuildingLibraryIcon', 'BuildingOffice2Icon', 'BuildingStorefrontIcon', 'BugAntIcon', 'CakeIcon', 'CalculatorIcon',
			'CalendarIcon', 'CameraIcon', 'ChartBarIcon', 'ChartPieIcon', 'ChatBubbleLeftIcon', 'CheckCircleIcon', 'CloudIcon',
			'ClockIcon', 'CodeBracketIcon', 'CogIcon', 'CommandLineIcon', 'ComputerDesktopIcon', 'CpuChipIcon', 'CreditCardIcon',
			'CubeIcon', 'DevicePhoneMobileIcon', 'DocumentTextIcon', 'EnvelopeIcon', 'ExclamationCircleIcon', 'EyeIcon',
			'FaceSmileIcon', 'FilmIcon', 'FingerPrintIcon', 'FireIcon', 'FlagIcon', 'FolderIcon', 'GiftIcon', 'GlobeAltIcon',
			'GlobeAsiaAustraliaIcon', 'HandRaisedIcon', 'HashtagIcon', 'HeartIcon', 'HomeIcon', 'HomeModernIcon',
			'IdentificationIcon', 'InformationCircleIcon', 'KeyIcon', 'LanguageIcon', 'LightBulbIcon', 'LinkIcon', 'ListBulletIcon',
			'LockClosedIcon', 'MagnifyingGlassIcon', 'MapPinIcon', 'MegaphoneIcon', 'MicrophoneIcon', 'MoonIcon', 'NewspaperIcon',
			'PaintBrushIcon', 'PaperAirplaneIcon', 'PencilIcon', 'PhoneIcon', 'PhotoIcon', 'PlayIcon', 'PresentationChartBarIcon',
			'PrinterIcon', 'PuzzlePieceIcon', 'RadioIcon', 'RectangleGroupIcon', 'RocketLaunchIcon', 'ScaleIcon', 'ScissorsIcon',
			'ServerIcon', 'ShieldCheckIcon', 'ShoppingBagIcon', 'ShoppingCartIcon', 'SignalIcon', 'SparklesIcon', 'SpeakerWaveIcon',
			'StarIcon', 'SunIcon', 'TicketIcon', 'TrashIcon', 'TrophyIcon', 'TruckIcon', 'TvIcon', 'UserCircleIcon', 'UserGroupIcon',
			'VideoCameraIcon', 'WalletIcon', 'WifiIcon', 'WrenchIcon',
		);
		$out = array();
		foreach ( $icons as $icon ) {
			$label        = preg_replace( '/Icon$/', '', $icon );
			$label        = preg_replace( '/(?<!^)([A-Z])/', ' $1', $label );
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Fixed allowlisted labels are included in the bundled POT and locale catalogs.
			$out[ $icon ] = __( trim( (string) $label ), 'docsbot' );
		}
		return $out;
	}

	/**
	 * Render an allowlisted dashboard Heroicon.
	 *
	 * @param string $icon Icon identifier.
	 * @return string
	 */
	private function custom_button_icon_svg( $icon ) {
		$icons = $this->custom_button_icon_options();
		$icon  = isset( $icons[ $icon ] ) ? $icon : 'LinkIcon';
		$href  = DOCSBOT_URL . 'assets/images/heroicons.svg#' . $icon;
		return '<svg class="docsbot-heroicon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="' . esc_url( $href ) . '"></use></svg>';
	}

	/**
	 * Normalize a stored custom-button key without its runtime button_ prefix.
	 *
	 * @param string $value Raw key.
	 * @return string
	 */
	private function normalize_custom_button_key( $value ) {
		$key = strtolower( trim( sanitize_text_field( $value ) ) );
		$key = preg_replace( '/^button_/', '', $key );
		if ( strlen( $key ) > 64 ) {
			return '';
		}
		return 1 === preg_match( '/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $key ) ? $key : '';
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
				<p class="docsbot-eyebrow"><?php esc_html_e( 'Deploy', 'docsbot' ); ?></p>
				<h2><?php esc_html_e( 'Put your bot to work', 'docsbot' ); ?></h2>
			</div>
			<span class="docsbot-status-badge <?php echo ! empty( $settings['enabled'] ) ? 'is-live' : ''; ?>">
				<?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Live', 'docsbot' ) : esc_html__( 'Paused', 'docsbot' ); ?>
			</span>
		</div>
		<form class="docsbot-deploy-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-unsaved-message="<?php esc_attr_e( 'You have unsaved deployment changes. Leave this page anyway?', 'docsbot' ); ?>">
			<input type="hidden" name="action" value="docsbot_deploy">
			<?php wp_nonce_field( 'docsbot_deploy' ); ?>
			<div class="docsbot-sticky-save" hidden>
				<span aria-live="polite"><?php esc_html_e( 'Deployment changes are not saved yet.', 'docsbot' ); ?></span>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save deployment', 'docsbot' ); ?></button>
			</div>
			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Widget availability', 'docsbot' ); ?></h2>
				<?php $this->option_toggle( 'enabled', __( 'Enable the widget', 'docsbot' ), __( 'Turn this off to pause the WordPress embed without changing your DocsBot bot.', 'docsbot' ), $settings['enabled'] ); ?>
				<div class="docsbot-grid docsbot-grid--2">
					<div class="docsbot-field">
						<label for="docsbot-includes"><?php esc_html_e( 'Only show on URL prefixes', 'docsbot' ); ?></label>
						<textarea id="docsbot-includes" name="include_prefixes" rows="5" placeholder="/docs/&#10;/account/"><?php echo esc_textarea( $settings['include_prefixes'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One path prefix per line. These control placement, not authorization; use the Audience controls below to protect access.', 'docsbot' ); ?></p>
					</div>
					<div class="docsbot-field">
						<label for="docsbot-excludes"><?php esc_html_e( 'Never show on URL prefixes', 'docsbot' ); ?></label>
						<textarea id="docsbot-excludes" name="exclude_prefixes" rows="5" placeholder="/checkout/&#10;/privacy/"><?php echo esc_textarea( $settings['exclude_prefixes'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Exclusions always win over inclusions.', 'docsbot' ); ?></p>
					</div>
				</div>
			</div>

			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Audience', 'docsbot' ); ?></h2>
				<?php $this->checkbox( 'logged_in_only', __( 'Only show to logged-in WordPress users', 'docsbot' ), $settings['logged_in_only'] ); ?>
				<fieldset class="docsbot-fieldset">
					<legend><?php esc_html_e( 'Allowed WordPress roles', 'docsbot' ); ?></legend>
					<p class="description"><?php esc_html_e( 'Leave every role unchecked to allow all roles.', 'docsbot' ); ?></p>
					<div class="docsbot-toggle-grid">
						<?php foreach ( $roles as $role_id => $role_name ) : ?>
							<?php $this->checkbox( 'allowed_roles[]', translate_user_role( $role_name ), in_array( $role_id, (array) $settings['allowed_roles'], true ), $role_id ); ?>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<div class="docsbot-grid docsbot-grid--2">
					<?php $this->select_field( 'membership_provider', __( 'Membership integration', 'docsbot' ), $providers, $settings['membership_provider'] ); ?>
					<div class="docsbot-field">
						<label for="docsbot-membership-rule"><?php esc_html_e( 'Plan, product, or role IDs', 'docsbot' ); ?></label>
						<input type="text" id="docsbot-membership-rule" name="membership_rule" value="<?php echo esc_attr( $settings['membership_rule'] ); ?>" placeholder="gold, 42">
						<p class="description"><?php esc_html_e( 'Comma-separated. Leave empty for any active membership; Ultimate Member requires role slugs.', 'docsbot' ); ?></p>
					</div>
				</div>
				<?php if ( 'none' !== $settings['membership_provider'] && ! $this->memberships->is_available( $settings['membership_provider'] ) ) : ?>
					<div class="docsbot-inline-message docsbot-inline-message--warning" role="status"><p><?php esc_html_e( 'The selected membership integration is not currently available. The widget will fail closed until it is active.', 'docsbot' ); ?></p></div>
				<?php endif; ?>
			</div>

			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Conversation identity', 'docsbot' ); ?></h2>
				<p><?php esc_html_e( 'Sharing identity makes DocsBot conversations non-anonymous. Each field is optional and is sent only after the visitor passes your access rules.', 'docsbot' ); ?></p>
				<div class="docsbot-toggle-grid">
					<?php $this->checkbox( 'share_name', __( 'Share display name', 'docsbot' ), $settings['share_name'] ); ?>
					<?php $this->checkbox( 'share_email', __( 'Share email address', 'docsbot' ), $settings['share_email'] ); ?>
					<?php $this->checkbox( 'share_user_id', __( 'Share pseudonymous site user ID', 'docsbot' ), $settings['share_user_id'] ); ?>
				</div>
				<p class="description"><?php esc_html_e( 'Review the suggested DocsBot text under Settings → Privacy before enabling identity fields.', 'docsbot' ); ?></p>
			</div>

			<div class="docsbot-card">
				<h2><?php esc_html_e( 'Private bot signing', 'docsbot' ); ?></h2>
				<?php if ( 'private' === $settings['bot_privacy'] ) : ?>
					<?php if ( $constant_signature || $settings['signature_key'] ) : ?>
						<div class="docsbot-signing-status is-ready"><span aria-hidden="true">✓</span><div><strong><?php esc_html_e( 'Private signing is ready', 'docsbot' ); ?></strong><p><?php esc_html_e( 'The bot signing key was retrieved securely and encrypted on this WordPress server. Visitors receive only short-lived signed tokens.', 'docsbot' ); ?></p></div></div>
					<?php else : ?>
						<div class="docsbot-signing-status is-warning"><span aria-hidden="true">!</span><div><strong><?php esc_html_e( 'Signing key unavailable', 'docsbot' ); ?></strong><p><?php esc_html_e( 'Reconnect this bot to retrieve its signing key. DocsBot will keep the widget paused until private signing is ready.', 'docsbot' ); ?></p></div></div>
					<?php endif; ?>
					<div class="docsbot-field docsbot-field--short">
						<label for="docsbot-jwt-ttl"><?php esc_html_e( 'Token lifetime (seconds)', 'docsbot' ); ?></label>
						<input type="number" id="docsbot-jwt-ttl" name="jwt_ttl" min="300" max="3600" step="60" value="<?php echo esc_attr( $settings['jwt_ttl'] ); ?>">
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'The selected bot is public, so no signature key is needed or retained.', 'docsbot' ); ?></p>
				<?php endif; ?>
				<a href="https://docsbot.ai/app/bots" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open bot embed settings in DocsBot', 'docsbot' ); ?></a>
			</div>
			<?php $this->save_action( __( 'Save deployment', 'docsbot' ), 'docsbot-save-large' ); ?>
		</form>
		<?php
	}

	/**
	 * Save or remove the API key.
	 *
	 * @return void
	 */
	public function save_connection() {
		$this->guard( 'docsbot_connect' );
		check_admin_referer( 'docsbot_connect' );
		$previous_settings = DocsBot_Plugin::settings();

		if ( $this->has_api_key_constant() ) {
			$this->redirect_feedback( 'connection', 'success', __( 'The connection is managed through wp-config.php.', 'docsbot' ) );
		}

		$intent = isset( $_POST['connection_intent'] ) ? sanitize_key( wp_unslash( $_POST['connection_intent'] ) ) : 'connect';
		if ( 'disconnect' === $intent ) {
			DocsBot_Plugin::update_settings(
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
			$this->clear_cache( $previous_settings['team_id'], $previous_settings['bot_id'] );
			$this->redirect_feedback( 'connection', 'success', __( 'DocsBot has been disconnected.', 'docsbot' ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( '' === $api_key ) {
			$this->redirect_feedback( 'connection', 'error', __( 'Enter an API key to connect DocsBot.', 'docsbot' ) );
		}

		$teams = $this->api->list_teams( $api_key );
		if ( is_wp_error( $teams ) ) {
			$this->redirect_feedback( 'connection', 'error', $teams->get_error_message() );
		}

		$encrypted = DocsBot_Crypto::encrypt( $api_key );
		if ( is_wp_error( $encrypted ) ) {
			$this->redirect_feedback( 'connection', 'error', $encrypted->get_error_message() );
		}

		DocsBot_Plugin::update_settings(
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
		$this->clear_cache( $previous_settings['team_id'], $previous_settings['bot_id'] );
		set_transient( 'docsbot_teams', $teams, 5 * MINUTE_IN_SECONDS );
		$this->redirect_feedback( 'connection', 'success', __( 'DocsBot connected. Now choose a team and bot.', 'docsbot' ) );
	}

	/**
	 * Save selected team/bot and pull the bot settings.
	 *
	 * @return void
	 */
	public function save_selection() {
		$this->guard( 'docsbot_select' );
		check_admin_referer( 'docsbot_select' );
		$previous_settings = DocsBot_Plugin::settings();

		$team_id = isset( $_POST['team_id'] ) ? sanitize_text_field( wp_unslash( $_POST['team_id'] ) ) : '';
		$bot_id  = isset( $_POST['bot_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bot_id'] ) ) : '';
		$teams   = $this->api->list_teams();
		if ( is_wp_error( $teams ) ) {
			$this->redirect_feedback( 'connection', 'error', $teams->get_error_message() );
		}

		$team = $this->find_by_id( $teams, $team_id );
		if ( ! $team ) {
			$this->redirect_feedback( 'connection', 'error', __( 'Select a valid DocsBot team.', 'docsbot' ) );
		}

		$bots = $this->api->list_bots( $team_id );
		if ( is_wp_error( $bots ) ) {
			$this->redirect_feedback( 'connection', 'error', $bots->get_error_message() );
		}
		foreach ( $bots as &$bot_item ) {
			if ( is_array( $bot_item ) ) {
				unset( $bot_item['signatureKey'] );
			}
		}
		unset( $bot_item );

		if ( '' === $bot_id ) {
			DocsBot_Plugin::update_settings(
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
			$this->clear_cache( $previous_settings['team_id'], $previous_settings['bot_id'] );
			set_transient( 'docsbot_bots_' . md5( $team_id ), $bots, 5 * MINUTE_IN_SECONDS );
			$this->redirect_feedback( 'connection', 'success', empty( $bots ) ? __( 'Team selected. Create a bot in DocsBot to continue.', 'docsbot' ) : __( 'Team selected. Now choose a bot.', 'docsbot' ) );
		}

		$listed_bot = $this->find_by_id( $bots, $bot_id );
		if ( ! $listed_bot ) {
			$this->redirect_feedback( 'connection', 'error', __( 'Select a valid DocsBot bot.', 'docsbot' ) );
		}

		$bot = $this->api->get_bot( $team_id, $bot_id );
		if ( is_wp_error( $bot ) ) {
			$this->redirect_feedback( 'connection', 'error', $bot->get_error_message() );
		}

		$settings          = $previous_settings;
		$changes           = array(
			'team_id'             => $team_id,
			'team_name'           => sanitize_text_field( $team['name'] ),
			'bot_id'              => $bot_id,
			'bot_name'            => sanitize_text_field( $bot['name'] ?? $listed_bot['name'] ),
			'bot_privacy'         => in_array( $bot['privacy'] ?? 'public', array( 'public', 'private' ), true ) ? $bot['privacy'] : 'public',
			'allowed_domains'     => $this->lines_from_array( $bot['allowedDomains'] ?? array() ),
			'header_alignment'    => in_array( $bot['headerAlignment'] ?? 'center', array( 'left', 'center' ), true ) ? $bot['headerAlignment'] : 'center',
			'link_safety_enabled' => ! empty( $bot['linkSafetyEnabled'] ),
			'use_image_upload'    => ! empty( $bot['imageUploads'] ),
			'use_audio_upload'    => ! empty( $bot['audioUploads'] ),
		);
		$signature_key     = isset( $bot['signatureKey'] ) && is_string( $bot['signatureKey'] )
			? trim( $bot['signatureKey'] )
			: '';
		$selection_changed = $team_id !== $settings['team_id'] || $bot_id !== $settings['bot_id'];
		if ( $selection_changed ) {
			$changes['signature_key'] = '';
			$changes['enabled']       = false;
		}
		if ( 'private' === $changes['bot_privacy'] && '' !== $signature_key && ! $this->has_signature_key_constant() ) {
			$encrypted_signature = DocsBot_Crypto::encrypt( $signature_key );
			if ( is_wp_error( $encrypted_signature ) ) {
				$this->redirect_feedback( 'connection', 'error', $encrypted_signature->get_error_message() );
			}
			$changes['signature_key'] = $encrypted_signature;
		} elseif ( 'public' === $changes['bot_privacy'] ) {
			$changes['signature_key'] = '';
		}
		unset( $bot['signatureKey'] );
		DocsBot_Plugin::update_settings( $changes );
		$this->clear_cache( $previous_settings['team_id'], $previous_settings['bot_id'] );
		set_transient( 'docsbot_bot_' . md5( $team_id . '|' . $bot_id ), $bot, 5 * MINUTE_IN_SECONDS );
		$this->redirect_feedback( 'connection', 'success', __( 'Bot connected and settings loaded.', 'docsbot' ) );
	}

	/**
	 * Save remote content settings.
	 *
	 * @return void
	 */
	public function save_remote_content() {
		$this->guard( 'docsbot_remote_content' );
		check_admin_referer( 'docsbot_remote_content' );
		$settings = DocsBot_Plugin::settings();
		$bot      = $this->cached_bot( $settings['team_id'], $settings['bot_id'] );
		if ( is_wp_error( $bot ) ) {
			$this->redirect_feedback( 'content', 'error', $bot->get_error_message() );
		}
		$labels = is_array( $bot ) && isset( $bot['labels'] ) && is_array( $bot['labels'] ) ? $bot['labels'] : array();
		$labels = array_merge(
			$labels,
			array(
				'firstMessage'     => $this->post_textarea( 'first_message', 500 ),
				'inputPlaceholder' => $this->post_text( 'input_placeholder', 120 ),
				'floatingButton'   => $this->post_text( 'floating_button', 80 ),
				'footerMessage'    => $this->post_textarea( 'footer_message', 1000 ),
			)
		);
		$result = $this->api->update_bot(
			$settings['team_id'],
			$settings['bot_id'],
			array(
				'name'              => $this->post_text( 'name', 100 ),
				'description'       => $this->post_textarea( 'description', 500 ),
				'showButtonLabel'   => $this->posted_bool( 'show_button_label' ),
				'showCopyButton'    => $this->posted_bool( 'show_copy_button' ),
				'hideSources'       => $this->posted_bool( 'hide_sources' ),
				'linkSafetyEnabled' => $this->posted_bool( 'link_safety_enabled' ),
				'labels'            => $labels,
			)
		);
		if ( ! is_wp_error( $result ) ) {
			DocsBot_Plugin::update_settings(
				array(
					'link_safety_enabled' => $this->posted_bool( 'link_safety_enabled' ),
					'show_agent_activity' => $this->posted_bool( 'show_agent_activity' ),
					'use_image_upload'    => $this->posted_bool( 'use_image_upload' ),
					'use_audio_upload'    => $this->posted_bool( 'use_audio_upload' ),
				)
			);
		}
		$this->finish_remote_save( 'content', $result, __( 'Content saved to DocsBot.', 'docsbot' ) );
	}

	/**
	 * Save remote design settings and local embed-only design overrides.
	 *
	 * @return void
	 */
	public function save_remote_design() {
		$this->guard( 'docsbot_remote_design' );
		check_admin_referer( 'docsbot_remote_design' );
		$settings         = DocsBot_Plugin::settings();
		$color            = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '';
		$alignment        = $this->posted_enum( 'alignment', array( 'left', 'right' ), 'right' );
		$header_alignment = $this->posted_enum( 'header_alignment', array( 'left', 'center' ), 'center' );
		$icon_choice      = $this->posted_enum( 'icon', array( 'default', 'comments', 'robot', 'life-ring', 'question', 'book', 'custom' ), 'default' );
		$bot_icon_choice  = $this->posted_enum( 'bot_icon', array( '', 'comment', 'robot', 'life-ring', 'info', 'book', 'custom' ), '' );
		$icon             = 'custom' === $icon_choice ? $this->post_url( 'icon_custom' ) : $icon_choice;
		$bot_icon         = 'custom' === $bot_icon_choice ? $this->post_url( 'bot_icon_custom' ) : $bot_icon_choice;
		$logo             = $this->post_url( 'logo' );

		if ( ! $color ) {
			$this->redirect_feedback( 'design', 'error', __( 'Enter a valid six-digit hex color.', 'docsbot' ) );
		}
		if ( 'custom' === $icon_choice && '' === $icon ) {
			$this->redirect_feedback( 'design', 'error', __( 'Choose a valid HTTPS button icon image.', 'docsbot' ) );
		}
		if ( 'custom' === $bot_icon_choice && '' === $bot_icon ) {
			$this->redirect_feedback( 'design', 'error', __( 'Choose a valid HTTPS bot avatar image.', 'docsbot' ) );
		}

		$result = $this->api->update_bot(
			$settings['team_id'],
			$settings['bot_id'],
			array(
				'color'           => $color,
				'icon'            => $icon,
				'alignment'       => $alignment,
				'botIcon'         => '' === $bot_icon ? false : $bot_icon,
				'logo'            => '' === $logo ? false : $logo,
				'headerAlignment' => $header_alignment,
				'branding'        => $this->posted_bool( 'branding' ),
			)
		);

		if ( ! is_wp_error( $result ) ) {
			DocsBot_Plugin::update_settings(
				array(
					'header_alignment' => $header_alignment,
				)
			);
		}
		$this->finish_remote_save( 'design', $result, __( 'Design saved to DocsBot.', 'docsbot' ) );
	}

	/**
	 * Generate a custom button draft without saving the bot.
	 *
	 * @return void
	 */
	public function ajax_custom_button_draft() {
		check_ajax_referer( 'docsbot_custom_button_draft', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to manage DocsBot settings.', 'docsbot' ) ), 403 );
		}

		$settings = DocsBot_Plugin::settings();
		if ( empty( $settings['team_id'] ) || empty( $settings['bot_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Connect a DocsBot bot first.', 'docsbot' ) ), 400 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- AJAX nonce verified above.
		$input = isset( $_POST['input'] ) ? sanitize_textarea_field( wp_unslash( $_POST['input'] ) ) : '';
		$input = function_exists( 'mb_substr' ) ? mb_substr( $input, 0, 2000 ) : substr( $input, 0, 2000 );
		if ( '' === trim( $input ) ) {
			wp_send_json_error( array( 'message' => __( 'Describe what the custom button should do.', 'docsbot' ) ), 400 );
		}

		$result = $this->api->draft_custom_button( $settings['team_id'], $settings['bot_id'], $input );
		if ( is_wp_error( $result ) ) {
			$data   = $result->get_error_data();
			$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 500;
			wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
		}

		$name         = sanitize_text_field( (string) ( $result['name'] ?? '' ) );
		$key          = $this->normalize_custom_button_key( (string) ( $result['functionKey'] ?? '' ) );
		$instructions = sanitize_textarea_field( (string) ( $result['instructions'] ?? '' ) );
		$button_text  = sanitize_text_field( (string) ( $result['buttonText'] ?? '' ) );
		$icons        = $this->custom_button_icon_options();
		$icon         = sanitize_text_field( (string) ( $result['icon'] ?? '' ) );
		if ( '' === $name || '' === $key || '' === $instructions || '' === $button_text ) {
			wp_send_json_error( array( 'message' => __( 'DocsBot returned an incomplete custom button draft.', 'docsbot' ) ), 502 );
		}

		wp_send_json_success(
			array(
				'enabled'      => true,
				'name'         => $name,
				'functionKey'  => $key,
				'instructions' => $instructions,
				'buttonText'   => $button_text,
				'icon'         => isset( $icons[ $icon ] ) ? $icon : 'LinkIcon',
				'url'          => '',
			)
		);
	}

	/**
	 * Save local action overrides.
	 *
	 * @return void
	 */
	public function save_actions() {
		$this->guard( 'docsbot_actions' );
		check_admin_referer( 'docsbot_actions' );
		$settings = DocsBot_Plugin::settings();
		$fields   = array( 'use_feedback', 'use_escalation', 'use_web_search' );
		$changes  = array();
		foreach ( $fields as $field ) {
			$changes[ $field ] = $this->posted_bool( $field );
		}
		$bot = $this->cached_bot( $settings['team_id'], $settings['bot_id'] );
		if ( is_wp_error( $bot ) ) {
			$this->redirect_feedback( 'actions', 'error', $bot->get_error_message() );
		}

		// Removal controls only operate on resources returned for the selected bot.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$remove_skill = isset( $_POST['remove_skill'] ) ? sanitize_text_field( wp_unslash( $_POST['remove_skill'] ) ) : '';
		if ( $remove_skill ) {
			$skills = $this->cached_skills( $settings['team_id'], $settings['bot_id'] );
			$valid  = ! is_wp_error( $skills ) && $this->find_by_id( $skills, $remove_skill );
			if ( ! $valid ) {
				$this->redirect_feedback( 'actions', 'error', __( 'That skill is not available for this bot.', 'docsbot' ) );
			}
			$result = $this->api->update_widget_skill( $settings['team_id'], $settings['bot_id'], $remove_skill, false );
			$this->clear_cache( $settings['team_id'], $settings['bot_id'] );
			if ( is_wp_error( $result ) ) {
				$this->redirect_feedback( 'actions', 'error', $result->get_error_message() );
			}
			$this->redirect_feedback( 'actions', 'success', __( 'Skill removed from the widget.', 'docsbot' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$remove_mcp = isset( $_POST['remove_mcp'] ) ? sanitize_text_field( wp_unslash( $_POST['remove_mcp'] ) ) : '';
		if ( $remove_mcp ) {
			$servers = isset( $bot['mcpServers'] ) && is_array( $bot['mcpServers'] ) ? $bot['mcpServers'] : array();
			if ( ! $this->find_by_id( $this->api->available_mcp_servers( $servers ), $remove_mcp ) ) {
				$this->redirect_feedback( 'actions', 'error', __( 'That MCP server is not available for this bot.', 'docsbot' ) );
			}
			$found = false;
			foreach ( $servers as &$server ) {
				if ( is_array( $server ) && hash_equals( (string) ( $server['id'] ?? '' ), $remove_mcp ) ) {
					$server['enabled'] = false;
					$found             = true;
				}
			}
			unset( $server );
			if ( ! $found ) {
				$this->redirect_feedback( 'actions', 'error', __( 'That MCP server is not available for this bot.', 'docsbot' ) );
			}
			$result = $this->api->update_bot( $settings['team_id'], $settings['bot_id'], array( 'mcpServers' => array_values( $servers ) ) );
			$this->finish_remote_save( 'actions', $result, __( 'MCP server removed from the widget.', 'docsbot' ) );
		}

		$tools     = isset( $bot['tools'] ) && is_array( $bot['tools'] ) ? $bot['tools'] : array();
		$providers = array( 'calendly', 'calcom', 'tidycal' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested field is sanitized below.
		$booking = isset( $_POST['booking'] ) && is_array( $_POST['booking'] ) ? wp_unslash( $_POST['booking'] ) : array();
		$active  = '';
		foreach ( $providers as $provider ) {
			$posted = isset( $booking[ $provider ] ) && is_array( $booking[ $provider ] ) ? $booking[ $provider ] : array();
			$is_on  = '1' === (string) ( $posted['enabled'] ?? '' ) && '' === $active;
			if ( $is_on ) {
				$active = $provider;
			}
			$instructions = sanitize_textarea_field( (string) ( $posted['instructions'] ?? '' ) );
			$instructions = function_exists( 'mb_substr' ) ? mb_substr( $instructions, 0, 2000 ) : substr( $instructions, 0, 2000 );
			$url          = $this->sanitize_action_url( (string) ( $posted['url'] ?? '' ) );
			$tools[ $provider ] = array(
				'enabled'          => $is_on,
				'instructions'     => $instructions,
				'url'              => $url,
				'hideEventDetails' => '1' === (string) ( $posted['hideEventDetails'] ?? '' ),
			);
			if ( 'calendly' === $provider ) {
				$tools[ $provider ]['hideCookieBanner'] = '1' === (string) ( $posted['hideCookieBanner'] ?? '' );
			}
			$changes[ 'use_' . $provider ] = $is_on;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested field is sanitized below.
		$posted_buttons = isset( $_POST['custom_buttons'] ) && is_array( $_POST['custom_buttons'] ) ? wp_unslash( $_POST['custom_buttons'] ) : array();
		if ( count( $posted_buttons ) > 20 ) {
			$this->redirect_feedback( 'actions', 'error', __( 'A bot can have at most 20 custom buttons.', 'docsbot' ) );
		}
		$buttons        = array();
		$keys           = array();
		foreach ( $posted_buttons as $button ) {
			if ( ! is_array( $button ) ) {
				continue;
			}
			$name         = sanitize_text_field( (string) ( $button['name'] ?? '' ) );
			$raw_key      = sanitize_text_field( (string) ( $button['functionKey'] ?? '' ) );
			$key          = $this->normalize_custom_button_key( $raw_key );
			$instructions = sanitize_textarea_field( (string) ( $button['instructions'] ?? '' ) );
			$button_text  = sanitize_text_field( (string) ( $button['buttonText'] ?? '' ) );
			$url          = $this->sanitize_action_url( (string) ( $button['url'] ?? '' ), true );
			$enabled      = '1' === (string) ( $button['enabled'] ?? '' );
			if ( '' === $name && '' === $key && '' === $instructions && '' === $button_text && '' === $url ) {
				continue;
			}
			$reserved = array(
				'search_documentation', 'human_escalation', 'calendly', 'calcom', 'tidycal', 'search_web', 'web_search',
				'code_interpreter', 'stripe_recent_invoices_and_subscriptions', 'stripe_billing_portal',
				'stripe_refund_latest_payment', 'stripe_cancel_subscription',
			);
			if (
				( '' !== $raw_key && '' === $key )
				|| ( $enabled && '' === $key )
				|| ( '' !== $key && in_array( $key, $reserved, true ) )
				|| ( '' !== $key && isset( $keys[ $key ] ) )
			) {
				$this->redirect_feedback( 'actions', 'error', __( 'Every custom button needs a unique key.', 'docsbot' ) );
			}
			if ( $enabled && ( '' === $name || '' === $instructions || '' === $button_text || '' === $url ) ) {
				$this->redirect_feedback( 'actions', 'error', __( 'Complete every field for each enabled custom button.', 'docsbot' ) );
			}
			if ( '' !== $key ) {
				$keys[ $key ] = true;
			}
			$icon         = sanitize_text_field( (string) ( $button['icon'] ?? '' ) );
			$icons        = $this->custom_button_icon_options();
			$buttons[]    = array(
				'enabled'      => $enabled,
				'name'         => substr( $name, 0, 100 ),
				'functionKey'  => substr( $key, 0, 64 ),
				'instructions' => substr( $instructions, 0, 2000 ),
				'buttonText'   => substr( $button_text, 0, 100 ),
				'icon'         => isset( $icons[ $icon ] ) ? $icon : 'LinkIcon',
				'url'          => $url,
			);
		}
		$tools['customButtons']        = $buttons;
		$changes['use_custom_buttons'] = (bool) array_filter( wp_list_pluck( $buttons, 'enabled' ) );
		$labels                         = is_array( $bot ) && isset( $bot['labels'] ) && is_array( $bot['labels'] ) ? $bot['labels'] : array();
		$labels['getSupport']           = $this->post_text( 'support_label', 80 );
		$support_link                   = $this->post_url( 'support_link' );
		$result                         = $this->api->update_bot(
			$settings['team_id'],
			$settings['bot_id'],
			array(
				'supportLink' => $support_link,
				'labels'      => $labels,
				'tools'       => $tools,
			)
		);
		if ( is_wp_error( $result ) ) {
			set_transient(
				$this->actions_draft_key(),
				array(
					'bot_id'      => $settings['bot_id'],
					'tools'       => $tools,
					'labels'      => $labels,
					'supportLink' => $support_link,
					'settings'    => $changes,
				),
				30 * MINUTE_IN_SECONDS
			);
		} else {
			delete_transient( $this->actions_draft_key() );
			DocsBot_Plugin::update_settings( $changes );
		}
		$this->finish_remote_save( 'actions', $result, __( 'Widget actions saved.', 'docsbot' ) );
	}

	/**
	 * Return the current administrator's temporary Actions draft key.
	 *
	 * @return string
	 */
	private function actions_draft_key() {
		return 'docsbot_actions_draft_' . get_current_user_id();
	}

	/**
	 * Save deployment and optionally sync domain restrictions.
	 *
	 * @return void
	 */
	public function save_deploy() {
		$this->guard( 'docsbot_deploy' );
		check_admin_referer( 'docsbot_deploy' );
		$settings = DocsBot_Plugin::settings();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Every role is allowlisted below.
		$roles              = isset( $_POST['allowed_roles'] ) ? (array) wp_unslash( $_POST['allowed_roles'] ) : array();
		$valid_roles        = array_keys( wp_roles()->roles );
		$roles              = array_values( array_intersect( array_map( 'sanitize_key', $roles ), $valid_roles ) );
		$provider           = isset( $_POST['membership_provider'] ) ? sanitize_key( wp_unslash( $_POST['membership_provider'] ) ) : 'none';
		$providers          = $this->memberships->providers();
		$provider           = isset( $providers[ $provider ] ) ? $provider : 'none';
		$constant_signature = $this->has_signature_key_constant();
		$changes            = array(
			'enabled'             => $this->posted_bool( 'enabled' ),
			'include_prefixes'    => $this->sanitize_prefix_lines( isset( $_POST['include_prefixes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['include_prefixes'] ) ) : '' ),
			'exclude_prefixes'    => $this->sanitize_prefix_lines( isset( $_POST['exclude_prefixes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['exclude_prefixes'] ) ) : '' ),
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

		if ( 'public' === $settings['bot_privacy'] ) {
			$changes['signature_key'] = '';
		}

		$has_signature = $constant_signature
			|| ! empty( $settings['signature_key'] );
		if ( ! empty( $changes['enabled'] ) && 'private' === $settings['bot_privacy'] && ! $has_signature ) {
			$changes['enabled'] = false;
			DocsBot_Plugin::update_settings( $changes );
			$this->redirect_feedback( 'deploy', 'error', __( 'Reconnect the bot so DocsBot can retrieve its signing key before enabling a private bot.', 'docsbot' ) );
		}

		if ( 'none' !== $provider ) {
			$changes['logged_in_only'] = true;
		}

		DocsBot_Plugin::update_settings( $changes );

		if ( $settings['team_id'] && $settings['bot_id'] ) {
			$bot = $this->api->get_bot( $settings['team_id'], $settings['bot_id'] );
			if ( is_wp_error( $bot ) ) {
				/* translators: %s: DocsBot API error message. */
				$this->redirect_feedback( 'deploy', 'warning', sprintf( __( 'Deployment saved, but DocsBot domain restrictions could not be checked: %s', 'docsbot' ), $bot->get_error_message() ) );
			}
			$remote_domains = isset( $bot['allowedDomains'] ) && is_array( $bot['allowedDomains'] ) ? $bot['allowedDomains'] : array();
			$site_host      = (string) wp_parse_url( home_url(), PHP_URL_HOST );
			$domains        = $this->api->with_allowed_domain( $remote_domains, $site_host );
			DocsBot_Plugin::update_settings( array( 'allowed_domains' => $this->lines_from_array( $domains ) ) );
			if ( ! empty( $remote_domains ) && $domains !== $remote_domains ) {
				$result = $this->api->update_bot( $settings['team_id'], $settings['bot_id'], array( 'allowedDomains' => $domains ) );
				if ( is_wp_error( $result ) ) {
					/* translators: %s: DocsBot API error message. */
					$this->redirect_feedback( 'deploy', 'warning', sprintf( __( 'Deployment saved, but this site could not be added to DocsBot domain restrictions: %s', 'docsbot' ), $result->get_error_message() ) );
				}
				$bot = $result;
			}
			$this->cache_bot( $settings['team_id'], $settings['bot_id'], $bot );
		}

		$this->redirect_feedback( 'deploy', 'success', __( 'Deployment settings saved.', 'docsbot' ) );
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
				esc_html__( 'You do not have permission to manage DocsBot.', 'docsbot' ),
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
		return defined( 'DOCSBOT_API_KEY' ) && '' !== trim( (string) DOCSBOT_API_KEY );
	}

	/**
	 * Check whether wp-config.php provides a usable signature key.
	 *
	 * @return bool
	 */
	private function has_signature_key_constant() {
		return defined( 'DOCSBOT_SIGNATURE_KEY' ) && '' !== trim( (string) DOCSBOT_SIGNATURE_KEY );
	}

	/**
	 * Render the local, credential-free widget preview.
	 *
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_widget_preview( $settings ) {
		$bot = $this->cached_bot( $settings['team_id'], $settings['bot_id'] );
		if ( ! is_array( $bot ) ) {
			return;
		}
		$labels         = isset( $bot['labels'] ) && is_array( $bot['labels'] ) ? $bot['labels'] : array();
		$name           = $bot['name'] ?? $settings['bot_name'];
		$description    = $bot['description'] ?? __( 'Ask me anything about this site.', 'docsbot' );
		$first_message  = $labels['firstMessage'] ?? __( 'Hi! How can I help?', 'docsbot' );
		$placeholder    = $labels['inputPlaceholder'] ?? __( 'Send a message…', 'docsbot' );
		$button_label   = $labels['floatingButton'] ?? __( 'Chat with us', 'docsbot' );
		$support_label  = $labels['getSupport'] ?? __( 'Get support', 'docsbot' );
		$footer         = $labels['footerMessage'] ?? '';
		$logo           = is_string( $bot['logo'] ?? '' ) ? esc_url( $bot['logo'], array( 'https' ) ) : '';
		$color          = sanitize_hex_color( $bot['color'] ?? '#0891b8' );
		$color          = $color ? $color : '#0891b8';
		$red            = hexdec( substr( $color, 1, 2 ) );
		$green          = hexdec( substr( $color, 3, 2 ) );
		$blue           = hexdec( substr( $color, 5, 2 ) );
		$foreground     = ( ( $red * 299 + $green * 587 + $blue * 114 ) / 1000 ) > 155 ? '#0f172a' : '#ffffff';
		$avatar_red     = (int) round( $red + ( 255 - $red ) * 0.6 );
		$avatar_green   = (int) round( $green + ( 255 - $green ) * 0.6 );
		$avatar_blue    = (int) round( $blue + ( 255 - $blue ) * 0.6 );
		$avatar_color   = sprintf( 'rgb(%d, %d, %d)', $avatar_red, $avatar_green, $avatar_blue );
		$avatar_text    = ( ( $avatar_red * 299 + $avatar_green * 587 + $avatar_blue * 114 ) / 1000 ) > 155 ? '#0f172a' : '#ffffff';
		$alignment      = 'left' === ( $bot['alignment'] ?? 'right' ) ? ' is-left' : '';
		$header_align   = 'left' === ( $bot['headerAlignment'] ?? 'center' ) ? ' has-left-header' : '';
		$launcher_value = is_string( $bot['icon'] ?? '' ) ? $bot['icon'] : 'default';
		$launcher_icon  = $this->preview_icon_data( $launcher_value );
		$launcher_url   = 0 === strpos( $launcher_value, 'https://' ) ? esc_url( $launcher_value, array( 'https' ) ) : '';
		$bot_icon       = is_string( $bot['botIcon'] ?? '' ) ? $bot['botIcon'] : '';
		$bot_icon_data  = $this->preview_icon_data( $bot_icon );
		$bot_icon_url   = 0 === strpos( $bot_icon, 'https://' ) ? esc_url( $bot_icon, array( 'https' ) ) : '';
		$preview_style  = sprintf(
			'--docsbot-preview-color: %1$s; --docsbot-preview-foreground: %2$s; --docsbot-preview-avatar-bg: %3$s; --docsbot-preview-avatar-foreground: %4$s',
			$color,
			$foreground,
			$avatar_color,
			$avatar_text
		);
		?>
		<div class="docsbot-card docsbot-preview-card<?php echo esc_attr( $alignment . $header_align ); ?>" data-docsbot-preview style="<?php echo esc_attr( $preview_style ); ?>">
			<div class="docsbot-preview-card__heading">
				<h2><?php esc_html_e( 'Preview', 'docsbot' ); ?></h2>
				<div class="docsbot-preview-mode" role="group" aria-label="<?php esc_attr_e( 'Preview mode', 'docsbot' ); ?>">
					<button type="button" class="is-active" data-preview-mode="widget" aria-pressed="true"><?php esc_html_e( 'Widget', 'docsbot' ); ?></button>
					<button type="button" data-preview-mode="embed" aria-pressed="false"><?php esc_html_e( 'Embed', 'docsbot' ); ?></button>
				</div>
			</div>
			<div class="docsbot-preview-stage" role="img" aria-label="<?php esc_attr_e( 'DocsBot widget preview', 'docsbot' ); ?>">
				<div aria-hidden="true">
				<div class="docsbot-widget-preview">
					<div class="docsbot-widget-preview__header">
						<span class="docsbot-widget-preview__reset"><svg viewBox="0 0 16 16"><path fill-rule="evenodd" d="M13.836 2.477a.75.75 0 0 1 .75.75v3.182a.75.75 0 0 1-.75.75h-3.182a.75.75 0 0 1 0-1.5h1.37l-.84-.841a4.5 4.5 0 0 0-7.08.932.75.75 0 0 1-1.3-.75 6 6 0 0 1 9.44-1.242l.842.84V3.227a.75.75 0 0 1 .75-.75Zm-.911 7.5A.75.75 0 0 1 13.199 11a6 6 0 0 1-9.44 1.241l-.84-.84v1.371a.75.75 0 0 1-1.5 0V9.591a.75.75 0 0 1 .75-.75H5.35a.75.75 0 0 1 0 1.5H3.98l.841.841a4.5 4.5 0 0 0 7.08-.932.75.75 0 0 1 1.025-.273Z" clip-rule="evenodd"/></svg></span>
						<img data-preview="logo" src="<?php echo esc_url( $logo ); ?>" alt="" <?php echo $logo ? '' : 'hidden'; ?>>
						<div data-preview="header-copy" <?php echo $logo ? 'hidden' : ''; ?>><strong data-preview="name"><?php echo esc_html( $name ); ?></strong><span data-preview="description"><?php echo esc_html( $description ); ?></span></div>
					</div>
					<div class="docsbot-widget-preview__conversation">
						<div class="docsbot-preview-row is-bot">
							<span class="docsbot-preview-avatar" <?php echo $bot_icon ? '' : 'hidden'; ?>><img data-preview-bot-image src="<?php echo esc_url( $bot_icon_url ); ?>" alt="" <?php echo $bot_icon_url ? '' : 'hidden'; ?>><svg data-preview-icon-svg viewBox="<?php echo esc_attr( $bot_icon_data['view_box'] ); ?>" aria-hidden="true" <?php echo $bot_icon_url ? 'hidden' : ''; ?>><path data-preview-icon-path d="<?php echo esc_attr( $bot_icon_data['path'] ); ?>"/></svg></span>
							<div class="docsbot-preview-message" data-preview="first-message"><?php echo esc_html( $first_message ); ?></div>
						</div>
						<div class="docsbot-preview-row is-user"><div class="docsbot-preview-message"><?php esc_html_e( 'Why are you so amazing?', 'docsbot' ); ?></div></div>
						<div class="docsbot-preview-row is-bot">
							<span class="docsbot-preview-avatar" <?php echo $bot_icon ? '' : 'hidden'; ?>><img data-preview-bot-image src="<?php echo esc_url( $bot_icon_url ); ?>" alt="" <?php echo $bot_icon_url ? '' : 'hidden'; ?>><svg data-preview-icon-svg viewBox="<?php echo esc_attr( $bot_icon_data['view_box'] ); ?>" aria-hidden="true" <?php echo $bot_icon_url ? 'hidden' : ''; ?>><path data-preview-icon-path d="<?php echo esc_attr( $bot_icon_data['path'] ); ?>"/></svg></span>
							<div class="docsbot-preview-message">
								<p><?php esc_html_e( 'Thanks! What would you like to know about DocsBot?', 'docsbot' ); ?></p>
								<div class="docsbot-preview-sources" data-preview-toggle-inverse="hide_sources" <?php echo ! empty( $bot['hideSources'] ) ? 'hidden' : ''; ?>>
									<div><strong><?php esc_html_e( 'Sources', 'docsbot' ); ?></strong><span data-preview-toggle="show_copy_button" <?php echo empty( $bot['showCopyButton'] ) ? 'hidden' : ''; ?>><svg class="docsbot-preview-copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M6 16H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></span></div>
									<span><?php esc_html_e( 'Embeddable Chat Widget — DocsBot', 'docsbot' ); ?> ↗</span>
								</div>
							</div>
						</div>
						<div class="docsbot-preview-feedback" data-preview-toggle="use_feedback" <?php echo empty( $settings['use_feedback'] ) ? 'hidden' : ''; ?>><button type="button">👍</button><button type="button">👎</button></div>
						<div class="docsbot-preview-support" data-preview-toggle="use_escalation" <?php echo empty( $settings['use_escalation'] ) ? 'hidden' : ''; ?>>
							<div class="docsbot-preview-row is-bot"><span class="docsbot-preview-avatar" <?php echo $bot_icon ? '' : 'hidden'; ?>><img data-preview-bot-image src="<?php echo esc_url( $bot_icon_url ); ?>" alt="" <?php echo $bot_icon_url ? '' : 'hidden'; ?>><svg data-preview-icon-svg viewBox="<?php echo esc_attr( $bot_icon_data['view_box'] ); ?>" aria-hidden="true" <?php echo $bot_icon_url ? 'hidden' : ''; ?>><path data-preview-icon-path d="<?php echo esc_attr( $bot_icon_data['path'] ); ?>"/></svg></span><div class="docsbot-preview-message"><?php esc_html_e( 'Can I connect you to the support team?', 'docsbot' ); ?></div></div>
							<button type="button" data-preview="support-label"><?php echo esc_html( $support_label ); ?></button>
						</div>
					</div>
					<div class="docsbot-widget-preview__composer"><div><span data-preview="placeholder"><?php echo esc_html( $placeholder ); ?></span><span class="docsbot-preview-composer-actions"><i data-preview-toggle="use_image_upload" <?php echo empty( $settings['use_image_upload'] ) ? 'hidden' : ''; ?>><svg viewBox="0 0 512 512"><path d="M448 80c8.8 0 16 7.2 16 16v319.8l-93.7-93.7c-12.5-12.5-32.8-12.5-45.3 0L224 423.1 147.3 346.4c-12.5-12.5-32.8-12.5-45.3 0L48 400.4V96c0-8.8 7.2-16 16-16h384zM64 32C28.7 32 0 60.7 0 96v320c0 35.3 28.7 64 64 64h384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm80 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg></i><i data-preview-toggle="use_audio_upload" <?php echo empty( $settings['use_audio_upload'] ) ? 'hidden' : ''; ?>><svg viewBox="0 0 384 512"><path d="M192 0c-53 0-96 43-96 96v160c0 53 43 96 96 96s96-43 96-96V96c0-53-43-96-96-96zM64 216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 89.1 66.2 162.7 152 174.4V464h-48c-13.3 0-24 10.7-24 24s10.7 24 24 24h144c13.3 0 24-10.7 24-24s-10.7-24-24-24h-48v-33.6C301.8 418.7 368 345.1 368 256v-40c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 70.7-57.3 128-128 128S64 326.7 64 256v-40z"/></svg></i><b><svg viewBox="0 0 512 512"><path d="M476 3.2L12.5 270.6c-18.1 10.4-15.8 35.6 2.2 43.2L121 358.4l287.3-253.2c5.5-4.9 13.3 2.6 8.6 8.3L176 407v80.5c0 23.6 28.5 32.9 42.5 15.8L282 426l124.6 52.2c14.2 6 30.4-2.9 33-18.2l72-432C515 7.8 493.3-6.8 476 3.2z"/></svg></b></span></div></div>
					<div class="docsbot-widget-preview__notice" data-preview="footer-message" <?php echo $footer ? '' : 'hidden'; ?>><?php echo esc_html( $footer ); ?></div>
					<div class="docsbot-widget-preview__footer" <?php echo isset( $bot['branding'] ) && empty( $bot['branding'] ) ? 'hidden' : ''; ?>><?php esc_html_e( 'Powered by DocsBot', 'docsbot' ); ?></div>
				</div>
				<div class="docsbot-widget-preview__launcher <?php echo empty( $bot['showButtonLabel'] ) ? '' : 'has-label'; ?>"><span><img data-preview-launcher-image src="<?php echo esc_url( $launcher_url ); ?>" alt="" <?php echo $launcher_url ? '' : 'hidden'; ?>><svg data-preview-launcher-svg viewBox="<?php echo esc_attr( $launcher_icon['view_box'] ); ?>" aria-hidden="true" <?php echo $launcher_url ? 'hidden' : ''; ?>><path data-preview-launcher-path d="<?php echo esc_attr( $launcher_icon['path'] ); ?>"/></svg></span><b data-preview="button-label" <?php echo empty( $bot['showButtonLabel'] ) ? 'hidden' : ''; ?>><?php echo esc_html( $button_label ); ?></b></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Return an allowlisted SVG path for a preview icon.
	 *
	 * @param string $icon Icon name.
	 * @return array{view_box:string,path:string}
	 */
	private function preview_icon_data( $icon ) {
		$paths = array(
			'default'   => array(
				'view_box' => '0 0 512 512',
				'path'     => 'M512 240c0 114.9-114.6 208-256 208c-37.1 0-72.3-6.4-104.1-17.9c-11.9 8.7-31.3 20.6-54.3 30.6C73.6 471.1 44.7 480 16 480c-6.5 0-12.3-3.9-14.8-9.9c-2.5-6-1.1-12.8 3.4-17.4c8.4-8.4 38.5-44.4 43.1-91.9C17.7 326.8 0 285.1 0 240C0 125.1 114.6 32 256 32s256 93.1 256 208z',
			),
			'comment'   => array(
				'view_box' => '0 0 512 512',
				'path'     => 'M512 240c0 114.9-114.6 208-256 208c-37.1 0-72.3-6.4-104.1-17.9c-11.9 8.7-31.3 20.6-54.3 30.6C73.6 471.1 44.7 480 16 480c-6.5 0-12.3-3.9-14.8-9.9c-2.5-6-1.1-12.8 3.4-17.4c8.4-8.4 38.5-44.4 43.1-91.9C17.7 326.8 0 285.1 0 240C0 125.1 114.6 32 256 32s256 93.1 256 208z',
			),
			'comments'  => array(
				'view_box' => '0 0 640 512',
				'path'     => 'M208 352c114.9 0 208-78.8 208-176S322.9 0 208 0S0 78.8 0 176c0 38.6 14.7 74.3 39.6 103.4c-7.9 21.1-24.9 35.3-39 45.7C-4.9 329.2-1.4 352 16 352c31 0 62.5-11.3 87.3-23.9C134.1 343.3 169.8 352 208 352zM448 176c0 112.3-99.1 196.9-216.5 207C255.8 457.4 336.4 512 432 512c38.2 0 73.9-8.7 104.7-23.9c24.8 12.6 56.3 23.9 87.3 23.9c17.4 0 20.9-22.8 15.4-26.9c-14.1-10.4-31.1-24.6-39-45.7c24.9-29 39.6-64.7 39.6-103.4c0-92.8-84.9-168.9-192.6-175.5c.4 5.1 .6 10.3 .6 15.5z',
			),
			'robot'     => array(
				'view_box' => '0 0 640 512',
				'path'     => 'M320 0c17.7 0 32 14.3 32 32v64h120c39.8 0 72 32.2 72 72v272c0 39.8-32.2 72-72 72H168c-39.8 0-72-32.2-72-72V168c0-39.8 32.2-72 72-72h120V32c0-17.7 14.3-32 32-32zM208 384c-21.3 0-21.3 32 0 32h32c21.3 0 21.3-32 0-32h-32zm96 0c-21.3 0-21.3 32 0 32h32c21.3 0 21.3-32 0-32h-32zm96 0c-21.3 0-21.3 32 0 32h32c21.3 0 21.3-32 0-32h-32zM264 256a40 40 0 1 0-80 0a40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80a40 40 0 1 0 0 80zM48 224h16v192H48c-26.5 0-48-21.5-48-48v-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48v96c0 26.5-21.5 48-48 48h-16V224h16z',
			),
			'life-ring' => array(
				'view_box' => '0 0 512 512',
				'path'     => 'M367.2 412.5C335.9 434.9 297.5 448 256 448s-79.9-13.1-111.2-35.5l58-58c15.8 8.6 34 13.5 53.3 13.5s37.4-4.9 53.3-13.5l58 58zM457.9 413.3c33.8-43.4 54-98 54-157.3s-20.2-113.9-54-157.3C477.8 71 441 34.2 413.3 54C369.9 20.2 315.3 0 256 0S142.1 20.2 98.7 54C71 34.2 34.2 71 54 98.7C20.2 142.1 0 196.7 0 256s20.2 113.9 54 157.3C34.2 441 71 477.8 98.7 458c43.4 33.8 98 54 157.3 54s113.9-20.2 157.3-54c27.7 19.8 64.5-17 44.6-44.7zM412.5 367.2l-58-58c8.6-15.8 13.5-34 13.5-53.3s-4.9-37.4-13.5-53.3l58-58C434.9 176.1 448 214.5 448 256s-13.1 79.9-35.5 111.2zM367.2 99.5l-58 58c-15.8-8.6-34-13.5-53.3-13.5s-37.4 4.9-53.3 13.5l-58-58C176.1 77.1 214.5 64 256 64s79.9 13.1 111.2 35.5zM157.5 309.3l-58 58C77.1 335.9 64 297.5 64 256s13.1-79.9 35.5-111.2l58 58c-8.6 15.8-13.5 34-13.5 53.3s4.9 37.4 13.5 53.3zM208 256a48 48 0 1 1 96 0a48 48 0 1 1-96 0z',
			),
			'question'  => array(
				'view_box' => '0 0 320 512',
				'path'     => 'M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128h-32C73.3 32 16 89.3 16 160c0 42.7 64 42.7 64 0zm80 320a40 40 0 1 0 0-80a40 40 0 1 0 0 80z',
			),
			'book'      => array(
				'view_box' => '0 0 448 512',
				'path'     => 'M96 0C43 0 0 43 0 96v320c0 53 43 96 96 96h320c42.7 0 42.7-64 0-64v-64c17.7 0 32-14.3 32-32V32c0-17.7-14.3-32-32-32H96zm0 384h256v64H96c-42.7 0-42.7-64 0-64zm32-240c0-8.8 7.2-16 16-16h192c21.3 0 21.3 32 0 32H144c-8.8 0-16-7.2-16-16zm16 48h192c21.3 0 21.3 32 0 32H144c-21.3 0-21.3-32 0-32z',
			),
			'info'      => array(
				'view_box' => '0 0 192 512',
				'path'     => 'M48 80a48 48 0 1 1 96 0a48 48 0 1 1-96 0zM0 224c0-17.7 14.3-32 32-32h64c17.7 0 32 14.3 32 32v224h32c42.7 0 42.7 64 0 64H32c-42.7 0-42.7-64 0-64h32V256H32c-17.7 0-32-14.3-32-32z',
			),
		);

		return $paths[ $icon ] ?? $paths['default'];
	}

	/**
	 * Cache and finish a remote bot save.
	 *
	 * @param string                       $tab     Return tab.
	 * @param array<string,mixed>|WP_Error $result  API result.
	 * @param string                       $success Success message.
	 * @return void
	 */
	private function finish_remote_save( $tab, $result, $success ) {
		if ( is_wp_error( $result ) ) {
			$this->redirect_feedback( $tab, 'error', $result->get_error_message() );
		}
		$settings = DocsBot_Plugin::settings();
		$privacy  = in_array( $result['privacy'] ?? $settings['bot_privacy'], array( 'public', 'private' ), true )
			? ( $result['privacy'] ?? $settings['bot_privacy'] )
			: 'public';
		$changes  = array(
			'bot_name'    => sanitize_text_field( $result['name'] ?? $settings['bot_name'] ),
			'bot_privacy' => $privacy,
		);
		if ( 'public' === $privacy ) {
			$changes['signature_key'] = '';
		} elseif ( ! $this->has_signature_key_constant() && ! empty( $result['signatureKey'] ) && is_string( $result['signatureKey'] ) ) {
			$encrypted_signature = DocsBot_Crypto::encrypt( trim( $result['signatureKey'] ) );
			if ( is_wp_error( $encrypted_signature ) ) {
				$this->redirect_feedback( $tab, 'error', $encrypted_signature->get_error_message() );
			}
			$changes['signature_key'] = $encrypted_signature;
		}
		DocsBot_Plugin::update_settings( $changes );
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
			<h2><?php esc_html_e( 'Connect a bot first', 'docsbot' ); ?></h2>
			<p><?php esc_html_e( 'Choose a DocsBot team and bot before configuring this section.', 'docsbot' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( $this->tab_url( 'connection' ) ); ?>"><?php esc_html_e( 'Go to connection', 'docsbot' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Cached teams.
	 *
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	private function cached_teams() {
		$cached = get_transient( 'docsbot_teams' );
		if ( false !== $cached ) {
			return $cached;
		}
		$teams = $this->api->list_teams();
		if ( ! is_wp_error( $teams ) ) {
			set_transient( 'docsbot_teams', $teams, 5 * MINUTE_IN_SECONDS );
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
		$key    = 'docsbot_bots_' . md5( $team_id );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			foreach ( $cached as &$cached_bot ) {
				if ( is_array( $cached_bot ) ) {
					unset( $cached_bot['signatureKey'] );
				}
			}
			unset( $cached_bot );
			return $cached;
		}
		$bots = $this->api->list_bots( $team_id );
		if ( ! is_wp_error( $bots ) ) {
			foreach ( $bots as &$bot ) {
				if ( is_array( $bot ) ) {
					unset( $bot['signatureKey'] );
				}
			}
			unset( $bot );
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
		$key    = 'docsbot_bot_' . md5( $team_id . '|' . $bot_id );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			if ( is_array( $cached ) ) {
				unset( $cached['signatureKey'] );
			}
			return $cached;
		}
		$bot = $this->api->get_bot( $team_id, $bot_id );
		if ( ! is_wp_error( $bot ) ) {
			if ( 'private' === ( $bot['privacy'] ?? 'public' ) && ! $this->has_signature_key_constant() && ! empty( $bot['signatureKey'] ) && is_string( $bot['signatureKey'] ) ) {
				$encrypted_signature = DocsBot_Crypto::encrypt( trim( $bot['signatureKey'] ) );
				if ( ! is_wp_error( $encrypted_signature ) ) {
					DocsBot_Plugin::update_settings( array( 'signature_key' => $encrypted_signature ) );
				}
			}
			unset( $bot['signatureKey'] );
			set_transient( $key, $bot, 5 * MINUTE_IN_SECONDS );
		}
		return $bot;
	}

	/**
	 * Cached widget skill summaries.
	 *
	 * @param string $team_id Team ID.
	 * @param string $bot_id  Bot ID.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	private function cached_skills( $team_id, $bot_id ) {
		$key    = 'docsbot_skills_' . md5( $team_id . '|' . $bot_id );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$skills = $this->api->list_skills( $team_id, $bot_id );
		if ( ! is_wp_error( $skills ) ) {
			set_transient( $key, $skills, 5 * MINUTE_IN_SECONDS );
		}
		return $skills;
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
		unset( $bot['signatureKey'] );
		set_transient( 'docsbot_bot_' . md5( $team_id . '|' . $bot_id ), $bot, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Clear plugin response caches.
	 *
	 * @param string $team_id Previous team ID.
	 * @param string $bot_id  Previous bot ID.
	 * @return void
	 */
	private function clear_cache( $team_id = '', $bot_id = '' ) {
		delete_transient( 'docsbot_teams' );
		if ( '' === $team_id || '' === $bot_id ) {
			$settings = DocsBot_Plugin::settings();
			$team_id  = $team_id ? $team_id : $settings['team_id'];
			$bot_id   = $bot_id ? $bot_id : $settings['bot_id'];
		}
		if ( $team_id ) {
			delete_transient( 'docsbot_bots_' . md5( $team_id ) );
		}
		if ( $team_id && $bot_id ) {
			delete_transient( 'docsbot_bot_' . md5( $team_id . '|' . $bot_id ) );
			delete_transient( 'docsbot_skills_' . md5( $team_id . '|' . $bot_id ) );
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
			'connection' => __( 'Connection', 'docsbot' ),
			'content'    => __( 'Content', 'docsbot' ),
			'design'     => __( 'Design', 'docsbot' ),
			'actions'    => __( 'Actions', 'docsbot' ),
			'deploy'     => __( 'Deploy', 'docsbot' ),
		);
	}

	/**
	 * Tab URL.
	 *
	 * @param string $tab Tab ID.
	 * @return string
	 */
	private function tab_url( $tab ) {
		return add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
				'tab'  => $tab,
			),
			admin_url( 'options-general.php' )
		);
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
	 * Render a consistently aligned primary form action.
	 *
	 * @param string $label         Button label.
	 * @param string $extra_classes Optional additional button classes.
	 * @return void
	 */
	private function save_action( $label, $extra_classes = '' ) {
		$classes = trim( 'primary ' . sanitize_html_class( $extra_classes ) );
		?>
		<div class="docsbot-form-actions">
			<?php submit_button( $label, $classes, 'submit', false ); ?>
		</div>
		<?php
	}

	/**
	 * Render a checkbox.
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param bool   $checked Checked state.
	 * @param string $value   Submitted value.
	 * @return void
	 */
	private function checkbox( $name, $label, $checked, $value = '1' ) {
		?>
		<label class="docsbot-toggle">
			<span><?php echo esc_html( $label ); ?></span>
			<span class="docsbot-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $checked ); ?>><span aria-hidden="true"></span></span>
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
	 * @param string $icon        Icon ID.
	 * @return void
	 */
	private function option_toggle( $name, $label, $description, $checked, $icon = '' ) {
		?>
		<label class="docsbot-option">
			<?php if ( $icon ) : ?>
				<span class="docsbot-option__icon" aria-hidden="true"><?php echo $this->action_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static allowlisted SVG. ?></span>
			<?php endif; ?>
			<span class="docsbot-option__copy"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $description ); ?></span></span>
			<span class="docsbot-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>><span aria-hidden="true"></span></span>
		</label>
		<?php
	}

	/**
	 * Render dashboard-style visual icon choices with a Media Library option.
	 *
	 * @param string               $name       Field name.
	 * @param string               $label      Field label.
	 * @param array<string,string> $options    Built-in icon options.
	 * @param mixed                $value      Current built-in value or image URL.
	 * @param string               $custom_id  Custom URL input ID.
	 * @return void
	 */
	private function icon_choice_field( $name, $label, $options, $value, $custom_id ) {
		$value = is_string( $value ) ? $value : '';
		if ( 'icon' === $name && '' === $value ) {
			$value = 'default';
		}
		$is_builtin   = array_key_exists( $value, $options );
		$choice       = $is_builtin ? $value : 'custom';
		$custom_value = $is_builtin ? '' : $value;
		?>
		<fieldset class="docsbot-field docsbot-icon-field">
			<legend><?php echo esc_html( $label ); ?></legend>
			<div class="docsbot-icon-options">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<?php $icon = $this->preview_icon_data( $option_value ); ?>
					<label class="docsbot-icon-option">
						<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $choice, $option_value ); ?>>
						<span class="docsbot-icon-option__preview" aria-hidden="true">
							<?php if ( '' === $option_value ) : ?>
								<span class="docsbot-icon-none">—</span>
							<?php else : ?>
								<svg viewBox="<?php echo esc_attr( $icon['view_box'] ); ?>"><path d="<?php echo esc_attr( $icon['path'] ); ?>"/></svg>
							<?php endif; ?>
						</span>
						<span><?php echo esc_html( $option_label ); ?></span>
					</label>
				<?php endforeach; ?>
				<label class="docsbot-icon-option">
					<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="custom" <?php checked( $choice, 'custom' ); ?>>
					<span class="docsbot-icon-option__preview" aria-hidden="true" data-custom-preview-for="<?php echo esc_attr( $custom_id ); ?>">
						<?php if ( $custom_value ) : ?>
							<img src="<?php echo esc_url( $custom_value ); ?>" alt="">
						<?php else : ?>
							<span class="dashicons dashicons-upload"></span>
						<?php endif; ?>
					</span>
					<span><?php esc_html_e( 'Custom', 'docsbot' ); ?></span>
				</label>
			</div>
			<div class="docsbot-media-field docsbot-custom-icon-field" data-custom-icon-for="<?php echo esc_attr( $name ); ?>" <?php echo 'custom' === $choice ? '' : 'hidden'; ?>>
				<input type="url" id="<?php echo esc_attr( $custom_id ); ?>" name="<?php echo esc_attr( $name . '_custom' ); ?>" value="<?php echo esc_attr( $custom_value ); ?>" placeholder="https://example.com/icon.png">
				<button type="button" class="button docsbot-choose-image" data-media-target="<?php echo esc_attr( $custom_id ); ?>" data-media-choice="<?php echo esc_attr( $name ); ?>" data-media-title="<?php echo esc_attr( $label ); ?>" data-media-button="<?php esc_attr_e( 'Use this image', 'docsbot' ); ?>"><?php esc_html_e( 'Choose image', 'docsbot' ); ?></button>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Return a small dashboard-style action icon.
	 *
	 * @param string $icon Icon ID.
	 * @return string
	 */
	private function action_icon( $icon ) {
		$paths = array(
			'feedback' => '<path d="M7 10v10H4V10h3Zm4.5 10H9V10l3-7 1.5.5V9H18a2 2 0 0 1 2 2l-1 7a2 2 0 0 1-2 2h-5.5Z"/>',
			'support'  => '<path d="M4 13v-2a8 8 0 0 1 16 0v2"/><path d="M4 13a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2v2Zm16 0a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2v2ZM17 15v1a4 4 0 0 1-4 4h-2"/>',
			'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
			'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h2M14 14h2M8 17h2"/>',
			'button'   => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M8 12h8"/>',
			'activity' => '<path d="M4 12h3l2-6 4 12 2-6h5"/>',
			'skills'   => '<path d="M12 3 4 7l8 4 8-4-8-4Z"/><path d="m4 11 8 4 8-4M4 15l8 4 8-4"/>',
			'server'   => '<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
		);
		$path  = isset( $paths[ $icon ] ) ? $paths[ $icon ] : $paths['button'];
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
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
			'docsbot_feedback_' . get_current_user_id() . '_' . $token,
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
		$key      = 'docsbot_feedback_' . get_current_user_id() . '_' . $token;
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
	 * @param string            $fallback Default value.
	 * @return string
	 */
	private function posted_enum( $name, $allowed, $fallback ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify their action nonce before using submitted values.
		$value = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : $fallback;
		return in_array( $value, $allowed, true ) ? $value : $fallback;
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
	 * Sanitize an action target while blocking executable URL schemes.
	 *
	 * DocsBot custom actions support HTTPS links plus common mail and phone
	 * actions. Scheduling settings may also contain a provider-relative path.
	 *
	 * @param string $value             Raw target.
	 * @param bool   $allow_app_schemes Whether custom app URL schemes are allowed.
	 * @return string
	 */
	private function sanitize_action_url( $value, $allow_app_schemes = false ) {
		$value = trim( sanitize_text_field( $value ) );
		$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 2048 ) : substr( $value, 0, 2048 );
		$value = preg_replace( '/[\x00-\x20\x7F]+/', '', $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^(?:javascript|data|vbscript|file):/i', $value ) ) {
			return '';
		}
		if ( preg_match( '/^[a-z][a-z0-9+.-]*:/i', $value ) ) {
			$scheme = strtolower( (string) wp_parse_url( $value, PHP_URL_SCHEME ) );
			if ( in_array( $scheme, array( 'https', 'mailto', 'tel' ), true ) ) {
				return esc_url_raw( $value, array( 'https', 'mailto', 'tel' ) );
			}
			return $allow_app_schemes ? $value : '';
		}
		return $allow_app_schemes ? '' : $value;
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
	 * Convert an array to newline-delimited scalar values.
	 *
	 * @param mixed $values Values.
	 * @return string
	 */
	private function lines_from_array( $values ) {
		return implode( "\n", array_map( 'sanitize_text_field', is_array( $values ) ? $values : array() ) );
	}
}
