/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

var PARENT = $.TemplatedWidget;

/**
 * Represents a generic item to be wrapped by a jQuery.wikibase.toolbar.
 * @constructor
 * @since 0.5
 */
$.widget( 'wikibase.toolbaritem', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-toolbar-item',
		templateParams: [
			''
		],
		templateShortCuts: {}
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );
		this.element
		.addClass( 'wikibase-toolbar-item' )
		.data( 'wikibase-toolbar-item', this );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this.element
		.removeClass( 'wikibase-toolbar-item' )
		.removeData( 'wikibase-toolbar-item' );
		PARENT.prototype.destroy.call( this );
	}
} );

}( wikibase, jQuery ) );
