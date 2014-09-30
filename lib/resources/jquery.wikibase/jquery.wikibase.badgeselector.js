/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, mw ) {
	'use strict';

var PARENT = $.TemplatedWidget;

/**
 * References one single $menu instance that is reused for all badgeselector instances.
 * @type {jQuery}
 */
var $menu = null;

/**
 * Cache for all badges that may be assigned.
 * @type {Object} Structure: {<{string} item id>: <{wikibase.datamodel.Item}>}
 */
var badges = {};

/**
 * Selector for toggling badges.
 * @since 0.5
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
 * @option {wikibase.store.EntityStore} entityStore
 *
 * @option {string} languageCode
 *
 * @option {boolean} [isRTL]
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
		entityStore: null,
		languageCode: null,
		isRtl: false,
		messages: {
			'badge-placeholder-title': 'Click to assign a badge.'
		}
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		if( !this.options.entityStore || !this.options.languageCode ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._createBadges();
		this._attachEventHandlers();
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		if( $( '.' + this.widgetBaseClass ).length === 0 ) {
			this._detachMenuEventListeners();

			$menu.data( 'menu' ).destroy();
			$menu.remove();
			$menu = null;
		} else if( $menu && ( $menu.data( this.widgetName ) === this ) ) {
			this._detachMenuEventListeners();
		}
		this.element.removeClass( 'ui-state-active' );

		PARENT.prototype.destroy.call( this );
	},

	_attachEventHandlers: function() {
		var self = this;

		this.element
		.on( 'click.' + this.widgetName, function( event ) {
			if( !self.isInEditMode() || self.option( 'disabled' ) ) {
				return;
			}

			// If the menu is already visible, hide it
			if( $menu && $menu.data( self.widgetName ) === self && $menu.is( ':visible' ) ) {
				self._hideMenu();
				return;
			}

			self._initMenu()
			.done( function() {
				if( self.option( 'disabled' ) || $menu.is( ':visible' ) ) {
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

	_hideMenu: function() {
		$menu.hide();
		this._detachMenuEventListeners();

		this.element.removeClass( 'ui-state-active' );
	},

	_attachMenuEventListeners: function() {
		var self = this;
		var degrade = function( event ) {
			if( !$( event.target ).closest( self.element ).length &&
				!$( event.target ).closest( $menu ).length ) {
				self._hideMenu();
			}
		};

		$( document ).on( 'mouseup.' + this.widgetName, degrade );
		$( window ).on( 'resize.' + this.widgetName, degrade );

		$menu.on( 'click.' + this.widgetName, function( event ) {
			var $li = $( event.target ).closest( 'li' ),
				badge = $li.data( self.widgetName + '-menuitem-badge' );

			if( badge ) {
				self._toggleBadge( badge, $li.hasClass( 'ui-state-active' ) );
				$li.toggleClass( 'ui-state-active' );
			}
		} );
	},

	_detachMenuEventListeners: function() {
		$( document ).add( $( window ) ).off( '.' + this.widgetName );
		$menu.off( 'click.' + this.widgetName );
	},

	/**
	 * Creates the individual badges' DOM structures.
	 *
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         No rejected parameters.
	 */
	_createBadges: function() {
		var deferred = $.Deferred();

		if( this.element.children( '.wb-badge' ).length ) {
			return deferred.resolve().promise();
		}

		if( !this.options.value.length && this.isInEditMode() ) {
			this._addEmptyBadge();
			return deferred.resolve().promise();
		}

		var self = this;

		this._fetchItems( this.options.value )
		.done( function() {
			for( var i = 0; i < self.options.value.length; i++ ) {
				self._addBadge( self.options.value[i] );
			}
			deferred.resolve();
		} )
		.fail( function() {
			// TODO: Display error message
			deferred.reject();
		} );

		return deferred.promise();
	},

	/**
	 * Returns the static $menu including its instantiation if it has not been performed already.
	 *
	 * @return {jQuery}
	 */
	_getMenu: function() {
		if( $menu ) {
			return $menu;
		}

		$menu = $( '<ul/>' )
			.text( '...' )
			.addClass( this.widgetBaseClass + '-menu' )
			.appendTo( 'body' );

		return $menu.menu();
	},

	/**
	 * Fills the menu and activates the menu items of the badges already assigned.
	 *
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         No rejected parameters.
	 */
	_initMenu: function() {
		var self = this,
			deferred = $.Deferred(),
			$menu = this._getMenu();

		self.repositionMenu();

		this._fillMenu()
		.done( function() {
			$menu.children( 'li' ).each( function() {
				var $li = $( this ),
					badgeId = $li.data( self.widgetName + '-menuitem-badge' );

				$li.toggleClass( 'ui-state-active', $.inArray( badgeId, self.value() ) !== -1 );
			} );

			$menu.hide();

			deferred.resolve();
		} )
		.fail( function() {
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
	_fillMenu: function() {
		var self = this,
			deferred = $.Deferred();

		this._fetchItems( $.map( this.options.badges, function( cssClasses, itemId ) {
			return itemId;
		} ) )
		.done( function() {
			$menu.empty();

			$.each( self.options.badges, function( itemId, cssClasses ) {
				var item = badges[itemId];
				var $item = $( '<a/>' )
					.on( 'click.' + self.widgetName, function( event ) {
						event.preventDefault();
					} );

				if( item ) {
					$item.text( item.getLabel( self.options.languageCode ) );
				} else {
					$item.append( wb.utilities.ui.buildMissingEntityInfo( itemId, wb.datamodel.Item.TYPE ) );
				}

				$( '<li/>' )
				.addClass( self.widgetBaseClass + '-menuitem-' + itemId )
				.data( self.widgetName + '-menuitem-badge', itemId )
				.append( $item
					.prepend( mw.template( 'wb-badge',
						itemId + ' ' + cssClasses,
						( item && item.getLabel( self.options.languageCode ) ) || itemId,
						itemId
					) )
				)
				.appendTo( $menu );
			} );

			deferred.resolve();
		} )
		.fail( function() {
			// TODO: Display error message.
			deferred.reject();
		} );

		return deferred;
	},

	/**
	 * Fetches item data for a list of item ids.
	 *
	 * @param {string[]} itemIds
	 * @return {jQuery.Promise}
	 *         No resolved parameters.
	 *         No rejected parameters.
	 */
	_fetchItems: function( itemIds ) {
		var self = this,
			deferred = $.Deferred(),
			i = 0;

		/**
		 * @param {string} itemId
		 * @param {wikibase.store.EntityStore} entityStore
		 * @param {jQuery.Deferred} deferred
		 */
		function fetchItem( itemId, entityStore, deferred ) {
			entityStore.get( itemId )
			.done( function( fetchedContent ) {
				if( fetchedContent ) {
					badges[itemId] = fetchedContent.getContent();
				}
				if( --i === 0 ) {
					deferred.resolve();
				}
			} )
			.fail( function() {
				// TODO: Have entityStore return a proper RepoApiError object.
				deferred.reject();
			} );
		}

		$.each( itemIds, function() {
			i++;
			fetchItem( this, self.options.entityStore, deferred );
		} );

		if( $.isEmptyObject( itemIds ) ) {
			deferred.resolve();
		}

		return deferred.promise();
	},

	/**
	 * (De-)Activates a badge.
	 *
	 * @param {string} badgeId
	 * @param {bool} targetState
	 */
	_toggleBadge: function( badgeId, targetState ) {
		if( targetState ) {
			this.element.children( '.wb-badge-' + badgeId ).remove();
			if( !this.element.children( '.wb-badge' ).length ) {
				this._addEmptyBadge();
			}
		} else {
			this._addBadge( badgeId );
			this._getEmptyBadge().remove();
		}

		this._trigger( 'change' );
	},

	/**
	 * @param {string} badgeId
	 */
	_addBadge: function( badgeId ) {
		var badgeItem = badges[badgeId];
		this.element.append(
			mw.template( 'wb-badge',
				badgeId + ' ' + this.options.badges[badgeId],
				badgeItem && badgeItem.getLabel( this.options.languageCode ) || badgeId,
				badgeId
			)
		);
	},

	_addEmptyBadge: function() {
		this.element.append( mw.template( 'wb-badge',
			'empty',
			this.options.messages['badge-placeholder-title'],
			''
		) );
	},

	/**
	 * @return {jQuery}
	 */
	_getEmptyBadge: function() {
		return this.element.children( '[data-wb-badge=""]' );
	},

	startEditing: function() {
		if( this.isInEditMode() ) {
			return;
		}

		if( !this.options.value.length ) {
			this._addEmptyBadge();
		}

		this.element.addClass( 'wb-edit' );

		this._trigger( 'afterstartediting' );
	},

	/**
	 * @param {boolean} dropValue
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if( !this.isInEditMode() ) {
			return;
		}

		this._getEmptyBadge().remove();

		if( $menu ) {
			$menu.hide();
		}

		this.element.removeClass( 'wb-edit' );

		if( !dropValue ) {
			self._trigger( 'afterstopediting', null, [dropValue] );
		} else {
			this.element.empty();

			// Since the widget might have been initialized on pre-existing DOM, badges need to be
			// fetched to ensure their data is available for resetting:
			this._fetchItems( this.options.value )
			.done( function() {
				for( var i = 0; i < self.options.value.length; i++ ) {
					self._addBadge( self.options.value[i] );
				}
				self._trigger( 'afterstopediting', null, [dropValue] );
			} );
		}
	},

	/**
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this.element.hasClass( 'wb-edit' );
	},

	/**
	 * @param {string[]} value
	 * @return {string[]|*}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		value = [];

		this.element.children( '.wb-badge' ).not( this._getEmptyBadge() ).each( function() {
			value.push( $( this ).data( 'wb-badge' ) );
		} );

		return value;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' && $menu && $menu.data( this.widgetName ) === this ) {
			$menu.hide();
		} else if ( key === 'value' && this.isInEditMode() ) {
			this._initMenu();
		}

		return response;
	},

	/**
	 * Aligns the menu to the element the widget is initialized on.
	 */
	repositionMenu: function() {
		$menu.position( {
			of: this.element,
			my: ( this.options.isRtl ? 'right' : 'left' ) + ' top',
			at: ( this.options.isRtl ? 'right' : 'left' ) + ' bottom',
			offset: '0 1',
			collision: 'none'
		} );
	}
} );

}( wikibase, jQuery, mediaWiki ) );
