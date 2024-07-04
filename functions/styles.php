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
		?>
		<style>
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
				background-color:#12114F;
				color:#fff!important
			}
			.shrinks-error{
				border:1px solid red;
				padding:3px
			}
			.jet-form-builder__conditional.clinics-list .jet-form-builder-repeater__actions button.jet-form-builder-repeater__new{
				color:#fff;
				background-color:#20C319;
				font-size:14px;
				font-weight:bold;
				border-radius:50px
			}
			.change-fees .jet-form-builder-row.field-type-switcher {
				border:none
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
				background-color:red
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

			table:not(.ui-datepicker-calendar) th,
			table td {
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
			#todo-pages-container{
				position: relative;
				margin-top: 20px;
			}
			#todo-pages-container .anony-content-slider-control{
				top: -30px;
			}
			#to-do-form{
				padding-bottom: 100px;
			}
			#to-do-form input[type=submit]{
				position: fixed;
				bottom: 0px;
				left: 0;
				right: 0;
				margin: auto;
			}
			#to-do-form table tbody td{
				border: none;
				outline: none;
				text-align: center;
				vertical-align: middle;
				padding: 5px;
			}
			#to-do-form table tbody tr td:nth-child(1) {
				border-left: 1px solid #e1e1e1;
			}
			.todo-page{
				display: none;
			}
			.todo-page.active-todo-page{
				display: block;
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
				background-color: #78a8b614;
				width: 100%;
				border-radius: 10px;
			}
			.anony-day-radio{
				display: inline-flex;
				padding: 5px;
				border-radius: 5px;
				cursor: pointer;
				box-sizing: border-box;
			}
			.anony-day-radio label{
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
				padding: 5px;
				border-radius: 5px;
				height: 60px;
				justify-content: space-between;
				flex-direction: column;
			}
			.anony-day-radio input{
				display: none;
			}
			.anony-day-radio label.active-day, input[type=submit]{
				background-color: #78A8B6;
				color:#fff;
				border:none
			}
			#snks-available-hours{
				list-style-type: none;
				margin: 0;
				padding: 0;
			}
			#snks-available-hours li{
				display: inline-flex;
				justify-content: center;
				align-items: center;
				padding: 5px 10px;
				border-radius: 5px;
				margin-left: 10px;
			}
			#snks-available-hours li.available-time{
				background-color: #E0EBEF;
				color: #000;
				border-radius: 50px;
				padding: 5px 15px;
				width: 30%;
				box-sizing: border-box;
				display: inline-flex;
				justify-content: center;
				align-items: center;
				margin-bottom: 10px;

			}
			#snks-available-hours li.available-time label{
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
			}
			#snks-available-hours li.available-time input{
				display:none
			}
			#snks-available-hours li.available-time.active-hour{
				background-color: #78A8B6;
				color: #fff;
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
				background-color: #78a8b65c;
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
			.anony-content-slider-container {
				position: relative;
				overflow: hidden;
				margin: auto;
				width: 319px;
			}
			.anony-content-slider div{
				max-width: 100vw;
			}
			.anony-content-slider-control{
				position: absolute;
				top: -5px;
				left: 0;
				text-align: center;
			}
			body.rtl .anony-content-slider-control{
				display: flex;
				justify-content: center;
				align-items: center;
				flex-direction: row-reverse;
			}

			.anony-content-slide {
				display: inline-block;
				vertical-align: top;
				margin: 0;
				width: 45.71px;
			}

			
			.anony-content-slider-nav .top, .anony-content-slider-nav .bottom {
				display: block;
				width: 10px;
				height: 1px;
				height: 1px;
				background-color: #fff;
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
			.anony-content-slider-control .button, #todo-pages-prev, #todo-pages-next{
				height: 25px;
				width: 25px;
				margin: 0 3px;
				background-color: #78a8b6;
				color: #fff;
				outline: none;
				border-radius: 50%;
				border: none;
				cursor: pointer;
				display: flex;
				justify-content: center;
				align-items: center;
			}
			.anony-content-slider-control .button:hover{
				background-color: rgb(0,0,0,1);
			}
			.anony-content-slider-nav{
				position: relative;
				top: -3px;
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
			}

			@media screen and (max-width:480px) {
				.anony-content-slider div{
					max-width: calc(100vw - 40px);
				}
				.anony-content-slide .wp-block-columns{
					flex-direction: column;
				}
			}
		</style>
		<?php
	}
);
