<?php
/**
 * Plugin Name:     WP Safe User Deletion
 * Description:     Double confirmation when deleting WordPress users; prompts to move content to another user and blocks deletion if content exists without reassignment.
 * Author:          Pivotal Agency
 * Author URI:      https://pivotalagency.com.au
 * Text Domain:     wp-safe-user-deletion
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package WP_Safe_User_Deletion
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the given user has any content (authored posts or links) that would be affected by deletion.
 *
 * @param int $user_id User ID.
 * @return bool True if the user has at least one post (any post type that supports author) or link.
 */
function udg_user_has_content( int $user_id ): bool {
	$posts = get_posts(
		array(
			'post_type'      => 'any',
			'author'         => $user_id,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	if ( ! empty( $posts ) ) {
		return true;
	}

	// Optionally include links (link owner) for consistency with core reassign behavior.
	if ( get_option( 'link_manager_enabled' ) ) {
		$links = get_bookmarks(
			array(
				'category' => '',
				'limit'   => 1,
				'owner'   => $user_id,
			)
		);
		if ( ! empty( $links ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Block user deletion when the user has content and no reassignment was selected.
 *
 * @param int      $id       User ID being deleted.
 * @param int|null $reassign User ID to reassign content to, or null.
 * @param WP_User  $user     The user object being deleted.
 */
function udg_block_delete_if_has_content_without_reassign( int $id, $reassign, WP_User $user ): void {
	if ( $reassign !== null && $reassign !== 0 ) {
		return;
	}

	if ( ! udg_user_has_content( $id ) ) {
		return;
	}

	$users_url = admin_url( 'users.php' );
	$back_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( $users_url ),
		esc_html__( 'Go back to Users', 'wp-safe-user-deletion' )
	);

	$message = sprintf(
		/* translators: 1: link back to users list */
		__( 'This user has content (posts or pages). To avoid losing data, go back and choose "Attribute all content to" another user before deleting. %1$s.', 'wp-safe-user-deletion' ),
		$back_link
	);

	wp_die(
		wp_kses_post( $message ),
		esc_html__( 'User deletion blocked', 'wp-safe-user-deletion' ),
		array(
			'response'  => 403,
			'back_link' => true,
		)
	);
}

/**
 * Show admin notice on the user delete confirmation screen.
 */
function udg_admin_notice_delete_screen(): void {
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'users' ) {
		return;
	}

	$action  = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
	$user_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
	if ( $action !== 'delete' || ! $user_id ) {
		return;
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}

	$has_content = udg_user_has_content( $user_id );
	$message     = $has_content
		? __( 'To avoid losing content, choose a user below to assign all posts and content to before deleting.', 'wp-safe-user-deletion' )
		: __( 'You can optionally assign this user\'s content to another user below before deleting.', 'wp-safe-user-deletion' );

	echo '<div class="notice notice-info"><p>' . esc_html( $message ) . '</p></div>';
}

/**
 * Enqueue script on the user delete confirmation screen only.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function udg_enqueue_delete_confirm_script( string $hook_suffix ): void {
	if ( $hook_suffix !== 'users.php' ) {
		return;
	}

	$action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
	$user_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( $action !== 'delete' || ! $user_id ) {
		return;
	}

	$script_path = plugin_dir_path( __FILE__ ) . 'js/delete-confirm.js';
	if ( ! file_exists( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'udg-delete-confirm',
		plugin_dir_url( __FILE__ ) . 'js/delete-confirm.js',
		array(),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'udg-delete-confirm',
		'udgDeleteConfirm',
		array(
			'confirmMessage' => __( 'This user has content. Assign it to another user to avoid data loss. Cancel to go back and choose a user.', 'wp-safe-user-deletion' ),
		)
	);
}

/**
 * Filter the reassign-user dropdown on the delete confirmation screen.
 * To restrict the list (e.g. to certain roles), return modified args with 'role__in' => array( 'administrator', 'editor' ).
 *
 * @param array $query_args  Arguments passed to WP_User_Query.
 * @param array $parsed_args Original arguments passed to wp_dropdown_users().
 * @return array Query args (unchanged by default).
 */
function udg_reassign_dropdown_args( array $query_args, array $parsed_args ): array {
	if ( ( $parsed_args['name'] ?? '' ) !== 'reassign_user' ) {
		return $query_args;
	}
	// Optional: restrict to specific roles, e.g. $query_args['role__in'] = array( 'administrator', 'editor' );
	return $query_args;
}

add_action( 'delete_user', 'udg_block_delete_if_has_content_without_reassign', 0, 3 );
add_action( 'admin_notices', 'udg_admin_notice_delete_screen' );
add_action( 'admin_enqueue_scripts', 'udg_enqueue_delete_confirm_script' );
add_filter( 'wp_dropdown_users_args', 'udg_reassign_dropdown_args', 10, 2 );
