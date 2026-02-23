<?php
/**
 * Player admin — meta boxes and columns for the squad CPT.
 *
 * @package DDCWWFCSC
 */

defined( 'ABSPATH' ) || exit;

class DDCWWFCSC_Player_Admin {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'use_block_editor_for_post_type',              array( __CLASS__, 'disable_block_editor' ), 10, 2 );
		add_action( 'add_meta_boxes',                              array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_ddcwwfcsc_player',                  array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
		add_filter( 'manage_ddcwwfcsc_player_posts_columns',       array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_ddcwwfcsc_player_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-ddcwwfcsc_player_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts',                               array( __CLASS__, 'apply_column_sorting' ) );
	}

	/**
	 * Disable the block editor for players.
	 *
	 * @param bool   $use_block_editor Whether to use block editor.
	 * @param string $post_type        Post type slug.
	 * @return bool
	 */
	public static function disable_block_editor( $use_block_editor, $post_type ) {
		if ( 'ddcwwfcsc_player' === $post_type ) {
			return false;
		}
		return $use_block_editor;
	}

	/**
	 * Register the Player Details meta box.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'ddcwwfcsc_player_details',
			__( 'Player Details', 'ddcwwfcsc' ),
			array( __CLASS__, 'render_details_box' ),
			'ddcwwfcsc_player',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Player Details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_details_box( $post ) {
		wp_nonce_field( 'ddcwwfcsc_player_meta', 'ddcwwfcsc_player_nonce' );

		$number   = get_post_meta( $post->ID, '_ddcwwfcsc_player_number', true );
		$position = get_post_meta( $post->ID, '_ddcwwfcsc_player_position', true );
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ddcwwfcsc_player_number"><?php esc_html_e( 'Squad Number', 'ddcwwfcsc' ); ?></label>
				</th>
				<td>
					<input type="number"
					       id="ddcwwfcsc_player_number"
					       name="ddcwwfcsc_player_number"
					       value="<?php echo esc_attr( $number ); ?>"
					       min="1"
					       max="99"
					       class="small-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="ddcwwfcsc_player_position"><?php esc_html_e( 'Position', 'ddcwwfcsc' ); ?></label>
				</th>
				<td>
					<select id="ddcwwfcsc_player_position" name="ddcwwfcsc_player_position">
						<option value=""><?php esc_html_e( '— Select Position —', 'ddcwwfcsc' ); ?></option>
						<?php
						$positions = array(
							'GK'  => __( 'GK — Goalkeeper', 'ddcwwfcsc' ),
							'DEF' => __( 'DEF — Defender', 'ddcwwfcsc' ),
							'MID' => __( 'MID — Midfielder', 'ddcwwfcsc' ),
							'FWD' => __( 'FWD — Forward', 'ddcwwfcsc' ),
						);
						foreach ( $positions as $value => $label ) :
							?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $position, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the Player Details meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['ddcwwfcsc_player_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['ddcwwfcsc_player_nonce'], 'ddcwwfcsc_player_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_ddcwwfcsc_player_number', absint( $_POST['ddcwwfcsc_player_number'] ?? 0 ) );
		update_post_meta( $post_id, '_ddcwwfcsc_player_position', DDCWWFCSC_Player_CPT::sanitize_position( $_POST['ddcwwfcsc_player_position'] ?? '' ) );
	}

	/**
	 * Add custom columns to the player list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public static function add_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['photo'] = __( 'Photo', 'ddcwwfcsc' );
			}
			$new[ $key ] = $label;
		}
		$new['number']   = __( '#', 'ddcwwfcsc' );
		$new['position'] = __( 'Position', 'ddcwwfcsc' );
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'photo':
				$thumb = get_the_post_thumbnail( $post_id, array( 40, 40 ) );
				echo $thumb ? $thumb : '—';
				break;

			case 'number':
				$num = get_post_meta( $post_id, '_ddcwwfcsc_player_number', true );
				echo $num ? esc_html( $num ) : '—';
				break;

			case 'position':
				$pos = get_post_meta( $post_id, '_ddcwwfcsc_player_position', true );
				echo $pos ? esc_html( $pos ) : '—';
				break;
		}
	}

	/**
	 * Declare sortable columns.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public static function sortable_columns( $columns ) {
		$columns['number'] = 'number';
		return $columns;
	}

	/**
	 * Apply meta-based sorting for the number column.
	 *
	 * @param WP_Query $query Current query.
	 */
	public static function apply_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'edit-ddcwwfcsc_player' !== $screen->id ) {
			return;
		}
		if ( isset( $_GET['orderby'] ) && 'number' === $_GET['orderby'] ) {
			$query->set( 'meta_key', '_ddcwwfcsc_player_number' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}
