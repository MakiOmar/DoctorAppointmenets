<?php
/**
 * WhatsApp AI Notifications Settings Page
 *
 * @package Shrinks
 */

defined( 'ABSPATH' ) || die();

/**
 * Add admin menu for WhatsApp AI Notifications
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'shrinks-settings',
			'WhatsApp AI Notifications',
			'WhatsApp AI Notifications',
			'manage_options',
			'whatsapp-ai-notifications',
			'snks_whatsapp_ai_notifications_page'
		);
	},
	20
);

/**
 * WhatsApp AI Notifications Settings Page
 */
function snks_whatsapp_ai_notifications_page() {
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
			قم بتكوين قوالب WhatsApp للإشعارات التلقائية لجلسات AI فقط. تأكد من إنشاء القوالب في WhatsApp Business API أولاً.
		</p>
		
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
			
			<h2>قوالب الإشعارات</h2>
			<p class="description">أدخل أسماء القوالب المطابقة لتلك المكونة في WhatsApp Business API. جميع الإشعارات تعمل فقط لجلسات AI.</p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="template_new_session">حجز جلسة جديدة للمريض</label>
					</th>
					<td>
						<input type="text" name="template_new_session" id="template_new_session" 
						       value="<?php echo esc_attr( $settings['template_new_session'] ); ?>" 
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمريض عند حجز جلسة أونلاين جديدة<br>
							<strong>المتغيرات:</strong> {{doctor}}, {{day}}, {{date}}, {{time}}<br>
							<strong>التمبلت الافتراضي:</strong> new_session
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمعالج عند حجز مريض جلسة جديدة<br>
							<strong>المتغيرات:</strong> {{patient}}, {{day}}, {{date}}, {{time}}<br>
							<strong>التمبلت الافتراضي:</strong> doctor_new
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمريض عند تفعيل خدمة روشتة من قبل المعالج<br>
							<strong>المتغيرات:</strong> {{patient}}, {{doctor}}<br>
							<strong>التمبلت الافتراضي:</strong> rosheta10
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمريض بعد تحديد موعد جلسة روشتة<br>
							<strong>المتغيرات:</strong> {{day}}, {{date}}, {{time}}<br>
							<strong>التمبلت الافتراضي:</strong> rosheta_app
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمريض قبل موعد الجلسة بـ 24 ساعة<br>
							<strong>المتغيرات:</strong> {{doctor}}, {{day}}, {{date}}, {{time}}<br>
							<strong>التمبلت الافتراضي:</strong> patient_rem_24h
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمريض قبل موعد الجلسة بساعة واحدة<br>
							<strong>المتغيرات:</strong> لا توجد متغيرات<br>
							<strong>التمبلت الافتراضي:</strong> patient_rem_1h
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمريض عند دخول المعالج للجلسة<br>
							<strong>المتغيرات:</strong> لا توجد متغيرات<br>
							<strong>التمبلت الافتراضي:</strong> patient_rem_now
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
						       class="regular-text">
						<p class="description">
							<strong>الإشعار:</strong> يُرسل للمعالج الساعة 12 ليلاً إذا كان لديه جلسات AI في اليوم التالي<br>
							<strong>المتغيرات:</strong> {{day}}, {{date}}<br>
			        <strong>التمبلت الافتراضي:</strong> doctor_rem
						</p>
					</td>
				</tr>
			</table>
			
			<h2>محتوى القوالب (للمرجعية فقط)</h2>
			<p class="description">هذه أمثلة لمحتوى القوالب. يجب إنشاءها في WhatsApp Business API أولاً.</p>
			
			<div class="card">
				<h3>new_session - حجز جلسة جديدة للمريض</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5; white-space: pre-wrap;">تم حجز جلسة أونلاين مع المعالج {{doctor}} يوم {{day}} الموافق {{date}} الساعة {{time}} بتوقيت مصر، ويمكنك الدخول للجلسة في موعدها من خلال صفحة الحجوزات بحسابك على موقع جلسة، وسيتم عمل الجلسة عبر نظام الاتصالات الخاص بالموقع.

ملاحظات:
- مدة الجلسة 45 دقيقة فقط.
- يمكنك الدخول للجلسة سواء من الموبايل أو اللابتوب.
- يجب الدخول للجلسة في موعدها تفاديا لالغاء الموعد.
- يرجى التأكد من جودة الانترنت قبل الدخول للجلسة والجلوس في مكان هادئ للحصول على أفضل تجربة.
- عند الدخول للجلسة سيطلب المتصفح منك السماح باستخدام المايك والكاميرا ويجب السماح بذلك لضمان عملهم أثناء الجلسة.
- في حالة ما اذا كانت مشكلتك تستدعي وصف أدوية وكان المعالج غير مصرح له بكتابة أدوية، يرجى الطلب من المعالج أثناء الجلسه تفعيل خدمة روشتة لك وذلك لتتمكن من عمل جلسة مجانية مع الطبيب النفسي الأخصائي الخاص بالموقع لكتابة ومتابعة الأدوية فقط بجانب جلساتك مع المعالج.

لأي استفسار يرجى التواصل مع خدمة العملاء على رقم الواتساب:
https://wa.me/+201097799323</code>
			</div>
			
			<div class="card">
				<h3>doctor_new - حجز جلسة جديدة للمعالج</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5;">تم حجز جلسة جديدة عبر ( موقع جلسة أونلاين ).
إسم العميل:  {{patient}}
اليوم: {{day}}
الموافق: {{date}}
الساعة: {{time}} بتوقيت مصر</code>
			</div>
			
			<div class="card">
				<h3>rosheta10 - تفعيل خدمة روشتة</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5;">أهلا {{patient}}! 
قام المعالج الخاص بك ( {{doctor}} ) بتفعيل خدمة روشتة لك، يرجى تحديد موعد الجلسه عن طريق صفحة الحجوزات خلال أسبوع من الآن لتفادي إلغاء الخدمة.</code>
			</div>
			
			<div class="card">
				<h3>rosheta_app - حجز موعد روشتة</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5; white-space: pre-wrap;">تم حجز جلسة مع الطبيب النفسي الأخصائي الخاص بالموقع يوم {{day}} الموافق {{date}} الساعة {{time}} بتوقيت مصر، ويمكنك الدخول للجلسة في موعدها من خلال صفحة الحجوزات بحسابك على موقع جلسة.

ملاحظات:
- مدة الجلسة 15 دقيقة فقط.
- الطبيب سيكون مسئول فقط عن وصف الأدوية، وللاستفسارات الأخرى يرجى المتابعة مع المعالج الخاص بك.
- يمكنك الدخول للجلسة سواء من الموبايل أو اللابتوب.
- يجب الدخول للجلسة في موعدها تفاديا لالغاء الموعد.
- يرجى التأكد من جودة الانترنت والجلوس في مكان هادئ قبل الدخول للجلسة للحصول على أفضل تجربة.
- عند الدخول للجلسة سيطلب المتصفح منك السماح باستخدام المايك والكاميرا ويجب السماح بذلك لضمان عملهم أثناء الجلسة.</code>
			</div>
			
			<div class="card">
				<h3>patient_rem_24h - تذكير المريض قبل 24 ساعة</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5; white-space: pre-wrap;">نذكرك بموعد جلستك غدا {{day}} الموافق {{date}} على موقع جلسة مع المعالج {{doctor}} الساعة {{time}} بتوقيت مصر، ويمكنك الدخول للجلسة في موعدها من خلال صفحة الحجوزات بحسابك على الموقع، وسيتم عمل الجلسة عبر نظام الاتصالات الخاص بالموقع.

ملاحظات:
- مدة الجلسة 45 دقيقة فقط.
- يمكنك الدخول للجلسة سواء من الموبايل أو اللابتوب.
- يجب الدخول للجلسة في موعدها تفاديا لالغاء الموعد.
- يرجى التأكد من جودة الانترنت قبل الدخول للجلسة والجلوس في مكان هادئ للحصول على أفضل تجربة.
- عند الدخول للجلسة سيطلب المتصفح منك السماح باستخدام المايك والكاميرا ويجب السماح بذلك لضمان عملهم أثناء الجلسة.
- في حالة ما اذا كانت مشكلتك تستدعي وصف أدوية وكان المعالج غير مصرح له بكتابة أدوية، يرجى الطلب من المعالج أثناء الجلسه تفعيل خدمة روشتة لك وذلك لتتمكن من عمل جلسة مجانية مع الطبيب النفسي الأخصائي الخاص بالموقع لكتابة ومتابعة الأدوية فقط بجانب جلساتك مع المعالج.</code>
			</div>
			
			<div class="card">
				<h3>patient_rem_1h - تذكير المريض قبل ساعة</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5;">باقي أقل من ساعة على موعد الجلسة، يرجى الدخول للجلسة في موعدها تفاديا لإلغاء الحجز, ويمكنك الدخول للجلسة من خلال صفحة الحجوزات بحسابك على الموقع.</code>
			</div>
			
			<div class="card">
				<h3>patient_rem_now - إشعار دخول المعالج</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5;">المعالج متواجد وبإنتظارك لبدء الجلسة.</code>
			</div>
			
			<div class="card">
				<h3>doctor_rem - تذكير المعالج بجلسات الغد</h3>
				<code dir="rtl" style="display: block; padding: 10px; background: #f5f5f5;">نذكرك بجلساتك غدا {{day}} الموافق {{date}} المحجوزه عبر ( موقع جلسة أونلاين )، يرجى التأكد من التواجد والدخول لكل جلسة في موعدها</code>
			</div>
			
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

