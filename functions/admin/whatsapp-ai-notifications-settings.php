<?php
/**
 * WhatsApp AI Notifications Settings Page
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Add admin menu for WhatsApp AI Notifications - DEPRECATED
 * Now managed in Therapist Registration Settings page
 * This page is kept only as a redirect
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'shrinks-settings',
			'WhatsApp Notifications (Redirect)',
			'WhatsApp Notifications',
			'manage_options',
			'whatsapp-ai-notifications',
			'snks_whatsapp_ai_notifications_redirect_page'
		);
	},
	20
);

/**
 * WhatsApp AI Notifications Redirect Page
 * Redirects admins to the unified settings in Registration Settings
 */
function snks_whatsapp_ai_notifications_redirect_page() {
	?>
	<div class="wrap">
		<h1>WhatsApp Notifications</h1>
		
		<div class="notice notice-info" style="padding: 20px;">
			<h2 style="margin-top: 0;">✅ تم دمج جميع إعدادات WhatsApp في صفحة واحدة</h2>
			<p style="font-size: 16px;">
				لتسهيل الإدارة، تم نقل جميع إعدادات WhatsApp (API وقوالب الإشعارات) إلى:
			</p>
			<p style="margin: 20px 0;">
				<a href="<?php echo admin_url( 'admin.php?page=therapist-registration-settings' ); ?>" 
				   class="button button-primary button-hero">
					🔧 انتقل إلى إعدادات تسجيل المعالجين
				</a>
			</p>
		</div>
		
		<div class="card" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 20px;">
			<h3>📋 ما ستجده في الصفحة الموحدة:</h3>
			<ul style="list-style: disc; padding-right: 20px; font-size: 14px;">
				<li>✅ <strong>WhatsApp API Configuration:</strong> الرابط، التوكن، رقم الهاتف، اللغة</li>
				<li>✅ <strong>OTP Template:</strong> قالب رمز التحقق</li>
				<li>✅ <strong>AI Notification Templates:</strong> 8 قوالب للإشعارات التلقائية</li>
				<li>✅ <strong>اختبار API:</strong> زر الاختبار المباشر</li>
			</ul>
			
			<h3 style="margin-top: 20px;">🎯 مميزات النظام الموحد:</h3>
			<ul style="list-style: disc; padding-right: 20px; font-size: 14px;">
				<li>✅ جميع الإعدادات في مكان واحد</li>
				<li>✅ لا تكرار أو تضارب في الإعدادات</li>
				<li>✅ سهولة في الصيانة والإدارة</li>
				<li>✅ إعدادات AI منفصلة ومنظمة في نفس الصفحة</li>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * Old settings page - DEPRECATED - kept for reference only
 */
function snks_whatsapp_ai_notifications_page_old() {
	// Handle form submission
	if ( isset( $_POST['submit_whatsapp_ai_notifications'] ) && check_admin_referer( 'snks_whatsapp_ai_notifications', 'snks_whatsapp_ai_notifications_nonce' ) ) {
		$settings = array(
			'enabled' => isset( $_POST['enabled'] ) ? '1' : '0',
			'template_new_session' => sanitize_text_field( $_POST['template_new_session'] ),
			'template_doctor_new' => sanitize_text_field( $_POST['template_doctor_new'] ),
			'template_rosheta10' => sanitize_text_field( $_POST['template_rosheta10'] ),
			'template_rosheta_app' => sanitize_text_field( $_POST['template_rosheta_app'] ),
			'template_patient_rem_24h' => sanitize_text_field( $_POST['template_patient_rem_24h'] ),
			'template_patient_rem_1h' => sanitize_text_field( $_POST['template_patient_rem_1h'] ),
			'template_patient_rem_now' => sanitize_text_field( $_POST['template_patient_rem_now'] ),
			'template_doctor_rem' => sanitize_text_field( $_POST['template_doctor_rem'] ),
		);
		
		update_option( 'snks_whatsapp_ai_notifications', $settings );
		echo '<div class="notice notice-success is-dismissible"><p>تم حفظ الإعدادات بنجاح!</p></div>';
	}
	
	$settings = snks_get_whatsapp_notification_settings();
	?>
	
	<div class="wrap">
		<h1>إعدادات إشعارات WhatsApp لجلسة AI</h1>
		<p class="description">
			قم بإدخال أسماء قوالب WhatsApp المعتمدة من WhatsApp Business API. جميع الإشعارات تعمل فقط لجلسات AI.
		</p>
		
		<div class="notice notice-info">
			<p>
				<strong>ملاحظة:</strong> إعدادات WhatsApp API (الرابط، التوكن، رقم الهاتف) موجودة في 
				<a href="<?php echo admin_url( 'admin.php?page=therapist-registration-settings' ); ?>">إعدادات تسجيل المعالجين</a>.
				هذه الصفحة خاصة فقط بأسماء قوالب الإشعارات التلقائية لجلسات AI.
			</p>
		</div>
		
		<div class="notice notice-warning">
			<p><strong>متطلبات:</strong> يجب إنشاء واعتماد جميع القوالب في WhatsApp Business API قبل استخدامها. أدخل اسم القالب فقط، وسيتم إرسال المتغيرات المطلوبة تلقائياً.</p>
		</div>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'snks_whatsapp_ai_notifications', 'snks_whatsapp_ai_notifications_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="enabled">تفعيل الإشعارات</label>
					</th>
					<td>
						<input type="checkbox" name="enabled" id="enabled" value="1" <?php checked( $settings['enabled'], '1' ); ?>>
						<p class="description">تفعيل إرسال إشعارات WhatsApp لجلسات AI تلقائياً</p>
					</td>
				</tr>
			</table>
			
			<h2>أسماء قوالب WhatsApp</h2>
			<p class="description">أدخل أسماء القوالب المعتمدة في WhatsApp Business API (أسماء القوالب فقط، بدون محتوى).</p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="template_new_session">حجز جلسة جديدة للمريض</label>
					</th>
					<td>
						<input type="text" name="template_new_session" id="template_new_session" 
						       value="<?php echo esc_attr( $settings['template_new_session'] ); ?>" 
						       class="regular-text" placeholder="new_session">
						<p class="description">
							يُرسل للمريض عند حجز جلسة AI جديدة | المتغيرات: doctor, day, date, time
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_doctor_new">حجز جلسة جديدة للمعالج</label>
					</th>
					<td>
						<input type="text" name="template_doctor_new" id="template_doctor_new" 
						       value="<?php echo esc_attr( $settings['template_doctor_new'] ); ?>" 
						       class="regular-text" placeholder="doctor_new">
						<p class="description">
							يُرسل للمعالج عند حجز مريض جلسة AI | المتغيرات: patient, day, date, time
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_rosheta10">تفعيل خدمة روشتة</label>
					</th>
					<td>
						<input type="text" name="template_rosheta10" id="template_rosheta10" 
						       value="<?php echo esc_attr( $settings['template_rosheta10'] ); ?>" 
						       class="regular-text" placeholder="rosheta10">
						<p class="description">
							يُرسل للمريض عند تفعيل خدمة روشتة من قبل المعالج | المتغيرات: patient, doctor
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_rosheta_app">حجز موعد روشتة</label>
					</th>
					<td>
						<input type="text" name="template_rosheta_app" id="template_rosheta_app" 
						       value="<?php echo esc_attr( $settings['template_rosheta_app'] ); ?>" 
						       class="regular-text" placeholder="rosheta_app">
						<p class="description">
							يُرسل للمريض بعد تحديد موعد جلسة روشتة | المتغيرات: day, date, time
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_patient_rem_24h">تذكير المريض قبل 24 ساعة</label>
					</th>
					<td>
						<input type="text" name="template_patient_rem_24h" id="template_patient_rem_24h" 
						       value="<?php echo esc_attr( $settings['template_patient_rem_24h'] ); ?>" 
						       class="regular-text" placeholder="patient_rem_24h">
						<p class="description">
							يُرسل للمريض قبل موعد جلسة AI بـ 24 ساعة | المتغيرات: doctor, day, date, time
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_patient_rem_1h">تذكير المريض قبل ساعة</label>
					</th>
					<td>
						<input type="text" name="template_patient_rem_1h" id="template_patient_rem_1h" 
						       value="<?php echo esc_attr( $settings['template_patient_rem_1h'] ); ?>" 
						       class="regular-text" placeholder="patient_rem_1h">
						<p class="description">
							يُرسل للمريض قبل موعد جلسة AI بساعة واحدة | بدون متغيرات
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_patient_rem_now">إشعار دخول المعالج</label>
					</th>
					<td>
						<input type="text" name="template_patient_rem_now" id="template_patient_rem_now" 
						       value="<?php echo esc_attr( $settings['template_patient_rem_now'] ); ?>" 
						       class="regular-text" placeholder="patient_rem_now">
						<p class="description">
							يُرسل للمريض عند دخول المعالج لجلسة AI | بدون متغيرات
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="template_doctor_rem">تذكير المعالج بجلسات الغد</label>
					</th>
					<td>
						<input type="text" name="template_doctor_rem" id="template_doctor_rem" 
						       value="<?php echo esc_attr( $settings['template_doctor_rem'] ); ?>" 
						       class="regular-text" placeholder="doctor_rem">
						<p class="description">
							يُرسل للمعالج الساعة 12 ليلاً إذا كان لديه جلسات AI في اليوم التالي | المتغيرات: day, date
						</p>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="submit_whatsapp_ai_notifications" class="button button-primary" value="حفظ الإعدادات">
			</p>
		</form>
	</div>
	
	<style>
		.card {
			background: white;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 15px;
			margin: 15px 0;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}
		.card h3 {
			margin-top: 0;
			color: #1d2327;
		}
		.card code {
			font-size: 13px;
			line-height: 1.6;
		}
	</style>
	<?php
}

