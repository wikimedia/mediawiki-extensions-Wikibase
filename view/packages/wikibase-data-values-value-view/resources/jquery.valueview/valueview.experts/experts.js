/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv ) {
	'use strict';

	/**
	 * Space for jQuery.valueview.Expert implementations introduced by this extension. A valueview
	 * expert is required to handle a certain type of data value in the valueview.
	 *
	 * NOTE: Expert implementations by other extensions might use a different place for those
	 *       implementations constructors.
	 *
	 * @since 0.1
	 *
	 * @type Object
	 */
	vv.experts = {};

}( jQuery, jQuery.valueview ) );
