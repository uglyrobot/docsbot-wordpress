<?php
/**
 * Membership visibility adapters.
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes supported membership plugins into a fail-closed visibility check.
 */
final class DocsBot_Memberships {

	/**
	 * Available provider labels.
	 *
	 * @return array<string,string>
	 */
	public function providers() {
		return apply_filters(
			'docsbot_membership_providers',
			array(
				'none'        => __( 'No membership restriction', 'docsbot' ),
				'woocommerce' => __( 'WooCommerce Memberships', 'docsbot' ),
				'memberpress' => __( 'MemberPress', 'docsbot' ),
				'pmpro'       => __( 'Paid Memberships Pro', 'docsbot' ),
				'rcp'         => __( 'Restrict Content Pro', 'docsbot' ),
				'wpmembers'   => __( 'WP-Members', 'docsbot' ),
				'ultimate'    => __( 'Ultimate Member roles', 'docsbot' ),
			)
		);
	}

	/**
	 * Detect whether a configured provider is currently callable.
	 *
	 * @param string $provider Provider ID.
	 * @return bool
	 */
	public function is_available( $provider ) {
		switch ( $provider ) {
			case 'none':
				return true;
			case 'woocommerce':
				return function_exists( 'wc_memberships_get_user_active_memberships' )
					&& function_exists( 'wc_memberships_is_user_active_member' );
			case 'memberpress':
				return class_exists( 'MeprUser' );
			case 'pmpro':
				return function_exists( 'pmpro_hasMembershipLevel' )
					&& function_exists( 'pmpro_getMembershipLevelsForUser' );
			case 'rcp':
				return function_exists( 'rcp_user_has_active_membership' );
			case 'wpmembers':
				return function_exists( 'wpmem_user_has_access' )
					&& function_exists( 'wpmem_get_user_products' );
			case 'ultimate':
				return function_exists( 'UM' );
			default:
				return false;
		}
	}

	/**
	 * Determine whether the current user satisfies a selected provider rule.
	 *
	 * The optional rule is a comma-separated list of public plan/product/role IDs.
	 * Empty means "any active membership" except Ultimate Member, where at least
	 * one selected WordPress role is required.
	 *
	 * @param string $provider Provider ID.
	 * @param string $rule     Comma-separated provider target IDs.
	 * @param int    $user_id  WordPress user ID.
	 * @return bool
	 */
	public function user_is_allowed( $provider, $rule, $user_id ) {
		if ( 'none' === $provider ) {
			return true;
		}

		if ( $user_id <= 0 || ! $this->is_available( $provider ) ) {
			return false;
		}

		$targets = array_values(
			array_filter(
				array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', (string) $rule ) ) )
			)
		);

		try {
			switch ( $provider ) {
				case 'woocommerce':
					if ( empty( $targets ) ) {
						return ! empty( wc_memberships_get_user_active_memberships( $user_id ) );
					}
					foreach ( $targets as $target ) {
						if ( wc_memberships_is_user_active_member( $user_id, $target ) ) {
							return true;
						}
					}
					return false;

				case 'memberpress':
					$user   = new MeprUser( $user_id );
					$active = method_exists( $user, 'active_product_subscriptions' )
						? array_map( 'strval', (array) $user->active_product_subscriptions( 'ids' ) )
						: array();
					return empty( $targets ) ? ! empty( $active ) : ! empty( array_intersect( $active, $targets ) );

				case 'pmpro':
					if ( empty( $targets ) ) {
						return ! empty( pmpro_getMembershipLevelsForUser( $user_id ) );
					}
					return (bool) pmpro_hasMembershipLevel( $targets, $user_id );

				case 'rcp':
					if ( empty( $targets ) ) {
						return (bool) rcp_user_has_active_membership( $user_id );
					}
					if ( ! function_exists( 'rcp_get_customer_by_user_id' ) ) {
						return false;
					}
					$customer = rcp_get_customer_by_user_id( $user_id );
					if ( ! $customer || ! method_exists( $customer, 'get_memberships' ) ) {
						return false;
					}
					foreach ( (array) $customer->get_memberships() as $membership ) {
						if (
							is_object( $membership )
							&& method_exists( $membership, 'is_active' )
							&& $membership->is_active()
							&& method_exists( $membership, 'get_object_id' )
							&& in_array( (string) $membership->get_object_id(), $targets, true )
						) {
							return true;
						}
					}
					return false;

				case 'wpmembers':
					if ( empty( $targets ) ) {
						$products = (array) wpmem_get_user_products( $user_id );
						$targets  = array_values( array_filter( array_map( 'strval', array_keys( $products ) ) ) );
					}
					return ! empty( $targets ) && (bool) wpmem_user_has_access( $targets, $user_id );

				case 'ultimate':
					if ( empty( $targets ) ) {
						return false;
					}
					$user = get_userdata( $user_id );
					return $user instanceof WP_User && ! empty( array_intersect( (array) $user->roles, $targets ) );
			}
		} catch ( Throwable $throwable ) {
			return false;
		}

		return false;
	}
}
