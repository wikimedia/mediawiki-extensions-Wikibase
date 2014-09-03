/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

var PARENT = $.Widget;

/**
 * Represents a label within wikibase scope.
 *
 * @constructor
 * @since 0.4
 */
$.widget( 'wikibase.toolbarlabel', PARENT, {
	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		this.element
		.addClass( this.widgetBaseClass )
		.data( 'wikibase-toolbaritem', this );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this.element
		.removeData( 'wikibase-toolbaritem' );

		PARENT.prototype.destroy.call( this );
	}
} );

}( wikibase, jQuery ) );
