<?php

use PHPUnit\Framework\TestCase;

final class VisibilityTest extends TestCase {

	private $widget;

	protected function setUp(): void {
		$this->widget = new DocsBot_Widget( new DocsBot_Memberships() );
	}

	public function test_empty_includes_allow_all_paths() {
		$settings = array( 'include_prefixes' => '', 'exclude_prefixes' => '' );
		$this->assertTrue( $this->widget->path_is_allowed( '/', $settings ) );
		$this->assertTrue( $this->widget->path_is_allowed( '/anything', $settings ) );
	}

	public function test_includes_are_literal_prefixes() {
		$settings = array( 'include_prefixes' => "/docs/\n/account/", 'exclude_prefixes' => '' );
		$this->assertTrue( $this->widget->path_is_allowed( '/docs/getting-started', $settings ) );
		$this->assertTrue( $this->widget->path_is_allowed( '/account/billing', $settings ) );
		$this->assertFalse( $this->widget->path_is_allowed( '/blog/docs', $settings ) );
	}

	public function test_excludes_win_over_includes() {
		$settings = array( 'include_prefixes' => '/docs/', 'exclude_prefixes' => '/docs/private/' );
		$this->assertFalse( $this->widget->path_is_allowed( '/docs/private/secret', $settings ) );
		$this->assertTrue( $this->widget->path_is_allowed( '/docs/public', $settings ) );
	}

	public function test_prefixes_match_complete_path_segments() {
		$settings = array( 'include_prefixes' => '/members/', 'exclude_prefixes' => '' );
		$this->assertTrue( $this->widget->path_is_allowed( '/members/account', $settings ) );
		$this->assertTrue( $this->widget->path_is_allowed( '/members', $settings ) );
		$this->assertFalse( $this->widget->path_is_allowed( '/membership', $settings ) );
		$this->assertFalse( $this->widget->path_is_allowed( '/members-public', $settings ) );
	}

	public function test_root_exclusion_denies_every_path() {
		$settings = array( 'include_prefixes' => '', 'exclude_prefixes' => '/' );
		$this->assertFalse( $this->widget->path_is_allowed( '/', $settings ) );
		$this->assertFalse( $this->widget->path_is_allowed( '/docs/page', $settings ) );
	}

	public function test_path_validation_rejects_urls_and_unbounded_input() {
		$this->assertTrue( $this->widget->validate_path( '/docs/page' ) );
		$this->assertFalse( $this->widget->validate_path( 'https://example.com/docs' ) );
		$this->assertFalse( $this->widget->validate_path( '/' . str_repeat( 'x', 2049 ) ) );
	}

	public function test_private_metadata_contains_only_logged_in_wordpress_user_ids() {
		$this->assertSame(
			array(
				'name'         => 'Ada',
				'priv_user_id' => '42',
			),
			$this->widget->build_private_metadata(
				42,
				array(
					'name'         => 'Ada',
					'priv_user_id' => 'untrusted',
				)
			)
		);
		$guest = $this->widget->build_private_metadata( 0 );
		$this->assertInstanceOf( stdClass::class, $guest );
		$this->assertSame( array(), get_object_vars( $guest ) );
	}

	public function test_no_membership_provider_allows_without_vendor_calls() {
		$memberships = new DocsBot_Memberships();
		$this->assertTrue( $memberships->user_is_allowed( 'none', '', 0 ) );
	}

	public function test_missing_membership_provider_fails_closed() {
		$memberships = new DocsBot_Memberships();
		$this->assertFalse( $memberships->user_is_allowed( 'memberpress', '', 123 ) );
	}

	public function test_wp_members_empty_rule_allows_any_assigned_product() {
		$memberships = new DocsBot_Memberships();
		$this->assertTrue( $memberships->user_is_allowed( 'wpmembers', '', 123 ) );
		$this->assertFalse( $memberships->user_is_allowed( 'wpmembers', '', 456 ) );
	}
}
