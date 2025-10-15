<?php
/**
 * Scripts
 *
 * @package Shrinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'wp_head',
	function () {
		global $wp;
		?>
		<style>
			<?php
			if ( ! is_page( 'account-setting' ) && ! is_page( 'my-profile' ) && ! is_page( 'my-bookings' ) ) {
				?>
				footer{
					display: none;
				}
				<?php
			}
			?>
			<?php
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				?>
				.order_details{
					color: #fff;
					display: flex;
					flex-direction: column;
					justify-content: center;
					align-items: center;
					text-align: center;
				}
				.woocommerce ul.order_details li {
					float: none;
					margin-left: 0;
					margin-bottom: 20px;
					text-transform: uppercase;
					font-size: 18px;
					line-height: 1;
					border-left: 0;
					padding-left: 0;
				}
				.el-kashier-div-button{
					text-align: center;
					margin-bottom: 20px;
				}
				.woocommerce ul.order_details li strong {
					font-size: 25px;
				}
				<?php
			}
			?>
			<?php
			global $wp;
			$light_color  = ! empty( $_COOKIE['light_color'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['light_color'] ) ) : '#dcf5ff';
			$dark_color   = ! empty( $_COOKIE['dark_color'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dark_color'] ) ) : '#024059';
			$darker_color = ! empty( $_COOKIE['darker_color'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['darker_color'] ) ) : '#012d3e';

			if ( isset( $wp->query_vars['doctor_id'] ) ) {
				$clinic_color   = get_user_meta( snks_url_get_doctors_id(), 'clinic_colors', true );
				$clinics_colors = json_decode( CLINICS_COLORS );
				if ( ! empty( $clinic_color ) ) {
					$clinic_colors = 'color_' . $clinic_color;
					$light_color   = $clinics_colors->$clinic_colors[0];
					$dark_color    = $clinics_colors->$clinic_colors[1];
					$darker_color  = $clinics_colors->$clinic_colors[2];
				}
				?>
				header, footer {
					display: none;
				}
				.snks-booking-page-container {
					background-color: #fff;
				}
				.snks-about-me li{
					color: <?php echo esc_attr( $dark_color ); ?>!important;;
				}
				
				<?php
			}
			if ( ! is_page( 'switch-user' ) ) {
				?>
				.wpcsa-bar{
					display: none;
				}
				<?php
			}
			//phpcs:disable

			if ( isset( $_SERVER['REQUEST_URI'] ) && ( strpos( $_SERVER['REQUEST_URI'], '/therapist/' ) !== false || is_page('booking-details') ) ) {
				?>
				body, .snks-booking-page-container,
				.elementor-3537 .elementor-element.elementor-element-3368f02 > .elementor-widget-container > .jet-tabs > .jet-tabs__content-wrapper,
				.elementor-3578 .elementor-element.elementor-element-c5e7b50 > .elementor-widget-container,
				.elementor-3546 .elementor-element.elementor-element-a809552:not(.elementor-motion-effects-element-type-background), .elementor-3546 .elementor-element.elementor-element-a809552 > .elementor-motion-effects-container > .elementor-motion-effects-layer,
				div.elementor .elementor-3546 .elementor-element.elementor-element-a809552:not(.elementor-motion-effects-element-type-background),
				.elementor-3546 .elementor-element.elementor-element-6fecfe97 > .elementor-widget-container, body.woocommerce-order-received
				{
					background-color: <?php echo esc_attr( $dark_color ); ?>!important;
				}
				.elementor-3537 .elementor-element.elementor-element-3368f02 > .elementor-widget-container > .jet-tabs > .jet-tabs__control-wrapper > .jet-tabs__control:not(.active-tab){
					background-color: <?php echo esc_attr( $darker_color ); ?>!important;
				}
				<?php
			}
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/register' ) !== false ) {
				?>
				body{
					background-color: #024059!important;
				}
				:where(.wp-block-columns.is-layout-flex) {
					gap: 0px!important;
				}
				p {
					margin-block-end:0 !important;
				}
				<?php
				//phpcs:enable
			}
			?>
			/* Overlay styling using ::before pseudo-element */
			.jet-form-builder.processing::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background-color: rgba(255, 255, 255, 0.7);
				z-index: 10;
				display: block;
			}

			/* To position the form container for overlay */
			.jet-form-builder {
				position: relative;
			}

			/* Optional: Spinner style */
			.jet-form-builder.processing::after {
				content: '';
				position: absolute;
				top: 50%;
				left: 50%;
				width: 40px;
				height: 40px;
				border: 4px solid rgba(255, 255, 255, 0.3);
				border-radius: 50%;
				border-top: 4px solid #000;
				animation: spin 1s linear infinite;
				transform: translate(-50%, -50%);
				z-index: 11;
			}

			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}
			.snks-tabs {
				margin: 20px 0;
			}

			.snks-tabs-nav {
				display: flex;
				padding: 0;
				list-style: none;
			}

			.snks-tab-item {
				padding: 10px 20px;
				cursor: pointer;
				border-bottom: none;
				background: #f9f9f9;
				margin-right: 5px;
			}

			.snks-tab-item.snks-active {
				color: #fff;
				border-bottom: 2px solid #fff;
				font-weight: bold;
				background-color: #012d3e;
			}
			.snks-tab-panel {
				display: none;
			}

			.snks-tab-panel.snks-active {
				display: block;
			}


			.consulting-session-data a{
				color:#000!important
			}
			table.consulting-session-table .consulting-session-label, table.consulting-session-table .consulting-session-data {
				text-align: center!important;
				font-size: 16.5px;
				border: 1px solid #024059;
			}
			.session-periods{
				flex-direction: column;
			}
			.woocommerce:where(body:not(.woocommerce-block-theme-has-button-styles)) #respond input#submit.alt, .woocommerce:where(body:not(.woocommerce-block-theme-has-button-styles)) a.button.alt, .woocommerce:where(body:not(.woocommerce-block-theme-has-button-styles)) button.button.alt, .woocommerce:where(body:not(.woocommerce-block-theme-has-button-styles)) input.button.alt, :where(body:not(.woocommerce-block-theme-has-button-styles)) .woocommerce #respond input#submit.alt, :where(body:not(.woocommerce-block-theme-has-button-styles)) .woocommerce a.button.alt, :where(body:not(.woocommerce-block-theme-has-button-styles)) .woocommerce button.button.alt, :where(body:not(.woocommerce-block-theme-has-button-styles)) .woocommerce input.button.alt{
				background-color: <?php echo esc_attr( $darker_color ); ?>!important;
			}
			.woocommerce-privacy-policy-link{
				color:#D89237
			}
			#price-break,#checkout-wrapper{
				background-color: <?php echo esc_attr( $dark_color ); ?>!important;
			}
			#add_payment_method #payment ul.payment_methods, .woocommerce-cart #payment ul.payment_methods, .woocommerce-checkout #payment ul.payment_methods {
				border-bottom: none;
			}
			#add_payment_method #payment, .woocommerce-cart #payment, .woocommerce-checkout #payment {
				background: #e9e6ed00;
				border-radius: 0;
				color: #fff;
			}
			#jet-tabs-content-5392 .jet-form-builder__field-wrap label :checked + span::before{
					background-color: <?php echo esc_attr( $darker_color ); ?>!important;
				}
			.booking-phone .anony-dial-codes > div{
				flex-direction: column;
					align-items: flex-start;
			}
			.booking-phone .anony-dial-codes > div .anony-dial-codes-phone-label{
				font-size: 16px;
					margin-bottom: 10px;
			}
			<?php if ( is_checkout() ) { ?>
				.wc_payment_methods li{
					padding: 10px!important;
					min-height: 57.5px;
				}
				.wc_payment_method label{
					display: inline-flex;
					width: 100%;
					justify-content: space-between;
					padding-right: 30px;
					align-items: center;
				}
				.wc_payment_method label div{
					display: flex;
					width: 150px;
					justify-content: flex-end;
					align-items: center;
				}
				#add_payment_method #payment ul.payment_methods li img.kashier-icon, .woocommerce-checkout #payment ul.payment_methods li img.kashier-icon{
					margin:0;
					padding: 0;
					max-width: 150px!important;
				}
				#secured-by-kashier-container{
					padding-top: 0;
				}
				#iFrame{
					z-index: 20!important;
				}
				#add_payment_method #payment ul.payment_methods, .woocommerce-cart #payment ul.payment_methods, .woocommerce-checkout #payment ul.payment_methods{
					padding-top: 5px;
				}
				#add_payment_method #payment ul.payment_methods li, .woocommerce-cart #payment ul.payment_methods li, .woocommerce-checkout #payment ul.payment_methods li{
					margin-bottom: 10px;
					position: relative;
				}
				#add_payment_method #payment ul.payment_methods li input, .woocommerce-cart #payment ul.payment_methods li input, .woocommerce-checkout #payment ul.payment_methods li input{
					position: absolute;
					top: 22px;
				}
				div.wc_payment_method.payment_method_cod{
					margin-top: auto!important;
					position: relative;
						top: 30%;
				}
				body {
					background-color: <?php echo esc_attr( $dark_color ); ?>!important;
				}
				#add_payment_method #payment, .woocommerce-cart #payment, .woocommerce-checkout #payment {
					margin-bottom: 40px;
				}
				#add_payment_method #payment ul.payment_methods li, .woocommerce-cart #payment ul.payment_methods li, .woocommerce-checkout #payment ul.payment_methods li {
					background-color: #fff;
					padding: 10px;
					border-radius: 10px;
					color: <?php echo esc_attr( $dark_color ); ?>!important;;
					font-weight: normal;
				}
				.woocommerce #payment #place_order, .woocommerce-page #payment #place_order {
					float: none!important;
				}
				.form-row.place-order{
					text-align: center;
				}
			<?php } ?>
			<?php if ( is_page( 'booking-details' ) || is_checkout() ) { ?>
			table.consulting-session-table .consulting-session-label, table.consulting-session-table .consulting-session-data {
				border: 1px solid <?php echo esc_attr( $dark_color ); ?>!important;
				color: <?php echo esc_attr( $dark_color ); ?>!important
			}
			
			.elementor-3537 .elementor-element.elementor-element-77c62da > .elementor-widget-container,
			.elementor-3537 .elementor-element.elementor-element-df572ee > .elementor-widget-container,
			.elementor-3537 .elementor-element.elementor-element-1a63c7b > .elementor-widget-container,
			#price-break .discount-section button{
				background-color: <?php echo esc_attr( $darker_color ); ?>!important;
			}
			input,.elementor-3537 .elementor-element.elementor-element-3368f02 > .elementor-widget-container > .jet-tabs > .jet-tabs__control-wrapper > .jet-tabs__control.active-tab .jet-tabs__label-text,
			.elementor-3578 .elementor-element.elementor-element-c5e7b50 .jet-form-builder__action-button{
				color: <?php echo esc_attr( $dark_color ); ?>;
			}
			#price-break .discount-section button{
				border-color: <?php echo esc_attr( $dark_color ); ?>!important;
			}
			.elementor-3578 .elementor-element.elementor-element-c5e7b50 .jet-form-builder__action-button:hover{
				color: #fff!important;
			}
			.account-submit{
				font-weight: 700!important;
				font-size: 20px!important;
				padding: 5px!important;
				font-family: "pt_bold_headingregular", Sans-serif!important;
			}
			::placeholder, ::-moz-placeholder,::-webkit-input-placeholder{
				color: <?php echo esc_attr( $dark_color ); ?>!important;
			}
			<?php } ?>
			#order_review_heading, .woocommerce-form-coupon-toggle, .checkout_coupon, .woocommerce-terms-and-conditions-wrapper{
				display: none;
			}
			input[type="search"], input[type="tel"], input[type="text"], input[type="email"], input[type="password"], input[type="url"], input[type="number"], textarea, select {
				margin: 0!important;
			}
			#doctor-login label, #doctor-login .jet-form-builder__label-text, #normal-login label, #normal-login .jet-form-builder__label-text{
				font-size: 14px!important;
				color: #024059!important;
				font-weight: bold;
			}
			e-page-transition{
				z-index: 9999999!important;
			}
			.jet-form-builder-col__start{
				display: inline-flex;
				align-items: center;
			}
			.consulting-session-table {
				width: 95%;
				margin: auto;
				border-collapse: separate;
				margin-bottom: 10px;
				border-spacing: 0;
			}
			.popup-trigger .elementor-widget-container, .popup-trigger .elementor-widget-container a{
				display: flex;
				justify-content: center;
				align-items: center;
			}
			.preview-container{
				background-color: #fff;
				padding: 10px;
				margin-bottom: 10px;
				border-radius: 10px;
			}
			.day-specific-form #app_clinic{
				display: none;
			}
			.day-specific-form form > .field-type-heading-field{
				background-color: #024059;
				color: #fff;
				padding: 10px;
				border-radius: 10px;
				text-align: center;
				margin:10px 0;
				cursor: pointer;
			}
			.day-specific-form form > .wp-block-columns-is-layout-flex{
				background-color: #f1f1f1;
				padding: 10px;
				border-radius: 10px;
				display: none;
			}
			.day-specific-form form > .field-type-submit-field{
				display: none;
			}
			.day-specific-form form > .wp-block-columns-is-layout-flex select{
				color: #024059;
				background-color: #fff;
				border: 1px solid #024059;
			}
			table.consulting-session-table .consulting-session-label {
				background-color: #c8c8c8!important;
				padding: 8px;
				width: 40%;
			}
			.jet-form-builder-repeater__remove{
				display:none!important
			}
			.consulting-session-table tr:first-child td:first-child {
				border-top-right-radius: 10px;
			}

			.consulting-session-table tr:first-child td:last-child {
				border-top-left-radius: 10px;
			}

			.consulting-session-table tr:last-child td:first-child {
				border-bottom-right-radius: 10px;
			}

			.consulting-session-table tr:last-child td:last-child {
				border-bottom-left-radius: 10px;
			}
			.snks-booking-item.snks-patient-booking-item{
				background-color: transparent;
			}
			table.consulting-session-table .consulting-session-data {
				padding: 8px;
				background-color: #fff!important;
				text-align: right;
				width: 60%;
			}
			#consulting-forms-container > p {
				margin: 0;
				padding: 10px;
				text-align: center;
			}
			.jet-popup {
				overflow: -moz-scrollbars-none; /* For older versions of Firefox */
				-ms-overflow-style: none; /* For Internet Explorer and Edge */
				scrollbar-width: none; /* For Firefox */
			}

			.jet-popup::-webkit-scrollbar, .jet-popup::-moz-scrollbar  {
				display: none;
			}
			.page-id-1935 .jet-popup{
				height: calc(100% - 60px)!important;
			}
			.page-id-1935 .jet-popup.jet-popup--front-mode .jet-popup__overlay{
				background-color: transparent;
			}
			#appointments-preview{
				display: none;
			}
			#iban-container{
				position: relative;
				display: block;
			}
			#iban-container input{
				margin: 0;
				padding-left: 50px;
				direction: ltr;
				text-align: left;
			}
			#iban-country-code{
				position: absolute;
				top:0;
				left: 0;
				padding: 7px 10px;
				background-color: #012d3e;
				color:#fff;
				box-sizing: border-box;
				height: 40px;
			}
			body.woocommerce-order-received,
			.elementor-3761 .elementor-element.elementor-element-2dc5242b:not(.elementor-motion-effects-element-type-background), 
			.elementor-3761 .elementor-element.elementor-element-2dc5242b > .elementor-motion-effects-container > .elementor-motion-effects-layer,.owl-carousel.days-container .owl-nav button,
			.elementor-3761 .elementor-element.elementor-element-1923cb73:not(.elementor-motion-effects-element-type-background), .elementor-3761 .elementor-element.elementor-element-1923cb73 > .elementor-motion-effects-container > .elementor-motion-effects-layer
			{
				background-color: <?php echo esc_attr( $dark_color ); ?>!important;
			}
			.elementor-3761 .elementor-element.elementor-element-54e84fd .elementor-button
			{
				background-color: <?php echo esc_attr( $darker_color ); ?>!important;
			}
			.e-con>.e-con-inner {
				padding: 0;
			}
			.anony-form .button-primary {
				background: #024059;
				border-color: #024059;
				box-shadow: none;
				color: #fff;
				text-decoration: none;
				text-shadow: none;
			}
			#submit-user_edit_session_notes{
				width: 100%;
			}
			.snks-notes-form{
				max-height:0;
				overflow:hidden;
				transition: all 1s ease-in-out;
				padding: 0;
				margin-bottom: 20px;
			}
			.show-notes-form.snks-notes-form{
				max-height: 500px;
			}
			.anony-gallery-thumbs::before{
				display: none;
			}
			.width-280{
				width: 280px;
				margin:auto;
			}
			.positioned-mark .jet-form-builder__required{
				left: 60px!important;
			}
			.jet-fancy-upload .jet-form-builder__label-text{
				font-size: 18px;
			}
			.jet-fancy-upload .jet-form-builder__label-text .jet-form-builder__required{
				font-size: 26px;
			}
			.jet-fancy-upload .jet-form-builder__heading .jet-form-builder__label-text{
				font-size: 22px;
				color: #fff;
				text-align: center;
			}
			.jet-fancy-upload .field-type-media-field{
				padding: 0!important;
			}
			.jet-fancy-upload .jet-form-builder-row{
				position: static;
			}
			.jet-fancy-upload.jet-form-builder-row{
				position: relative;
			}
			.relative-column{
				position: relative;
			}
			#teeth-area {
				overflow: hidden;
				position: absolute;
				top: -20px;
				width: 100%;
				height: 30px;
			}
			#teeth-area::before {
				content: "";
				font-family: "shape divider from ShapeDividers.com";
				position: absolute;
				z-index: 3;
				pointer-events: none;
				background-repeat: no-repeat;
				bottom: -0.1vw;
				left: -0.1vw;
				right: -0.1vw;
				top: 0;
				background-size: cover;
				background-position: bottom;
				background-image: url('/wp-content/uploads/2024/09/teeth-2.png');
				z-index: 9999;
			}
			.consulting-form{
				max-width: 340px;
				margin: auto;
				margin-top: 30px;
			}
			@media (min-width: 320px) and ( max-width: 360px ) {
				.attendance_type_wrapper label {
					font-size: 19px!important;
				}
				
			}
			@media (min-width: 360px) and ( max-width: 400px ) {
				.snks-period-label, .snks-period-price {
					font-size: 15px!important;
				}
				
			}
			@media (max-width: 400px) {
				div.shap-head-bg {
					width: 70px;
					height: 70px;
					bottom: -8px;
					left: -11px;
				}
				#head3 div.shap-head-bg{
					left:0;
					right: -11px;
				}
				div#head2{
					left:10px
				}
				div#head3 {
					right: 13px;
				}
				.shap-head img{
					width: 60px;
				}
				
				body.page-id-137 .is-layout-flex{
					width: 100%;
				}
				#certificate-1-preview.anony-nice-uploader, .snks-tear-shap-wrapper{
					width: 130px!important;
					height: 130px!important;
				}
				#certificate-2-preview.anony-nice-uploader,#certificate-3-preview.anony-nice-uploader ,#certificate-4-preview.anony-nice-uploader,#certificate-5-preview.anony-nice-uploader {
					width: 60px!important;
					height: 60px!important;
				}
				.elementor-137 .elementor-element.elementor-element-bfc1bf5 > .elementor-widget-container {
					padding: 0!important;
				}
				.snks-available-hours ul{
					flex-direction: column;
				}
				.snks-available-hours ul li, .snks-available-hours li.available-time label{
					width: 100%!important;
				}
				
				.consulting-form{
					max-width: 250px;
				}
				
			}
			@media (max-width: 320px) {
				.attendance_type_wrapper label{
					font-size: 19px!important;
				}
				
			}
			.clinic-detail {
				display: none;
				position: fixed;
				max-width: 90%;
				top: calc(50% - 150px);
				left: 0;
				background-color: #fff;
				right: 0;
				margin: auto;
				padding: 10px;
				border-radius: 10px;
			}
			.close-clinic-popup {
				height: 30px;
				width: 30px;
				background-color: #e61f1f;
				color: #fff;
				border-radius: 50%;
				display: flex;
				justify-content: center;
				align-items: center;
				font-size: 20px;
				position: absolute;
				top: -15px;
				cursor: pointer;
			}
			#snks_account_settings{
				transition: all 1s ease-in-out;
				padding: 0 10px;
			}
			.jet-popup-loader {
				border: 4px rgb(6, 68, 93) solid;
				border-top-width: 4px;
				border-top-style: solid;
				border-top-color: #fff;
			}
			.doctor-actions-wrapper{
				display: flex;
				justify-content: space-around;
				align-items: baseline;
			}
			.swal2-close{
				font-family: "Arial", sans-serif!important;
			}
			.snks-appointment-button{
				border-top-left-radius:20px;border-bottom-left-radius:20px;
			}
			.snks-disabled .snks-appointment-button {
				background-color: #3d7288!important;
			}
			.snks-timetable-accordion-wrapper.future:nth-child(1) .snks-timetable-accordion-content-wrapper{
				display: block;
			}
			.snks-timetable-accordion-wrapper{
				max-width: 360px;
				margin: auto;
				margin-bottom: 10px;
			}
			.snks-timetable-accordion{
				border-top-left-radius: 10px;
				border-top-right-radius: 10px;
			}
			.change-to-list > div {
				display: flex;
				margin: 10px 0;
				align-items: center;
			}
			#withdrawal-settings-form h1 {
				font-family: "pt_bold_headingregular", Sans-serif!important;
			}
			.change-to-list > div input{
				margin-left: 10px;
			}
			#doctor-change-appointment-submit{
				margin-top: 15px;
			}
			.snks-timetables-count{
				border-top-right-radius: 10px;
			}
			.snks-timetable-accordion-content{
				border-bottom-left-radius: 10px;
				border-bottom-right-radius: 10px;
			}
			#my-bookings-container{
				margin-top: 20px;
			}
			.snks-offline-border-radius{
				border-top-left-radius:20px;border-bottom-left-radius:20px;
				overflow: hidden;
			}

			.snks-booking-item-row{
				height: 45px;
			}
			.snks-item-icons-bg img{
				width: 35px;
			}
			.snks-booking-item{
				background-color: #fff;
			}
			.snks-booking-item .anony-grid-col{
				padding: 0;
			}
			.rotate-90{
				transform: rotate(-90deg);
				white-space: nowrap;
				transform-origin: center;
			}
			.rotate-90 *{
				white-space: nowrap!important;
			}
			/* Better positioning for rotated Arabic text */
			.snks-start-meeting.rotate-90 {
				text-align: center;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 100%;
				position: absolute;
				top: calc(50% - 15px);
				left: 50%;
				transform: translateX(-50%) rotate(-90deg);
				transform-origin: center;
			}
			/* Positioning for non-rotated session start buttons */
			.snks-start-meeting {
				text-align: center;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 100%;
				color: #fff !important;
			}
			.snks-bg{
				background-color: #024059!important;
				color:#fff!important
			}
			.snks-bg-secondary{
				background-color: #012d3e!important;
				color:#fff!important
			}
			#consulting-forms-container{
				margin: auto;
				min-width: 95%;
			}
			.snks-period-label:before{
				content: '';
				display: inline-block;
				width: 15px;
				height: 15px;
				margin-left: 3px;
				flex-shrink: 0;
				flex-grow: 0;
				border: 1px solid #fff;
				background-repeat: no-repeat;
				background-position: center center;
				background-size: 70% 70%;
				border-radius: 50%;
				border-color: #fff;
				background-color: #fff;
				background-image: url("data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%27-4 -4 8 8%27%3e%3ccircle r=%273%27 fill=%27%23fff%27/%3e%3c/svg%3e");
			}
			.snks-period-label.snks-light-bg::before{
				background-color: #024059 !important;
				border: 1px solid #024059!important;
			}
			.snks-period-label, .snks-period-price {
				width: 100%;
				max-width: 126px;
				justify-content: center;
				border-radius: 20px;
				font-size: 16px;
				text-align: center;
			}
			.snks-period-price {
				border-top-left-radius: 0;
				border-top-right-radius: 0;
				border-bottom-right-radius: 10px;
				border-bottom-left-radius: 10px;
				padding: 3px 0 5px 0;
			}
			a.snks-button{
				background-color: #024059!important;
				color:#fff!important;
				border-radius: 3px;
				justify-content: center;
			}
			.snks-secondary-bg{
				background-color: #dcf5ff;
				color:#024059
			}
			.snks-item-icons-bg{
				background-color: #f1fbff;
			}
			.attendance_types_wrapper{
				display: flex;
				width: 100%;
				justify-content: space-around;
				background-color: #024059;
				padding: 20px 5px;
			}
			.attendance_type_wrapper{
				width: 50%;
				padding: 3px;
			}
			.periods_wrapper{
				opacity: 0;
			}
			.snks-separator{
				border-top: 1px solid #fff;
			}
			.attendance_type_wrapper label{
				padding: 5px;
				background-color: #012d3e;
				border-radius: 10px;
				height: 50px;
				box-sizing: border-box;
				color:#fff;
				width: 100%;
				display: flex;
				font-size: 24px;
				align-items: center;
				justify-content: center;
			}
			.attendance_type_wrapper label img{
				margin: 0;
				height: 30px;
				margin-left: 5px;
			}
			.snks-dark-icon{
				display: none;
			}
			.attendance_type_wrapper.active label .snks-dark-icon{
				display: inline-flex;
			}
			.attendance_type_wrapper.active label .snks-light-icon{
				display: none;
			}
			#snks-booking-page .attendance_type_wrapper.active label{
				background-color: #fff!important;
				color: #024059;
			}
			.field-type-switcher, .field-type-select-field{
				position: relative;
			}
			.jet-form-builder__field.text-field{
				color: #024059;
			}
			#allow_appointment_change{
				margin-right: 20px;
			}

			.bulk-action-checkbox{
				height: 20px;
				width: 20px;
				display: none;
			}
			.bulk-action-toggle{
				position: absolute;
				top: calc(50% - 10px);
				left: 40px;
				cursor: pointer;
			}
			.bulk-action-toggle-tip{
				background-color: #fff;
				color: #024059;
				padding: 5px 10px;
				top: -40px;
				position: absolute;
				width: 100px;
				text-align: center;
				left: -5px;
				border-radius: 20px;
			}
			.bulk-action-toggle-tip-close{
				height: 20px;
				width: 20px;
				background-color: #024059;
				color:#fff;
				border-radius: 50%;
				position: absolute;
				top: -15px;
				right: 10px;
			}
			.snks-timetable-accordion-actions{
				max-height: 0;
				overflow: hidden;
				padding: 0;
				/*transition: all 0.8s ease-in-out;*/
			}
			.snks-timetable-accordion-actions.snks-timetable-active-accordion{
				max-height: 150px;
				padding: 5px;
			}

			.snks-switcher-text{
				position: absolute;
				font-weight: bold;
				color: #024059;
			}
			.snks-light-bg{
				background-color: #fff;
				color: #024059;
			}
			.field-type-select-field select{
				-webkit-appearance: none;
				-moz-appearance: none;
				appearance: none;
				background-image:none!important
			}
			
			/*.field-type-select-field:after, .snks-timetable-accordion:after, */.bulk-action-toggle-tip:after {
				content: '\25BC'; /* Unicode character for down arrow */
				position: absolute;
				top: 51%;
				left: 10px;
				transform: translateY(-50%);
				pointer-events: none;
				color: #fff;
				font-size: 12px;
			}
			.snks-timetable-accordion-toggle{
				position: absolute;
				left: 10px;
				top:calc(50% - 10px);
				transition: all 1s ease-in-out;
			}
			.snks-active-accordion .snks-timetable-accordion-toggle{
				transform: rotate(180deg);
				transform-origin: center center;
				top:calc(50% - 20px);
			}
			.bulk-action-toggle-tip:after{
				top: 39px;
			}

			.switcher-no{
				right: 2px;
			}
			.switcher-yes{
				right: 75px;
			}

			.field-type-radio-field .jet-form-builder__field-label.for-radio :checked + span::before {
				border-color: #024059;
				background-color: #024059;
			}
			.field-type-radio-field .jet-form-builder__field-label.for-radio > span::before {
				border: 1px solid #024059;
				background-color: #024059;
			}
			#snks-booking-form input[type=radio] {
				display: none;
			}
			.periods-prices{
				position: relative;
			}
			.field-type-switcher input.jet-form-builder__field:checked {
				background: #024059;
			}
			.margin-top{
				margin-top: 10px;
			}
			.section-bg{
				background-color: #fff;
				padding: 10px;
				align-items: flex-start;
			}
			.jet-form-builder-repeater__row-remove {
				align-self: center;
				position: absolute;
				top: -10px;
				left: 10px;
			}
			#add-appointments .jet-form-builder-repeater__row-fields  .wp-block-columns{
				flex-direction: column;
			}
			#add-appointments select {
				color: #024059;
				background-color: #fff;
			}
			#add-appointments .wp-block-column > .wp-block-group{
				background-color: #fff;
				margin-bottom: 20px;
				border-radius: 10px;
			}
			#add-appointments .jet-form-builder-row{
				padding: 0;
				border-radius: 10px;
			}
			#add-appointments .jet-form-builder-repeater{
				padding: 10px;
			}
			.jet-form-builder-repeater__row-fields .clinc-row{
				gap:10px
			}
			.jet-form-builder-repeater__row-fields .clinc-row input{
				border: none;
			}
			.jet-form-builder-repeater__row{
				background-color: #e6e4e4;
				padding: 15px!important;
				margin-top: 20px!important;
				border-radius: 8px;
				position: relative;
			}
			.session-periods{
				align-items: flex-start;
			}
			.field-type-switcher {
				display: flex;
				flex-wrap: nowrap;
				margin: 0;
				padding: 5px 0;
				flex-direction: row-reverse;
			}
			.rounded{
				border-radius: 20px;
			}
			.jet-form-builder__heading-desc{
				color: #b5b5b5
			}
			.offline-clinic-details{
				display: inline-flex;
				list-style: none;
				padding: 10px;
				margin: 5px;
				border-radius: 10px;
				flex-direction: column;
				justify-content: flex-start;
				align-items: flex-start;
				-webkit-box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75);
				-moz-box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75);
				box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75);
			}
			ul#consulting-forms-tabs li.active-tab{
				background-color: #024059;
				color: #fff;
			}
			/* Reset default list styles */
			ul#consulting-forms-tabs {
				list-style: none;
				padding: 0;
				margin: 0;
			}

			/* Style the list items as horizontal tabs */
			ul#consulting-forms-tabs li {
				display: inline-block;
				margin-right: 10px;
				padding: 5px 10px;
				background-color: #fff;
				border-radius: 4px;
				border: 1px solid #024059;
				cursor: pointer;
			}

			/* Apply active tab styles */
			.timetable-preview-item{
				display: none;
			}
			.timetable-preview-tab{
				cursor: pointer;
			}
			.ui-datepicker{
				width:350px;
				right: 0;
				margin: auto;
			}
			.preview-timetable{
				margin-bottom: 30px;
			}
			/*Flat picker*/
			.flatpickr-day.disabled{
				position:relative;
				opacity: 0.5;
			}
			.flatpickr-day.disabled:after{
				content: '/';
			position: absolute;
			right: 13px;
			font-size: 25px;
			opacity: 0.5;
				color:red
			}
			.nextMonthDay{
				color:#34499c!important
			}
			.select2-container--default .select2-selection--single {
				background-color: #f5f5f5;
				border: navajowhite;
				border-radius: 4px;
			}
			.select2-container .select2-selection--single {
				height: 40px;
			}
			.select2-container .select2-selection--single .select2-selection__rendered {
				position: relative;
				top: 8px;
			}
			.select2-container--default .select2-selection--single .select2-selection__arrow {
				height: 40px;
			}
			.trigger-error{
				display:none
			}
			.accordion-content{
				display:none
			}
			.accordion-heading{
				padding:10px;
				background-color:#fff;
				border-radius:10px;
				cursor:pointer;
			}
			.accordion-heading{
				background-color:#024059;
				color:#fff!important
			}
			.accordion-heading.active-accordion{
				background-color:'#012d3e';
			}
			.shrinks-error{
				border:1px solid red;
				padding:3px
			}
			.jet-form-builder__conditional.clinics-list .jet-form-builder-repeater__actions button.jet-form-builder-repeater__new{
				color:#fff;
				background-color:#024059;
				font-size:14px;
				font-weight:bold;
				border-radius:50px
			}
			.jet-form-builder__conditional.clinics-list .jet-form-builder-repeater__actions button.jet-form-builder-repeater__new:before{
				content: "+";
				font-size: 20px;
				color: #fff;
				line-height: 0;
				position: relative;
				top: 3px;
				font-weight: bold;
				margin-left: 5px;
			}
			.change-fees .jet-form-builder-row.field-type-switcher {
				border:none
			}

			.jet-form-builder-row.field-type-switcher {
				display: flex;
				flex-direction: row-reverse;
				justify-content: start;
				align-items: center;
				/*padding: 10px;*/
				background-color: #fff;
				border-radius: 50px;
				border: none!important
			}
			input[type=number]{
				background-color: #f5f5f5;
				border: 1px solid #f5f5f5 !important;
				top: 2px;
				position: relative;
			}
			.inline-description-wrapper .field-type-number-field{
				flex-direction: row;
				align-items: center;
			}
			.inline-description-wrapper .field-type-number-field .inline-description{
				margin-left: 10px;
				max-width: 50px;
			}
			.inline-description-wrapper .field-type-number-field .inline-description,.inline-description-wrapper .field-type-number-field .jet-form-builder__field-wrap,.inline-description-wrapper .field-type-number-field .jet-form-builder__desc{
				flex: initial;
				display: inline-block;
				width: auto;
			}
			.inline-description-wrapper .field-type-number-field .jet-form-builder__desc{
				font-size: 18px;
				font-weight: bold;
				color:#024059!important;
			}
			.inline-description-wrapper .field-type-number-field .jet-form-builder__label{
				width: auto!important;
				flex: initial;
			}
			.jet-form-builder__desc{
				text-align: right;
			}
			.jet-form-builder-row{
				position:relative
			}
			select.jet-form-builder__field {
				color: #fff;
				background-color: #024059;
			}
			
			.field-type-switcher span {
				position: absolute;
				left: 10px;
				display: inline-flex;
				align-items: baseline;
			}
			.field-type-switcher input {
				margin-left: 10px!important;
			}
			.jet-form-builder-row.field-type-switcher .jet-form-builder__label {
				width: auto;
				flex: initial;
			}
			.default-border{
				border: 1px solid #000;
			}
			.default-border-radius{
				border-radius: 10px;
			}
			.default-padding{
				padding: 10px
			}
			.jet-form-builder-row.field-type-switcher {
				position: relative;
			}
			.anony-go-back{
				position: absolute;
				top: 10px;
				left: 30px;
				color: #fff !important;
				display: inline-flex;
				justify-content: center;
				align-items: center;
				padding: 10px;
				background-color: #000;
				font-size: 25px;
				border-radius: 3px;
				line-height: 10px;
				height: 40px;
				width: 49px;
				z-index: 11;
			}
			.anony-booking-popup{
				position: absolute;
				top: 60px;
				left: 30px;
				color: #fff !important;
				display: inline-flex;
				padding: 10px;
				background-color: #000;
				font-size: 25px;
				border-radius: 3px;
				line-height: 10px;
				z-index: 11;
			}
			/*.jet-popup.jet-popup--front-mode.jet-popup--hide-state{
				display: none;
			}
			.jet-popup.jet-popup--front-mode.jet-popup--show-state{
				display: block;
			}*/
			.jet-form-builder-repeater__custom_remove{
				height: 25px!important;
				width: 25px!important;
				display: inline-flex!important;
				justify-content: center!important;
				align-items: center!important;
				line-height: 0;
				padding: 3px!important;
				background-color:red;
				font-family: "Arial"!important;
			}
			.jet-form-builder-repeater__row {
				padding: 5px 0;
			}
			table.ui-datepicker-calendar, .ui-datepicker{
				background-color: #3a4091;
			}
			td:not(.ui-datepicker-unselectable) a{
				color:#fff
			}
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions):not(.consulting-session-table):not(.user-transactions) {
				border: 1px solid #ccc;
				border-collapse: collapse;
				margin: 0;
				padding: 0;
				width: 100%;
				table-layout: fixed;
			}

			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) caption {
				font-size: 1.5em;
				margin: .5em 0 .75em;
			}

			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) tr {
				background-color: #024059;
				border: 1px solid #024059;
				padding: .35em;
				border-radius: 10px;
				color: #fff;
			}
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) tr.snks-is-off{
				background-color: #000;
				border-color: #000;
			}
			.required-upload .jet-form-builder__required{
				top:0!important;
				left:0!important
			}
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions):not( .shop_table ) th,
			table:not( .shop_table ) td {
				padding: .625em;
				text-align: center;
			}

			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) th {
				font-size: .85em;
				text-transform: uppercase;
			}

			@media screen and (max-width: 600px) {
				table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) {
				border: 0;
			}

			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) caption {
				font-size: 1.3em;
			}
			
			/*table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) thead {
				border: none;
				clip: rect(0 0 0 0);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute;
				width: 1px;
			}
			
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) tr {
				border-bottom: 3px solid #ddd;
				display: block;
				margin-bottom: .625em;
			}
			
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) td {
				border-bottom: 1px solid #ddd;
				display: block;
				font-size: .8em;
				text-align: <?php echo is_rtl() ? 'left' : 'right'; ?>;
			}
			
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) td::before {
				content: attr(data-label);
				float: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				font-weight: bold;
				text-transform: uppercase;
			}
			*/
			table:not(.ui-datepicker-calendar):not(.consulting-session-table):not(.user-transactions) td:last-child {
				border-bottom: 0;
			}
			}
		</style>
		<style>

			#call-customer-care{
				position: fixed;
				bottom: 70px;
				left: 10px;
			}
			#call-customer-care > a{
				display: inline-block;
			}
			table tbody>tr:nth-child(odd)>td{
				background-color: rgb(217,217,217,0.20)!important;
			}
			table tbody tr td input.to-do-input{
				background-color: transparent!important;
			}
			table tbody>tr:nth-child(even)>td{
				background-color: rgb(255,255,255,0.20)!important;
			}
			.snks-form-days {
				background-color: #02405914;
				width: 100%;
				border-radius: 10px;
			}
			.anony-day-radio{
				display: inline-flex;
				border-radius: 5px;
				cursor: pointer;
				box-sizing: border-box;
			}
			.anony-day-radio label{
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
				padding: 10px 5px;
				border-radius: 5px;
				justify-content: space-between;
				flex-direction: column;
				font-size: 16px;
				width:100%
			}
			.anony-day-radio input{
				display: none;
			}
			.anony-day-radio label.active-day, input[type=submit]{
				background-color: #fff;
				color:#024059;
				border:none
			}
			#snks-available-hours-wrapper{
				display:none
			}
			.snks-available-hours{
				list-style-type: none;
				margin: 0;
				padding: 0;
			}
			.snks-available-hours li{
				display: inline-flex;
				justify-content: center;
				align-items: center;
				border-radius: 5px;
				font-size: 15px;
				padding: 0;
			}
			.snks-available-hours li.available-time label{
				background-color: #024059;
				color: #fff;
				border-radius: 8px;
				padding: 10px;
				box-sizing: border-box;
				display: inline-flex;
				justify-content: center;
				align-items: center;
				margin-bottom: 10px;
				border:1px solid #fff;
				overflow: hidden;
				font-size: 16.5px;

			}
			.snks-available-hours ul{
				display: flex;
				flex-wrap: wrap;
				max-width: 90vw;
				width: 100%;
				justify-content: center;
			}
			.offline-clinic-hours{
				flex-direction: column;
			}
			.snks-available-hours li.available-time input{
				display:none
			}
			.snks-available-hours li.available-time.active-hour label{
				background-color: #fff;
				color: #024059;
			}
			#consulting-form-submit-wrapper{
				display: none;
			}
			.snks-form .row{
				margin-bottom: 15px;
			}
			.snks-form label{
				margin-bottom: 5px;
			}
			.snks-form input[type=text], .snks-form input[type=password], .snks-radio{
				background-color: #F5F5F5;
				padding: 10px;
				border-radius: 5px;
				border:none
			}
			input[type=submit]{
				margin-bottom:50px ;
				width: 100%;
				text-align: center;
				
			}
			#consulting-form{
				position: relative;
			}
			#consulting-form-price{
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			#consulting-form h5{
				display: flex;
				align-items: center;
				font-size: 15px;
				font-weight: 500 ;
			}
			#consulting-form input[type=submit]{
				margin-top: 20px;
			}
			.snks-form-days{
				margin-bottom: 20px;
			}
			#consulting-form hr{
				border: none;
				margin: 20px 0;
				height: 1px;
				background: #b4b4b4;
				background: repeating-linear-gradient(90deg, #b4b4b4, #b4b4b4 6px, transparent 6px, transparent 12px);
			}
			.snks-radio{
				display: inline-block;
				width: 135px;
				position: relative;
			}
			.snks-radio.snks-checked{
				background-color: #0240595c;
			}
			.snks-radio label{
				position: absolute;
				top: 30%;
				right: 40%;
			}
			#family-account-container{
				display:none
			}
			::-webkit-input-placeholder { /* Chrome/Opera/Safari */
				font-size: 12px;
			}
			::-moz-placeholder { /* Firefox 19+ */
				font-size: 12px;
			}
			:-ms-input-placeholder { /* IE 10+ */
				font-size: 12px;
			}
			:-moz-placeholder { /* Firefox 18- */
				font-size: 12px;
			}
			/**Content slider */


			.owl-carousel.days-container .owl-nav button {
				position: absolute;
				top: calc(50% - 15px);
				display: block;
				width: 30px;
				height: 30px;
				padding: 0;
				cursor: pointer;
				border: none;
				outline: none;
				z-index: 12;
				color: #fff !important;
				border-radius: 50%;
				font-size: 20px !important;
			}
			.owl-next{
				left:-31px
			}
			.owl-prev{
				right:-31px
			}

		
			.anony-day-number{
				font-size: 27px;
				padding: 8px;
				width: 45px;
				text-align: center;
			}
			.vertical-divider{
				width: 2px;
				height: 170px;
				background-color:#024059;
				position: absolute;
				top: 65px;
				left: 40%
			}
			.anony-greater-than .top {
				transform: rotate(45deg);
				top: 8px;
				position: relative;
			}

			.anony-greater-than .bottom {
				transform: rotate(-45deg);
			}

			.jet-tabs__content-wrapper{
				min-height: 200px!important;
			}
			.anony-smaller-than .top {
				transform: rotate(-45deg);
				top: 8px;
				position: relative;
			}

			.anony-smaller-than .bottom {
				transform: rotate(45deg);
			}
			.anony-content-slide{
				width: 60px!important;
				align-items: center;
					justify-content: center;
			}
			.clinic-rules {
				max-width: 300px;
				margin: auto;
				margin-bottom: 30px;
			}
			.clinic-rules h1{
				font-size: 100%;
				text-align: center;
			}
			.clinic-rules h1, .clinic-rules p, .offline-clinic-hours h3, .offline-clinic-hours .showNextClinic, .snks-color, .snks-color *{
				color: <?php echo esc_attr( $darker_color ); ?>!important;
			}
			.offline-clinic-hours h3{
				margin: 5px;
			}
			.offline-clinic-hours .showNextClinic{
				margin-bottom: 15px;
			}
			.clinic-rules p{
				display: flex;
				align-items: center;
			}
			.jet-form-builder-message {
				/*position: fixed;
				top: calc(50% - 45px);
				left: 0;
				right: 0;*/
				background-color: #fff;
				margin: auto;
				max-width: 432px;
				position: relative;
			}
			.jet-form-builder-message--error{
				position: fixed!important;
				top: calc(50% - 45px)!important;
				left: 0!important;
				right: 0!important;
				z-index: 9!important;
			}
			.jet-form-builder-message::before{
				content: 'x';
				height: 30px;
				width: 30px;
				border-radius: 50%;
				color: #fff;
				position: absolute;
				top: -20px;
				left: 10px;
				display: flex;
				justify-content: center;
				align-items: center;
			}
			.snks-booking-item-wrapper{
				margin-bottom: 10px;
			}
			#countdown-timer{
				text-align: center;
				font-size: 18px;
				color: red;
				font-weight: bold;
				margin-bottom: 10px;
				position: fixed;
				background-color: #fff;
				padding: 10px;
				border-radius: 8px;
				top: 10px;
				left: 30px;
				z-index: 21474836488;

			}
			.jet-form-builder-message--error::before{
				background-color: #d41c1c;
			}
			.jet-form-builder-message--success{
				display: none;
			}
			.jet-form-builder-message--success::before{
				background-color: green;
			}
			@media screen and (min-width:481px) {
				.snks-booking-page-container{
					background-color:#024059
				}
				body:not(.page-id-2409) .anony-go-back{
					display:none
				}
			}
			@media screen and (max-width:350px) {
				#head2{
					left:0px!important;
				}
				#head3{
					right:0px!important;
				}
			}
			@media screen and (max-width:480px) {
				body{
					padding: 0!important;
				}
				#snks-booking-page, #checkout-wrapper{
					max-width: 480px!important;
					border:none!important;
				}
				.clinc-row{
					flex-direction: column;
					gap: 10px;
				}
				.anony-content-slide .wp-block-columns{
					flex-direction: column;
				}
				.vertical-divider{
					height: 120px;
					top:80px
				}
			}
			.elementor-3363 #email{
				text-align: left;
			}
			#billing-phone{
				direction: ltr;
					text-align: left;
			}
			#snks-booking-page{
				position: relative;
				overflow: hidden;
				max-width: 428px;
				margin: auto;
				background-color: <?php echo esc_html( $light_color ); ?>;
				padding-top: 50px;
				border-right: 2px solid #fff;
					border-left: 2px solid #fff;
			}
			#snks-booking-page .periods_wrapper.snks-separator{
				background-color: <?php echo esc_html( $light_color ); ?>!important;
			}
			.woocommerce-checkout #customer_details .col-1{
				display: none;
			}
			.financials-gray-section{
				background-color:#dddddd;
			}
			
			.financials-white-section{
				background-color:#fff;
				border-radius: 10px;
				padding: 10px;
			}
			[type="button"]:focus, [type="button"]:hover, [type="submit"]:focus, [type="submit"]:hover, button:focus, button:hover {
				background-color: <?php echo esc_html( $darker_color ); ?>!important;
				color:#fff!important
			}
			.justify{
				text-align: justify!important;
			}
			.current-password{
				background-color: #012d3e;
			}
			.current-password .jet-form-builder__label-text{
				color:#fff;
				font-size: 17px;
			}
			.snks-dynamic-bg-darker{
				background-color: <?php echo esc_html( $darker_color ); ?>!important;
				color:#fff!important;
			}
			.snks-dynamic-text{
				color: <?php echo esc_html( $darker_color ); ?>!important;
			}
			.snks-dynamic-bg,
			#snks-booking-page .anony-accordion-header, #snks-booking-page .periods_wrapper.snks-bg,#snks-booking-page .attendance_types_wrapper,
			#snks-booking-page .snks-period-label.snks-light-bg::before,
			#snks-booking-page #teeth-area,
			#snks-booking-page .snks-bg,
			#snks-booking-page .snks-available-hours li.available-time label,
			#consulting-form-submit,
			.elementor-2988 .elementor-element.elementor-element-48a78d3:not(.elementor-motion-effects-element-type-background), .elementor-2988 .elementor-element.elementor-element-48a78d3 > .elementor-motion-effects-container > .elementor-motion-effects-layer, .elementor-3023 .elementor-element.elementor-element-45f1e78:not(.elementor-motion-effects-element-type-background), .elementor-3023 .elementor-element.elementor-element-45f1e78 > .elementor-motion-effects-container > .elementor-motion-effects-layer,
			.elementor-3023 .elementor-element.elementor-element-0f8e6b8:not(.elementor-motion-effects-element-type-background), .elementor-3023 .elementor-element.elementor-element-0f8e6b8 > .elementor-motion-effects-container > .elementor-motion-effects-layer{
				background-color: <?php echo esc_html( $dark_color ); ?>!important;
			}
			#snks-booking-page .attendance_type_wrapper label, #snks-booking-page .period_wrapper label span{
				background-color: <?php echo esc_html( $darker_color ); ?>!important;
			}
			#snks-booking-page .period_wrapper label span{
				color: <?php echo esc_html( $light_color ); ?>!important;
			}
			#snks-booking-page .attendance_type_wrapper.active label{
				color: <?php echo esc_html( $dark_color ); ?>!important;
			}
			#snks-booking-page .snks-period-label.snks-light-bg::before,
			.anony-day-radio label.active-day,
			.profile-details h1, .profile-details h2, #snks-booking-page .snks-light-bg{
				color: <?php echo esc_html( $dark_color ); ?>!important;
			}
			#consulting-form-submit{
				color:#fff!important
			}
			#consulting-forms-container .snks-available-hours li.available-time.active-hour label{
				background-color: #fff!important;
				color: <?php echo esc_html( $dark_color ); ?>!important;
			}
			.snks-tear-shap-wrapper{
				width: 200px;
				height: 200px;
				margin: auto;
				margin-top: 50px;
				transform: rotate(45deg);
			}
			#username{
				direction: ltr;
			}
			#pricing-details-toggle{
				cursor: pointer;
				transition: transform 0.6s ease-in-out;
			}
			#pricing-details-toggle.rotate {
				transform: rotate(180deg); /* or 360deg if you want full spin */
			}
			div:where(.swal2-container) {
				z-index: 999999!important;
			}
			.popup-trigger img, .popup-trigger canvas{
				max-height: 40px;
			}
			.woocommerce #payment #place_order, .woocommerce-page #payment #place_order {
				float: none;
				width: 100%;
				box-sizing: border-box;
				margin-bottom: 1em;
			}
			.snks-timetable-accordion-content-wrapper{
				display: none;
			}
			.snks-org-doctors-container .snks-tear-shap-wrapper{
				width: 150px;
					height: 150px;
			}
			.snks-org-doctors-container .snks-tear-shap.sub {
				top: 5px;
				box-shadow: none;
			}
			.snks-tear-shap{
				width: 100%;
				height: 100%;
				border-radius: 50%;
				border-top-right-radius: 0;
				transform: rotate(-45deg);
				overflow: hidden;
				background-color: #fff;
				display: flex;
			}
			.snks-tear-shap.sub{
				position: absolute;
				right: 0px;
				top: 13px;
				z-index: -1;
			}
			.snks-tear-shap img{
				height: 203px;
				width: 203px;
			}
			.snks-listing-periods{
				border-bottom: 1px solid #fff;
				height: 162px;
			}
			.snks-doctor-listing{
				overflow: hidden;
				padding-bottom: 50px;
			}
			.snks-doctor-listing .snks-listing-periods > div {
				display: flex;
				flex-direction: column;
			}
			.snks-doctor-listing .snks-listing-periods > div .period_wrapper, .snks-doctor-listing .snks-listing-periods > div .period_wrapper label{
				display: flex;
				flex-direction: row;
				width: 100%!important;
			}
			.snks-profile-image-wrapper{
				position: relative;
				max-width: 350px;
				margin: auto;
				text-align: center;
			}
			#head1{
				top: -40px;
					left: 40px;
				animation: moveUpDown1 2s infinite alternate; /* Animation properties */
			}
			#head2{
				bottom: -20px;
				left: -10px;
				animation: moveUpDown2 2s infinite alternate; /* Animation properties */
			}
			#head3{
				bottom: 50px;
			right: -10px;
			animation: moveUpDown3 2s infinite alternate; /* Animation properties */
			}
			.snks_tabs-container {
				display: flex;
				width: 100%;
				width: 100%
			}
			.next-clinic-details{
				display: none;
			}
			.offline-clinic-hours{
				flex-grow: 1;
			}
			.offline-clinic-hours .elementor-3023, .next-clinic-details{
				width:100%;
				flex-grow: 1;
			}
			.snks_tab, .snks-tab-item {
			flex: 1;
			padding: 10px;
			text-align: center;
			cursor: pointer;
			background-color: #f1f1f1;
			margin: 0 10px;
			border-radius: 5px;
			}

			.snks_tab-content {
			display: none;
			padding: 20px;
			background-color: #fff;
			}

			.snks_active {
			display: block!important;
			}

			.snks_tab.snks_active-tab {
			background-color: #ccc;
			}

			.snks-loading{
				position: relative;
				overflow: hidden;
			}
			.snks-loading::before{
				content: '';
				display: block;
				position: absolute;
				background-color: rgb(255,255,255,0.80);
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
			}
			.snks-loading::after{
				content: '';
				display: block;
				position: absolute;
				background-color: rgb(255,255,255,0.80);
				top: 0;
				left: 0;
				bottom: 0;
				right: 0;
				margin: auto;
				width: 40px;
				height: 40px;
				border: 2px solid #024059;
				border-top: 0;
				border-right: 0;
				border-radius: 50%;
				-webkit-animation:spin 1s linear infinite;
				-moz-animation:spin 1s linear infinite;
				animation:spin 1s linear infinite;
			}
			.shap-head{
				position: absolute;
				height: 77px;
				transition: transform 1.5s ease-in-out;
			}
			.shap-head-bg {
				position: absolute;
				width: 80px;
				height: 80px;
				background-color: #fff;
				border-radius: 50%;
				bottom: -20px;
				left: -13px;
				z-index: -1;
				right: 0;
			}
			#head3 .shap-head-bg {
				left: 0;
				right: -13px;
			}
			
			@-moz-keyframes spin { 
				100% { -moz-transform: rotate(360deg); } 
			}
			@-webkit-keyframes spin { 
				100% { -webkit-transform: rotate(360deg); } 
			}
			@keyframes spin { 
				100% { 
					-webkit-transform: rotate(360deg); 
					transform:rotate(360deg); 
				} 
			}
			@keyframes moveUpDown1 {
				0% {
					transform: translate3d(0px, -20px, 0px);
					animation-timing-function: ease-in;
				}
				100% {
					transform: translate3d(0px, -40px, 0px);
					animation-timing-function: ease-out;
				}
			}
			
			@keyframes moveUpDown2 {
				0% {
					transform: translate3d(0px, 0px, 0px);
					animation-timing-function: ease-in;
				}
				100% {
					transform: translate3d(0px, -20px, 0px);
					animation-timing-function: ease-out;
				}
			}

			@keyframes moveUpDown3 {
				0% {
					transform: translate3d(0px, 50px, 0px);
					animation-timing-function: ease-in;
				}
				100% {
					transform: translate3d(0px, 30px, 0px);
					animation-timing-function: ease-out;
				}
			}
			.profile-details, .org-profile-details{
				margin-top: 27px;
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
			}
			.profile-details h1,.profile-details h2{
				margin: 5px 0;
				color:#024059
			}
			.snks-about-me{
				padding: 20px 20px;
			}
			.snks-about-me li {
				font-size: 20px;
					text-align: justify;
				padding-bottom: 10px;
			}
			.anony-arrow-down {
				width: 0;
				height: 0;
				border-left: 5px solid transparent;
				border-right: 5px solid transparent;
				border-top: 7px solid white; /* Adjust color and size as needed */
			}
			.anony-accordion-container {
				max-width: 450px;
				max-width: 600px;
			}
			#account-manager-phone{
				display: flex;
				justify-content: space-between;
				margin-top: 10px;
			}
			#account-manager-phone > p, #account-manager-phone > label{
				-webkit-box-flex: 0;
				-ms-flex: 0 0 30%;
				flex: 0 0 30%;
				max-width: 30%;
			}
			#account-manager-phone .anony-dial-codes div:first-child input{
				background-color: #b4b4b4;
				margin-right: 5px;
				margin-right: 5px;
				border: 0;
				width: 178px;
			}
			#account-manager-phone button.anony_dial_codes_selected_choice{
				height: 38px;
			}
			.anony-dial-codes-phone-label, .anony-filter-input{
				font-family: "hacen_liner_print-outregular"!important
			}
			.anony-accordion-item {
				background-color: #024059; /* White background for items */
				border: 1px solid #E0E0E0; /* Light border */
				border-radius: 8px;
				margin-bottom: 10px;
				box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Softer shadow */
			}
			/*[type="button"]:focus, [type="button"]:hover, [type="submit"]:focus, [type="submit"]:hover, button:focus, button:hover {
				color: #fff;
				background-color: #182843!important;
				text-decoration: none;
			}*/
			.anony-accordion-header {
				color: #000; /* White text */
				padding: 6px;
				font-size: 18px;
				border: none;
				width: 100%;
				text-align: inherit;
				cursor: pointer;
				outline: none;
				display: flex;
				justify-content: center;
				align-items: center;
				border-radius: 0;
				transition: background-color 0.3s ease;
				margin-top: 30px;
				position: relative;
			}

			.anony-accordion-header:hover {
				background-color: #024059;
				color: #fff
			}

			.anony-accordion-content {
				background-color: #e9e9e9;
				color:#024059;
				overflow: hidden;
				padding: 0 15px;
				max-height: 0;
				transition: all 0.3s ease;
			}
			.certificates-repeater .jet-form-builder-repeater__row-remove {
				position: relative;
				top: auto;
				left: auto;
			}
			.certificates-repeater .jet-form-builder-repeater__row {
				background-color: #fff;
			}
			.certificates-repeater .text-field {
				background-color: #c0c0c0;
				border: 0;
			}
			.certificates-repeater .field-type-text-field{
				margin-right: 28px;
				margin-left: 5px;
				
			}
			.page-id-3316 .elementor-3319 .elementor-element.elementor-element-69cd4439:not(.elementor-motion-effects-element-type-background){
				display:none
			}
			.elementor-3319 .elementor-element.elementor-element-5a717ae5:not(.elementor-motion-effects-element-type-background){
				width: 200px;
				margin-right: auto;
				margin-left: auto;
			}
			.snks-tabs-content{
				margin-top: 20px;
			}
			#send-verification-code{
				width: 129px;
				background-color: #000;
				margin: auto;
				padding: 5px 10px;
				display: block;
			}
			#account-manager-phone > p:first-child{
				display: none;
			}
			#account-manager-phone .anony-dial-codes-phone-label{
				width: 100px;
				color: #333333;
				font-weight: normal;
				font-size: 20px;
			}
			e-preloader[type=pulse] {
				height: 1em;
				width: 1em;
				position: fixed!important;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				margin: auto;
			}
			.certificates-repeater .field-type-text-field .jet-form-builder__field-wrap::before{
				content:'';
				position: absolute;
				display: block;
				width:20px;
				height: 100%;
				background-color: #024059;
				border-radius: 2px;
				right: -28px;
			}
			.certificates-repeater .jet-form-builder-repeater__row {
				margin-top: 0px !important;
			}
			.anony-accordion-content p {
				margin: 15px 0;
				line-height: 1.5;
			}

			.anony-accordion-icon {
				position: absolute;
				left: 10px;
				top:45%;
				transition: transform 0.3s ease;
				display: flex;
			}

			.active .anony-accordion-icon {
				transform: rotate(180deg);
			}
			.anony-accordion-container{
				padding: 0;
			}
			.snks-profile-accordion{
				width: 100%;
			}
			.profile-details .anony-accordion-item {
				border: 0;
				border-radius: 0px;
				margin-bottom: 10px;
				box-shadow: none;
				margin: 0;
			}
		</style>
		<?php if ( snks_is_doctor() ) { ?>
		<style>
			#withdrawal-settings-form{
				background-color: #ffffff;
			}
			#withdrawal-settings-form *{
				font-family: "hacen_liner_print-outregular";
			}
			.withdrawal-radio label {
				color: #024059;
				display: flex;
				align-items: center;
				font-size: 20px;
				font-weight: bolder;
				cursor: pointer;
			}
			span.anony-custom-radio{
				width: 18px;
				height: 18px;
				border: 4px solid #024059;
				border-radius: 50%;
				margin-right: 10px;
				display: inline-block;
				margin-left: 5px;
				background-color: #024059;
			}
			.withdrawal-radio{
				margin-bottom: 15px;
			}
			.withdrawal-radio p{
				margin-top: 8px;
				color: #656565;
				margin-right: 15px;
				font-size: 17px;
			}
			.withdrawal-accounts-fields .field-group label{
				margin: 10px 0;
			}
			#submit-withdrawal-form{
				background-color: #09739d;
			}
			.withdrawal-radio input[type=radio]{
				display: none;
			}
			.gray-bg{
				background-color: #dddddd;
			}
			.white-bg{
				background-color: #fff;
			}
			.withdrawal-radio .checked {
				background-color: #fff;
			}
			.withdrawal-section-title{
				border-radius: 30px;
				margin:auto;
				width:150px;
				font-size:23px;
				font-weight: bold;
				text-align:center;
				color:#024059;
				width: 200px;
				margin-bottom: 20px;
			}
			.withdrawal-section{
				margin-bottom: 20px;
			}
			.withdrawal-button{
				display: block;
				background-color: #024059;
				color: #fff;
				border-radius: 25px;
				border: none;
				width:200px;
				margin: 20px auto;
			}
			.withdrawal-accounts-fields{
				margin-top: 20px;
				padding: 20px;
			}
			.withdrawal-accounts-fields input{
				border:none;
				background-color: #e6e4e4;
			}
			#withdrawal-settings-form{
				max-width:428px;
				margin:auto
			}
		</style>
			<?php
		}
	}
);
