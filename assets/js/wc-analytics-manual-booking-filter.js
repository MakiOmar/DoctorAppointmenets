/**
 * Registers Orders report filter for admin manual bookings (Jalsah AI).
 * Depends on wp.hooks and wp.i18n (loaded before wc-admin-app).
 */
( function () {
	if ( typeof wp === 'undefined' || ! wp.hooks || ! wp.i18n ) {
		return;
	}
	var addFilter = wp.hooks.addFilter;
	var __ = wp.i18n.__;

	addFilter(
		'woocommerce_admin_orders_report_filters',
		'shrinks/manual-booking',
		function ( filters ) {
			return [
				{
					label: __( 'Manual booking', 'shrinks' ),
					staticParams: [ 'chartType', 'paged', 'per_page' ],
					param: 'snks_manual_booking',
					showFilters: function () {
						return true;
					},
					defaultValue: 'all',
					filters: [
						{ label: __( 'All orders', 'shrinks' ), value: 'all' },
						{ label: __( 'Manual bookings only', 'shrinks' ), value: 'only' },
						{ label: __( 'Exclude manual bookings', 'shrinks' ), value: 'exclude' },
					],
				},
			].concat( filters );
		}
	);
} )();
