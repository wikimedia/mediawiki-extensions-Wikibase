( function( $ ) {
	'use strict';

	var origValueview = $.valueview || {};

	// The actual valueview jQuery widget is defined as jQuery.valueview.valueview. Since this is
	// confusing and since we don't even need or want a namespace for the valueview, we just
	// overwrite the namespace "valueview" created by jQuery.widget with the actual widget
	// constructor.
	$.valueview = $.valueview.valueview;

	// Copy everything else defined so far:
	$.extend( $.valueview, origValueview );

	// Simulate namespace nevertheless, in case jQuery requires it:
	$.valueview.valueview = $.valueview;

	// Allow to query for ":valueview" isntead of ":valueview-valueview" (as defined by
	// jQuery.Widget):
	$.expr[ ':' ].valueview = $.expr[ ':' ][ 'valueview-valueview' ];

}( jQuery ) );
