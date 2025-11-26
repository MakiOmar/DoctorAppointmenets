<?php
/**
 * Therapist Registration Shortcode
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Therapist Registration Shortcode
 */
function snks_therapist_registration_shortcode( $atts ) {
	// Get settings
	$settings = snks_get_therapist_registration_settings();
	
	// Enqueue necessary scripts and styles
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'wp-util' );
	
	ob_start();
	?>
	<div id="therapist-registration-form-container">
		<style>
		/* Two-step registration styles */
		.registration-step {
			display: none;
		}
		.registration-step.active {
			display: block;
		}
		.instructions-step {
			max-width: 800px;
			margin: 0 auto;
			padding: 30px;
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			direction: rtl;
			text-align: right;
		}
		.instructions-step h2 {
			text-align: center;
			color: #1f2937;
			margin-bottom: 20px;
			font-size: 24px;
			font-weight: 700;
		}
		.instructions-step .instructions-header {
			text-align: center;
			color: #dc3545;
			font-weight: 600;
			margin-bottom: 30px;
			font-size: 16px;
		}
		.instructions-step .divider {
			text-align: center;
			margin: 30px 0;
			color: #6b7280;
			font-size: 18px;
		}
		.instructions-step .instruction-item {
			margin-bottom: 25px;
			padding: 15px;
			background: #f9fafb;
			border-radius: 6px;
			border-right: 4px solid #2271b1;
		}
		.instructions-step .instruction-item h3 {
			margin: 0 0 12px 0;
			color: #1f2937;
			font-size: 18px;
			font-weight: 600;
		}
		.instructions-step .instruction-item ul {
			margin: 12px 0;
			padding-right: 25px;
			list-style-type: disc;
		}
		.instructions-step .instruction-item li {
			margin-bottom: 8px;
			color: #374151;
			line-height: 1.6;
		}
		.instructions-step .instruction-item p {
			margin: 8px 0;
			color: #374151;
			line-height: 1.6;
		}
		.instructions-step .notes-section {
			background: #fef3c7;
			border: 1px solid #fbbf24;
			border-radius: 6px;
			padding: 20px;
			margin-top: 30px;
		}
		.instructions-step .notes-section h3 {
			margin: 0 0 15px 0;
			color: #92400e;
			font-size: 18px;
			font-weight: 600;
		}
		.instructions-step .notes-section ul {
			margin: 0;
			padding-right: 25px;
			list-style-type: disc;
		}
		.instructions-step .notes-section li {
			margin-bottom: 10px;
			color: #78350f;
			line-height: 1.6;
		}
		.continue-btn, .back-btn {
			background: #2271b1;
			color: #fff;
			border: none;
			padding: 15px 40px;
			border-radius: 6px;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			transition: background 0.3s;
			display: block;
			margin: 30px auto 0;
		}
		.continue-btn:hover, .back-btn:hover {
			background: #1d5f98;
		}
		.back-btn {
			background: #6b7280;
			margin: 0 0 20px 0;
		}
		.back-btn:hover {
			background: #4b5563;
		}
		.therapist-reg-form {
			max-width: 600px;
			margin: 0 auto;
			padding: 20px;
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		.therapist-reg-form h2 {
			text-align: center;
			color: #333;
			margin-bottom: 30px;
		}

		.form-group:first-of-type {
			margin-top: 0;
		}
		.form-group label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			color: #555;
			line-height: 2;
		}
		.form-group input,
		.form-group select,
		.form-group textarea {
			width: 100%;
			padding: 12px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
			box-sizing: border-box;
		}
		.form-warning {
			background: #fff4e5;
			border-left: 4px solid #f2994a;
			padding: 12px 16px;
			border-radius: 6px;
			color: #92400e;
			margin: 18px 0;
			font-size: 14px;
			display: flex;
			gap: 8px;
			align-items: center;
		}
		.form-warning .warning-icon {
			font-size: 18px;
		}
		/* Prevent iOS zoom on input focus */
		@supports (-webkit-touch-callout: none) {
			.form-group input[type="text"],
			.form-group input[type="email"],
			.form-group input[type="tel"],
			.form-group input[type="password"],
			.form-group textarea,
			.form-group select,
			.dynamic-row input[type="text"],
			.diagnosis-list input[type="checkbox"],
			.category-list input[type="checkbox"] {
				font-size: 16px !important;
			}
		}
		.form-group input:focus,
		.form-group select:focus,
		.form-group textarea:focus {
			outline: none;
			border-color: #2271b1;
			box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.1);
		}
		.phone-input-group {
			display: flex;
			gap: 10px;
		}
		.country-code-select {
			flex: 0 0 120px;
		}
		.phone-number-input {
			flex: 1;
		}
		.file-upload-group {
			position: relative;
			border: 2px dashed #ddd;
			padding: 30px 20px;
			text-align: center;
			border-radius: 8px;
			background: #fafafa;
			transition: all 0.3s ease;
			cursor: pointer;
			margin-top: 24px;
		}
		.file-upload-group:hover, .file-upload-group.dragover {
			border-color: #2271b1;
			background: #f0f6ff;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(34, 113, 177, 0.1);
		}
		.file-upload-group input[type="file"] {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			opacity: 0;
			cursor: pointer;
		}
		.upload-icon {
			font-size: 48px;
			color: #2271b1;
			margin-bottom: 15px;
			display: block;
		}
		.upload-text {
			font-size: 16px;
			color: #333;
			margin-bottom: 8px;
			font-weight: 600;
		}
		.upload-hint {
			font-size: 14px;
			color: #666;
			margin-bottom: 15px;
		}
		.file-preview {
			display: flex;
			flex-wrap: wrap;
			gap: 15px;
			margin-top: 20px;
			justify-content: center;
		}
		.preview-item {
			position: relative;
			width: 120px;
			height: 120px;
			border-radius: 8px;
			overflow: hidden;
			border: 2px solid #e0e0e0;
			background: #fff;
		}
		.preview-image {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		.preview-file {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 10px;
			height: 100%;
			text-align: center;
		}
		.file-icon {
			font-size: 24px;
			color: #2271b1;
			margin-bottom: 8px;
		}
		.file-name {
			font-size: 12px;
			color: #333;
			word-break: break-all;
			line-height: 1.2;
		}
		.remove-file {
			position: absolute;
			top: 5px;
			right: 5px;
			width: 24px;
			height: 24px;
			background: #ff4757;
			color: white;
			border: none;
			border-radius: 50%;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 14px;
			z-index: 10;
		}
		.remove-file:hover {
			background: #ff3838;
		}
		.upload-progress {
			width: 100%;
			height: 6px;
			background: #e0e0e0;
			border-radius: 3px;
			margin-top: 10px;
			overflow: hidden;
		}
		.progress-bar {
			height: 100%;
			background: linear-gradient(90deg, #2271b1, #4fc3f7);
			transition: width 0.3s ease;
			border-radius: 3px;
		}
		.file-size {
			font-size: 11px;
			color: #888;
			margin-top: 4px;
		}
		.max-files-notice {
			background: #fff3cd;
			color: #856404;
			padding: 12px;
			border-radius: 6px;
			margin-top: 15px;
			border: 1px solid #ffeaa7;
			font-size: 14px;
		}
		.submit-btn {
			background: #2271b1;
			color: #fff;
			border: none;
			padding: 15px 30px;
			border-radius: 4px;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			width: 100%;
			transition: background 0.3s;
		}
		.submit-btn:hover {
			background: #1d5f98;
		}
		.submit-btn:disabled {
			background: #ccc;
			cursor: not-allowed;
		}
		.alert {
			padding: 12px;
			border-radius: 4px;
			margin: 15px 0;
		}
		.alert-success {
			background: #d4edda;
			border: 1px solid #c3e6cb;
			color: #155724;
		}
		.alert-error {
			background: #f8d7da;
			border: 1px solid #f5c6cb;
			color: #721c24;
		}
		.required {
			color: #dc3545;
		}
		.form-section {
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 30px;
			background: #fdfdfd;
		}
		.section-header {
			margin-bottom: 15px;
		}
		.section-header h3 {
			margin: 0 0 8px;
			font-size: 18px;
			color: #1f2937;
			text-align: center;
			background: #f3f4f6;
			padding: 12px 20px;
			border-radius: 6px;
		}
		.section-note {
			margin: 0;
			font-size: 14px;
			color: #6b7280;
			line-height: 2;
		}
		.section-body {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		.inline-options {
			display: flex;
			flex-wrap: nowrap;
			gap: 24px;
			align-items: center;
		}
		.inline-options.vertical-options {
			flex-direction: column;
			align-items: flex-start;
			gap: 12px;
		}
		#therapist-registration-form .form-group > p{
			margin-bottom: 10px;
		}
		.inline-options label {
			display: flex;
			align-items: center;
			gap: 8px;
			white-space: nowrap;
			font-weight: 500;
			color: #374151;
			line-height: 2;
		}
		.role-panel {
			border-top: 1px dashed #e5e7eb;
			padding-top: 20px;
			margin-top: 10px;
			display: none;
		}
		.form-subsection h4 {
			margin:20px 0 8px 0 ;
			font-size: 16px;
			color: #1f2937;
		}
		#children-dx-section h4,
		#adult-dx-section h4 {
			font-size: 18px;
			text-align: center;
			margin-bottom: 20px;
			font-weight: 600;
		}
		.file-upload-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 16px;
		}
		.dynamic-row {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			align-items: center;
			margin-bottom: 12px;
		}
		.dynamic-row input[type="text"],
		.dynamic-row input[type="file"] {
			flex: 1 1 200px;
		}
		.remove-row-btn {
			background-color: #fee2e2;
			border: 1px solid #fecaca;
			color: #991b1b;
			border-radius: 6px;
			padding: 6px 12px;
			cursor: pointer;
			transition: background 0.2s ease;
		}
		.remove-row-btn:hover {
			background-color: #fecaca;
		}
		.category-list {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 12px;
		}
		.category-box {
			background: #f9fafb;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 12px 16px;
			display: flex;
			align-items: center;
			gap: 10px;
			transition: background 0.2s ease, border 0.2s ease;
			line-height: 1.5;
		}
		.category-box.disabled {
			opacity: 0.6;
			cursor: not-allowed;
		}
		.helper-text {
			font-size: 13px;
			color: #6b7280;
		}
		.max-selection-message {
			color: #b91c1c;
			font-weight: 600;
			display: none;
		}
		.diagnosis-list {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
			gap: 12px;
		}
		.diagnosis-list label {
			display: flex;
			align-items: center;
			gap: 8px;
			line-height: 2;
		}
		.english-tooltip {
			position: relative;
			display: inline-block;
			cursor: help;
			color: #2271b1;
			margin-right: 4px;
			font-size: 14px;
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}
		.english-tooltip::after {
			content: attr(data-tooltip);
			position: fixed;
			bottom: auto;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			background-color: #333;
			color: #fff;
			padding: 12px 16px;
			border-radius: 8px;
			font-size: 13px;
			opacity: 0;
			pointer-events: none;
			transition: opacity 0.3s;
			z-index: 10000;
			min-width: 250px;
			max-width: 90vw;
			white-space: normal;
			text-align: left;
			box-shadow: 0 4px 12px rgba(0,0,0,0.3);
			line-height: 1.5;
		}
		.english-tooltip::before {
			content: '';
			position: fixed;
			top: 50%;
			left: 50%;
			transform: translate(-50%, calc(-50% - 60px));
			border: 8px solid transparent;
			border-top-color: #333;
			opacity: 0;
			pointer-events: none;
			transition: opacity 0.3s;
			z-index: 10000;
		}
		.english-tooltip:hover::after,
		.english-tooltip:hover::before,
		.english-tooltip.active::after,
		.english-tooltip.active::before {
			opacity: 1;
		}
		.tooltip-overlay {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.3);
			z-index: 9999;
			display: none;
			cursor: pointer;
		}
		.tooltip-overlay.active {
			display: block;
		}
		body.tooltip-active {
			overflow: hidden;
		}
		body.tooltip-active .diagnosis-list input[type="checkbox"],
		body.tooltip-active .category-list input[type="checkbox"] {
			pointer-events: none;
		}
		@media (min-width: 768px) {
			.english-tooltip::after {
				position: absolute;
				top: auto;
				bottom: 100%;
				left: auto;
				right: 50%;
				transform: translateX(50%);
				max-width: 350px;
			}
			.english-tooltip::before {
				position: absolute;
				top: auto;
				bottom: 100%;
				left: auto;
				right: 50%;
				transform: translateX(50%);
				margin-bottom: 2px;
			}
			.tooltip-overlay {
				display: none !important;
			}
		}
		/* RTL Support */
		[dir="rtl"] .phone-input-group {
			direction: ltr;
		}
		[dir="rtl"] .form-group label {
			text-align: right;
		}
		.input-error {
			border-color: #dc3545 !important;
			box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.25);
		}
		.error-message {
			color: #dc3545;
			font-size: 12px;
			margin-top: 6px;
		}
		.file-upload-group.input-error {
			border-color: #dc3545 !important;
			border-style: solid;
			background: #fef2f2;
			box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.15);
		}
		#therapy-certificates.input-error {
			border: 2px dashed #dc3545;
			padding: 20px;
			border-radius: 8px;
			background: #fef2f2;
		}
		.form-group.input-error label {
			color: #dc3545;
		}
		.form-group.input-error input,
		.form-group.input-error select,
		.form-group.input-error textarea {
			border-color: #dc3545 !important;
			background: #fef2f2;
		}
		.form-subsection.input-error,
		.category-list.input-error,
		.inline-options.input-error {
			border: 1px solid #dc3545;
			border-radius: 8px;
			padding: 16px;
			background: #fef2f2;
		}
		input.input-error,
		textarea.input-error,
		select.input-error {
			border-color: #dc3545 !important;
			background: #fef2f2;
		}
		.optional-text {
			font-size: 0.85rem;
			color: #6c757d;
			margin-inline-start: 8px;
		}
		</style>
		
		<!-- Step 1: Instructions -->
		<div id="instructions-step" class="registration-step active">
			<div class="instructions-step">
				<h2>كيفية عمل دليل موقع جلسة</h2>
				<p class="instructions-header">( يجب قراءة النقاط التالية قبل التسجيل )</p>
				<div class="divider">——————————————————-</div>
				
				<div class="instruction-item">
					<p>الدليل خاص بالمعالجين المعتمدين فقط ( اطباء نفسيين واخصائيين نفسيين اكلينيكيين ).</p>
				</div>
				
				<div class="instruction-item">
					<p>يعتمد دليل موقع جلسة في تقييم المعالجين علي نقاط كل معالج في كل تشخيص من تشخيصات الصحة النفسية وذلك كبديل اكثر مصداقية من تقييم العملاء للمعالجين ، وبالتالي يمكن ان يكون ترتيبك في المقدمه في بعض التشخيصات دونا عن التشخيصات الاخرى حتى وان سجلت حديثا بالدليل، وبالتالي يتيح ذلك لجميع المعالجين الظهور بشكل متوازن بدلا مش احتكار ظهور اصحاب التقييمات العاليه فقط في البداية في انظمة تقييم العملاء.</p>
				</div>
				
				<div class="instruction-item">
					<h3>يتم تحديد نقاط كل معالج في كل تشخيص بناء على عدة عوامل:</h3>
					<ul>
						<li>الدرجة العلمية.</li>
						<li>عدد سنين الخبرة بعد الدرجة العلميه.</li>
						<li>المدارس العلاجية التي يستخدمها المعالج.</li>
						<li>قوة التدريب قي المدرسة العلاجية كمكان التدريب وعدد ساعاته ووجود الاشراف من عدمه او اذا ما كان تعليم ذاتي أو خبرة شخصية.</li>
						<li>عدد سنين الخبره بعد الحصول علي اي تدريب.</li>
						<li>الحصول على تدريب متخصص في علاج اضطراب معين.</li>
						<li>تفضيلات المعالج الشخصية لتشخيصات معينة.</li>
					</ul>
				</div>
				
				<div class="instruction-item">
					<h3>بعد الإنضمام للدليل يوجد عدة عوامل تؤدي تلقائيا الي خفض تقييمك:</h3>
					<ul>
						<li>عدم الالتزام بالدخول في مواعيد جلساتك.</li>
						<li>الاعتذار عن الجلسات بشكل متكرر وعدم ابلاغ خدمة العملاء  قبل موعد الجلسة بمدة مناسبة.</li>
						<li>انخفاض معدل اعادة الحجز معك من نفس العميل في تشخيصات معينة.</li>
						<li>محاولة اعطاء او الحصول على اي بيانات تواصل من العملاء، او التلميح للعميل بالحجز معك خارج الموقع.</li>
					</ul>
				</div>
				
				<div class="notes-section">
					<h3>ملاحظات:</h3>
					<ul>
						<li>لن يتم احتساب نقاط اي معلومات غير دقيقه او اي صور للشهادات غير واضحة.</li>
						<li>بعد الانضمام للدليل يمكنك تعديل تقييمك في حالة حصولك علي اي شهادات او خبرات اضافية عن طريق التواصل مع خدمة العملاء.</li>
						<li>بعد اتمام التسجيل ستتواصل معك خدمة العملاء بالموقع  في اقرب وقت، وذلك لاستلام حساب لوحة التحكم الخاصة بك لبدء اضافة مواعيدك واسعار جلساتك لداخل او خارج مصر، وسيتم ارسال فيديو لشرح طريقة التعامل مع الموقع بسهولة.</li>
						<li>يحصل موقع جلسة على ٤٠٪؜ من سعر الجلسة ويتم تحويل نسبة ال٦٠٪؜ لرصيدك علي الموقع بعد اتمام الجلسة ، ويمكنك سحب اي مبالغ موجودة بحسابك خلال يوم عمل واحد.</li>
						<li>يمكنك استخدام حسابك بالموقع ايضا لعمل صفحة شخصية لادارة حجوزاتك الشخصية بشكل تلقائي، وفي حالة حجز عميل خاص بك من خلال تلك الصفحة لن يتم خصم اي نسبة من سعر الجلسه وستحصل علي مبلغ الجلسه كاملا، ستتيح لك صفحتك الشخصية عدة ميزات حيث ستقوم بكل وظيفة السكرتير والمحاسب بشكل تلقائي، وستستطيع من خلالها استقبال اتعاب جلساتك من جميع انحاء العالم لان صفحتك ستدعم الدفع بالفيزا وماستر كارد بالاضافه لوسائل الدفع الموجوده بمصر، وستحتوي صفحتك ايضا علي نظام اتصالات خاص بك بجوده عاليه وبلا حدود .</li>
						<li>للمصداقيه مع العملاء سيتم وضع شهاداتك علي الموقع مع ازاله اي معلومات شخصية على الشهادة وابقاء البيانات العلميه فقط، مع تخفيض جودة صورة الشهادة.</li>
					</ul>
				</div>
				
				<button type="button" class="continue-btn" id="continue-to-form-btn">متابعة</button>
			</div>
		</div>
		
		<!-- Step 2: Registration Form -->
		<div id="form-step" class="registration-step">
			<form id="therapist-registration-form" class="therapist-reg-form" enctype="multipart/form-data" novalidate>
				<button type="button" class="back-btn" id="back-to-instructions-btn">← رجوع لصفحة التعليمات</button>
			<h2>تسجيل معالج جديد</h2>
			
			<div id="form-messages"></div>
			
			<div class="form-section">
				<div class="section-header">
					<h3>البيانات الشخصية</h3>
					<p class="section-note">يرجى ادخال بياناتك الشخصية</p>
				</div>
				<div class="section-body">
			<div class="form-group">
						<label for="name">الاسم الكامل (بالعربية) <span class="required">*</span></label>
				<input type="text" id="name" name="name" required>
			</div>
			
			<div class="form-group">
						<label for="email">البريد الإلكتروني <span class="required">*</span></label>
						<input type="email" id="email" name="email" required>
			</div>
			
			<div class="form-group">
						<label for="phone">رقم الهاتف <span class="required">*</span></label>
						<input type="tel" id="phone" name="phone" required>
			</div>
			
			<div class="form-group">
						<label for="whatsapp">رقم واتساب <span class="required">*</span></label>
						<input type="tel" id="whatsapp" name="whatsapp" required>
			</div>
			
			<div class="form-group">
						<label for="profile_image">الصورة الشخصية <span class="required">*</span></label>
						<div class="file-upload-group" data-field="profile_image">
					<span class="upload-icon">📷</span>
					<div class="upload-text">ارفع الصورة الشخصية</div>
					<div class="upload-hint">ملف صورة (JPG أو PNG)</div>
					<input type="file" id="profile_image" name="profile_image" accept="image/*" required>
							<div class="file-preview" id="preview_profile_image"></div>
				</div>
					</div>
					
					<div class="form-subsection">
						<h4>البطاقة الشخصية</h4>
						<p class="section-note">يرجى رفع صورة البطاقة الشخصية (وجه وظهر).</p>
						<div class="file-upload-grid">
				<div class="file-upload-group" data-field="identity_front">
					<span class="upload-icon">🪪</span>
								<div class="upload-text">البطاقة الشخصية (وجه)</div>
								<div class="upload-hint">صورة (JPG أو PNG)</div>
								<input type="file" id="identity_front" name="identity_front" accept="image/*">
					<div class="file-preview" id="preview_identity_front"></div>
				</div>
				<div class="file-upload-group" data-field="identity_back">
					<span class="upload-icon">🆔</span>
								<div class="upload-text">البطاقة الشخصية (ظهر)</div>
								<div class="upload-hint">صورة (JPG أو PNG)</div>
								<input type="file" id="identity_back" name="identity_back" accept="image/*">
					<div class="file-preview" id="preview_identity_back"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<input type="hidden" id="doctor_specialty" name="doctor_specialty">
			
			<div class="form-section">
				<div class="section-header">
					<h3>المعلومات المهنية</h3>
					<p class="section-note">اختر المسمى الوظيفي وأرفق المستندات المطلوبة.</p>
				</div>
				<div class="section-body">
			<div class="form-group">
						<p>اختر المسمى الوظيفي <span class="required">*</span></p>
						<div class="inline-options vertical-options">
							<label><input type="radio" name="role" value="psychiatrist"> طبيب نفسي</label>
							<label><input type="radio" name="role" value="clinical_psychologist"> أخصائي نفسي إكلينيكي</label>
						</div>
			</div>
			
					<div id="psychiatrist-section" class="role-panel">
						<div class="form-subsection">
							<h4>اختر الدرجة / الرتبة <span class="required">*</span></h4>
							<div class="inline-options vertical-options">
								<label><input type="radio" name="psy_rank" value="resident"> طبيب مقيم طب نفسي</label>
								<label><input type="radio" name="psy_rank" value="specialist"> أخصائي طب نفسي</label>
								<label><input type="radio" name="psy_rank" value="consultant"> استشاري طب نفسي</label>
							</div>
						</div>
						<div class="form-subsection">
							<h4>المستندات المطلوبة <span class="required">*</span></h4>
							<div class="file-upload-grid">
								<div class="file-upload-group" data-field="grad_cert">
									<span class="upload-icon">🎓</span>
									<div class="upload-text">شهادة التخرج</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="grad_cert" name="grad_cert" accept="image/*,.pdf,.txt,.doc,.docx">
									<div class="file-preview" id="preview_grad_cert"></div>
								</div>
								<div class="file-upload-group" data-field="practice_license">
									<span class="upload-icon">📝</span>
									<div class="upload-text">تصريح مزاولة المهنة</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="practice_license" name="practice_license" accept="image/*,.pdf,.txt,.doc,.docx">
									<div class="file-preview" id="preview_practice_license"></div>
								</div>
								<div class="file-upload-group" data-field="syndicate_id">
									<span class="upload-icon">💳</span>
									<div class="upload-text">كارنية نقابة الأطباء</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="syndicate_id" name="syndicate_id" accept="image/*,.pdf,.txt,.doc,.docx">
									<div class="file-preview" id="preview_syndicate_id"></div>
								</div>
							</div>
						</div>
						<div class="form-subsection" id="degree-upload" style="display: none;">
							<h4>شهادة الرتبة (أخصائي / استشاري)</h4>
							<div class="file-upload-grid">
								<div class="file-upload-group" data-field="rank_degree">
									<span class="upload-icon">📄</span>
									<div class="upload-text">شهادة درجة الأخصائي أو الاستشاري</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="rank_degree" name="rank_degree" accept="image/*,.pdf,.txt,.doc,.docx">
									<div class="file-preview" id="preview_rank_degree"></div>
								</div>
							</div>
				</div>
			</div>
			
					<div id="psychologist-section" class="role-panel">
						<div class="form-subsection">
							<h4>الكلية / القسم <span class="required">*</span></h4>
							<div class="inline-options vertical-options">
								<label><input type="radio" name="psych_origin" value="arts"> آداب قسم علم نفس</label>
								<label><input type="radio" name="psych_origin" value="human_studies"> دراسات إنسانية قسم علم نفس</label>
								<label><input type="radio" name="psych_origin" value="human_sciences"> علوم إنسانية قسم علم نفس</label>
							</div>
						</div>
						<div class="form-subsection">
							<h4>المستندات المطلوبة <span class="required">*</span></h4>
							<div class="file-upload-grid">
								<div class="file-upload-group" data-field="cp_grad_degree">
									<span class="upload-icon">🎓</span>
									<div class="upload-text">قم برفع شهادة التخرج</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="cp_grad_degree" name="cp_grad_degree" accept="image/*,.pdf,.txt,.doc,.docx">
									<div class="file-preview" id="preview_cp_grad_degree"></div>
								</div>
								<div class="file-upload-group" data-field="cp_highest_degree">
									<span class="upload-icon">🏅</span>
									<div class="upload-text">قم برفع أعلى شهادة حصلت عليها في علم النفس الإكلينيكي (دبلوم - ماجستير - دكتوراه)</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="cp_highest_degree" name="cp_highest_degree" accept="image/*,.pdf,.txt,.doc,.docx">
									<div class="file-preview" id="preview_cp_highest_degree"></div>
								</div>
							</div>
						</div>
						<div class="form-subsection">
							<p>هل حصلت على تصريح مزاولة المهنة من وزارة الصحة؟ <span class="required">*</span></p>
							<div class="inline-options">
								<label><input type="radio" name="cp_moh_license" value="yes"> نعم</label>
								<label><input type="radio" name="cp_moh_license" value="no"> لا</label>
							</div>
							<div id="cp_moh_license_upload" class="form-subsection" style="display: none;">
								<div class="file-upload-grid">
									<div class="file-upload-group" data-field="cp_moh_license_file">
										<span class="upload-icon">📑</span>
									<div class="upload-text">قم برفع تصريح مزاولة المهنة من وزارة الصحة</div>
									<div class="upload-hint">ملفات صور أو مستندات (JPG، PNG، PDF، DOC، DOCX، TXT)</div>
									<input type="file" id="cp_moh_license_file" name="cp_moh_license_file" accept="image/*,.pdf,.txt,.doc,.docx">
										<div class="file-preview" id="preview_cp_moh_license_file"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="form-section">
				<div class="section-header">
					<h3>الشهادات والدورات</h3>
					<p class="section-note">قم برفع جميع شهادات العلاج النفسي التي حصلت عليها، وأضف الدورات أو الخبرات ذات الصلة.</p>
					</div>
				<div class="section-body">
					<div class="form-subsection">
						<h4>قم برفع جميع شهادات العلاج النفسي التي حصلت عليها <span class="required">*</span></h4>
						<div id="therapy-certificates">
							<div class="dynamic-row certificate-row">
								<input type="file" name="therapy_certificates[]" accept="image/*,.pdf,.txt,.doc,.docx" required>
								<button type="button" class="remove-row-btn" data-remove="certificate">❌</button>
				</div>
						</div>
						<button type="button" id="add-certificate-btn" class="add-btn">إضافة شهادة أخرى</button>
						<p class="helper-text">يسمح بملفات الصور أو المستندات (JPG، PNG، PDF، DOC، DOCX، TXT).</p>
			</div>
			
					<div class="form-subsection" style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
						<h4>هل حضرت دورات أخرى ولم تحصل على شهادة أو لديك خبرة شخصية في أحد طرق العلاج النفسي؟ <span class="optional-text">(اختياري)</span></h4>
						<div id="courses-container">
							<div class="dynamic-row course-row">
								<input type="text" name="course_school[]" placeholder="مدرسة العلاج النفسي">
								<input type="text" name="course_place[]" placeholder="مكان الحصول عليها (أو تعليم ذاتي)">
								<input type="text" name="course_year[]" placeholder="سنة الحصول عليها">
								<button type="button" class="remove-row-btn" data-remove="course">❌</button>
							</div>
						</div>
						<button type="button" id="add-course-btn" class="add-btn">إضافة دورة أخرى</button>
					</div>
				</div>
			</div>
			
			<div class="form-section">
				<div class="section-header">
					<h3>الفئات المفضلة</h3>
					<p class="section-note">ما هي الفئات التي لديك خبرة أكثر فيها وتفضل التعامل معها وتحقق معها أفضل النتائج؟<br><small>اختر من 1 إلى 4 فئات فقط</small></p>
				</div>
				<div class="section-body">
					<div class="category-list">
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="الأطفال"> الأطفال</label>
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="المراهقين والبالغين"> المراهقين والبالغين</label>
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="المسنين"> المسنين</label>
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="العلاج الزواجي ومشاكل العلاقات"> العلاج الزواجي ومشاكل العلاقات</label>
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="الاضطرابات الجنسية والجندرية"> الاضطرابات الجنسية والجندرية</label>
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="اضطرابات النوم"> اضطرابات النوم</label>
						<label class="category-box"><input type="checkbox" name="preferred_groups[]" value="اضطرابات النوم"> علاج الصدمات</label>
					</div>
					<p class="helper-text max-selection-message">يمكنك اختيار أربع فئات فقط.</p>
				</div>
			</div>
			
			<div id="diagnoses-section" class="form-section" style="display: none;">
				<div class="section-header">
					<h3>التشخيصات المفضلة</h3>
					<p class="section-note">ما هي التشخيصات التي لديك خبرة بها وتفضل التعامل معها وتحقق معها أفضل النتائج؟<br><small>يمكنك اختيار أي عدد من التشخيصات</small></p>
				</div>
				<div class="section-body">
					<div id="children-dx-section" class="form-subsection" style="display: none;">
						<h4>تشخيصات مرتبطة بالأطفال</h4>
						<div class="diagnosis-list">
							<label><input type="checkbox" name="dx_children[]" value="Intellectual Disability (ID)"> الإعاقة الذهنية / اضطراب النموّ العقلي <span class="english-tooltip" data-tooltip="Intellectual Disability (ID)">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Autism Spectrum Disorder (ASD)"> اضطراب طيف التوحّد <span class="english-tooltip" data-tooltip="Autism Spectrum Disorder (ASD)">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="ADHD"> اضطراب فرط الحركة وتشتّت الانتباه <span class="english-tooltip" data-tooltip="Attention-Deficit / Hyperactivity Disorder (ADHD)">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Learning Disorders"> صعوبات التعلّم <span class="english-tooltip" data-tooltip="Learning Difficulties / Learning Disorders">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Trauma & Stressor-Related (children)"> اضطرابات الصدمة والضغوط النفسية عند الأطفال <span class="english-tooltip" data-tooltip="Trauma- & Stressor-Related Disorders (in children)">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Gender Dysphoria (children)"> اضطراب الهوية الجندرية عند الأطفال <span class="english-tooltip" data-tooltip="Gender Dysphoria (in children)">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Disruptive & Conduct & Behavior Modification"> اضطرابات السلوك والانضباط وتعديل السلوك <span class="english-tooltip" data-tooltip="Behavior Modification / Disruptive, Impulse-Control & Conduct Disorders ..etc">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Emotional Disorders (children)"> الاضطرابات العاطفية والانفعالية <span class="english-tooltip" data-tooltip="Emotional Disorders">ℹ️</span></label>
							<label><input type="checkbox" name="dx_children[]" value="Habit & Somatic Disorders (children)"> اضطرابات السلوكيات والعادات <span class="english-tooltip" data-tooltip="Habit & Somatic Disorders">ℹ️</span></label>
						</div>
					</div>
					
					<div id="adult-dx-section" class="form-subsection" style="display: none;">
						<h4>تشخيصات مرتبطة بالمراهقين والبالغين</h4>
						
						<div id="adult-dx-psychologist" style="display: none;">
							<div class="diagnosis-list">
								<label><input type="checkbox" name="dx_adult[]" value="Depressive Disorders"> اضطرابات الاكتئاب <span class="english-tooltip" data-tooltip="Depressive Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Anxiety Disorders"> اضطرابات القلق <span class="english-tooltip" data-tooltip="Anxiety Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="OCD & Related"> الوسواس القهري والاضطرابات ذات الصلة <span class="english-tooltip" data-tooltip="Obsessive–Compulsive and Related Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Trauma & Stressor (Adults)"> اضطرابات الصدمة والضغوط للكبار (تشمل اضطراب التكيف) <span class="english-tooltip" data-tooltip="Trauma- and Stressor-Related Disorders (Adults, including Adjustment Disorder)">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Gender Dysphoria (Adults)"> اضطراب الهوية الجندرية للكبار <span class="english-tooltip" data-tooltip="Gender Dysphoria (Adults)">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Disruptive & Impulse-Control (Adults)"> اضطرابات السلوك والاندفاع <span class="english-tooltip" data-tooltip="Disruptive, Impulse-Control, and Conduct Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Behavioral Addictive (Non-Substance)"> الاضطرابات الإدمانية السلوكية (غير متعلقة بالمواد) <span class="english-tooltip" data-tooltip="Behavioral Addictive Disorders (Non-Substance Related)">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Personality Disorders Cluster B"> اضطرابات الشخصية – الفئة ب <span class="english-tooltip" data-tooltip="Cluster B">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Personality Disorders Cluster C"> اضطرابات الشخصية – الفئة ج <span class="english-tooltip" data-tooltip="Cluster C">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Paraphilic Disorders"> الاضطرابات البارافيليّة (الانحرافات الجنسية) <span class="english-tooltip" data-tooltip="Paraphilic Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="General Psychological Issues"> المشكلات النفسية العامة (مثل الاحتراق الوظيفي، الحزن الطبيعي، ومشاكل الحياة) <span class="english-tooltip" data-tooltip="General Psychological Issues">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Chronic Pain with Psychological Factors"> الألم المزمن المرتبط بعوامل نفسية <span class="english-tooltip" data-tooltip="Chronic Pain with Psychological Factors">ℹ️</span></label>
							</div>
						</div>
						
						<div id="adult-dx-psychiatrist" style="display: none;">
							<div class="diagnosis-list">
								<label><input type="checkbox" name="dx_adult[]" value="Depressive Disorders"> اضطرابات الاكتئاب <span class="english-tooltip" data-tooltip="Depressive Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Anxiety Disorders"> اضطرابات القلق <span class="english-tooltip" data-tooltip="Anxiety Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="OCD & Related"> الوسواس القهري والاضطرابات ذات الصلة <span class="english-tooltip" data-tooltip="Obsessive–Compulsive and Related Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Trauma & Stressor (Adults)"> اضطرابات الصدمة والضغوط للكبار (تشمل اضطراب التكيف) <span class="english-tooltip" data-tooltip="Trauma- and Stressor-Related Disorders (Adults, including Adjustment Disorder)">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Gender Dysphoria (Adults)"> اضطراب الهوية الجندرية للكبار <span class="english-tooltip" data-tooltip="Gender Dysphoria (Adults)">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Disruptive & Impulse-Control (Adults)"> اضطرابات السلوك والاندفاع <span class="english-tooltip" data-tooltip="Disruptive, Impulse-Control, and Conduct Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Behavioral Addictive (Non-Substance)"> الاضطرابات الإدمانية السلوكية (غير متعلقة بالمواد) <span class="english-tooltip" data-tooltip="Behavioral Addictive Disorders (Non-Substance Related)">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Personality Disorders Cluster B"> اضطرابات الشخصية – الفئة ب <span class="english-tooltip" data-tooltip="Cluster B">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Personality Disorders Cluster C"> اضطرابات الشخصية – الفئة ج <span class="english-tooltip" data-tooltip="Cluster C">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Paraphilic Disorders"> الاضطرابات البارافيليّة (الانحرافات الجنسية) <span class="english-tooltip" data-tooltip="Paraphilic Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Couple & Marital Therapy"> مشكلات العلاقات الزوجية والعائلية <span class="english-tooltip" data-tooltip="Couple and Relationship Therapy / Marital Therapy">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="General Psychological Issues"> المشكلات النفسية العامة (مثل الاحتراق الوظيفي، الحزن الطبيعي، ومشاكل الحياة) <span class="english-tooltip" data-tooltip="General Psychological Issues">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Chronic Pain with Psychological Factors"> الألم المزمن المرتبط بعوامل نفسية <span class="english-tooltip" data-tooltip="Chronic Pain with Psychological Factors">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Schizophrenia Spectrum & Psychotic Disorders"> اضطرابات الفصام والطيف الذهاني <span class="english-tooltip" data-tooltip="Schizophrenia Spectrum and Other Psychotic Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Personality Disorders Cluster A"> اضطرابات الشخصية من النمط (أ) <span class="english-tooltip" data-tooltip="Cluster A Personality Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Bipolar and Related Disorders"> اضطرابات المزاج ثنائية القطب والاضطرابات ذات الصلة <span class="english-tooltip" data-tooltip="Bipolar and Related Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Dissociative Disorders"> الاضطرابات الانفصالية <span class="english-tooltip" data-tooltip="Dissociative Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Somatic Symptom and Related Disorders"> الاضطرابات الجسدية الشكل والاضطرابات ذات الصلة <span class="english-tooltip" data-tooltip="Somatic Symptom and Related Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Substance/Medication-Induced Mental Disorders"> الاضطرابات النفسية الناجمة عن استخدام مواد أو أدوية <span class="english-tooltip" data-tooltip="Substance/Medication-Induced Mental Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Feeding and Eating Disorders"> اضطرابات الأكل والتغذية <span class="english-tooltip" data-tooltip="Feeding and Eating Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Sexual Dysfunctions"> الاضطرابات الجنسية <span class="english-tooltip" data-tooltip="Sexual Dysfunctions">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Substance-Related and Addictive Disorders"> الاضطرابات المرتبطة بتعاطي المواد والإدمان <span class="english-tooltip" data-tooltip="Substance-Related and Addictive Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Neurocognitive Disorders"> الاضطرابات العصبية المعرفية <span class="english-tooltip" data-tooltip="Neurocognitive Disorders">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Personality Change Due to Another Medical Condition"> تغيرات الشخصية الناتجة عن حالة طبية أخرى <span class="english-tooltip" data-tooltip="Personality Change Due to Another Medical Condition">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Mental Disorders Due to Another Medical Condition or Medication"> الاضطرابات النفسية الناتجة عن حالة طبية أو دواء <span class="english-tooltip" data-tooltip="Mental Disorders Due to Another Medical Condition or Medication">ℹ️</span></label>
								<label><input type="checkbox" name="dx_adult[]" value="Medication-Induced Movement Disorders"> اضطرابات الحركة الناجمة عن الأدوية <span class="english-tooltip" data-tooltip="Medication-Induced Movement Disorders">ℹ️</span></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<button type="submit" class="submit-btn" id="submit-btn">ابدأ التسجيل</button>
			<div class="form-warning">
				<span class="warning-icon">⚠️</span>
				<span>قد يستغرق الأمر بعض الوقت لرفع جميع الصور، يُرجى ترك الصفحة مفتوحة لحين اكتمال الرفع.</span>
			</div>
		</form>
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Two-step navigation
		const instructionsStep = document.getElementById('instructions-step');
		const formStep = document.getElementById('form-step');
		const continueBtn = document.getElementById('continue-to-form-btn');
		const backBtn = document.getElementById('back-to-instructions-btn');
		
		if (continueBtn) {
			continueBtn.addEventListener('click', function() {
				if (instructionsStep && formStep) {
					instructionsStep.classList.remove('active');
					formStep.classList.add('active');
					// Scroll to top of form
					formStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			});
		}
		
		if (backBtn) {
			backBtn.addEventListener('click', function() {
				if (instructionsStep && formStep) {
					formStep.classList.remove('active');
					instructionsStep.classList.add('active');
					// Scroll to top of instructions
					instructionsStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			});
		}
		
		initFancyUploads();

		const dom = cacheDom();

		bindDynamicRowButtons();
		bindRoleHandlers();
		bindPreferredGroupHandlers();
		bindDiagnosisHandlers();
		bindFileListeners();
		bindInputListeners();
		bindFormSubmission();
		bindTooltipHandlers();

		initialize();

		function cacheDom() {
			return {
				hiddenSpecialty: document.getElementById('doctor_specialty'),
				form: document.getElementById('therapist-registration-form'),
				roleRadios: Array.from(document.querySelectorAll('input[name="role"]')),
				roleFieldGroup: (function() {
					const radio = document.querySelector('input[name="role"]');
					return radio ? radio.closest('.form-group') : null;
				})(),
				psyRankRadios: Array.from(document.querySelectorAll('input[name="psy_rank"]')),
				psyRankContainer: (function() {
					const radio = document.querySelector('input[name="psy_rank"]');
					return radio ? radio.closest('.form-subsection') : null;
				})(),
				psychOriginRadios: Array.from(document.querySelectorAll('input[name="psych_origin"]')),
				psychOriginContainer: (function() {
					const radio = document.querySelector('input[name="psych_origin"]');
					return radio ? radio.closest('.form-subsection') : null;
				})(),
				cpMohRadios: Array.from(document.querySelectorAll('input[name="cp_moh_license"]')),
				cpMohContainer: (function() {
					const radio = document.querySelector('input[name="cp_moh_license"]');
					return radio ? radio.closest('.form-subsection') : null;
				})(),
				psychiatristSection: document.getElementById('psychiatrist-section'),
				psychologistSection: document.getElementById('psychologist-section'),
				degreeUpload: document.getElementById('degree-upload'),
				cpMohUpload: document.getElementById('cp_moh_license_upload'),
				doctorFileInputs: Array.from(document.querySelectorAll('input[name="grad_cert"], input[name="practice_license"], input[name="syndicate_id"], input[name="identity_front"], input[name="identity_back"]')),
				degreeFile: document.querySelector('input[name="rank_degree"]'),
				cpDegree: document.querySelector('input[name="cp_highest_degree"]'),
				cpLicenseFile: document.querySelector('input[name="cp_moh_license_file"]'),
				certContainer: document.getElementById('therapy-certificates'),
				addCertBtn: document.getElementById('add-certificate-btn'),
				courseContainer: document.getElementById('courses-container'),
				addCourseBtn: document.getElementById('add-course-btn'),
				preferredGroupCheckboxes: Array.from(document.querySelectorAll('input[name="preferred_groups[]"]')),
				maxSelectionMessage: document.querySelector('.max-selection-message'),
				diagnosesSection: document.getElementById('diagnoses-section'),
				childrenDxSection: document.getElementById('children-dx-section'),
				adultDxSection: document.getElementById('adult-dx-section'),
				adultDxPsych: document.getElementById('adult-dx-psychiatrist'),
				adultDxPsychologist: document.getElementById('adult-dx-psychologist'),
				childrenDxCheckboxes: Array.from(document.querySelectorAll('input[name="dx_children[]"]')),
				adultDxCheckboxes: Array.from(document.querySelectorAll('input[name="dx_adult[]"]')),
				preferredGroupsWrapper: document.querySelector('.category-list'),
				messagesDiv: $('#form-messages'),
				submitBtn: $('#submit-btn')
			};
		}

		function initialize() {
			toggleRoleSections();
			handleRankChange();
			handleCpMohChange();
			enforcePreferredGroupsLimit();
			updateDxSectionsVisibility();
			updateCertificateRemoveState();
			updateCourseRemoveState();
			refreshTherapyCertificatesState();
			updateDoctorSpecialty();
		}

		function bindDynamicRowButtons() {
			if (dom.addCertBtn) {
				dom.addCertBtn.addEventListener('click', addCertificateRow);
			}
			if (dom.addCourseBtn) {
				dom.addCourseBtn.addEventListener('click', addCourseRow);
			}

			if (dom.certContainer) {
				Array.from(dom.certContainer.querySelectorAll('.certificate-row')).forEach(function(row) {
					attachRemoveButton(row, 'certificate');
				});
				Array.from(dom.certContainer.querySelectorAll('input[name="therapy_certificates[]"]')).forEach(function(input) {
					input.addEventListener('change', refreshTherapyCertificatesState);
				});
			}

			if (dom.courseContainer) {
				Array.from(dom.courseContainer.querySelectorAll('.course-row')).forEach(function(row) {
					attachRemoveButton(row, 'course');
				});
			}
		}

		function bindRoleHandlers() {
			dom.roleRadios.forEach(function(radio) {
				radio.addEventListener('change', function() {
					toggleRoleSections();
					updateDxSectionsVisibility();
					if (dom.roleFieldGroup) {
						clearFieldError(dom.roleFieldGroup);
					}
				});
			});

			dom.psyRankRadios.forEach(function(radio) {
				radio.addEventListener('change', function() {
					handleRankChange();
					if (dom.psyRankContainer) {
						clearFieldError(dom.psyRankContainer);
					}
				});
			});

			dom.psychOriginRadios.forEach(function(radio) {
				radio.addEventListener('change', function() {
					updateDoctorSpecialty();
					if (dom.psychOriginContainer) {
						clearFieldError(dom.psychOriginContainer);
					}
				});
			});

			dom.cpMohRadios.forEach(function(radio) {
				radio.addEventListener('change', function() {
					handleCpMohChange();
					if (dom.cpMohContainer) {
						clearFieldError(dom.cpMohContainer);
					}
				});
			});
		}

		function bindPreferredGroupHandlers() {
			dom.preferredGroupCheckboxes.forEach(function(cb) {
				cb.addEventListener('change', function() {
					enforcePreferredGroupsLimit();
					updateDxSectionsVisibility();
					if (dom.preferredGroupCheckboxes.some(function(item) { return item.checked; })) {
						if (dom.preferredGroupsWrapper) {
							clearFieldError(dom.preferredGroupsWrapper);
						}
					}
				});
			});
		}

		function bindDiagnosisHandlers() {
			dom.childrenDxCheckboxes.forEach(function(cb) {
				cb.addEventListener('change', function() {
					if (dom.childrenDxCheckboxes.some(function(item) { return item.checked; })) {
						clearFieldError(dom.childrenDxSection);
					}
				});
			});

			dom.adultDxCheckboxes.forEach(function(cb) {
				cb.addEventListener('change', function() {
					const visibleAdultCheckboxes = dom.adultDxCheckboxes.filter(function(item) {
						return item.offsetParent !== null;
					});
					if (visibleAdultCheckboxes.some(function(item) { return item.checked; })) {
						clearFieldError(dom.adultDxSection);
					}
				});
			});
		}

		function bindFileListeners() {
			document.addEventListener('change', function(event) {
				const target = event.target;
				if (!target) {
					return;
				}

				if (target.matches('.file-upload-group input[type="file"]')) {
					const group = target.closest('.file-upload-group');
					if (group && target.files && target.files.length > 0) {
						clearFieldError(group);
					}
				}

				if (target.name === 'therapy_certificates[]') {
					refreshTherapyCertificatesState();
				}

				if (target.name === 'preferred_groups[]' && dom.preferredGroupsWrapper) {
					if (dom.preferredGroupCheckboxes.some(function(item) { return item.checked; })) {
						clearFieldError(dom.preferredGroupsWrapper);
					}
				}

				if (target.name === 'dx_children[]' && dom.childrenDxSection) {
					if (dom.childrenDxCheckboxes.some(function(item) { return item.checked; })) {
						clearFieldError(dom.childrenDxSection);
					}
				}

				if (target.name === 'dx_adult[]' && dom.adultDxSection) {
					const visibleAdultCheckboxes = dom.adultDxCheckboxes.filter(function(item) {
						return item.offsetParent !== null;
					});
					if (visibleAdultCheckboxes.some(function(item) { return item.checked; })) {
						clearFieldError(dom.adultDxSection);
					}
				}
			});
		}

		function bindInputListeners() {
			document.addEventListener('input', function(event) {
				const target = event.target;
				if (!target) {
					return;
				}

				if (target.matches('input[required], textarea[required], select[required]')) {
					if (target.type === 'radio' || target.type === 'checkbox' || target.type === 'file') {
						return;
					}
					if ((target.value || '').trim() !== '') {
						clearFieldError(target);
					}
				}
			});
		}

		function bindTooltipHandlers() {
			// Create overlay for mobile
			let overlay = document.getElementById('tooltip-overlay');
			if (!overlay) {
				overlay = document.createElement('div');
				overlay.id = 'tooltip-overlay';
				overlay.className = 'tooltip-overlay';
				document.body.appendChild(overlay);
			}

			function closeAllTooltips() {
				document.querySelectorAll('.english-tooltip.active').forEach(function(tooltip) {
					tooltip.classList.remove('active');
				});
				overlay.classList.remove('active');
				document.body.classList.remove('tooltip-active');
			}

			function openTooltip(tooltip) {
				tooltip.classList.add('active');
				overlay.classList.add('active');
				document.body.classList.add('tooltip-active');
			}

			// Prevent all events from passing through overlay
			overlay.addEventListener('click', function(event) {
				event.preventDefault();
				event.stopPropagation();
				closeAllTooltips();
			}, true);

			overlay.addEventListener('touchend', function(event) {
				event.preventDefault();
				event.stopPropagation();
				closeAllTooltips();
			}, true);

			// Prevent checkbox click when clicking on tooltip
			document.addEventListener('click', function(event) {
				// If overlay is active, prevent all clicks from reaching checkboxes
				if (overlay.classList.contains('active')) {
					const tooltip = event.target.closest('.english-tooltip');
					if (!tooltip) {
						event.preventDefault();
						event.stopPropagation();
						closeAllTooltips();
						return false;
					}
				}

				const tooltip = event.target.closest('.english-tooltip');
				if (tooltip) {
					event.preventDefault();
					event.stopPropagation();
					// Toggle tooltip on mobile
					if (window.innerWidth < 768) {
						const wasActive = tooltip.classList.contains('active');
						closeAllTooltips();
						if (!wasActive) {
							openTooltip(tooltip);
						}
					}
					return false;
				}
			}, true);

			// Also handle touch events for mobile
			document.addEventListener('touchend', function(event) {
				// If overlay is active, prevent all touches from reaching checkboxes
				if (overlay.classList.contains('active')) {
					const tooltip = event.target.closest('.english-tooltip');
					if (!tooltip) {
						event.preventDefault();
						event.stopPropagation();
						closeAllTooltips();
						return false;
					}
				}

				const tooltip = event.target.closest('.english-tooltip');
				if (tooltip) {
					event.preventDefault();
					event.stopPropagation();
					if (window.innerWidth < 768) {
						const wasActive = tooltip.classList.contains('active');
						closeAllTooltips();
						if (!wasActive) {
							openTooltip(tooltip);
						}
					}
					return false;
				}
			}, true);

			// Prevent checkbox clicks when overlay is active
			document.addEventListener('change', function(event) {
				if (overlay.classList.contains('active') && (event.target.type === 'checkbox' || event.target.type === 'radio')) {
					event.preventDefault();
					event.stopPropagation();
					// Restore checkbox state
					if (event.target.type === 'checkbox') {
						event.target.checked = !event.target.checked;
					}
					return false;
				}
			}, true);

			// Prevent mousedown and touchstart on checkboxes when overlay is active
			document.addEventListener('mousedown', function(event) {
				if (overlay.classList.contains('active') && (event.target.type === 'checkbox' || event.target.type === 'radio')) {
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			}, true);

			document.addEventListener('touchstart', function(event) {
				if (overlay.classList.contains('active') && (event.target.type === 'checkbox' || event.target.type === 'radio')) {
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			}, true);
		}

		function bindFormSubmission() {
			if (!dom.form) {
				return;
			}

			$(dom.form).on('submit', function(e) {
				e.preventDefault();

				if (dom.messagesDiv.length) {
					dom.messagesDiv.empty();
				}

				const currentRole = getCurrentRole();
				if (!currentRole) {
					if (dom.roleFieldGroup) {
						markFieldError(dom.roleFieldGroup);
					}
					showFormError('يرجى اختيار المسمى الوظيفي.', dom.roleFieldGroup || dom.form);
					return;
				}

				if (dom.roleFieldGroup) {
					clearFieldError(dom.roleFieldGroup);
				}

				const invalidGeneralField = findFirstInvalidGeneralField();
				if (invalidGeneralField) {
					markFieldError(invalidGeneralField.field);
					showFormError(
						invalidGeneralField.message,
						invalidGeneralField.field.closest('.form-group') || invalidGeneralField.field
					);
					return;
				}

				updateDoctorSpecialty();

				if (currentRole === 'psychiatrist') {
					const rankSelected = dom.psyRankRadios.some(function(radio) {
						return radio.checked;
					});
					if (!rankSelected) {
						const target = dom.psyRankContainer || dom.psychiatristSection || dom.form;
						markFieldError(target);
						showFormError('يرجى اختيار الدرجة / الرتبة.', target);
						return;
					}
					if (dom.psyRankContainer) {
						clearFieldError(dom.psyRankContainer);
					}
				} else if (currentRole === 'clinical_psychologist') {
					const originSelected = dom.psychOriginRadios.some(function(radio) {
						return radio.checked;
					});
					if (!originSelected) {
						const target = dom.psychOriginContainer || dom.psychologistSection || dom.form;
						markFieldError(target);
						showFormError('يرجى اختيار جهة التخرج للأخصائي النفسي الإكلينيكي.', target);
						return;
					}
					if (dom.psychOriginContainer) {
						clearFieldError(dom.psychOriginContainer);
					}

					const mohSelected = dom.cpMohRadios.some(function(radio) {
						return radio.checked;
					});
					if (!mohSelected) {
						const target = dom.cpMohContainer || dom.psychologistSection || dom.form;
						markFieldError(target);
						showFormError('يرجى تحديد حالة ترخيص وزارة الصحة.', target);
						return;
					}
					if (dom.cpMohContainer) {
						clearFieldError(dom.cpMohContainer);
					}
				}

				const preferredSelected = dom.preferredGroupCheckboxes.some(function(cb) {
					return cb.checked;
				});
				if (!preferredSelected) {
					if (dom.preferredGroupsWrapper) {
						markFieldError(dom.preferredGroupsWrapper);
					}
					showFormError('يرجى اختيار فئة واحدة على الأقل ضمن الفئات المفضلة.', dom.preferredGroupsWrapper || dom.form);
					return;
				}
				if (dom.preferredGroupsWrapper) {
					clearFieldError(dom.preferredGroupsWrapper);
				}

				if (isElementVisible(dom.childrenDxSection)) {
					const childrenChecked = dom.childrenDxCheckboxes.some(function(cb) {
						return cb.checked;
					});
					if (!childrenChecked) {
						markFieldError(dom.childrenDxSection);
						showFormError('يرجى اختيار تشخيص واحد على الأقل من تشخيصات الأطفال.', dom.childrenDxSection);
						return;
					}
					clearFieldError(dom.childrenDxSection);
				}

				if (isElementVisible(dom.adultDxSection)) {
					const visibleAdultCheckboxes = dom.adultDxCheckboxes.filter(function(cb) {
						return cb.offsetParent !== null;
					});
					if (visibleAdultCheckboxes.length) {
						const adultChecked = visibleAdultCheckboxes.some(function(cb) {
							return cb.checked;
						});
						if (!adultChecked) {
							markFieldError(dom.adultDxSection);
							showFormError('يرجى اختيار تشخيص واحد على الأقل من تشخيصات المراهقين أو البالغين.', dom.adultDxSection);
							return;
						}
						clearFieldError(dom.adultDxSection);
					}
				}

				if (!validateRequiredUploads()) {
					return;
				}

				if (dom.submitBtn.length) {
					dom.submitBtn.prop('disabled', true).text('جاري الإرسال...');
				}

				const formData = new FormData(dom.form);
				formData.append('action', 'register_therapist_shortcode');
				formData.append('nonce', '<?php echo wp_create_nonce( 'therapist_registration_shortcode' ); ?>');
				formData.append('otp_method', '<?php echo esc_js( $settings['otp_method'] ); ?>');

				$.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						const successMessage = response && response.data && response.data.message ? response.data.message : 'تم التسجيل بنجاح وسيقوم فرق خدمة العملاء بالتواصل معك في أقرب وقت';
						if (response && response.success) {
							if (typeof Swal !== 'undefined') {
								Swal.fire({
									icon: 'success',
									title: 'تم الإرسال',
									text: successMessage,
									confirmButtonText: 'حسناً'
								}).then(function() {
									window.location.reload();
								});
							} else {
								dom.messagesDiv.html('<div class="alert alert-success">' + successMessage + '</div>');
								dom.form.reset();
								refreshTherapyCertificatesState();
							}
							updateCertificateRemoveState();
							updateCourseRemoveState();
						} else {
							const errorMessage = response && response.data && response.data.message ? response.data.message : 'Registration failed. Please try again.';
							if (typeof Swal !== 'undefined') {
								Swal.fire({
									icon: 'error',
									title: 'حدث خطأ',
									text: errorMessage,
									confirmButtonText: 'حسناً'
								});
							} else {
								dom.messagesDiv.html('<div class="alert alert-error">' + errorMessage + '</div>');
							}
						}
					},
					error: function() {
						dom.messagesDiv.html('<div class="alert alert-error">An error occurred. Please try again.</div>');
					},
					complete: function() {
						if (dom.submitBtn.length) {
							dom.submitBtn.prop('disabled', false).text('ابدأ التسجيل');
						}
					}
				});
			});
		}

		function initFancyUploads() {
			$('.file-upload-group').each(function() {
				const $uploadGroup = $(this);
				const $input = $uploadGroup.find('input[type="file"]');
				const $preview = $uploadGroup.find('.file-preview');
				const fieldName = $uploadGroup.data('field');
				const isMultiple = $uploadGroup.data('multiple') === true;
				const maxSizeAttr = $input.attr('data-max-size');
				const maxSize = maxSizeAttr ? parseInt(maxSizeAttr, 10) : null;
				const maxFiles = 10;
				
				let selectedFiles = [];
				
				$uploadGroup.on('dragover dragenter', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).addClass('dragover');
				});
				
				$uploadGroup.on('dragleave dragend', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass('dragover');
				});
				
				$uploadGroup.on('drop', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass('dragover');
					
					const files = e.originalEvent.dataTransfer.files;
					handleFiles(files);
				});
				
				$input.on('change', function() {
					handleFiles(this.files);
				});
				
				function handleFiles(files) {
					for (let i = 0; i < files.length; i++) {
						const file = files[i];
						
						if (maxSize && file.size > maxSize) {
							const sizeMB = (maxSize / 1024 / 1024).toFixed(1);
							alert('File "' + file.name + '" is too large. Maximum size is ' + sizeMB + 'MB');
							continue;
						}
						
						if (isMultiple && selectedFiles.length >= maxFiles) {
							$uploadGroup.find('.max-files-notice').show();
							break;
						}
						
						if (!isMultiple) {
							selectedFiles = [];
							$preview.empty();
						}
						
						selectedFiles.push(file);
						addFilePreview(file);
					}
					
					updateFileInput();
				}
				
				function addFilePreview(file) {
					const fileId = 'file_' + Math.random().toString(36).substr(2, 9);
					const isImage = file.type.startsWith('image/');
					const isPDF = file.type === 'application/pdf';
					
					let previewHtml = `
						<div class="preview-item" data-file-id="${fileId}">
							<button type="button" class="remove-file" onclick="removeFile('${fieldName}', '${fileId}')">&times;</button>
					`;
					
					if (isImage) {
						const reader = new FileReader();
						reader.onload = function(e) {
							$(`[data-file-id="${fileId}"] .preview-content`).html(`
								<img src="${e.target.result}" alt="${file.name}" class="preview-image">
							`);
						};
						reader.readAsDataURL(file);
						
						previewHtml += `
							<div class="preview-content">
								<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
									<span>Loading...</span>
								</div>
							</div>
						`;
					} else {
						let icon = '📄';
						if (isPDF) {
							icon = '📋';
						}
						
						previewHtml += `
							<div class="preview-file">
								<div class="file-icon">${icon}</div>
								<div class="file-name">${file.name}</div>
								<div class="file-size">${formatFileSize(file.size)}</div>
							</div>
						`;
					}
					
					previewHtml += '</div>';
					$preview.append(previewHtml);
					
					$(`[data-file-id="${fileId}"]`).data('file', file);
				}
				
				function formatFileSize(bytes) {
					if (bytes === 0) {
						return '0 Bytes';
					}
					const k = 1024;
					const sizes = ['Bytes', 'KB', 'MB', 'GB'];
					const i = Math.floor(Math.log(bytes) / Math.log(k));
					return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
				}
				
				window.removeFile = function(fieldName, fileId) {
					const $targetGroup = $(`.file-upload-group[data-field="${fieldName}"]`);
					const $targetPreview = $targetGroup.find('.file-preview');
					const $item = $targetPreview.find(`[data-file-id="${fileId}"]`);

					const fileIndex = selectedFiles.findIndex(function(file) {
						return $item.data('file') && $item.data('file').name === file.name;
					});
					if (fileIndex > -1) {
						selectedFiles.splice(fileIndex, 1);
					}
					
					$item.remove();
					updateFileInput();
					
					if (selectedFiles.length < maxFiles) {
						$targetGroup.find('.max-files-notice').hide();
					}
				};
				
				function updateFileInput() {
					const dt = new DataTransfer();
					selectedFiles.forEach(function(file) {
						dt.items.add(file);
					});
					$input[0].files = dt.files;
				}
			});
		}
		
		function bindDiagnosisHandlers() {
			// listeners registered earlier in bindDiagnosisHandlers declaration
		}

		function markFieldError(element) {
			if (!element) {
				return;
			}
			element.classList.add('input-error');
			if (element.closest) {
				const group = element.closest('.form-group, .form-subsection');
				if (group) {
					group.classList.add('input-error');
				}
			}
		}

		function clearFieldError(element) {
			if (!element) {
				return;
			}
			element.classList.remove('input-error');
			if (element.closest) {
				const group = element.closest('.form-group, .form-subsection');
				if (group) {
					group.classList.remove('input-error');
				}
			}
		}

		function showFormError(message, focusElement) {
			const handleFocus = function() {
				if (!focusElement) {
					return;
				}
				setTimeout(function() {
					const focusable = focusElement.querySelector ? focusElement.querySelector('input, select, textarea, button') : null;
					if (focusable && typeof focusable.focus === 'function') {
						focusable.focus({ preventScroll: true });
					} else if (typeof focusElement.focus === 'function') {
						focusElement.focus({ preventScroll: true });
					}
				}, 150);
			};

			if (typeof Swal !== 'undefined') {
				Swal.fire({
					icon: 'error',
					title: 'تنبيه',
					text: message,
					confirmButtonText: 'حسناً'
				}).then(handleFocus);
			} else if (dom.messagesDiv.length) {
				dom.messagesDiv.html('<div class="alert alert-error">' + message + '</div>');
				handleFocus();
			} else {
				alert(message);
				handleFocus();
			}
		}

		function scrollToElementCenter(element) {
			if (!element || typeof element.scrollIntoView !== 'function') {
				return;
			}
			element.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}

		function updateDoctorSpecialty() {
			if (!dom.hiddenSpecialty) {
				return;
			}
			const role = getCurrentRole();
			let specialty = '';

			if (role === 'psychiatrist') {
				const rankRadio = dom.psyRankRadios.find(function(radio) {
					return radio.checked;
				});
				if (rankRadio && rankRadio.parentElement) {
					specialty = rankRadio.parentElement.textContent.trim();
				}
				if (!specialty) {
					specialty = 'طبيب نفسي';
				}
			} else if (role === 'clinical_psychologist') {
				specialty = 'أخصائي نفسي إكلينيكي';
			}

			dom.hiddenSpecialty.value = specialty;
		}

		function getCurrentRole() {
			const checked = dom.roleRadios.find(function(radio) {
				return radio.checked;
			});
			return checked ? checked.value : '';
		}

		function toggleRoleSections() {
			const role = getCurrentRole();

			if (role === 'psychiatrist') {
				showElement(dom.psychiatristSection, true);
				showElement(dom.psychologistSection, false);
				if (dom.psychologistSection) {
					clearFieldError(dom.psychologistSection);
				}
				if (dom.psychOriginContainer) {
					clearFieldError(dom.psychOriginContainer);
				}
				if (dom.cpMohContainer) {
					clearFieldError(dom.cpMohContainer);
				}
				setRequired(dom.psyRankRadios, true);
				setRequired(dom.psychOriginRadios, false);
				setRequired(dom.cpMohRadios, false);
				setRequired(dom.doctorFileInputs, true);
				if (dom.cpDegree) {
					dom.cpDegree.required = false;
				}
				if (dom.cpLicenseFile) {
					dom.cpLicenseFile.required = false;
				}
			} else if (role === 'clinical_psychologist') {
				showElement(dom.psychiatristSection, false);
				showElement(dom.psychologistSection, true);
				if (dom.psychiatristSection) {
					clearFieldError(dom.psychiatristSection);
				}
				if (dom.psyRankContainer) {
					clearFieldError(dom.psyRankContainer);
				}
				setRequired(dom.psyRankRadios, false);
				setRequired(dom.psychOriginRadios, true);
				setRequired(dom.cpMohRadios, true);
				setRequired(dom.doctorFileInputs, false);
				if (dom.cpDegree) {
					dom.cpDegree.required = true;
				}
				if (dom.degreeFile) {
					dom.degreeFile.required = false;
				}
						} else {
				showElement(dom.psychiatristSection, false);
				showElement(dom.psychologistSection, false);
				if (dom.psychiatristSection) {
					clearFieldError(dom.psychiatristSection);
				}
				if (dom.psychologistSection) {
					clearFieldError(dom.psychologistSection);
				}
				setRequired(dom.psyRankRadios, false);
				setRequired(dom.psychOriginRadios, false);
				setRequired(dom.cpMohRadios, false);
				setRequired(dom.doctorFileInputs, false);
			}

			if (role !== 'psychiatrist') {
				showElement(dom.degreeUpload, false);
				if (dom.degreeFile) {
					dom.degreeFile.required = false;
					dom.degreeFile.value = '';
					clearFieldError(dom.degreeUpload);
				}
			}

			if (role !== 'clinical_psychologist') {
				showElement(dom.cpMohUpload, false);
				if (dom.cpLicenseFile) {
					dom.cpLicenseFile.required = false;
					dom.cpLicenseFile.value = '';
					clearFieldError(dom.cpMohUpload);
				}
			}

			updateDoctorSpecialty();
			updateAdultDxByRole();
		}

		function handleRankChange() {
			if (!dom.degreeUpload) {
				return;
			}
			const selectedRank = dom.psyRankRadios.find(function(radio) {
				return radio.checked;
			});
			if (selectedRank && (selectedRank.value === 'specialist' || selectedRank.value === 'consultant')) {
				showElement(dom.degreeUpload, true);
				if (dom.degreeFile) {
					dom.degreeFile.required = true;
						}
					} else {
				showElement(dom.degreeUpload, false);
				if (dom.degreeFile) {
					dom.degreeFile.required = false;
					dom.degreeFile.value = '';
					clearFieldError(dom.degreeUpload);
				}
			}
			updateDoctorSpecialty();
		}

		function handleCpMohChange() {
			if (!dom.cpMohUpload) {
				return;
			}
			const selectedLicense = dom.cpMohRadios.find(function(radio) {
				return radio.checked;
			});
			if (selectedLicense && selectedLicense.value === 'yes') {
				showElement(dom.cpMohUpload, true);
				if (dom.cpLicenseFile) {
					dom.cpLicenseFile.required = true;
				}
			} else {
				showElement(dom.cpMohUpload, false);
				if (dom.cpLicenseFile) {
					dom.cpLicenseFile.required = false;
					dom.cpLicenseFile.value = '';
					clearFieldError(dom.cpMohUpload);
				}
			}
		}

		function updateAdultDxByRole() {
			if (!dom.adultDxSection) {
				return;
			}
			const adultGroupChecked = dom.preferredGroupCheckboxes.find(function(cb) {
				return cb.checked && cb.value === 'المراهقين والبالغين';
			});
			if (!adultGroupChecked) {
				showElement(dom.adultDxSection, false);
				showElement(dom.adultDxPsych, false);
				showElement(dom.adultDxPsychologist, false);
				return;
			}

			const role = getCurrentRole();
			showElement(dom.adultDxSection, true);
			if (role === 'psychiatrist') {
				showElement(dom.adultDxPsych, true);
				showElement(dom.adultDxPsychologist, false);
			} else if (role === 'clinical_psychologist') {
				showElement(dom.adultDxPsychologist, true);
				showElement(dom.adultDxPsych, false);
			} else {
				showElement(dom.adultDxPsych, false);
				showElement(dom.adultDxPsychologist, false);
			}
		}

		function updateDxSectionsVisibility() {
			const selectedValues = dom.preferredGroupCheckboxes.filter(function(cb) {
				return cb.checked;
			}).map(function(cb) {
				return cb.value;
			});

			const hasChildren = selectedValues.includes('الأطفال');
			const hasAdults = selectedValues.includes('المراهقين والبالغين');
			const role = getCurrentRole();
			const hasRole = role === 'psychiatrist' || role === 'clinical_psychologist';

			// Show diagnoses section only if (children or adults) AND role is selected
			const shouldShowDiagnosesSection = (hasChildren || hasAdults) && hasRole;
			showElement(dom.diagnosesSection, shouldShowDiagnosesSection);

			if (!shouldShowDiagnosesSection) {
				// Hide all diagnosis subsections if main section is hidden
				showElement(dom.childrenDxSection, false);
				showElement(dom.adultDxSection, false);
				showElement(dom.adultDxPsych, false);
				showElement(dom.adultDxPsychologist, false);
				if (dom.childrenDxSection) {
					clearFieldError(dom.childrenDxSection);
				}
				if (dom.adultDxSection) {
					clearFieldError(dom.adultDxSection);
				}
				return;
			}

			showElement(dom.childrenDxSection, hasChildren);
			if (!hasChildren && dom.childrenDxSection) {
				clearFieldError(dom.childrenDxSection);
			}

			if (hasAdults) {
				updateAdultDxByRole();
			} else {
				showElement(dom.adultDxSection, false);
				showElement(dom.adultDxPsych, false);
				showElement(dom.adultDxPsychologist, false);
				if (dom.adultDxSection) {
					clearFieldError(dom.adultDxSection);
				}
			}
		}

		function enforcePreferredGroupsLimit() {
			const checkedCount = dom.preferredGroupCheckboxes.filter(function(cb) {
				return cb.checked;
			}).length;

			if (checkedCount >= 4) {
				dom.preferredGroupCheckboxes.forEach(function(cb) {
					if (!cb.checked) {
						cb.disabled = true;
						if (cb.parentElement) {
							cb.parentElement.classList.add('disabled');
						}
					}
				});
				if (dom.maxSelectionMessage) {
					dom.maxSelectionMessage.style.display = 'block';
				}
			} else {
				dom.preferredGroupCheckboxes.forEach(function(cb) {
					cb.disabled = false;
					if (cb.parentElement) {
						cb.parentElement.classList.remove('disabled');
					}
				});
				if (dom.maxSelectionMessage) {
					dom.maxSelectionMessage.style.display = 'none';
				}
			}
		}

		function validateRequiredUploads() {
			const role = getCurrentRole();
			const selectedRank = dom.psyRankRadios.find(function(radio) {
				return radio.checked;
			});
			const selectedCpLicense = dom.cpMohRadios.find(function(radio) {
				return radio.checked;
			});

			const requirements = [
				{ name: 'profile_image', message: 'يرجى رفع صورة شخصية.' },
				{ name: 'identity_front', message: 'يرجى رفع صورة البطاقة الشخصية (الوجه).' },
				{ name: 'identity_back', message: 'يرجى رفع صورة البطاقة الشخصية (الظهر).' }
			];

			if (role === 'psychiatrist') {
				requirements.push(
					{ name: 'grad_cert', message: 'يرجى رفع شهادة التخرج.' },
					{ name: 'practice_license', message: 'يرجى رفع ترخيص مزاولة المهنة.' },
					{ name: 'syndicate_id', message: 'يرجى رفع صورة بطاقة النقابة.' }
				);
				if (selectedRank && (selectedRank.value === 'specialist' || selectedRank.value === 'consultant')) {
					requirements.push({ name: 'rank_degree', message: 'يرجى رفع شهادة الرتبة.' });
				}
			}

			if (role === 'clinical_psychologist') {
				requirements.push(
					{ name: 'cp_grad_degree', message: 'يرجى رفع شهادة التخرج للأخصائي الإكلينيكي.' },
					{ name: 'cp_highest_degree', message: 'يرجى رفع أعلى شهادة إكلينيكية.' }
				);
				if (selectedCpLicense && selectedCpLicense.value === 'yes') {
					requirements.push({ name: 'cp_moh_license_file', message: 'يرجى رفع تصريح وزارة الصحة.' });
				}
			}

			for (let i = 0; i < requirements.length; i++) {
				const requirement = requirements[i];
				const input = document.querySelector('input[name="' + requirement.name + '"]');
				if (!input) {
					continue;
				}
				const container = input.closest('.file-upload-group');
				const visible = isElementVisible(input) || (container && isElementVisible(container));
				if (!visible) {
					clearFieldError(container || input);
					continue;
				}
				const hasValue = input.files && input.files.length > 0;
				if (!hasValue) {
					markFieldError(container || input);
					showFormError(requirement.message, container || input);
					return false;
				}
				clearFieldError(container || input);
			}

			if (dom.certContainer) {
				const certificateInputs = dom.certContainer.querySelectorAll('input[name="therapy_certificates[]"]');
				const hasCertificate = Array.from(certificateInputs).some(function(input) {
					return input.files && input.files.length > 0;
				});
				if (!hasCertificate) {
					dom.certContainer.classList.add('input-error');
					showFormError('يرجى رفع شهادة علاج نفسي واحدة على الأقل.', dom.certContainer);
					return false;
				}
				dom.certContainer.classList.remove('input-error');
			}

			return true;
		}

		function refreshTherapyCertificatesState() {
			if (!dom.certContainer) {
					return;
			}
			const certificateInputs = dom.certContainer.querySelectorAll('input[name="therapy_certificates[]"]');
			const hasCertificate = Array.from(certificateInputs).some(function(input) {
				return input.files && input.files.length > 0;
			});
			if (hasCertificate) {
				dom.certContainer.classList.remove('input-error');
			}
		}

		function addCertificateRow() {
			if (!dom.certContainer) {
				return;
			}
			const row = document.createElement('div');
			row.className = 'dynamic-row certificate-row';

			const input = document.createElement('input');
			input.type = 'file';
			input.name = 'therapy_certificates[]';
			input.accept = 'image/*,.pdf,.txt,.doc,.docx';
			input.required = true;
			input.addEventListener('change', refreshTherapyCertificatesState);

			row.appendChild(input);
			attachRemoveButton(row, 'certificate');
			dom.certContainer.appendChild(row);
			updateCertificateRemoveState();
			refreshTherapyCertificatesState();
		}

		function addCourseRow() {
			if (!dom.courseContainer) {
				return;
			}
			const row = document.createElement('div');
			row.className = 'dynamic-row course-row';

			const schoolInput = document.createElement('input');
			schoolInput.type = 'text';
			schoolInput.name = 'course_school[]';
			schoolInput.placeholder = 'مدرسة العلاج النفسي';

			const placeInput = document.createElement('input');
			placeInput.type = 'text';
			placeInput.name = 'course_place[]';
			placeInput.placeholder = 'مكان الحصول عليها (أو تعليم ذاتي)';

			const yearInput = document.createElement('input');
			yearInput.type = 'text';
			yearInput.name = 'course_year[]';
			yearInput.placeholder = 'سنة الحصول عليها';

			row.appendChild(schoolInput);
			row.appendChild(placeInput);
			row.appendChild(yearInput);
			attachRemoveButton(row, 'course');
			dom.courseContainer.appendChild(row);
			updateCourseRemoveState();
		}

		function attachRemoveButton(row, type) {
			if (!row) {
				return;
			}
			const existing = row.querySelector('.remove-row-btn');
			if (existing) {
				existing.remove();
			}
			row.appendChild(createRemoveButton(type));
		}

		function createRemoveButton(type) {
			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'remove-row-btn';
			button.textContent = '❌';
			button.addEventListener('click', function() {
				const container = type === 'certificate' ? dom.certContainer : dom.courseContainer;
				if (!container) {
					return;
				}
				const selector = type === 'certificate' ? '.certificate-row' : '.course-row';
				const row = button.closest(selector);
				if (!row) {
					return;
				}
				const rows = container.querySelectorAll(selector);
				if (rows.length > 1) {
					row.remove();
						} else {
					row.querySelectorAll('input').forEach(function(input) {
						input.value = '';
					});
				}
				if (type === 'certificate') {
					updateCertificateRemoveState();
					refreshTherapyCertificatesState();
				} else {
					updateCourseRemoveState();
				}
			});
			return button;
		}

		function updateCertificateRemoveState() {
			if (!dom.certContainer) {
				return;
			}
			const rows = dom.certContainer.querySelectorAll('.certificate-row');
			rows.forEach(function(row) {
				const button = row.querySelector('.remove-row-btn');
				if (button) {
					button.style.display = rows.length > 1 ? '' : 'none';
				}
			});
		}

		function updateCourseRemoveState() {
			if (!dom.courseContainer) {
				return;
			}
			const rows = dom.courseContainer.querySelectorAll('.course-row');
			rows.forEach(function(row) {
				const button = row.querySelector('.remove-row-btn');
				if (button) {
					button.style.display = rows.length > 1 ? '' : 'none';
				}
			});
		}

		function setRequired(elements, state) {
			toArray(elements).forEach(function(element) {
				if (element) {
					element.required = !!state;
				}
			});
		}

		function toArray(collection) {
			if (!collection) {
				return [];
			}
			if (Array.isArray(collection)) {
				return collection;
			}
			if (NodeList.prototype.isPrototypeOf(collection)) {
				return Array.from(collection);
			}
			return [collection];
		}

		function showElement(element, shouldShow) {
			if (!element) {
				return;
			}
			element.style.display = shouldShow ? 'block' : 'none';
		}

		function isElementVisible(element) {
			if (!element) {
				return false;
			}
			return element.offsetParent !== null;
		}

		function findFirstInvalidGeneralField() {
			if (!dom.form) {
				return null;
			}
			const candidates = Array.from(dom.form.querySelectorAll('input[required], textarea[required], select[required]')).filter(function(field) {
				if (!field || field.disabled) {
					return false;
				}
				if (field.type === 'radio' || field.type === 'checkbox' || field.type === 'file') {
					return false;
				}
				return isElementVisible(field);
			});

			for (let i = 0; i < candidates.length; i++) {
				const field = candidates[i];
				const value = (field.value || '').trim();
				if (!value) {
					const labelText = getFieldLabelText(field);
					return {
						field: field,
						message: labelText ? 'يرجى إدخال ' + labelText + '.' : 'يرجى ملء جميع الحقول الإلزامية.'
					};
				}
				if (field.type === 'email' && !validateEmailFormat(value)) {
					return {
						field: field,
						message: 'يرجى إدخال بريد إلكتروني صحيح.'
					};
				}
				clearFieldError(field);
			}
			return null;
		}

		function getFieldLabelText(field) {
			if (!field) {
				return '';
			}
			let label = null;
			if (field.id) {
				label = document.querySelector('label[for="' + field.id + '"]');
			}
			if (!label && field.closest) {
				const group = field.closest('.form-group');
				if (group) {
					label = group.querySelector('label');
				}
			}
			if (label && label.textContent) {
				return label.textContent.replace('*', '').trim();
			}
			const ariaLabel = field.getAttribute('aria-label');
			if (ariaLabel) {
				return ariaLabel.trim();
			}
			const placeholder = field.getAttribute('placeholder');
			if (placeholder) {
				return placeholder.trim();
			}
			if (field.name) {
				return field.name.replace(/[_\[\]]+/g, ' ').trim();
			}
			return '';
		}

		function validateEmailFormat(value) {
			if (!value) {
				return false;
			}
			const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return pattern.test(value);
		}
	});
	</script>
	<?php
	
	return ob_get_clean();
}
add_shortcode( 'therapist_registration_form', 'snks_therapist_registration_shortcode' );

/**
 * Handle therapist registration form submission via shortcode
 */
function snks_handle_therapist_registration_shortcode() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'therapist_registration_shortcode' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}
	
	// Get settings
	$settings = snks_get_therapist_registration_settings();
	global $wpdb;
	
	// Validate required fields
	$required_fields = array( 'name', 'email', 'phone', 'whatsapp', 'role' );
	
	foreach ( $required_fields as $field ) {
		if ( empty( $_POST[ $field ] ) ) {
			wp_send_json_error( array( 'message' => sprintf( 'Missing required field: %s', $field ) ) );
		}
	}
	
	// Validate email if provided
	if ( ! empty( $_POST['email'] ) && ! is_email( $_POST['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Invalid email address' ) );
	}
	
	$role = sanitize_text_field( $_POST['role'] ?? '' );
	$valid_roles = array( 'psychiatrist', 'clinical_psychologist' );
	if ( ! in_array( $role, $valid_roles, true ) ) {
		wp_send_json_error( array( 'message' => 'يرجى اختيار المسمى الوظيفي الصحيح.' ) );
	}
	
	if ( empty( $_POST['doctor_specialty'] ) ) {
		$_POST['doctor_specialty'] = 'psychiatrist' === $role ? 'طبيب نفسي' : 'أخصائي نفسي إكلينيكي';
	}
	
	if ( empty( $_POST['doctor_specialty'] ) ) {
		wp_send_json_error( array( 'message' => 'Missing required field: doctor_specialty' ) );
	}
	
	$has_uploaded_file = function( $field_name ) {
		return isset( $_FILES[ $field_name ] ) && ! empty( $_FILES[ $field_name ]['name'] );
	};
	
	if ( 'psychiatrist' === $role ) {
		if ( empty( $_POST['psy_rank'] ) ) {
			wp_send_json_error( array( 'message' => 'يرجى اختيار الدرجة المهنية للطبيب النفسي.' ) );
		}
		
		$required_files = array( 'grad_cert', 'practice_license', 'syndicate_id' );
		foreach ( $required_files as $file_field ) {
			if ( ! $has_uploaded_file( $file_field ) ) {
				wp_send_json_error( array( 'message' => 'يرجى رفع جميع المستندات المطلوبة للطبيب النفسي.' ) );
			}
		}
		
		if ( in_array( $_POST['psy_rank'], array( 'specialist', 'consultant' ), true ) && ! $has_uploaded_file( 'rank_degree' ) ) {
			wp_send_json_error( array( 'message' => 'يرجى رفع شهادة درجة الأخصائي أو الاستشاري.' ) );
		}
	} elseif ( 'clinical_psychologist' === $role ) {
		if ( empty( $_POST['psych_origin'] ) ) {
			wp_send_json_error( array( 'message' => 'يرجى اختيار جهة التخرج للأخصائي النفسي الإكلينيكي.' ) );
		}
		
		if ( empty( $_POST['cp_moh_license'] ) ) {
			wp_send_json_error( array( 'message' => 'يرجى تحديد حالة تصريح وزارة الصحة.' ) );
		}
		
		$required_files = array( 'cp_grad_degree', 'cp_highest_degree' );
		foreach ( $required_files as $file_field ) {
			if ( ! $has_uploaded_file( $file_field ) ) {
				wp_send_json_error( array( 'message' => 'يرجى رفع جميع المستندات المطلوبة للأخصائي النفسي الإكلينيكي.' ) );
			}
		}
		
		if ( 'yes' === $_POST['cp_moh_license'] && ! $has_uploaded_file( 'cp_moh_license_file' ) ) {
			wp_send_json_error( array( 'message' => 'يرجى رفع تصريح وزارة الصحة.' ) );
		}
	}
	
	foreach ( array( 'identity_front', 'identity_back' ) as $identity_field ) {
		if ( ! $has_uploaded_file( $identity_field ) ) {
		wp_send_json_error( array( 'message' => 'يرجى رفع صورة البطاقة الشخصية (وجه وظهر).' ) );
		}
	}
	
	// Ensure at least one therapy certificate
	$has_certificate = false;
	if ( isset( $_FILES['therapy_certificates'] ) && isset( $_FILES['therapy_certificates']['name'] ) && is_array( $_FILES['therapy_certificates']['name'] ) ) {
		foreach ( $_FILES['therapy_certificates']['name'] as $certificate_name ) {
			if ( ! empty( $certificate_name ) ) {
				$has_certificate = true;
				break;
			}
		}
	}
	if ( ! $has_certificate ) {
		wp_send_json_error( array( 'message' => 'يرجى رفع شهادة علاج نفسي واحدة على الأقل.' ) );
	}
	
	// Validate courses
	$course_schools = isset( $_POST['course_school'] ) ? (array) $_POST['course_school'] : array();
	$course_places  = isset( $_POST['course_place'] ) ? (array) $_POST['course_place'] : array();
	$course_years   = isset( $_POST['course_year'] ) ? (array) $_POST['course_year'] : array();
	$course_count   = max( count( $course_schools ), count( $course_years ), count( $course_places ) );
	$normalized_courses = array();

	for ( $i = 0; $i < $course_count; $i++ ) {
		$school = trim( $course_schools[ $i ] ?? '' );
		$place  = trim( $course_places[ $i ] ?? '' );
		$year   = trim( $course_years[ $i ] ?? '' );

		if ( '' === $school && '' === $year && '' === $place ) {
			continue;
		}

		if ( '' === $school || '' === $year ) {
			wp_send_json_error( array( 'message' => 'يرجى استكمال بيانات الدورة (المدرسة والسنة) أو ترك الحقل فارغاً.' ) );
		}

		$normalized_courses[] = array(
			'school' => $school,
			'place'  => $place,
			'year'   => $year,
		);
	}

	$_POST['course_school'] = array_column( $normalized_courses, 'school' );
	$_POST['course_place']  = array_column( $normalized_courses, 'place' );
	$_POST['course_year']   = array_column( $normalized_courses, 'year' );
	
	// Validate preferred groups selection
	$preferred_groups = isset( $_POST['preferred_groups'] ) ? array_filter( (array) $_POST['preferred_groups'], 'strlen' ) : array();
	if ( empty( $preferred_groups ) ) {
		wp_send_json_error( array( 'message' => 'يرجى اختيار الفئات التي تفضل العمل معها.' ) );
	}
	if ( count( $preferred_groups ) > 4 ) {
		wp_send_json_error( array( 'message' => 'يمكن اختيار أربع فئات فقط كحد أقصى.' ) );
	}
	$_POST['preferred_groups'] = $preferred_groups;

	// Validate diagnoses selections when sections are visible
	$diagnoses_children = isset( $_POST['dx_children'] ) ? array_filter( (array) $_POST['dx_children'], 'strlen' ) : array();
	$diagnoses_adult = isset( $_POST['dx_adult'] ) ? array_filter( (array) $_POST['dx_adult'], 'strlen' ) : array();

	if ( in_array( 'الأطفال', $preferred_groups, true ) && empty( $diagnoses_children ) ) {
		wp_send_json_error( array( 'message' => 'يرجى اختيار تشخيص واحد على الأقل من قسم الأطفال.' ) );
	}

	if ( in_array( 'المراهقين والبالغين', $preferred_groups, true ) && empty( $diagnoses_adult ) ) {
		wp_send_json_error( array( 'message' => 'يرجى اختيار تشخيص واحد على الأقل من قسم المراهقين والبالغين.' ) );
	}

	$_POST['dx_children'] = $diagnoses_children;
	$_POST['dx_adult'] = $diagnoses_adult;
	
	$normalize_phone = static function( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		$value = preg_replace( '/\s+/', '', $value );
		return sanitize_text_field( $value );
	};

	$find_user_by_phone = static function( $value ) {
		if ( empty( $value ) ) {
			return false;
		}

		$user = get_user_by( 'login', $value );
		if ( $user ) {
			return $user;
		}

		$users = get_users(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $value,
				'number'     => 1,
				'fields'     => 'all',
			)
		);

		return ! empty( $users ) ? $users[0] : false;
	};

	$phone    = $normalize_phone( $_POST['phone'] ?? '' );
	$whatsapp = $normalize_phone( $_POST['whatsapp'] ?? '' );
	$email    = sanitize_email( $_POST['email'] ?? '' );

	$duplicate_conditions = array();
	$duplicate_params     = array();

	if ( ! empty( $phone ) ) {
		$duplicate_conditions[] = 'phone = %s';
		$duplicate_params[]     = $phone;
	}

	if ( ! empty( $whatsapp ) ) {
		$duplicate_conditions[] = 'whatsapp = %s';
		$duplicate_params[]     = $whatsapp;
	}

	if ( ! empty( $email ) ) {
		$duplicate_conditions[] = 'email = %s';
		$duplicate_params[]     = $email;
	}

	if ( ! empty( $duplicate_conditions ) ) {
		$query = 'SELECT id FROM ' . $table_name . ' WHERE ' . implode( ' OR ', $duplicate_conditions ) . ' LIMIT 1';
		$existing_application = $wpdb->get_var( $wpdb->prepare( $query, $duplicate_params ) );

		if ( $existing_application ) {
			wp_send_json_error(
				array(
					'message' => 'يوجد طلب سابق مرتبط بنفس بيانات الاتصال. يرجى استخدام بيانات مختلفة أو التواصل مع فريق الدعم.'
				)
			);
		}
	}

	$phone_user    = $find_user_by_phone( $phone );
	$whatsapp_user = $find_user_by_phone( $whatsapp );

	if ( $phone_user && $whatsapp_user && $phone_user->ID !== $whatsapp_user->ID ) {
		wp_send_json_error(
			array(
				'message' => 'أرقام الاتصال المُدخلة مرتبطة بحسابات مختلفة. يرجى استخدام نفس الحساب أو تحديث الأرقام.',
			)
		);
	}

	$user_id = 0;
	if ( $phone_user ) {
		$user_id = $phone_user->ID;
	} elseif ( $whatsapp_user ) {
		$user_id = $whatsapp_user->ID;
	}

	$uploaded_files = array();
	$file_fields    = array(
		'profile_image',
		'identity_front',
		'identity_back',
		'grad_cert',
		'practice_license',
		'syndicate_id',
		'rank_degree',
		'cp_grad_degree',
		'cp_highest_degree',
		'cp_moh_license_file',
	);

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	foreach ( $file_fields as $field ) {
		if ( ! empty( $_FILES[ $field ]['name'] ) ) {
					$attachment_id = media_handle_upload( $field, 0 );
					if ( ! is_wp_error( $attachment_id ) ) {
						$uploaded_files[ $field ] = $attachment_id;
					}
				}
			}

	$therapy_certificate_ids = array();
	if ( ! empty( $_FILES['therapy_certificates']['name'] ) && is_array( $_FILES['therapy_certificates']['name'] ) ) {
		$file_count = count( $_FILES['therapy_certificates']['name'] );
		for ( $i = 0; $i < $file_count; $i++ ) {
			if ( empty( $_FILES['therapy_certificates']['name'][ $i ] ) ) {
				continue;
			}

			$key = 'therapy_certificate_' . $i;

			$_FILES[ $key ] = array(
				'name'     => $_FILES['therapy_certificates']['name'][ $i ],
				'type'     => $_FILES['therapy_certificates']['type'][ $i ],
				'tmp_name' => $_FILES['therapy_certificates']['tmp_name'][ $i ],
				'error'    => $_FILES['therapy_certificates']['error'][ $i ],
				'size'     => $_FILES['therapy_certificates']['size'][ $i ],
			);

			$attachment_id = media_handle_upload( $key, 0 );

					if ( ! is_wp_error( $attachment_id ) ) {
				$therapy_certificate_ids[] = $attachment_id;
			}

			unset( $_FILES[ $key ] );
		}
	}

	$role = sanitize_text_field( $_POST['role'] ?? '' );
	$psychiatrist_rank = '';
	if ( 'psychiatrist' === $role ) {
		$rank_key = $_POST['psy_rank'] ?? '';
		$rank_map = array(
			'resident' => 'طبيب مقيم طب نفسي',
			'specialist' => 'أخصائي طب نفسي',
			'consultant' => 'استشاري طب نفسي',
		);
		$psychiatrist_rank = isset( $rank_map[ $rank_key ] ) ? $rank_map[ $rank_key ] : sanitize_text_field( $rank_key );
	}
	
	$psych_origin      = '';
	if ( 'clinical_psychologist' === $role ) {
		$origin_key = $_POST['psych_origin'] ?? '';
		$origin_map = array(
			'arts' => 'آداب قسم علم نفس',
			'human_studies' => 'دراسات إنسانية قسم علم نفس',
			'human_sciences' => 'علوم إنسانية قسم علم نفس',
		);
		$psych_origin = isset( $origin_map[ $origin_key ] ) ? $origin_map[ $origin_key ] : sanitize_text_field( $origin_key );
	}
	
	$cp_moh_license = 'clinical_psychologist' === $role ? sanitize_text_field( $_POST['cp_moh_license'] ?? '' ) : '';

	$courses = array();
	if ( ! empty( $_POST['course_school'] ) && is_array( $_POST['course_school'] ) ) {
		$schools = $_POST['course_school'];
		$places  = $_POST['course_place'] ?? array();
		$years   = $_POST['course_year'] ?? array();
		$course_count = count( $schools );
		
		for ( $i = 0; $i < $course_count; $i++ ) {
			$school = sanitize_text_field( $schools[ $i ] ?? '' );
			$place = sanitize_text_field( $places[ $i ] ?? '' );
			$year = sanitize_text_field( $years[ $i ] ?? '' );
			
			if ( '' !== $school && '' !== $year ) {
				$courses[] = array(
					'school' => $school,
					'place'  => $place,
					'year'   => $year,
				);
			}
		}
	}
	
	$preferred_groups = array_map( 'sanitize_text_field', $preferred_groups );
	$diagnoses_children = array_map( 'sanitize_text_field', $diagnoses_children );
	$diagnoses_adult = array_map( 'sanitize_text_field', $diagnoses_adult );
	
	// Insert into database
	$table_name = $wpdb->prefix . 'therapist_applications';
	
	$result = $wpdb->insert(
		$table_name,
		array(
			'user_id' => $user_id ? $user_id : null,
			'name' => sanitize_text_field( $_POST['name'] ),
			'name_en' => sanitize_text_field( $_POST['name_en'] ?? '' ),
			'email' => $email,
			'phone' => sanitize_text_field( $phone ),
			'whatsapp' => sanitize_text_field( $whatsapp ),
			'doctor_specialty' => sanitize_text_field( $_POST['doctor_specialty'] ?? '' ),
			'role' => $role,
			'psychiatrist_rank' => $psychiatrist_rank,
			'psych_origin' => $psych_origin,
			'cp_moh_license' => $cp_moh_license,
			'graduate_certificate' => $uploaded_files['grad_cert'] ?? null,
			'practice_license' => $uploaded_files['practice_license'] ?? null,
			'syndicate_card' => $uploaded_files['syndicate_id'] ?? null,
			'rank_certificate' => $uploaded_files['rank_degree'] ?? null,
			'cp_graduate_certificate' => $uploaded_files['cp_grad_degree'] ?? null,
			'cp_highest_degree' => $uploaded_files['cp_highest_degree'] ?? null,
			'cp_moh_license_file' => $uploaded_files['cp_moh_license_file'] ?? null,
			'profile_image' => $uploaded_files['profile_image'] ?? null,
			'identity_front' => $uploaded_files['identity_front'] ?? null,
			'identity_back' => $uploaded_files['identity_back'] ?? null,
			'certificates' => ! empty( $therapy_certificate_ids ) ? wp_json_encode( $therapy_certificate_ids ) : null,
			'therapy_courses' => ! empty( $courses ) ? wp_json_encode( $courses ) : null,
			'preferred_groups' => ! empty( $preferred_groups ) ? wp_json_encode( $preferred_groups ) : null,
			'diagnoses_children' => ! empty( $diagnoses_children ) ? wp_json_encode( $diagnoses_children ) : null,
			'diagnoses_adult' => ! empty( $diagnoses_adult ) ? wp_json_encode( $diagnoses_adult ) : null,
			'status' => 'pending',
			'submitted_at' => current_time( 'mysql' )
		),
		array(
			'%d',
			'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
			'%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d',
			'%s', '%s', '%s', '%s', '%s', '%s', '%s'
		)
	);
	
	if ( $result ) {
		// Send notification email to admin
		$admin_email = get_option( 'admin_email' );
		$subject = 'New Therapist Registration Application';
		$message = sprintf(
			"A new therapist has submitted a registration application.\n\nName: %s\nEmail: %s\nPhone: %s\nSpecialty: %s\n\nPlease review the application in the admin dashboard.",
			$_POST['name'],
			$_POST['email'] ?? 'Not provided',
			$phone,
			$_POST['doctor_specialty']
		);
		
		wp_mail( $admin_email, $subject, $message );
		
		$response = array(
			'message'        => 'تم التسجيل بنجاح وسيقوم فرق خدمة العملاء بالتواصل معك في أقرب وقت',
			'application_id' => $wpdb->insert_id,
		);

		if ( $user_id ) {
			$response['user_id'] = $user_id;
		}

		wp_send_json_success( $response );
	} else {
		wp_send_json_error( array( 'message' => 'حدث خطأ أثناء حفظ الطلب. حاول مرة أخرى.' ) );
	}
}

add_action( 'wp_ajax_register_therapist_shortcode', 'snks_handle_therapist_registration_shortcode' );
add_action( 'wp_ajax_nopriv_register_therapist_shortcode', 'snks_handle_therapist_registration_shortcode' );

/**
 * Get multilingual OTP message for therapist registration
 */
function snks_get_multilingual_otp_message( $otp_code, $language = 'ar' ) {
	$messages = array(
		'ar' => 'رمز التحقق: %s',
		'en' => 'Verification code: %s',
		'fr' => 'Code de vérification: %s',
		'es' => 'Código de verificación: %s',
		'de' => 'Bestätigungscode: %s',
		'it' => 'Codice di verifica: %s',
		'tr' => 'Doğrulama kodu: %s',
		'ur' => 'تصدیقی کوڈ: %s'
	);
	
	// Fallback to Arabic if language not found
	$template = isset( $messages[ $language ] ) ? $messages[ $language ] : $messages['ar'];
	
	return sprintf( $template, $otp_code );
}

/**
 * Get multilingual email OTP message for therapist registration
 */
function snks_get_multilingual_email_otp_message( $otp_code, $language = 'ar' ) {
	$messages = array(
		'ar' => array(
			'subject' => 'رمز التحقق - تسجيل المعالج في جلسة',
			'body' => 'رمز التحقق الخاص بك: %s

هذا الرمز صالح لمدة 10 دقائق.'
		),
		'en' => array(
			'subject' => 'Verification Code - Jalsah Therapist Registration',
			'body' => 'Your verification code: %s

This code is valid for 10 minutes.'
		),
		'fr' => array(
			'subject' => 'Code de vérification - Inscription thérapeute Jalsah',
			'body' => 'Votre code de vérification: %s

Ce code est valide pendant 10 minutes.'
		),
		'es' => array(
			'subject' => 'Código de verificación - Registro de terapeuta Jalsah',
			'body' => 'Su código de verificación: %s

Este código es válido por 10 minutos.'
		),
		'de' => array(
			'subject' => 'Bestätigungscode - Jalsah Therapeutenregistrierung',
			'body' => 'Ihr Bestätigungscode: %s

Dieser Code ist 10 Minuten gültig.'
		),
		'it' => array(
			'subject' => 'Codice di verifica - Registrazione terapeuta Jalsah',
			'body' => 'Il tuo codice di verifica: %s

Questo codice è valido per 10 minuti.'
		),
		'tr' => array(
			'subject' => 'Doğrulama Kodu - Jalsah Terapist Kaydı',
			'body' => 'Doğrulama kodunuz: %s

Bu kod 10 dakika geçerlidir.'
		),
		'ur' => array(
			'subject' => 'تصدیقی کوڈ - جلسہ تھراپسٹ رجسٹریشن',
			'body' => 'آپ کا تصدیقی کوڈ: %s

یہ کوڈ 10 منٹ کے لیے درست ہے۔'
		)
	);
	
	// Fallback to Arabic if language not found
	$template = isset( $messages[ $language ] ) ? $messages[ $language ] : $messages['ar'];
	
	return array(
		'subject' => $template['subject'],
		'body' => sprintf( $template['body'], $otp_code )
	);
}

/**
 * Send WhatsApp message using WhatsApp Business API
 */
function snks_send_whatsapp_message( $phone_number, $message, $settings ) {
	// Get WhatsApp API settings
	$api_url = $settings['whatsapp_api_url'];
	$access_token = $settings['whatsapp_api_token'];
	$phone_number_id = $settings['whatsapp_phone_number_id'];
	
	// Check if all required settings are available
	if ( empty( $api_url ) || empty( $access_token ) || empty( $phone_number_id ) ) {
		return new WP_Error( 'missing_config', 'WhatsApp API configuration is incomplete' );
	}
	
	// Format phone number (ensure it has proper format without + prefix for API)
	$phone_number = ltrim( $phone_number, '+' );
	
	// Prepare API endpoint - updated to match Meta's format
	$endpoint = rtrim( $api_url, '/' ) . '/' . $phone_number_id . '/messages';
	
	// Prepare request body - conditional template or text message
	$use_template = isset( $settings['whatsapp_use_template'] ) ? $settings['whatsapp_use_template'] : 1;
	
	if ( $use_template ) {
		// Use template message format for guaranteed delivery
		$template_name = isset( $settings['whatsapp_template_name'] ) ? $settings['whatsapp_template_name'] : 'hello_world';
		$template_language = $settings['whatsapp_message_language'] === 'ar' ? 'ar' : 'en_US';
		
		// Extract verification code from message (assuming it's the first 6-digit number)
		preg_match('/\b\d{6}\b/', $message, $matches);
		$verification_code = isset($matches[0]) ? $matches[0] : '123456';
		
		// Debug template parameters (removed for production)
		
		// Build components array for OTP template - body and button components
		$components = array(
			array(
				'type' => 'body',
				'parameters' => array(
					array(
						'type' => 'text',
						'text' => $verification_code
					)
				)
			),
			array(
				'type' => 'button',
				'sub_type' => 'url',
				'index' => '0',
				'parameters' => array(
					array(
						'type' => 'text',
						'text' => $verification_code
					)
				)
			)
		);
		
		$body = array(
			'messaging_product' => 'whatsapp',
			'recipient_type' => 'individual',
			'to' => $phone_number,
			'type' => 'template',
			'template' => array(
				'name' => $template_name,
				'language' => array(
					'code' => $template_language
				),
				'components' => $components
			)
		);
	} else {
		// Use text message format (requires active conversation)
		$body = array(
			'messaging_product' => 'whatsapp',
			'to' => $phone_number,
			'type' => 'text',
			'text' => array(
				'body' => $message
			)
		);
	}
	
	// Prepare headers - exactly matching Meta's format
	$headers = array(
		'Authorization' => 'Bearer ' . $access_token,
		'Content-Type' => 'application/json',
	);
	
	// Make API request with exact Meta specifications
	$args = array(
		'headers' => $headers,
		'body' => wp_json_encode( $body ),
		'timeout' => 15, // Reduced timeout to prevent gateway timeouts
		'blocking' => true,
		'sslverify' => true,
	);
	
	$response = wp_remote_post( $endpoint, $args );
	
	// Check for errors
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	// Get response body and code
	$response_body = wp_remote_retrieve_body( $response );
	$response_code = wp_remote_retrieve_response_code( $response );
	
	// Enhanced logging for debugging
	// Enhanced logging for debugging (removed for production)
	
	// Check response code - Meta typically returns 200 for success
	if ( $response_code !== 200 ) {
		$error_data = json_decode( $response_body, true );
		$error_message = 'WhatsApp API request failed';
		
		// Extract detailed error message from Meta's response format
		if ( isset( $error_data['error']['message'] ) ) {
			$error_message = $error_data['error']['message'];
		} elseif ( isset( $error_data['error']['error_user_msg'] ) ) {
			$error_message = $error_data['error']['error_user_msg'];
		}
		
		return new WP_Error( 'api_error', $error_message, array( 
			'response_code' => $response_code,
			'response_body' => $response_body 
		) );
	}
	
	// Parse response data
	$response_data = json_decode( $response_body, true );
	
	// Return success response
	return $response_data;
}

