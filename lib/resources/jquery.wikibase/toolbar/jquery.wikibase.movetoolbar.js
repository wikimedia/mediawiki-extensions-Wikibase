/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.toolbar;

/**
 * "Move" toolbar widget.
 * @since 0.4
 * @extends jQuery.wikibase.toolbar
 *
 * This widget offers two buttons to move a referenced object within a list up or down. With the
 * corresponding toolbar definition being initialized on a parent item, the movetoolbar itself
 * needs to be initialized on every list item.
 *
 * @event up
 *        Triggered when the "move up" button is hit.
 *        - {jQuery.Event}
 *
 * @event down
 *        Triggered when the "move down" button is hit.
 *        - {jQuery.Event}
 */
$.widget( 'wikibase.movetoolbar', PARENT, {
	/**
	 * @type {Object}
	 */
	_buttons: null,

	/**
	 * @see jQuery.wikibase.toolbar._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		this._buttons = {};

		if( !this.options.$content.length ) {
			this.options.$content
				= this._createDefaultButton( 'up' ).add( this._createDefaultButton( 'down' ) )
					.appendTo( this._getContainer() );
			this.draw();
		}
	},

	/**
	 * @param {string} direction "up"|"down"
	 * @return {jQuery}
	 */
	_createDefaultButton: function( direction ) {
		var self = this;

		this._buttons[direction] = $( '<span/>' ).toolbarbutton( {
			$label: $( '<span/>', {
				title: mw.msg( 'wikibase-move-' + direction ),
				'class': 'ui-icon '
					+ ( direction === 'up' ? 'ui-icon-triangle-1-n' : 'ui-icon-triangle-1-s' )
			} ),
			cssClassSuffix: 'move'
		} )
		.on( 'toolbarbuttonaction.' + this.widgetName, function() {
			self._trigger( direction );
		} );

		return this._buttons[direction];
	},

	/**
	 * Returns a button by its name creating the button if it has not yet been created.
	 *
	 * @param {string} buttonName "up"|"down"
	 * @return {jQuery.wikibase.toolbarbutton}
	 */
	getButton: function( buttonName ) {
		return this._buttons[buttonName].data( 'toolbarbutton' );
	}
} );

}( mediaWiki, jQuery ) );
