/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.Widget;

	/**
	 * Selector for choosing a Snak type. This will display all Snak types which can be displayed
	 * by jQuery.snakview (this is the case if there is a jQuery.snakview.variations.Variation
	 * object registered for a certain type of Snak).
	 *
	 * NOTE: Because this is tightly bound to the snakview variations, this can't be considered an
	 *  independent wiki an thus should be considered part of the jQuery.wikibase.snakview rather
	 *  than as a stand alone widget.
	 *  There could also be some sort of generic menu widget. Menu items could be defined via some
	 *  array of plain objects with label/key fields and this widget would be obsolete since all
	 *  available variations could be given to such a widget without much overhead in code. This
	 *  would also allow for an independent snak type selector widget which does not only list
	 *  Snaks which are available as variation objects.
	 *
	 * @since 0.4
	 */
	$.wikibase.snakview.SnakTypeSelector = wb.utilities.inherit( PARENT, {
		widgetName: 'wikibase-snaktypeselector',
		widgetBaseClass: 'wb-snaktypeselector',

		/**
		 * @type jQuery
		 */
		_menu: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this,
				$menu = this._buildMenu().appendTo( 'body' ).hide();

			this._menu = $menu.data( 'menu' );

			// TODO: add a title message
			this.element
			.addClass( 'ui-icon ui-icon-gear ' + this.widgetBaseClass )
			.on( 'click.wb-snaktypeselector', function( event ) {
				if ( $menu.is( ':visible' ) ) {
					$menu.hide();
					return;
				}
				$menu.show();
				self.setMenuPosition();

				// when clicking somewhere outside menu
				var degrade = function( event ) {
					if ( event.target !== self.element[0] ) {
						$menu.hide();
					}
					$( document ).add( $( window ) ).off( '.wb-snaktype-selector' );
				};
				$( document ).on( 'mouseup.wb-snaktypeselector', degrade  );
				$( window ).on( 'resize.wb-snaktypeselector', degrade );
			} );

			// listen to clicks, after click on a menu item, select its type as active:
			$menu.on( 'click', function( event ) {
				var $li = $( event.target ).closest( 'li' ),
					type = $li.data( 'snaktypeselector-menuitem-type' );

				if( type ) {
					self.selectSnakType( type );
				}
			} );
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			var $menu = this._menu.element;
			this._menu.destroy();
			$menu.remove();

			this.element.removeClass( 'ui-icon ui-icon-gear ' + this.widgetBaseClass );

			// remove listeners to events responsible for closing menu:
			$( document ).add( $( window ) ).off( '.wb-snaktype-selector' );

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Returns a DOM structure for the selector's menu where the Snak type can be chosen from.
		 * @since 0.4
		 *
		 * @return jQuery
		 */
		_buildMenu: function() {
			var classPrefix = this.widgetBaseClass + '-menuitem-',
				$menu = $( '<ul/>' ).addClass( this.widgetBaseClass + '-menu' ),
				snakTypes = $.wikibase.snakview.variations.getCoveredSnakTypes();

			$.each( snakTypes, function( i, type ) {
				$menu.append(
					$( '<li/>' )
					.addClass( classPrefix + type ) // type should only be lower case string anyhow!
					.data( 'snaktypeselector-menuitem-type', type )
					.append( $( '<a/>' ).attr( 'href', 'javascript:void(0);' ).text(
						mw.msg( 'wikibase-snakview-snaktypeselector-' + type )
					) )
				);
			} );

			return $menu.menu();
		},

		/**
		 * Activates the given snak type while enabling all others.
		 * @since 0.4
		 *
		 * @param {String} snakType
		 */
		selectSnakType: function( snakType ) {
			var $menu = this._menu.element,
				$typeNode = $menu.children( '.' + this.widgetBaseClass + '-menuitem-' + snakType );

			$menu.children( '.ui-state-active' ).removeClass( 'ui-state-active' );
			$typeNode.addClass( 'ui-state-active' );
		},

		/**
		 * Sets the menu's position.
		 * @since 0.4
		 */
		setMenuPosition: function() {
			var isRtl = $( 'body' ).hasClass( 'rtl' );

			this._menu.element.position( {
				of: this.element,
				my: ( isRtl ? 'left' : 'right' ) + ' top',
				at: ( isRtl ? 'right' : 'left' ) + ' bottom',
				offset: '0 1',
				collision: 'none'
			} );
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
