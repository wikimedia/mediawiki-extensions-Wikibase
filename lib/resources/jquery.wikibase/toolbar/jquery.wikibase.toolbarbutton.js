/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
'use strict';

var PARENT = $.wikibase.toolbaritem;

/**
 * Represents a button to be used in a jQuery.wikibase.toolbar.
 * @extends jQuery.wikibase.toolbaritem
 * @since 0.5
 *
 * @event action
 *        Triggered when the button is hit while not being disabled.
 *        - {jQuery.Event}
 */
$.widget( 'wikibase.toolbarbutton', PARENT, {
	/**
	 * @see jQuery.wikibase.toolbaritem.options
	 */
	options: {
		template: 'wikibase-toolbar-button',
		templateParams: [
			'',
			'#',
			''
		],
		templateShortCuts: {
			$link: 'a'
		},
		$label: null
	},

	/**
	 * @see jQuery.wikibase.toolbaritem._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		var self = this;

		if( !this.$link.children().length ) {
			this.$link.append( this._getLabel() );
		}

		this.$link
		.on( 'click.toolbarbutton', function( event ) {
			event.preventDefault();

			if( self.options.disabled ) {
				return false;
			}

			self._trigger( 'action' );
		} );
	},

	/**
	 * @see jQuery.wikibase.toolbaritem.destroy
	 */
	destroy: function() {
		this.$link.off( '.toolbarbutton' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @return {jQuery}
	 */
	_getLabel: function() {
		return typeof this.options.$label === 'string'
			? $( document.createTextNode( this.options.$label ) )
			: this.options.$label;
	},

	focus: function() {
		this.$link.focus();
	},

	/**
	 * Main drawing routine.
	 */
	draw: function() {}
} );

} )( jQuery );
