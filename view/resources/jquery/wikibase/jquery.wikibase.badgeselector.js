/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	/**
	 * References one single $menu instance that is reused for all badgeselector instances.
	 *
	 * @type {jQuery}
	 */
	var $menu = null;

	/**
	 * Selector for toggling badges.
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {string[]} [value]
	 *         Item ids of badges currently assigned.
	 *         Default: []
	 *
	 * @option {Object} [badges]
	 *         All badges that may be assigned.
	 *         Structure: {<{string} item id>: <{string} custom badge css classes>}
	 *         Default: {}
	 *
	 * @option {wikibase.entityIdFormatter.entityIdPlainFormatter} entityIdPlainFormatter
	 *
	 * @option {boolean} [isRtl]
	 *         Whether the widget is displayed in right-to-left context.
	 *         Default: false
	 *
	 * @option {Object} [messages]
	 *         - badge-placeholder-title
	 *           HTML title attribute of the placeholder displayed when no badge is selected.
	 *
	 * @event change
	 *        - {jQuery.Event}
	 *
	 * @event afterstartediting
	 *       - {jQuery.Event}
	 *
	 * @event afterstopediting
	 *        - {jQuery.Event}
	 *        - {boolean} Whether to drop the value.
	 */
	$.widget( 'wikibase.badgeselector', PARENT, {
		/**
		 * @see jQuery.ui.TemplatedWidget.options
		 */
		options: {
			template: 'wikibase-badgeselector',
			templateParams: [
				''
			],
			templateShortCuts: {},
			value: [],
			badges: {},
			entityIdPlainFormatter: null,
			isRtl: false,
			messages: {
				'badge-placeholder-title': 'Click to assign a badge.'
			}
		},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function () {
			if ( !this.options.entityIdPlainFormatter ) {
				throw new Error( 'Required option(s) missing' );
			}

			PARENT.prototype._create.call( this );

			this._createBadges();
			this._attachEventHandlers();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function () {
			if ( $( '.' + this.widgetBaseClass ).length === 0 ) {
				this._detachMenuEventListeners();

				$menu.data( 'menu' ).destroy();
				$menu.remove();
				$menu = null;
			} else if ( $menu && ( $menu.data( this.widgetName ) === this ) ) {
				this._detachMenuEventListeners();
			}
			this.element.removeClass( 'ui-state-active' );

			PARENT.prototype.destroy.call( this );
		},

		_attachEventHandlers: function () {
			var self = this;

			this.element
			.on( 'click.' + this.widgetName, function ( event ) {
				if ( !self.isInEditMode() || self.option( 'disabled' ) ) {
					return;
				}

				// If the menu is already visible, hide it
				// eslint-disable-next-line no-jquery/no-sizzle
				if ( $menu && $menu.data( self.widgetName ) === self && $menu.is( ':visible' ) ) {
					self._hideMenu();
					return;
				}

				self._initMenu()
					.done( function () {
						// eslint-disable-next-line no-jquery/no-sizzle
						if ( self.option( 'disabled' ) || $menu.is( ':visible' ) ) {
							$menu.hide();
							return;
						}

						$menu.data( self.widgetName, self );
						$menu.show();
						self.repositionMenu();
						self._attachMenuEventListeners();

						self.element.addClass( 'ui-state-active' );
					} );
			} );
		},

		_hideMenu: function () {
			$menu.hide();
			this._detachMenuEventListeners();

			this.element.removeClass( 'ui-state-active' );
		},

		_attachMenuEventListeners: function () {
			var self = this;
			var degrade = function ( event ) {
				if ( !$( event.target ).closest( self.element ).length &&
					!$( event.target ).closest( $menu ).length ) {
					self._hideMenu();
				}
			};

			$( document ).on( 'mouseup.' + this.widgetName, degrade );
			$( window ).on(
				'resize.' + this.widgetName,
				function ( event ) {
					self.repositionMenu();
				}
			);

			$menu.on( 'click.' + this.widgetName, function ( event ) {
				var $li = $( event.target ).closest( 'li' ),
					badge = $li.data( self.widgetName + '-menuitem-badge' );

				if ( badge ) {
					self._toggleBadge( badge, $li.hasClass( 'ui-state-active' ) );
					$li.toggleClass( 'ui-state-active' );
				}
			} );
		},

		_detachMenuEventListeners: function () {
			$( document ).add( $( window ) ).off( '.' + this.widgetName );
			$menu.off( 'click.' + this.widgetName );
		},

		/**
		 * Creates the individual badges' DOM structures.
		 */
		_createBadges: function () {
			if ( this.element.children( '.wb-badge' ).length ) {
				return;
			}

			this._updateEmptyBadge();
			this._addBadges();
		},

		/**
		 * Returns the static $menu including its instantiation if it has not been performed already.
		 *
		 * @return {jQuery}
		 */
		_getMenu: function () {
			if ( $menu ) {
				return $menu;
			}

			$menu = $( '<ul>' )
				.text( '...' )
				.addClass( this.widgetFullName + '-menu' )
				.appendTo( document.body );

			return $menu.menu();
		},

		/**
		 * Fills the menu and activates the menu items of the badges already assigned.
		 *
		 * @return {jQuery.Promise}
		 *         No resolved parameters.
		 *         No rejected parameters.
		 */
		_initMenu: function () {
			var self = this,
				deferred = $.Deferred(),
				$m = this._getMenu();

			self.repositionMenu();

			this._fillMenu()
			.done( function () {
				$m.children( 'li' ).each( function () {
					var $li = $( this ),
						badgeId = $li.data( self.widgetName + '-menuitem-badge' );

					$li
					.addClass( 'ui-menu-item' )
					.toggleClass( 'ui-state-active', self.value().indexOf( badgeId ) !== -1 );
				} );

				$m.hide();

				deferred.resolve();
			} )
			.fail( function () {
				deferred.reject();
			} );

			return deferred.promise();
		},

		/**
		 * Fills the menu with a menu item for each badge that may be assigned.
		 *
		 * @return {jQuery.Promise}
		 *         No resolved parameters.
		 *         No rejected parameters.
		 */
		_fillMenu: function () {
			var self = this,
				deferred = $.Deferred(),
				// eslint-disable-next-line no-jquery/no-map-util
				badgeIds = $.map( this.options.badges, function ( cssClasses, itemId ) {
					return itemId;
				} );

			$.when.apply( $, badgeIds.map( function ( badgeId ) {
				return self.options.entityIdPlainFormatter.format( badgeId );
			} ) ).done( function ( /* … */ ) {
				var badgeLabels = arguments;
				$menu.empty();

				badgeIds.forEach( function ( itemId, index ) {
					var badgeLabel = badgeLabels[ index ];
					var $item = $( '<a>' )
						.on( 'click.' + self.widgetName, function ( event ) {
							event.preventDefault();
						} )
						.text( badgeLabel );

					$( '<li>' )
					.addClass( self.widgetFullName + '-menuitem-' + itemId )
					.data( self.widgetName + '-menuitem-badge', itemId )
					.append( $item
						.prepend( mw.wbTemplate( 'wb-badge',
							itemId + ' ' + self.options.badges[ itemId ],
							badgeLabel,
							itemId
						) )
					)
					.appendTo( $menu );
				} );

				deferred.resolve();
			} )
			.fail( function () {
				// TODO: Display error message.
				deferred.reject();
			} );

			return deferred.promise();
		},

		/**
		 * (De-)Activates a badge.
		 *
		 * @param {string} badgeId
		 * @param {boolean} targetState
		 */
		_toggleBadge: function ( badgeId, targetState ) {
			if ( targetState ) {
				this.element.children( '.wb-badge-' + badgeId ).remove();
			} else {
				this._addBadge( badgeId );
			}
			this._updateEmptyBadge();
			this._trigger( 'change' );
		},

		_addBadges: function () {
			var self = this;
			this.options.value.forEach( function ( badgeId ) {
				self._addBadge( badgeId );
			} );
		},

		/**
		 * Add the DOM for a badge with the given itemId.
		 *
		 * @param {string} badgeId
		 */
		_addBadge: function ( badgeId ) {
			var self = this,
				$badge;

			function addBadgeDom( badgeLabel ) {
				var $oldBadge = $badge;

				$badge = mw.wbTemplate( 'wb-badge',
					badgeId + ' ' + self.options.badges[ badgeId ],
					badgeLabel,
					badgeId
				);

				if ( $oldBadge ) {
					$oldBadge.replaceWith( $badge );
				} else {
					self.element.append( $badge );
				}
			}

			// First add a placeholder without a nice label
			addBadgeDom( badgeId );

			this.options.entityIdPlainFormatter.format( badgeId ).done( function ( badgeLabel ) {
				// Now add a badge with the right label
				addBadgeDom( badgeLabel );
			} );
		},

		/**
		 * Make sure there is an empty badge exactly when there should be one.
		 *
		 * An empty badge is needed when in edit mode and no other badges are selected.
		 * The empty badge acts as a menu anchor in this case.
		 */
		_updateEmptyBadge: function () {
			var $badges = this.element.children( '.wb-badge' ),
				needEmptyBadge = this.isInEditMode() && $badges.length === 0,
				$emptyBadge = $badges.filter( '[data-wb-badge=""]' );

			if ( needEmptyBadge && $emptyBadge.length === 0 ) {
				this.element.append( mw.wbTemplate( 'wb-badge',
					'empty',
					this.options.messages[ 'badge-placeholder-title' ],
					''
				) );
			} else if ( !needEmptyBadge && $emptyBadge.length !== 0 ) {
				$emptyBadge.remove();
			}
		},

		_startEditing: function () {
			this._updateEmptyBadge();
			return $.Deferred().resolve().promise();
		},

		/**
		 * @param {boolean} dropValue
		 */
		_stopEditing: function ( dropValue ) {
			if ( $menu ) {
				$menu.hide();
			}

			if ( !dropValue ) {
				this._updateEmptyBadge();

			} else {
				this.element.empty();

				// Reinitialize badges based on this.options.value
				this._addBadges();
			}
			return $.Deferred().resolve().promise();
		},

		/**
		 * @param {string[]} value
		 * @return {string[]|*}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			value = [];

			this.element.children( '.wb-badge' ).each( function () {
				var v = $( this ).data( 'wb-badge' );
				if ( v ) {
					value.push( v );
				}
			} );

			return value;
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 */
		_setOption: function ( key, value ) {
			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' && $menu && $menu.data( this.widgetName ) === this ) {
				$menu.hide();
			} else if ( key === 'value' && this.isInEditMode() ) {
				this._initMenu();
			}

			return response;
		},

		/**
		 * Aligns the menu to the element the widget is initialized on.
		 */
		repositionMenu: function () {
			$menu.position( {
				of: this.element,
				my: ( this.options.isRtl ? 'right' : 'left' ) + ' top',
				at: ( this.options.isRtl ? 'right' : 'left' ) + ' bottom',
				offset: '0 1',
				collision: 'none'
			} );
		}
	} );

}() );
