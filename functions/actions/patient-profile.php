<?php
/**
 * Patient profile
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();
add_shortcode(
	'patient_profile',
	function () {
		if ( ! snks_is_patient() ) {
			return;
		}
		$patient_nickname = get_user_meta( get_current_user_id(), 'nickname', true );
		$family_id        = get_user_meta( get_current_user_id(), 'family-id', true );
		$family_nickname  = '';
		$family_email     = '';
		if ( ! empty( $family_id ) ) {
			$family_nickname = get_user_meta( absint( $family_id ), 'nickname', true );
			$family          = get_user_by( 'id', absint( $family_id ) );
			if ( $family ) {
				$family_email = $family->user_email;
			}
		}
		if ( '' !== $family_nickname && ! is_email( $family_nickname ) ) {
			$family_nickname_disabled = ' readonly';
		} else {
			$family_nickname_disabled = '';
		}
		if ( '' !== $patient_nickname && ! is_email( $patient_nickname ) ) {
			$patient_nickname_disabled = ' readonly';
		} else {
			$patient_nickname_disabled = '';
		}
		ob_start();
		?>
		<style>

		</style>
		<form class="snks-form" id="register-family" autocomplete="off" action="" method="post">
			<input type="hidden" name="family-id" value="<?php echo esc_html( $family_id ); ?>"/>
			<div class="row">
				<label>الإسم المستعار</label>
				<input type="text" id="patient-nickname" name="patient-nickname" value="<?php echo ! is_email( $patient_nickname ) ? esc_html( $patient_nickname ) : ''; ?>" placeholder="الإسم هنا"<?php echo esc_attr( $patient_nickname_disabled ); ?>/>
				<input type="hidden" name="patient-nickname-current" value="<?php echo esc_html( $patient_nickname ); ?>"/>
			</div>
			<?php if ( empty( $family_id ) ) { ?>
			<div class="row">
				<label>هل تريد إنشاء حساب للأهل</label><br>
				<span class="snks-radio"><input type="radio" id="create-family-account-1" name="create-family-account" value="yes"/> <label for="create-family-account-1">نعم</label></span>
				<span class="snks-radio"><input type="radio" id="create-family-account-2" name="create-family-account" value="no"/><label for="create-family-account-2">لا</label></span>
			</div>
			<?php } ?>
			<div <?php echo empty( $family_id ) ? ' id="family-account-container"' : ''; ?>>
				<div class="row">
					<label>الإسم المستعار للأهل</label>
					<input type="text" id="family-nickname" name="family-nickname" value="<?php echo ! is_email( $family_nickname ) ? esc_html( $family_nickname ) : ''; ?>"  placeholder="الإسم المستعار هنا" readonly/>
					<input type="hidden" name="family-nickname-current" value="<?php echo esc_html( $family_nickname ); ?>"/>
				</div>
				<div class="row">
					<label>البريد الإلكتروني الخاص بحساب الأهل</label>
					<input type="text" style="direction:ltr;text-align: right;" name="family-email" value="<?php echo esc_html( $family_email ); ?>" placeholder="@gmail.com"/>
					<input type="hidden" name="family-email-current" value="<?php echo esc_html( $family_email ); ?>"/>
				</div>
				<div class="row">
					<label>كلمة المرور للأهل</label>
					<input type="password" style="direction:ltr;text-align: right;" name="family-password" value="" placeholder="******"/>
				</div>
				<div class="row">
					<label>تأكيد كلمة المرور للمتعالج</label>
					<input type="password" style="direction:ltr;text-align: right;" name="family-confirm-password" value="" placeholder="******"/>
				</div>
			</div>
			<input type="submit" name="patient-profile" value="تحديث الملف الشخصي"/>
		</form>
		<?php
		return ob_get_clean();
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! snks_is_patient() ) {
			return;
		}
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$( '#family-nickname' ).val( $( '#patient-nickname' ).val() + '*');
				$( '#patient-nickname' ).on(
					'input',
					function () {
						$( '#family-nickname' ).val( $( this ).val() + '*');
					}
				);
				$( '.snks-radio input[type=radio]' ).on(
					'change',
					function () {
						$( '.snks-radio' ).removeClass('snks-checked');
						if ($(this).is(':checked')) {
							$(this).closest( '.snks-radio' ).addClass('snks-checked');
						}

						if ( $(this).val() === 'yes' ) {
							$('#family-account-container').show();
							$('#family-account-container').find('input').prop('required',true);
						} else {
							$('#family-account-container').hide();
							$('#family-account-container').find('input').prop('required',false);
						}
					}
				);
				var $radios = $('input:radio[name=create-family-account]');
				if($radios.is(':checked') === false) {
					$radios.filter('[value=no]').prop('checked', true);
					$radios.trigger('change');
				}
			});
		</script>
		<?php
	}
);

add_action(
	'init',
	function () {
		//phpcs:disable
		$_request = $_POST;
		//phpcs:enable
		if ( ! isset( $_request['patient-profile'] ) || ! snks_is_patient() || empty( $_request['patient-nickname'] ) ) {
			return;
		}
		$user_id = get_current_user_id();
		if ( 'yes' === $_request['create-family-account'] &&
		empty( $_request['family-id'] ) &&
		! empty( $_request['family-email'] ) &&
		! empty( $_request['family-password'] ) &&
		! empty( $_request['family-nickname'] ) &&
		! empty( $_request['family-confirm-password'] ) ) {
			$user = anony_register_user(
				array(
					'user_email'      => sanitize_text_field( wp_unslash( $_request['family-email'] ) ),
					'password'        => sanitize_text_field( $_request['family-password'] ),
					'repeat_password' => sanitize_text_field( $_request['family-confirm-password'] ),
					'user_role'       => 'family',
					'account-type'    => 'family',
				)
			);
			update_user_meta( $user->ID, 'nickname', sanitize_text_field( wp_unslash( $_request['family-nickname'] ) ) );
			update_user_meta( $user->ID, 'patient-id', $user_id );
			update_user_meta( $user_id, 'family-id', $user->ID );

		} elseif ( ! empty( $_request['family-id'] ) ) {
			$args = array(
				'ID' => absint( $_request['family-id'] ),
			);
			if ( ! empty( $_request['family-email'] ) && $_request['family-email'] !== $_request['family-email-current'] ) {
				$args['user_email'] = sanitize_text_field( wp_unslash( $_request['family-email'] ) );
			}
			if ( ! empty( $_request['family-nickname'] ) && $_request['family-nickname'] !== $_request['family-nickname-current'] ) {
				$args['nickname'] = sanitize_text_field( wp_unslash( $_request['family-nickname'] ) );
			}

			if ( ! empty( $_request['family-password'] ) && $_request['family-password'] === $_request['family-confirm-password'] ) {
				$args['user_pass'] = sanitize_text_field( $_request['family-password'] );
			}
			if ( isset( $args['user_email'] ) || isset( $args['user_pass'] ) || isset( $args['nickname'] ) ) {
				$success = wp_update_user( $args );
			}
		}

		if ( ! empty( $_request['patient-nickname'] ) && $_request['patient-nickname'] !== $_request['patient-nickname-current'] ) {
			update_user_meta( $user_id, 'nickname', sanitize_text_field( wp_unslash( $_request['patient-nickname'] ) ) );
		}
		update_user_meta( $user_id, 'profile-complete', 'yes' );
		wp_safe_redirect( get_the_permalink( 682 ) );
		exit();
	}
);