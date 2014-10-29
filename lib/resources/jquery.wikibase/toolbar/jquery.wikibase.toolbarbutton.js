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
 * @option {string} [cssClassSuffix]
 *         If set, another css class "wikibase-toolbar-button-<cssClassSuffix>" is added to the
 *         button's root node.
 *         Default: null
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
		$label: null,
		cssClassSuffix: null
	},

	/**
	 * @see jQuery.wikibase.toolbaritem._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		var self = this;

		if( this.options.cssClassSuffix ) {
			this.element.addClass( 'wikibase-toolbar-button-' + this.options.cssClassSuffix );
		}

		if( !this.$link.contents().length ) {
			this.$link.append( this._getLabel() );
		}

		this.$link
		.on( 'click.toolbarbutton keydown.toolbarbutton', function( event ) {
			event.preventDefault();

			if( self.options.disabled ) {
				return false;
			}


			if( event.type !== 'keydown' || event.keyCode === $.ui.keyCode.ENTER ) {
				self._trigger( 'action' );
			}
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
	 * @see jQuery.wikibase.toolbaritem._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'cssClassSuffix' ) {
			if( this.options.cssClassSuffix ) {
				this.element
				.removeClass( 'wikibase-toolbar-button-' + this.options.cssClassSuffix );
			}
			this.element.addClass( 'wikibase-toolbar-button-' + value );
		}
		return PARENT.prototype._setOption.apply( this, arguments );
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
