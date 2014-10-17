/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
	'use strict';

var PARENT = $.wikibase.toolbar;

/**
 * Toolbar by default featuring a single button.
 * @since 0.5
 * @extends jQuery.wikibase.toolbar
 *
 * @option {boolean} [renderItemSeparators]
 *         Default: true
 *
 * @option {string} [label]
 *         Default: ''
 *
 * @option {string} [eventName]
 *         Default: 'action'
 *
 * @option {string} [buttonCssClassSuffix]
 *         Default: null
 *
 * @event <custom name> (see options)
 *        Triggered when the default button is hit.
 *        - {jQuery.Event}
 */
$.widget( 'wikibase.singlebuttontoolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.toolbar.options
	 */
	options: {
		renderItemSeparators: true,
		label: '',
		eventName: 'action',
		buttonCssClassSuffix: null
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
			$label: this.options.label,
			cssClassSuffix: this.options.buttonCssClassSuffix
		} )
		.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( self.options.eventName );
		} );
	},

	focus: function() {
		var button = this.options.$content.first().data( 'toolbarbutton' );
		if( button ) {
			button.focus();
		}
	}
} );

}( jQuery ) );
