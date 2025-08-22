<?php
/**
 * Profit Settings Admin Page
 * 
 * Manages AI session profit settings for therapists
 * 
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}



/**
 * Add profit settings menu to admin
 */
function snks_add_profit_settings_menu() {
	add_submenu_page(
		'jalsah-ai-management',
		__( 'Profit Settings', 'anony-turn' ),
		__( 'Profit Settings', 'anony-turn' ),
		'manage_options',
		'profit-settings',
		'snks_profit_settings_page'
	);
}
add_action( 'admin_menu', 'snks_add_profit_settings_menu' );

/**
 * Profit settings page content
 */
function snks_profit_settings_page() {
	// Load AI admin styles
	if ( function_exists( 'snks_load_ai_admin_styles' ) ) {
		snks_load_ai_admin_styles();
	}
	
	// Handle form submissions
	if ( isset( $_POST['submit_profit_settings'] ) ) {
		snks_handle_profit_settings_submission();
	}
	
	// Get current settings
	$global_settings = get_option( 'snks_ai_profit_global_settings', array(
		'default_first_percentage' => 70.00,
		'default_subsequent_percentage' => 75.00
	) );
	
	// Get all therapists with their profit settings
	$therapists = snks_get_all_therapists_with_profit_settings();
	
	?>
	<div class="wrap">
		<h1><?php echo __( 'AI Session Profit Settings', 'anony-turn' ); ?></h1>
		
		<!-- Global Settings Section -->
		<div class="card">
			<h2><?php echo __( 'Global Settings', 'anony-turn' ); ?></h2>
			<form method="post" action="">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="default_first_percentage"><?php echo __( 'First Session Profit Percentage (%)', 'anony-turn' ); ?></label>
						</th>
						<td>
							<input type="number" 
								   id="default_first_percentage" 
								   name="default_first_percentage" 
								   value="<?php echo esc_attr( $global_settings['default_first_percentage'] ); ?>" 
								   step="0.01" 
								   min="0" 
								   max="100" 
								   class="regular-text" />
							<p class="description"><?php echo __( 'Default profit percentage for the first session with each new patient', 'anony-turn' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_subsequent_percentage"><?php echo __( 'Subsequent Sessions Profit Percentage (%)', 'anony-turn' ); ?></label>
						</th>
						<td>
							<input type="number" 
								   id="default_subsequent_percentage" 
								   name="default_subsequent_percentage" 
								   value="<?php echo esc_attr( $global_settings['default_subsequent_percentage'] ); ?>" 
								   step="0.01" 
								   min="0" 
								   max="100" 
								   class="regular-text" />
							<p class="description"><?php echo __( 'Default profit percentage for subsequent sessions with the same patient', 'anony-turn' ); ?></p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="submit_profit_settings" class="button-primary" value="<?php echo __( 'Save Global Settings', 'anony-turn' ); ?>" />
				</p>
			</form>
		</div>
		
		<!-- Individual Therapist Settings -->
		<div class="card">
			<h2><?php echo __( 'Individual Therapist Settings', 'anony-turn' ); ?></h2>
			
			<!-- Bulk Operations -->
			<div class="bulk-operations" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
				<h3><?php echo __( 'Bulk Operations', 'anony-turn' ); ?></h3>
				<form method="post" action="" id="bulk-settings-form">
					<label for="bulk_first_percentage"><?php echo __( 'First Session Percentage:', 'anony-turn' ); ?></label>
					<input type="number" id="bulk_first_percentage" name="bulk_first_percentage" step="0.01" min="0" max="100" style="width: 100px;" />
					
					<label for="bulk_subsequent_percentage" style="margin-left: 20px;"><?php echo __( 'Subsequent Sessions Percentage:', 'anony-turn' ); ?></label>
					<input type="number" id="bulk_subsequent_percentage" name="bulk_subsequent_percentage" step="0.01" min="0" max="100" style="width: 100px;" />
					
					<button type="button" class="button" onclick="applyBulkSettings()"><?php echo __( 'Apply to Selected', 'anony-turn' ); ?></button>
					<button type="button" class="button" onclick="selectAllTherapists()"><?php echo __( 'Select All', 'anony-turn' ); ?></button>
					<button type="button" class="button" onclick="deselectAllTherapists()"><?php echo __( 'Deselect All', 'anony-turn' ); ?></button>
				</form>
			</div>
			
			<!-- Therapists Table -->
			<form method="post" action="" id="therapists-settings-form">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-cb check-column">
								<input type="checkbox" id="select-all-therapists" />
							</th>
							<th scope="col"><?php echo __( 'Therapist', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Email', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'First Session Percentage (%)', 'anony-turn' ); ?></th>
							<th scope="col"><?php echo __( 'Subsequent Sessions Percentage (%)', 'anony-turn' ); ?></th>
							<th scope="col">الحالة</th>
							<th scope="col">آخر تحديث</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $therapists ) ) : ?>
							<tr>
								<td colspan="7">لا يوجد معالجون</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $therapists as $therapist ) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="selected_therapists[]" value="<?php echo esc_attr( $therapist['therapist_id'] ); ?>" class="therapist-checkbox" />
									</th>
									<td>
										<strong><?php echo esc_html( $therapist['display_name'] ); ?></strong>
										<input type="hidden" name="therapist_ids[]" value="<?php echo esc_attr( $therapist['therapist_id'] ); ?>" />
									</td>
									<td><?php echo esc_html( $therapist['user_email'] ); ?></td>
									<td>
										<input type="number" 
											   name="first_percentage[<?php echo esc_attr( $therapist['therapist_id'] ); ?>]" 
											   value="<?php echo esc_attr( $therapist['first_session_percentage'] ); ?>" 
											   step="0.01" 
											   min="0" 
											   max="100" 
											   style="width: 80px;" />
									</td>
									<td>
										<input type="number" 
											   name="subsequent_percentage[<?php echo esc_attr( $therapist['therapist_id'] ); ?>]" 
											   value="<?php echo esc_attr( $therapist['subsequent_session_percentage'] ); ?>" 
											   step="0.01" 
											   min="0" 
											   max="100" 
											   style="width: 80px;" />
									</td>
									<td>
										<select name="is_active[<?php echo esc_attr( $therapist['therapist_id'] ); ?>]">
											<option value="1" <?php selected( $therapist['is_active'], 1 ); ?>>نشط</option>
											<option value="0" <?php selected( $therapist['is_active'], 0 ); ?>>غير نشط</option>
										</select>
									</td>
									<td><?php echo esc_html( $therapist['updated_at'] ? date( 'Y-m-d H:i', strtotime( $therapist['updated_at'] ) ) : 'لم يتم التحديث' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				
				<p class="submit">
					<input type="submit" name="submit_therapist_settings" class="button-primary" value="<?php echo __( 'Save Therapist Settings', 'anony-turn' ); ?>" />
				</p>
			</form>
		</div>
		
		<!-- Statistics Section -->
		<div class="card">
			<h2>إحصائيات سريعة</h2>
			<?php
			$stats = snks_get_profit_settings_statistics();
			?>
			<table class="form-table">
				<tr>
					<th scope="row">إجمالي المعالجين</th>
					<td><?php echo esc_html( $stats['total_therapists'] ); ?></td>
				</tr>
				<tr>
					<th scope="row">المعالجون النشطون</th>
					<td><?php echo esc_html( $stats['active_therapists'] ); ?></td>
				</tr>
				<tr>
					<th scope="row">المعالجون مع إعدادات مخصصة</th>
					<td><?php echo esc_html( $stats['custom_settings'] ); ?></td>
				</tr>
				<tr>
					<th scope="row">المعالجون باستخدام الإعدادات الافتراضية</th>
					<td><?php echo esc_html( $stats['default_settings'] ); ?></td>
				</tr>
			</table>
		</div>
	</div>
	
	<script>
	function selectAllTherapists() {
		document.querySelectorAll('.therapist-checkbox').forEach(checkbox => {
			checkbox.checked = true;
		});
		document.getElementById('select-all-therapists').checked = true;
	}
	
	function deselectAllTherapists() {
		document.querySelectorAll('.therapist-checkbox').forEach(checkbox => {
			checkbox.checked = false;
		});
		document.getElementById('select-all-therapists').checked = false;
	}
	
	function applyBulkSettings() {
		const firstPercentage = document.getElementById('bulk_first_percentage').value;
		const subsequentPercentage = document.getElementById('bulk_subsequent_percentage').value;
		const selectedCheckboxes = document.querySelectorAll('.therapist-checkbox:checked');
		
		if (selectedCheckboxes.length === 0) {
			alert('يرجى تحديد معالج واحد على الأقل');
			return;
		}
		
		if (!firstPercentage && !subsequentPercentage) {
			alert('<?php echo __( 'Please enter at least one percentage', 'anony-turn' ); ?>');
			return;
		}
		
		selectedCheckboxes.forEach(checkbox => {
			const therapistId = checkbox.value;
			if (firstPercentage) {
				document.querySelector(`input[name="first_percentage[${therapistId}]"]`).value = firstPercentage;
			}
			if (subsequentPercentage) {
				document.querySelector(`input[name="subsequent_percentage[${therapistId}]"]`).value = subsequentPercentage;
			}
		});
		
		alert('تم تطبيق الإعدادات على المعالجين المحددين. اضغط "حفظ إعدادات المعالجين" لحفظ التغييرات.');
	}
	
	// Handle select all checkbox
	document.getElementById('select-all-therapists').addEventListener('change', function() {
		document.querySelectorAll('.therapist-checkbox').forEach(checkbox => {
			checkbox.checked = this.checked;
		});
	});
	</script>
	<?php
}

/**
 * Handle profit settings form submission
 */
function snks_handle_profit_settings_submission() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'غير مصرح لك بالوصول إلى هذه الصفحة' );
	}
	
	// Handle global settings
	if ( isset( $_POST['submit_profit_settings'] ) ) {
		$global_settings = array(
			'default_first_percentage' => floatval( $_POST['default_first_percentage'] ),
			'default_subsequent_percentage' => floatval( $_POST['default_subsequent_percentage'] )
		);
		
		update_option( 'snks_ai_profit_global_settings', $global_settings );
		
		echo '<div class="notice notice-success"><p>تم حفظ الإعدادات العامة بنجاح.</p></div>';
	}
	
	// Handle therapist settings
	if ( isset( $_POST['submit_therapist_settings'] ) ) {
		$therapist_ids = $_POST['therapist_ids'] ?? array();
		$first_percentages = $_POST['first_percentage'] ?? array();
		$subsequent_percentages = $_POST['subsequent_percentage'] ?? array();
		$is_active = $_POST['is_active'] ?? array();
		
		$updated_count = 0;
		
		foreach ( $therapist_ids as $therapist_id ) {
			$settings = array(
				'first_session_percentage' => floatval( $first_percentages[ $therapist_id ] ?? 70.00 ),
				'subsequent_session_percentage' => floatval( $subsequent_percentages[ $therapist_id ] ?? 75.00 ),
				'is_active' => intval( $is_active[ $therapist_id ] ?? 1 )
			);
			
			if ( snks_update_therapist_profit_settings( $therapist_id, $settings ) ) {
				$updated_count++;
			}
		}
		
		echo '<div class="notice notice-success"><p>تم تحديث إعدادات ' . $updated_count . ' معالج بنجاح.</p></div>';
	}
}

/**
 * Get all therapists with their profit settings
 */
function snks_get_all_therapists_with_profit_settings() {
	global $wpdb;
	
	$therapists_table = $wpdb->prefix . 'snks_ai_profit_settings';
	
	$therapists = $wpdb->get_results( "
		SELECT 
			u.ID as therapist_id,
			u.display_name,
			u.user_email,
			COALESCE(ps.first_session_percentage, 70.00) as first_session_percentage,
			COALESCE(ps.subsequent_session_percentage, 75.00) as subsequent_session_percentage,
			COALESCE(ps.is_active, 1) as is_active,
			ps.updated_at
		FROM {$wpdb->users} u
		LEFT JOIN $therapists_table ps ON u.ID = ps.therapist_id
		WHERE u.ID IN (
			SELECT user_id FROM {$wpdb->usermeta} 
			WHERE meta_key = '{$wpdb->prefix}capabilities' 
			AND meta_value LIKE '%doctor%'
		)
		ORDER BY u.display_name ASC
	", ARRAY_A );
	
	return $therapists;
}

/**
 * Get profit settings statistics
 */
function snks_get_profit_settings_statistics() {
	global $wpdb;
	
	$therapists_table = $wpdb->prefix . 'snks_ai_profit_settings';
	
	$total_therapists = $wpdb->get_var( "
		SELECT COUNT(*) FROM {$wpdb->users} u
		WHERE u.ID IN (
			SELECT user_id FROM {$wpdb->usermeta} 
			WHERE meta_key = '{$wpdb->prefix}capabilities' 
			AND meta_value LIKE '%doctor%'
		)
	" );
	
	$active_therapists = $wpdb->get_var( "
		SELECT COUNT(*) FROM $therapists_table WHERE is_active = 1
	" );
	
	$custom_settings = $wpdb->get_var( "
		SELECT COUNT(*) FROM $therapists_table
	" );
	
	$default_settings = $total_therapists - $custom_settings;
	
	return array(
		'total_therapists' => $total_therapists,
		'active_therapists' => $active_therapists,
		'custom_settings' => $custom_settings,
		'default_settings' => $default_settings
	);
}
