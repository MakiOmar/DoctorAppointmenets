<?php
/**
 * User metabox
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
return;
add_action(
	'edit_user_profile',
	/**
	 * Add timetable metabox
	 *
	 * @param object $user User's object.
	 * @return void
	 */
	function ( $user ) {

		if ( in_array( 'doctor', $user->roles, true ) ) {
			?>
			<style>
				.user-custom-section{
					background-color: #fff;
					padding: 20px;
					border-radius: 10px;
				}
				.user-custom-section h1{
					display: flex;
					align-items: center;
					margin-bottom: 15px;
				}
				#current-user-timetable {
					margin-top: 15px;
					border-collapse: collapse;
					width: 100%;
				}

				#current-user-timetable td, #current-user-timetable th {
					border: 1px solid #ddd;
					padding: 8px;
				}

				#current-user-timetable tr:nth-child(even):not(.snks-booked){background-color: #f2f2f2;}

				#current-user-timetable tr:hover {background-color: #ddd;}

				#current-user-timetable th {
					padding-top: 12px;
					padding-bottom: 12px;
					text-align: left;
					background-color: #000;
					color: white;
				}
				.snks-booked{
					background-color: #04AA6D;
					color: #fff;
				}
				.snks-cancelled{
					background-color: #656565!important;
				}
				.snks-passed-waiting{
					background-color: #E31343!important;
				}
				.snks-completed{
					background-color: #0474AA!important;
				}
				.snks-booke, .snks-booked a, .snks-passed-waiting a,.snks-passed-waiting, .snks-cancelled a,.snks-cancelled, .snks-completed a, .snks-completed{
					color:#fff
				}
				#current-user-timetable .snks-booked:hover, #current-user-timetable .snks-booked:hover a{
					color:#000
				}
				#user_timetable_session_title_label{
					display: none;
				}
				.colors-keys{
					margin-right: 10px;
				}
				.colors-keys > span{
					display: inline-block;
					margin-right: 5px;
					height: 10px;
					width: 10px;
				}
				.user-custom-section label{
					display: inline-flex;
					flex-direction: column;
				}
				#snks-insert_timetable{
					vertical-align: bottom!important;
				}
				.insert-error{
					color:red;
					display: none;
				}
			</style>
			
			<section class="user-custom-section">
				<span class="colors-keys"><span class="snks-booked"></span>Booked</span>
				<span class="colors-keys"><span class="snks-passed-waiting"></span>Old not booked</span>
				<span class="colors-keys"><span class="snks-cancelled"></span>Cancelled</span>
				<span class="colors-keys"><span class="snks-completed"></span>Completed</span>
				<h1>
					<svg width="30px" height="30px" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M27 7H5C3.34315 7 2 8.34315 2 10V25C2 26.6569 3.34315 28 5 28H27C28.6569 28 30 26.6569 30 25V10C30 8.34315 28.6569 7 27 7Z" fill="#9FA8DA"/>
					<path d="M30 10V12H3H2V10C2 9.20435 2.31607 8.44129 2.87868 7.87868C3.44129 7.31607 4.20435 7 5 7H27C27.7956 7 28.5587 7.31607 29.1213 7.87868C29.6839 8.44129 30 9.20435 30 10Z" fill="#1A237E"/>
					<path d="M11 9C10.7348 9 10.4804 8.89464 10.2929 8.70711C10.1054 8.51957 10 8.26522 10 8V6H8V8C8 8.26522 7.89464 8.51957 7.70711 8.70711C7.51957 8.89464 7.26522 9 7 9C6.73478 9 6.48043 8.89464 6.29289 8.70711C6.10536 8.51957 6 8.26522 6 8V5C6 4.73478 6.10536 4.48043 6.29289 4.29289C6.48043 4.10536 6.73478 4 7 4H11C11.2652 4 11.5196 4.10536 11.7071 4.29289C11.8946 4.48043 12 4.73478 12 5V8C12 8.26522 11.8946 8.51957 11.7071 8.70711C11.5196 8.89464 11.2652 9 11 9Z" fill="#283593"/>
					<path d="M25 9C24.7348 9 24.4804 8.89464 24.2929 8.70711C24.1054 8.51957 24 8.26522 24 8V6H22V8C22 8.26522 21.8946 8.51957 21.7071 8.70711C21.5196 8.89464 21.2652 9 21 9C20.7348 9 20.4804 8.89464 20.2929 8.70711C20.1054 8.51957 20 8.26522 20 8V5C20 4.73478 20.1054 4.48043 20.2929 4.29289C20.4804 4.10536 20.7348 4 21 4H25C25.2652 4 25.5196 4.10536 25.7071 4.29289C25.8946 4.48043 26 4.73478 26 5V8C26 8.26522 25.8946 8.51957 25.7071 8.70711C25.5196 8.89464 25.2652 9 25 9Z" fill="#1A237E"/>
					<path d="M9 21H8C7.44772 21 7 21.4477 7 22V23C7 23.5523 7.44772 24 8 24H9C9.55228 24 10 23.5523 10 23V22C10 21.4477 9.55228 21 9 21Z" fill="#0D47A1"/>
					<path d="M14 16H13C12.4477 16 12 16.4477 12 17V18C12 18.5523 12.4477 19 13 19H14C14.5523 19 15 18.5523 15 18V17C15 16.4477 14.5523 16 14 16Z" fill="#0D47A1"/>
					<path d="M14 21H13C12.4477 21 12 21.4477 12 22V23C12 23.5523 12.4477 24 13 24H14C14.5523 24 15 23.5523 15 23V22C15 21.4477 14.5523 21 14 21Z" fill="#0D47A1"/>
					<path d="M19 16H18C17.4477 16 17 16.4477 17 17V18C17 18.5523 17.4477 19 18 19H19C19.5523 19 20 18.5523 20 18V17C20 16.4477 19.5523 16 19 16Z" fill="#0D47A1"/>
					<path d="M19 21H18C17.4477 21 17 21.4477 17 22V23C17 23.5523 17.4477 24 18 24H19C19.5523 24 20 23.5523 20 23V22C20 21.4477 19.5523 21 19 21Z" fill="#0D47A1"/>
					<path d="M24 16H23C22.4477 16 22 16.4477 22 17V18C22 18.5523 22.4477 19 23 19H24C24.5523 19 25 18.5523 25 18V17C25 16.4477 24.5523 16 24 16Z" fill="#C2185B"/>
					<path d="M24 21H23C22.4477 21 22 21.4477 22 22V23C22 23.5523 22.4477 24 23 24H24C24.5523 24 25 23.5523 25 23V22C25 21.4477 24.5523 21 24 21Z" fill="#0D47A1"/>
					<path d="M5 7C4.20435 7 3.44129 7.31607 2.87868 7.87868C2.31607 8.44129 2 9.20435 2 10V25C2 25.7956 2.31607 26.5587 2.87868 27.1213C3.44129 27.6839 4.20435 28 5 28H16V7H5Z" fill="#C5CAE9"/>
					<path d="M5 7C4.20435 7 3.44129 7.31607 2.87868 7.87868C2.31607 8.44129 2 9.20435 2 10V12H3H16V7H5Z" fill="#283593"/>
					<path d="M11 9C10.7348 9 10.4804 8.89464 10.2929 8.70711C10.1054 8.51957 10 8.26522 10 8V6H8V8C8 8.26522 7.89464 8.51957 7.70711 8.70711C7.51957 8.89464 7.26522 9 7 9C6.73478 9 6.48043 8.89464 6.29289 8.70711C6.10536 8.51957 6 8.26522 6 8V5C6 4.73478 6.10536 4.48043 6.29289 4.29289C6.48043 4.10536 6.73478 4 7 4H11C11.2652 4 11.5196 4.10536 11.7071 4.29289C11.8946 4.48043 12 4.73478 12 5V8C12 8.26522 11.8946 8.51957 11.7071 8.70711C11.5196 8.89464 11.2652 9 11 9Z" fill="#283593"/>
					<path d="M9 21H8C7.44772 21 7 21.4477 7 22V23C7 23.5523 7.44772 24 8 24H9C9.55228 24 10 23.5523 10 23V22C10 21.4477 9.55228 21 9 21Z" fill="#EC407A"/>
					<path d="M14 16H13C12.4477 16 12 16.4477 12 17V18C12 18.5523 12.4477 19 13 19H14C14.5523 19 15 18.5523 15 18V17C15 16.4477 14.5523 16 14 16Z" fill="#1565C0"/>
					<path d="M14 21H13C12.4477 21 12 21.4477 12 22V23C12 23.5523 12.4477 24 13 24H14C14.5523 24 15 23.5523 15 23V22C15 21.4477 14.5523 21 14 21Z" fill="#1565C0"/>
					</svg> Timetable
				</h1>
				<label for="user_timetable_date">
					<strong>Date:</strong>
					<input type="date" id="user_timetable_date" name="user_timetable_date" value="">
				</label>
		
				<label for="user_timetable_time">
					<strong>Time:</strong>
					<input type="time" step="1" id="user_timetable_time" name="user_timetable_time" value="">
				</label>
				<label for="user_timetable_purpose">
					<strong>Purpose:</strong>
					<select id="user_timetable_purpose" name="user_timetable_purpose">
						<option value="consulting"><?php esc_html_e( 'Consulting', 'anony-shrinks' ); ?></option>
						<option value="session"><?php esc_html_e( 'Session', 'anony-shrinks' ); ?></option>
					</select>
				</label>

				<label for="user_timetable_patient_id">
					<strong>Patient ID:</strong>
					<input type="text" id="user_timetable_patient_id" name="user_timetable_patient_id" value="0">
				</label>
				<label id="user_timetable_session_title_label" for="user_timetable_session_title">
					<strong>Session title:</strong>
					<input type="text" id="user_timetable_session_title" name="user_timetable_session_title" value="">
				</label>
				<a href="#" id="snks-insert_timetable" class="button button-primary">Insert</a>
				<p class="insert-error">عفواً! لم يتم الإدخال، ربما هناك موعد بالعل في هذا التوقيت.</p>
				<div>
					<table id="current-user-timetable">
						<tr>
							<th>NO.</th>
							<th>Day</th>
							<th>Hour</th>
							<th>Purpose</th>
							<th>Session title</th>
							<th>Patient</th>
							<th>Spent time</th>
							<th>Booked</th>
							<th>Actions</th>
						</tr>
					<?php
					// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$_req       = wp_unslash( $_GET );
					$timetables = snks_get_user_timetables( $_req['user_id'] );
					if ( ! empty( $timetables ) ) {
						?>
						<?php
						foreach ( $timetables as $timetable ) {
							$profile_link = esc_html__( 'Waiting', 'anony-shrinks' );
							$order_id     = $timetable->order_id;
							$order_edit   = '';
							$class        = '';
							if ( $order_id > 0 || 'open' === $timetable->session_status ) {
								$order_edit = ' | <a href="' . admin_url( '/post.php?post=' . $order_id . '&action=edit' ) . '">View order</a>';
								$class     .= ' snks-booked';
							}
							if ( snks_is_past_date( $timetable->date_time ) && 'waiting' === $timetable->session_status ) {
								$class .= ' snks-passed-waiting';
							}

							if ( 'cancelled' === $timetable->session_status ) {
								$class .= ' snks-cancelled';
							}

							if ( 'completed' === $timetable->session_status ) {
								$class .= ' snks-completed';
							}
							if ( '0' != $timetable->client_id ) {
								$profile_link = '';
								$profiles     = explode( ',', $timetable->client_id );
								foreach ( $profiles as $profile ) {
									$profile_link .= '<a href="' . esc_url( add_query_arg( 'user_id', $profile, admin_url( '/user-edit.php' ) ) ) . '">' . esc_html__( 'Profile', 'anony-shrinks' ) . '</a> | ';
								}
							}
							?>
							<tr class="snks-table-row<?php echo esc_attr( $class ); ?>">
								<td><?php echo '#' . esc_html( $timetable->ID ); ?></td>
								<td><?php echo esc_html( $timetable->booking_day ); ?></td>
								<td><?php echo esc_html( $timetable->start_time ); ?></td>
								<td><?php echo esc_html( $timetable->purpose ); ?></td>
								<td><?php echo esc_html( $timetable->session_title ); ?></td>
								<td><?php echo wp_kses_post( $profile_link ); ?></td>
								<td><?php echo esc_html( $timetable->time_spent ); ?></td>
								<td><?php echo $timetable->booking_availability ? 'No' : 'Yes'; ?></td>
								<td><a href="#" class="timetable-action" data-action="delete_timetable" data-id="<?php echo esc_html( $timetable->ID ); ?>">Delete</a><?php echo wp_kses_post( $order_edit ); ?> | 
								<a href="#" class="add_patient">Add patient</a>
								<div class="new_patient_form" style="display:none">
									<input type="number" value=""/>
									<a class="button button-success add_patient_id" data-id="<?php echo esc_html( $timetable->ID ); ?>" data-clients-ids="<?php echo esc_html( $timetable->client_id ); ?>">Insert</a>
								</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</table>
				</div>
				</section>
			<?php
		}
	},
	999
);
add_action(
	'admin_footer',
	function () {
		?>
		<script>
			jQuery( document ).ready(
				function ( $ ) {
					$( '.add_patient' ).on(
						'click',
						function(e) {
							e.preventDefault();
							$('.new_patient_form').hide();
							$(this).next('div').show();
						}
					);
					$( '.add_patient_id' ).on(
						'click',
						function(e) {
							e.preventDefault();
							var bookingId = $( this ).data('id');
							var clientsIds = $( this ).data('clients-ids');
							var patientId = $( this ).prev('input[type=number]').val();
							// Perform nonce check.
							var nonce = '<?php echo esc_html( wp_create_nonce( 'new_patient_nonce' ) ); ?>';
							// Send AJAX request.
							$.ajax({
								type: 'POST',
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', // Replace with your actual endpoint.
								data: {
									bookingId : bookingId,
									patientId : patientId,
									clientsIds : clientsIds,
									action    : 'new_patient',
									nonce     : nonce,
								},
								success: function(response) {
									console.log(response);
								},
								error: function(xhr, status, error) {
									console.error('Error:', error);
								}
							});
							}
					);
					$('#user_timetable_purpose').on(
						'change',
						function () {
							if ( $( this ).val() === 'session' ) {
								$('#user_timetable_session_title_label').css( 'display', 'inline-flex' );
							} else {
								$('#user_timetable_session_title_label').hide();
							}
						}
					)
				}
			);
		</script>
		<?php
	}
);
