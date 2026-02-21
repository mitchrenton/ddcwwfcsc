<?php
/**
 * Custom Avatar: local avatar upload for user profiles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Custom_Avatar {

    const META_KEY = '_ddcwwfcsc_custom_avatar';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'show_user_profile', array( __CLASS__, 'render_avatar_field' ) );
        add_action( 'edit_user_profile', array( __CLASS__, 'render_avatar_field' ) );
        add_action( 'personal_options_update', array( __CLASS__, 'save_avatar_field' ) );
        add_action( 'edit_user_profile_update', array( __CLASS__, 'save_avatar_field' ) );
        add_filter( 'pre_get_avatar', array( __CLASS__, 'filter_avatar' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_media' ) );
    }

    /**
     * Render the avatar upload field on the user profile page.
     */
    public static function render_avatar_field( $user ) {
        $attachment_id = (int) get_user_meta( $user->ID, self::META_KEY, true );
        $preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
        ?>
        <h2><?php esc_html_e( 'Custom Avatar', 'ddcwwfcsc' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label><?php esc_html_e( 'Profile Photo', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <div id="ddcwwfcsc-avatar-preview" style="margin-bottom:10px;">
                        <?php if ( $preview_url ) : ?>
                            <img src="<?php echo esc_url( $preview_url ); ?>" style="width:96px;height:96px;border-radius:50%;object-fit:cover;">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="ddcwwfcsc-avatar-id" name="ddcwwfcsc_custom_avatar" value="<?php echo esc_attr( $attachment_id ?: '' ); ?>">
                    <button type="button" class="button" id="ddcwwfcsc-avatar-upload"><?php esc_html_e( 'Upload Image', 'ddcwwfcsc' ); ?></button>
                    <button type="button" class="button" id="ddcwwfcsc-avatar-remove" <?php echo $attachment_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Upload a custom profile photo. Falls back to Gravatar if not set.', 'ddcwwfcsc' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the custom avatar attachment ID.
     */
    public static function save_avatar_field( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        if ( ! isset( $_POST['ddcwwfcsc_custom_avatar'] ) ) {
            return;
        }

        $attachment_id = absint( $_POST['ddcwwfcsc_custom_avatar'] );

        if ( $attachment_id ) {
            update_user_meta( $user_id, self::META_KEY, $attachment_id );
        } else {
            delete_user_meta( $user_id, self::META_KEY );
        }
    }

    /**
     * Filter the avatar to use the custom upload when available.
     *
     * @param string|null $avatar      HTML for the avatar or null.
     * @param mixed       $id_or_email User ID, email, WP_User, WP_Post, or WP_Comment.
     * @param array       $args        Avatar arguments.
     * @return string|null Custom avatar HTML or null to fall back to Gravatar.
     */
    public static function filter_avatar( $avatar, $id_or_email, $args ) {
        $user_id = self::resolve_user_id( $id_or_email );

        if ( ! $user_id ) {
            return $avatar;
        }

        $attachment_id = (int) get_user_meta( $user_id, self::META_KEY, true );

        if ( ! $attachment_id ) {
            return $avatar;
        }

        $url = wp_get_attachment_image_url( $attachment_id, array( $args['size'], $args['size'] ) );

        if ( ! $url ) {
            return $avatar;
        }

        $class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );
        if ( ! empty( $args['class'] ) ) {
            $class = array_merge( $class, (array) $args['class'] );
        }

        return sprintf(
            '<img alt="%s" src="%s" class="%s" height="%d" width="%d" loading="lazy" decoding="async">',
            esc_attr( $args['alt'] ),
            esc_url( $url ),
            esc_attr( implode( ' ', $class ) ),
            (int) $args['size'],
            (int) $args['size']
        );
    }

    /**
     * Resolve a user ID from the mixed $id_or_email parameter.
     *
     * @param mixed $id_or_email User ID, email, WP_User, WP_Post, or WP_Comment.
     * @return int User ID or 0.
     */
    private static function resolve_user_id( $id_or_email ) {
        if ( is_numeric( $id_or_email ) ) {
            return (int) $id_or_email;
        }

        if ( is_string( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
            return $user ? $user->ID : 0;
        }

        if ( $id_or_email instanceof WP_User ) {
            return $id_or_email->ID;
        }

        if ( $id_or_email instanceof WP_Post ) {
            return (int) $id_or_email->post_author;
        }

        if ( $id_or_email instanceof WP_Comment ) {
            if ( ! empty( $id_or_email->user_id ) ) {
                return (int) $id_or_email->user_id;
            }
            if ( ! empty( $id_or_email->comment_author_email ) ) {
                $user = get_user_by( 'email', $id_or_email->comment_author_email );
                return $user ? $user->ID : 0;
            }
            return 0;
        }

        return 0;
    }

    /**
     * Enqueue the WP media uploader and inline JS on profile pages.
     */
    public static function enqueue_media( $hook ) {
        if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
            return;
        }

        wp_enqueue_media();

        $js = <<<'JS'
(function($){
    var frame;
    $('#ddcwwfcsc-avatar-upload').on('click', function(e){
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({
            title: 'Select Avatar Image',
            button: { text: 'Use as Avatar' },
            multiple: false,
            library: { type: 'image' }
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;
            $('#ddcwwfcsc-avatar-id').val(attachment.id);
            $('#ddcwwfcsc-avatar-preview').html(
                '<img src="' + url + '" style="width:96px;height:96px;border-radius:50%;object-fit:cover;">'
            );
            $('#ddcwwfcsc-avatar-remove').show();
        });
        frame.open();
    });

    $('#ddcwwfcsc-avatar-remove').on('click', function(e){
        e.preventDefault();
        $('#ddcwwfcsc-avatar-id').val('');
        $('#ddcwwfcsc-avatar-preview').html('');
        $(this).hide();
    });
})(jQuery);
JS;

        wp_add_inline_script( 'media-editor', $js );
    }
}
