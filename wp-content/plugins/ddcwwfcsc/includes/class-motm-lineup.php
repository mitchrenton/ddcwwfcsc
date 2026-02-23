<?php
/**
 * MOTM Lineup — reads the squad-based lineup for a fixture.
 *
 * The TheSportsDB API integration has been replaced by a president-managed
 * squad roster (ddcwwfcsc_player CPT). This class now only provides
 * get_lineup() with backwards-compatible fallback to legacy API meta.
 *
 * @package DDCWWFCSC
 */

defined( 'ABSPATH' ) || exit;

class DDCWWFCSC_MOTM_Lineup {

	/**
	 * No-op init — kept for compatibility with ddcwwfcsc.php.
	 */
	public static function init() {}

	/**
	 * Get the lineup for a fixture.
	 *
	 * Priority:
	 *   1. New squad-based lineup (_ddcwwfcsc_motm_lineup_ids) set by president.
	 *   2. Legacy manual override (_ddcwwfcsc_motm_lineup_override).
	 *   3. Legacy API-fetched lineup (_ddcwwfcsc_motm_lineup).
	 *
	 * Returns an array of players in the same shape used by the front-end:
	 *   [ [ 'name' => string, 'number' => int, 'starter' => bool ], ... ]
	 *
	 * @param int $post_id Fixture post ID.
	 * @return array
	 */
	public static function get_lineup( $post_id ) {
		// 1. New squad-based lineup.
		$lineup_ids = get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_ids', true );

		if ( ! empty( $lineup_ids ) && is_array( $lineup_ids ) ) {
			$players = array();
			foreach ( $lineup_ids as $entry ) {
				$player_id = absint( $entry['player_id'] ?? 0 );
				$starter   = (bool) ( $entry['starter'] ?? false );
				if ( ! $player_id ) {
					continue;
				}
				$data = DDCWWFCSC_Player_CPT::get_player_data( $player_id );
				if ( $data['name'] ) {
					$players[] = array(
						'name'    => $data['name'],
						'number'  => $data['number'],
						'starter' => $starter,
					);
				}
			}
			if ( ! empty( $players ) ) {
				return $players;
			}
		}

		// 2. Legacy fallback: manual override.
		$override = get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_override', true );
		if ( ! empty( $override ) && is_array( $override ) ) {
			return $override;
		}

		// 3. Legacy fallback: API-fetched lineup.
		$lineup = get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup', true );
		return is_array( $lineup ) ? $lineup : array();
	}

	/**
	 * Check whether a lineup has been set for a fixture.
	 *
	 * @param int $post_id Fixture post ID.
	 * @return bool
	 */
	public static function is_lineup_set( $post_id ) {
		return ! empty( self::get_lineup( $post_id ) );
	}
}
