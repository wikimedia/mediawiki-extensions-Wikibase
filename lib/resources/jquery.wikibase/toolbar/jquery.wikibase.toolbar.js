/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw ) {
'use strict';

var PARENT = $.wikibase.toolbaritem;

/**
 * Toolbar widget that can be filled with compatible toolbar items.
 * @constructor
 * @extends jQuery.wikibase.toolbaritem
 * @since 0.4
 *
 * @option {jQuery} [$content]
 *         jQuery wrapped DOM elements, each featuring an instance of jQuery.wikibase.toolbaritem.
 *         Default: $()
 *
 * @option {boolean} [renderItemSeparators]
 *         Defines whether the toolbar should be displayed with separators "|" between each item. In
 *         that case everything will also be wrapped within "[" and "]".
 *         Default: false
 */
$.widget( 'wikibase.toolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.toolbaritem.options
	 */
	options: {
		template: 'wikibase-toolbar',
		templateParams: [
			'',
			''
		],
		templateShortCuts: {},
		$content: $(),
		$container: null,
		renderItemSeparators: false
	},

	/**
	 * @see jQuery.wikibase.toolbaritem._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		if( this._getItems().length !== this.options.$content.length ) {
			this.draw();
		}

		this._getContainer()
		.addClass( this.widgetBaseClass + '-container' )
		.addClass( 'wikibase-toolbar-container' );
	},

	/**
	 * @see jQuery.wikibase.toolbaritem.destroy
	 */
	destroy: function() {
		if( this.options.renderItemSeparators ) {
			// Re-render without separators to have them removed.
			this.option( 'renderItemSeparators', false );
		}

		// Remove toolbar items managed by the widget:
		$.each( this._getItems(), function() {
			this.destroy();
			this.element.remove();
		} );

		var $container = this._getContainer();

		$container
		.removeClass( this.widgetBaseClass + '-container' )
		.removeClass( 'wikibase-toolbar-container' )
		.removeClass( 'ui-state-disabled' )
		.off( '.' + this.widgetName );

		if( $container.get( 0 ) !== this.element.get( 0 ) ) {
			$container.remove();
		}

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @return {jQuery.wikibase.toolbaritem[]}
	 */
	_getItems: function() {
		var items = [];
		this._getContainer().children().each( function() {
			var item = $( this ).data( 'wikibase-toolbar-item' );
			if( item ) {
				items.push( item );
			}
		} );
		return items;
	},

	/**
	 * @return {jQuery}
	 */
	_getContainer: function() {
		return this.options.$container || this.element;
	},

	draw: function() {
		var self = this,
			$container = this._getContainer(),
			$children = $();

		$container.children().each( function() {
			$( this ).detach();
		} );

		$container.empty();

		this.options.$content.each( function( i ) {
			var $item = $( this );

			if( i !== 0 && self.options.renderItemSeparators ) {
				$children = $children.add( $( document.createTextNode( '|' ) ) );
			}

			$children = $children.add( $item );

			var item = $item.data( 'wikibase-toolbar-item' );
			if( item ) {
				item.draw();
			}
		} );

		if( this.options.renderItemSeparators && this.options.$content.length ) {
			$container.append( mw.wbTemplate( 'wikibase-toolbar-bracketed', $children ) );
		} else {
			$container.append( $children );
		}
	},

	/**
	 * @see jQuery.wikibase.toolbaritem._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'disabled' ) {
			this._setState( value ? 'disable' : 'enable' );
			return this;
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === '$content' || key === 'renderItemSeparators' ) {
			this.draw();
		}

		return response;
	},

	/**
	 * @param {string} state
	 */
	_setState: function( state ) {
		this._getContainer()[
			state === 'disable' ? 'addClass' : 'removeClass'
		]( 'ui-state-disabled' );
		$.each( this._getItems(), function() {
			this[state]();
		} );
	}

} );

} )( jQuery, mediaWiki );
