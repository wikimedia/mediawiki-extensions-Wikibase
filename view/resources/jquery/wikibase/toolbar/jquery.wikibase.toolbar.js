/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	require( './jquery.wikibase.toolbaritem.js' );

	var PARENT = $.wikibase.toolbaritem;

	/**
	 * Toolbar widget that can be filled with compatible toolbar items.
	 *
	 * @constructor
	 * @extends jQuery.wikibase.toolbaritem
	 *
	 * @option {jQuery} [$content]
	 *         jQuery wrapped DOM elements, each featuring an instance of jQuery.wikibase.toolbaritem.
	 *         Default: $()
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
			$container: null
		},

		/**
		 * @see jQuery.wikibase.toolbaritem._create
		 */
		_create: function () {
			PARENT.prototype._create.call( this );

			if ( this._getItems().length !== this.options.$content.length ) {
				this.draw();
			}

			this.getContainer()
			.addClass( this.widgetBaseClass + '-container wikibase-toolbar-container' );
		},

		/**
		 * @see jQuery.wikibase.toolbaritem.destroy
		 */
		destroy: function () {
			// Remove toolbar items managed by the widget:
			this._getItems().forEach( function ( item ) {
				item.destroy();
				item.element.remove();
			} );

			var $container = this.getContainer();

			$container
			.removeClass( this.widgetBaseClass
				+ '-container wikibase-toolbar-container ui-state-disabled' )
			.off( '.' + this.widgetName );

			if ( $container.get( 0 ) !== this.element.get( 0 ) ) {
				$container.remove();
			}

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @return {jQuery.wikibase.toolbaritem[]}
		 */
		_getItems: function () {
			var items = [];
			this.getContainer().children().each( function () {
				var item = $( this ).data( 'wikibase-toolbar-item' );
				if ( item ) {
					items.push( item );
				}
			} );
			return items;
		},

		/**
		 * Returns the node actually containing the toolbar DOM structure.
		 *
		 * @return {jQuery}
		 */
		getContainer: function () {
			return this.options.$container || this.element;
		},

		draw: function () {
			var $container = this.getContainer(),
				$children = $();

			$container.children().each( function () {
				$( this ).detach();
			} );

			$container.empty();

			this.options.$content.each( function ( i ) {
				var $item = $( this );

				$children = $children.add( $item );

				var item = $item.data( 'wikibase-toolbar-item' );
				if ( item ) {
					item.draw();
				}
			} );

			$container.append( $children );
		},

		/**
		 * @see jQuery.wikibase.toolbaritem._setOption
		 */
		_setOption: function ( key, value ) {
			if ( key === 'disabled' ) {
				this._setState( value );
				this.options[ key ] = value;
				return this;
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === '$content' ) {
				this.draw();
			}

			return response;
		},

		/**
		 * @param {boolean} disable
		 */
		_setState: function ( disable ) {
			this.getContainer()
				.toggleClass( this.widgetFullName + '-disabled ui-state-disabled', !!disable )
				.attr( 'aria-disabled', disable );
			this._getItems().forEach( function ( item ) {
				item[ disable ? 'disable' : 'enable' ]();
			} );
		},

		/**
		 * @see jQuery.wikibase.toolbaritem.focus
		 */
		focus: function () {
			var items = this._getItems();

			for ( var i = 0; i < items.length; i++ ) {
				if ( !items[ i ].option( 'disabled' ) ) {
					items[ i ].focus();
					return;
				}
			}

			this.element.trigger( 'focus' );
		}

	} );

}() );
