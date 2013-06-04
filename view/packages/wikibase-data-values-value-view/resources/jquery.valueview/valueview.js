/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @since 0.1
 *
 * @requires jquery.valueview.base
 * @requires jquery.valueview.valueview
 */
( function( $ ) {
	'use strict';

	var origValueview = $.valueview;

	// The actual valueview jQuery widget is defined as jQuery.valueview.valueview. Since this is
	// confusing and since we don't even need or want a namespace for the valueview, we just overwrite
	// the namespace "valueview" created by jQuery.widget with the actual widget constructor.
	$.valueview = $.valueview.valueview;
	$.extend( $.valueview, origValueview ); // copy everything else defined so far

	$.valueview.valueview = $.valueview; // simulate namespace nevertheless in case jQuery requires it

	// Allow to query for ":valueview" isntead of ":valueview-valueview" (as defined by jQuery.widget):
	$.expr[ ':' ].valueview = $.expr[ ':' ][ 'valueview-valueview' ];

}( jQuery ) );
