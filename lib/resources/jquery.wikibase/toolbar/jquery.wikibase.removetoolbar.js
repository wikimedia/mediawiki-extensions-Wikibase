/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
// TODO: Merge with addtoolbar.
( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.toolbar;

/**
 * "Remove" toolbar widget.
 * @since 0.4
 * @extends jQuery.wikibase.toolbar
 *
 * This widget, by default, offers an "remove" button.
 *
 * @option {boolean} [renderItemSeparators]
 *         Default: true
 *
 * @event remove
 *        Triggered when the default "remove" button is hit.
 *        - {jQuery.Event}
 */
$.widget( 'wikibase.removetoolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.toolbar.options
	 */
	options: {
		renderItemSeparators: true,
		label: mw.msg( 'wikibase-remove' )
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

		return $( '<span>' ).toolbarbutton( {
			$label: this.options.label,
			cssClassSuffix: 'remove'
		} )
		.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( 'remove' );
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
