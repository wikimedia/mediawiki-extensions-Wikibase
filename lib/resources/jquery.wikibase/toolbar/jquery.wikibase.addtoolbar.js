/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
// TODO: Merge with removetoolbar.
( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.toolbar;

/**
 * "Add" toolbar widget.
 * @extends jQuery.wikibase.toolbar
 * @since 0.4
 *
 * This widget, by default, offers an "add" button.
 *
 * @option {boolean} [renderItemSeparators]
 *         Default: true
 *
 * @event add
 *        Triggered when the default "add" button is hit.
 *        - {jQuery.Event}
 */
$.widget( 'wikibase.addtoolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.toolbar.options
	 */
	options: {
		renderItemSeparators: true,
		label: mw.msg( 'wikibase-add' )
	},

	/**
	 * @see jQuery.wikibase.toolbar._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		if( !this.options.$content.length ) {
			this.options.$content = this._createDefaultButton().appendTo( this._getContainer() );
			this.draw();
		}
	},

	/**
	 * @return {jQuery}
	 */
	_createDefaultButton: function() {
		var self = this;

		return $( '<span/>' ).toolbarbutton( {
			$label: this.options.label
		} )
		.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( 'add' );
		} );
	},

	focus: function() {
		var button = this.options.$content.first().data( 'toolbarbutton' );
		if( button ) {
			button.focus();
		}
	}
} );

}( mediaWiki, jQuery ) );
