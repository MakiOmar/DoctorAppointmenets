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
			if ( isset( $wp->query_vars['doctor_id'] ) ) {
				?>
				header, footer {
					display: none;
				}
				.snks-booking-page-container {
					background-color: #fff;
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
			?>

			#teeth-area {
				overflow: hidden;
				position: absolute;
				top: -30px;
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
				top: -0.1vw;
				background-size: 200% 15px;
				background-position: 50% 100%;
				background-image: url('data:image/svg+xml;charset=utf8, <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 98 11" preserveAspectRatio="none"><path d="M98 10L97 0l-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10-1-10-1 10L9 0 8 10 7 0 6 10 5 0 4 10 3 0 2 10 1 0 0 10v1h98z" fill="%23024059"/></svg>');
			}
			.consulting-form{
				max-width: 360px;
				margin-top: 30px;
			}

			@media (min-width: 361px) and ( max-width: 480px ) {
				.attendance_type_wrapper label{
					font-size: 27px!important;
				}
				.snks-period-label, .snks-period-price {
					font-size: 17px!important;
					width:126px!important;
				}
				
			}
			@media (max-width: 360px) {
				.consulting-form{
					max-width: 280px;
				}
				.snks-period-label, .snks-period-price{
					width:104px!important
				}
				
			}

			@media (min-width: 2100px) {
				#teeth-area::before {
					background-size: 100% calc(2vw + 15px);
				}
			}
			.snks-appointment-button{
				border-top-left-radius:20px;border-bottom-left-radius:20px;
			}
			.snks-disabled .snks-appointment-button {
				background-color: #7a898f;
			}
			.snks-timetable-accordion-wrapper{
				max-width: 360px;
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
			}
			.rotate-90 *{
				white-space: nowrap!important;
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
				text-align: center;
				border-radius: 20px;
				font-size: 17px;
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
				padding: 5px 10px;
				background-color: #012d3e;
				border-radius: 10px;
				height: 50px;
				box-sizing: border-box;
				color:#fff;
				width: 100%;
				text-align: center;
				display: flex;
				font-size: 26px;
				align-items: center;
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
			.attendance_type_wrapper.active label{
				background-color: #fff;
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
				left: 30px;
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
			
			.field-type-select-field:after, .snks-timetable-accordion:after, .bulk-action-toggle-tip:after {
				content: '\25BC'; /* Unicode character for down arrow */
				position: absolute;
				top: 51%;
				left: 10px;
				transform: translateY(-50%);
				pointer-events: none;
				color: #fff;
				font-size: 12px;
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
				border-color: #024059!important;
				background-color: #024059!important;
			}
			.field-type-radio-field .jet-form-builder__field-label.for-radio > span::before {
				border: 1px solid #024059!important;
				background-color: #024059!important;
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
			.jet-form-builder-repeater__row-fields .clinc-row{
				gap:10px
			}
			.jet-form-builder-repeater__row-fields .clinc-row input{
				border: none;
			}
			.jet-form-builder-repeater__row{
				background-color: #e6e4e5;
				padding: 5px!important;
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
			.accordion-heading.active-accordion{
				background-color:#024059;
				color:#fff!important
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
				background-color: #e4e4e4;
				border: 1px solid #e4e4e4 !important;
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
			select {
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
			.jet-form-builder-repeater__remove{
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
			table:not(.ui-datepicker-calendar) {
				border: 1px solid #ccc;
				border-collapse: collapse;
				margin: 0;
				padding: 0;
				width: 100%;
				table-layout: fixed;
			}

			table:not(.ui-datepicker-calendar) caption {
				font-size: 1.5em;
				margin: .5em 0 .75em;
			}

			table:not(.ui-datepicker-calendar) tr {
				background-color: #3a4091;
				border: 1px solid #3a4091;
				padding: .35em;
				border-radius: 10px;
				color: #fff;
			}
			table:not(.ui-datepicker-calendar) tr.snks-is-off{
				background-color: #000;
				border-color: #000;
			}

			table:not(.ui-datepicker-calendar):not( .shop_table ) th,
			table:not( .shop_table ) td {
				padding: .625em;
				text-align: center;
			}

			table:not(.ui-datepicker-calendar) th {
				font-size: .85em;
				text-transform: uppercase;
			}

			@media screen and (max-width: 600px) {
				table:not(.ui-datepicker-calendar) {
				border: 0;
			}

			table:not(.ui-datepicker-calendar) caption {
				font-size: 1.3em;
			}
			
			table:not(.ui-datepicker-calendar) thead {
				border: none;
				clip: rect(0 0 0 0);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute;
				width: 1px;
			}
			
			table:not(.ui-datepicker-calendar) tr {
				border-bottom: 3px solid #ddd;
				display: block;
				margin-bottom: .625em;
			}
			
			table:not(.ui-datepicker-calendar) td {
				border-bottom: 1px solid #ddd;
				display: block;
				font-size: .8em;
				text-align: <?php echo is_rtl() ? 'left' : 'right'; ?>;
			}
			
			table:not(.ui-datepicker-calendar) td::before {
				/*
				* aria-label has no advantage, it won't be read inside a table
				content: attr(aria-label);
				*/
				content: attr(data-label);
				float: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				font-weight: bold;
				text-transform: uppercase;
			}
			
			table:not(.ui-datepicker-calendar) td:last-child {
				border-bottom: 0;
			}
			}
		</style>
		<style>
			.slick-list {
				max-width: 85vw!important;
			}
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

			}
			.snks-available-hours ul{
				display: flex;
				flex-wrap: wrap;
				max-width: 90vw;
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
			#consulting-form-submit{
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

			.slick-slide {
				margin: 0 5px;
			}

			.slick-prev, .slick-next {
				font-size: 0!important;
				line-height: 0;
				position: absolute;
				top: 50%;
				display: block;
				width: 20px;
				height: 20px;
				padding: 0;
				cursor: pointer;
				color: transparent;
				border: none;
				outline: none;
				background: transparent!important;
				z-index: 12;
			}
			.slick-prev::before, .slick-next::before {
				font-family: 'slick';
				font-size: 23px;
				line-height: 1;
				opacity:1;
				color: #024059;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				cursor: pointer;
			}
			
			.anony-day-number{
				font-size: 27px;
				margin: 8px;
			}

			.anony-greater-than .top {
				transform: rotate(45deg);
				top: 8px;
				position: relative;
			}

			.anony-greater-than .bottom {
				transform: rotate(-45deg);
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
				max-width: 60px!important;
			}
			@media screen and (min-width:481px) {
				.snks-booking-page-container{
					background-color:#024059
				}
			}
			@media screen and (max-width:480px) {
				.clinc-row{
					flex-direction: column;
					gap: 10px;
				}
				.anony-content-slide .wp-block-columns{
					flex-direction: column;
				}
				.vertical-divider{
					width: 2px;
					height: 120px;
					background-color:#024059;
					position: absolute;
					top: 80px;
					left: 40%
				}
			}
			#snks-booking-page{
				overflow: hidden;
				max-width: 428px;
				margin: auto;
				background-color: #d9f4ff;
				padding-top: 50px;
			}
			.snks-tear-shap-wrapper{
				width: 200px;
				height: 200px;
				margin: auto;
				margin-top: 50px;
				transform: rotate(45deg);
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
				left: 0px;
				animation: moveUpDown2 2s infinite alternate; /* Animation properties */
			}
			#head3{
				bottom: 50px;
			right: 0px;
			animation: moveUpDown3 2s infinite alternate; /* Animation properties */
			}
			.shap-head{
				position: absolute;
				height: 77px;
				transition: transform 1.5s ease-in-out;
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
			.profile-details{
				margin-top: 20px;
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
				padding: 20px 30px;
			}
			.snks-about-me ul li {
				margin-bottom: 15px;
				font-size: 20px;
				text-align: justify;
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

			.anony-accordion-item {
				background-color: #024059; /* White background for items */
				border: 1px solid #E0E0E0; /* Light border */
				border-radius: 8px;
				margin-bottom: 10px;
				box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Softer shadow */
			}

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
		<?php
	}
);
