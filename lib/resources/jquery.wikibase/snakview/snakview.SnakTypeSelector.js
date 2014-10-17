/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, $ ) {
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
	 *
	 * @event change Triggered before the snak type changes
	 *        (1) {jQuery.Event}
	 *        (2) {string|null} The new Snak type or null if emptied
	 *
	 * @event afterchange Triggered after the snak type got changed
	 *        (1) {jQuery.Event}
	 */
	$.widget( 'wikibase.SnakTypeSelector', PARENT, {
		widgetName: 'wikibase-snaktypeselector',

		/**
		 * Icon node.
		 * @type {jQuery}
		 */
		$icon: null,

		/**
		 * The menu's Widget object
		 * @type Object
		 */
		_menu: null,

		/**
		 * Will hold the function for removing global listeners if there are any. If not, this
		 * will hold an empty function instead.
		 * @type Function
		 */
		_unbindGlobalListenersFn: $.noop,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this,
				widgetName = this.widgetName,
				$menu = this._buildMenu().appendTo( 'body' ).hide();

			this._menu = $menu.data( 'menu' );

			// TODO: add a title message
			this.element
			.addClass( 'ui-state-default ' + this.widgetBaseClass )
			.on( 'mouseover.' + widgetName, function( event ) {
				self.element.addClass( 'ui-state-hover' );
			} )
			.on( 'mouseout.' + widgetName, function( event ) {
				self.element.removeClass( 'ui-state-hover' );
			} )
			.on( 'click.' + widgetName, function( event ) {
				// don't show menu if selector is disabled!
				// otherwise, simply toggle menu's visibility
				if( self.isDisabled() || $menu.is( ':visible' ) ) {
					$menu.hide();
					return;
				}

				$menu.show();
				self.repositionMenu();

				self.element.addClass( 'ui-state-active' );

				// close the menu when clicking, regardless of whether the click is performed on the
				// menu itself or outside of it:
				var degrade = function( event ) {
					if ( event.target !== self.element[0] ) {
						$menu.hide();
						self.element.removeClass( 'ui-state-active' );
					}
					self._unbindGlobalListenersFn();
				};
				// also make this available for destroy() function!
				self._unbindGlobalListenersFn = function() {
					// unbind event after closing menu, explicitly unbind specific handler to
					// support instantiation of multiple snaktypeselector widgets.
					$( document ).add( $( window ) )
					.off( 'mouseup.' + widgetName + 'resize.' + widgetName, degrade );
					self._unbindGlobalListenersFn = $.noop;
				};
				$( document ).on( 'mouseup.' + widgetName, degrade  );
				$( window ).on( 'resize.' + widgetName, degrade );
			} );

			this.$icon = $( '<span>' )
				.addClass( 'ui-icon ui-icon-snaktypeselector' )
				.appendTo( this.element );

			// listen to clicks; after click on a menu item, select its type as active:
			$menu.on( 'click', function( event ) {
				var $li = $( event.target ).closest( 'li' ),
					type = $li.data( 'snaktypeselector-menuitem-type' );

				if( type ) {
					self._setSnakType( type );
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

			this.$icon.remove();

			this.element.removeClass( 'ui-state-default ui-state-hover ' + this.widgetBaseClass );

			// remove event listeners responsible for closing this instance's menu:
			this._unbindGlobalListenersFn();

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.Widget.disable
		 * @since 0.4
		 */
		disable: function() {
			this._menu.element.hide();
			this.element.removeClass( 'ui-state-active ui-state-hover' );
			this.element.addClass( 'ui-state-disabled' );
			return PARENT.prototype.disable.call( this );
		},

		/**
		 * @see jQuery.Widget.enable
		 * @since 0.4
		 */
		enable: function() {
			this.element.removeClass( 'ui-state-disabled' );
			return PARENT.prototype.enable.call( this );
		},

		/**
		 * Returns whether the widget is currently disabled.
		 * @return 0.4
		 */
		isDisabled: function() {
			return this.option( 'disabled' );
		},

		/**
		 * Returns a DOM structure for the selector's menu where the Snak type can be chosen from.
		 * @since 0.4
		 *
		 * @return jQuery
		 */
		_buildMenu: function() {
			var classPrefix = this.widgetBaseClass + '-menuitem-',
				$menu = $( '<ul>' ).addClass( this.widgetBaseClass + '-menu' ),
				snakTypes = $.wikibase.snakview.variations.getCoveredSnakTypes();

			$.each( snakTypes, function( i, type ) {
				$menu.append(
					$( '<li>' )
					.addClass( classPrefix + type ) // type should only be lower case string anyhow!
					.data( 'snaktypeselector-menuitem-type', type )
					.append( $( '<a>' ).attr( 'href', 'javascript:void(0);' ).text(
						mw.msg( 'wikibase-snakview-snaktypeselector-' + type )
					) )
				);
			} );

			return $menu.menu();
		},

		/**
		 * Returns the Snak type marked as selected. If the first parameter is set, it is has to
		 * be a string and is considered the new Snak type.
		 * @since 0.4
		 *
		 * @param {string|null} [snakType]
		 * @return {string|null|undefined} undefined in case of using this as a setter.
		 */
		snakType: function( snakType ) {
			if( snakType === undefined ) {
				var $snakTypeLi = this._menu.element.children( '.ui-state-active' ).first();
				return $snakTypeLi.length
					? $snakTypeLi.data( 'snaktypeselector-menuitem-type' )
					: null;
			}
			this._setSnakType( snakType );
		},

		/**
		 * Activates the given snak type while enabling all others.
		 * @since 0.4
		 *
		 * @param {string|null} snakType
		 */
		_setSnakType: $.NativeEventHandler( 'change', {
			initially: function( event, snakType ) {
				if( this.snakType() === snakType ) {
					event.cancel(); // same type selected already, no change
				}
			},
			natively: function( event, snakType ) {
				var $menu = this._menu.element;

				// take active status from currently active Snak type list item:
				$menu.children( '.ui-state-active' ).removeClass( 'ui-state-active' );

				if( snakType !== null ) {
					// set list item of new type active:
					$menu.children( '.' + this.widgetBaseClass + '-menuitem-' + snakType )
						.addClass( 'ui-state-active' );
				}

				this._trigger( 'afterchange' );
			}
		} ),

		/**
		 * Positions the menu.
		 * @since 0.4
		 */
		repositionMenu: function() {
			var isRtl = $( 'body' ).hasClass( 'rtl' );

			this._menu.element.position( {
				of: this.$icon,
				my: ( isRtl ? 'right' : 'left' ) + ' top',
				at: ( isRtl ? 'left' : 'right' ) + ' bottom',
				offset: '0 1',
				collision: 'none'
			} );
		}
	} );

	$.wikibase.snakview.SnakTypeSelector = $.wikibase.SnakTypeSelector;

	// We have to override this here because $.widget sets it no matter what's in
	// the prototype
	$.wikibase.snakview.SnakTypeSelector.prototype.widgetBaseClass = 'wb-snaktypeselector';

}( mediaWiki, jQuery ) );
