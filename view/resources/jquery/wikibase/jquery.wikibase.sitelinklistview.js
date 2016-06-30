/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * @param {string} eventNames
 * @param {string} namespace
 * @return {string}
 */
function namespaceEventNames( eventNames, namespace ) {
	return eventNames.split( ' ' ).join( '.' + namespace + ' ' ) + '.' + namespace;
}

/**
 * Displays and allows editing multiple site links.
 * @since 0.5
 * @extends jQuery.ui.EditableTemplatedWidget
 *
 * @option {wikibase.datamodel.SiteLink[]} [value]
 *         Default: []
 *
 * @option {string[]} [allowedSiteIds]
 *         Default: []
 *
 * @option {Function} getListItemAdapter
 *
 * @option {jQuery.util.EventSingletonManager} [eventSingletonManager]
 *         Should be set when the widget instance is part of a sitelinkgroupview.
 *         Default: null (will be constructed automatically)
 *
 * @option {jQuery} [$counter]
 *         Node(s) that shall contain information about the number of site links.
 *
 * @option {boolean} [autoInput]
 *         Whether to automatically show and add new input fields to add a new value when in edit
 *         mode.
 *         Default: true
 */
$.widget( 'wikibase.sitelinklistview', PARENT, {
	options: {
		template: 'wikibase-sitelinklistview',
		templateParams: [
			'' // listview
		],
		templateShortCuts: {
			$listview: 'ul'
		},
		value: [],
		allowedSiteIds: [],
		eventSingletonManager: null,
		getListItemAdapter: null,
		$counter: null,
		autoInput: true
	},

	/**
	 * @type {jQuery.util.EventSingletonManager}
	 */
	_eventSingletonManager: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if ( !this.options.getListItemAdapter ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._eventSingletonManager = this.options.eventSingletonManager
			|| new $.util.EventSingletonManager();

		this.draw();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.destroy
	 */
	destroy: function() {
		this.$listview.data( 'listview' ).destroy();
		this.$listview.off( '.' + this.widgetName );
		this.element.removeClass( 'wikibase-sitelinklistview' );

		this._eventSingletonManager.unregister( this, window, '.' + this.widgetName );

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.draw
	 */
	draw: function() {
		if ( !this.$listview.data( 'listview' ) ) {
			this._createListView();
		}

		if ( this.options.$counter && this.options.$counter.is( ':empty' ) ) {
			this._refreshCounter();
		}

		if ( this.options.autoInput && !this.isFull() ) {
			var self = this,
				event = this.widgetEventPrefix + 'afterstartediting.' + this.widgetName,
				updateAutoInput = function() {
					self._updateAutoInput();
				};

			this.element
			.off( event, updateAutoInput )
			.on( event, updateAutoInput );
		}

		return $.Deferred().resolve().promise();
	},

	/**
	 * Creates the listview widget managing the sitelinkview widgets
	 */
	_createListView: function() {
		var self = this,
			listItemAdapter = this.options.getListItemAdapter(
				function() {
					return $.map( self._getUnusedAllowedSiteIds(), function( siteId ) {
						return wb.sites.getSite( siteId );
					} );
				}
			);

		// Encapsulate sitelinkviews by suppressing their events:
		this.$listview
		.listview( {
			listItemAdapter: listItemAdapter,
			value: self.options.value || null,
			listItemNodeName: 'LI',
			encapsulate: true
		} )
		.on( listItemAdapter.prefixedEvent( 'change.' + this.widgetName ), function( event ) {
			event.stopPropagation();
			if ( self.options.autoInput ) {
				self._updateAutoInput();
				self._refreshCounter();
			}
			self._trigger( 'change' );
		} )
		.on( listItemAdapter.prefixedEvent( 'toggleerror.' + this.widgetName ), function( event, error ) {
			event.stopPropagation();
		} )
		.on( 'keydown.' + this.widgetName, function( event ) {
			if ( event.keyCode === $.ui.keyCode.BACKSPACE ) {
				var $sitelinkview = $( event.target ).parentsUntil( this ).andSelf().filter( '.listview-item' ),
					sitelinkview = listItemAdapter.liInstance( $sitelinkview );

				if ( sitelinkview ) {
					self._removeSitelinkviewIfEmpty( sitelinkview, event );
				}
			}
		} )
		.on(
			[
				listItemAdapter.prefixedEvent( 'create.' + this.widgetName ),
				listItemAdapter.prefixedEvent( 'afterstartediting.' + this.widgetName ),
				listItemAdapter.prefixedEvent( 'afterstopediting.' + this.widgetName ),
				listItemAdapter.prefixedEvent( 'disable.' + this.widgetName )
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		)
		.on(
			'listviewitemremoved.' + this.widgetName,
			function( event, sitelinkview ) {
				self._refreshCounter();
				if ( sitelinkview ) {
					// Do not trigger "change" event when handling empty elements.
					self._trigger( 'change' );
				}
			}
		);
	},

	/**
	 * @param {jQuery.wikibase.sitelinkview} sitelinkview
	 * @param {jQuery.Event} event
	 */
	_removeSitelinkviewIfEmpty: function( sitelinkview, event ) {
		var $sitelinkview = sitelinkview.element,
			listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items(),
			isLast = $sitelinkview.get( 0 ) === $items.last().get( 0 ),
			isEmpty = sitelinkview.isEmpty()
				|| sitelinkview.option( 'value' ) && !sitelinkview.value();

		if ( isEmpty ) {
			event.preventDefault();
			event.stopPropagation();

			// Shift focus to previous line or to following line if there is no previous:
			$items.each( function( i ) {
				if ( this === $sitelinkview.get( 0 ) ) {
					if ( i > 0 ) {
						lia.liInstance( $items.eq( i - 1 ) ).focus();
					} else if ( $items.length > 1 ) {
						lia.liInstance( $items.eq( i + 1 ) ).focus();
					}
					return false;
				}
			} );

			if ( !isLast ) {
				listview.removeItem( $sitelinkview );
			}
		}
	},

	_updateAutoInput: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items(),
			$lastSitelinkview = $items.last(),
			lastSitelinkview = lia.liInstance( $lastSitelinkview ),
			secondToLast = $items.length > 1 && lia.liInstance( $items.eq( -2 ) ),
			secondToLastEmpty = secondToLast && secondToLast.isEmpty(),
			secondToLastInvalidPending
				= secondToLast && !secondToLast.isValid() && !secondToLast.option( 'value' );

		if ( lastSitelinkview
			&& lastSitelinkview.isEmpty()
			&& ( secondToLastEmpty || secondToLastInvalidPending )
		) {
			listview.removeItem( $lastSitelinkview );
		} else if ( !lastSitelinkview || lastSitelinkview.isValid() && !this.isFull() ) {
			this.enterNewItem();
		}
	},

	/**
	 * @return {string[]}
	 */
	_getUnusedAllowedSiteIds: function() {
		var representedSiteIds = $.map( this.value(), function( siteLink ) {
			return siteLink.getSiteId();
		} );

		return $.grep( this.option( 'allowedSiteIds' ), function( siteId ) {
			return $.inArray( siteId, representedSiteIds ) === -1;
		} );
	},

	/**
	 * Returns whether all allowed sites are linked or no more site links may be added.
	 *
	 * @return {boolean}
	 */
	isFull: function() {
		return !this._getUnusedAllowedSiteIds().length
			|| this.value().length === this.options.allowedSiteIds.length;
	},

	/**
	 * Refreshes any nodes featuring a counter.
	 */
	_refreshCounter: function() {
		if ( !this.options.$counter ) {
			return;
		}

		this.options.$counter
		.addClass( this.widgetName + '-counter' )
		.empty()
		.append( this._getFormattedCounterText() );
	},

	/**
	 * Returns a formatted string with the number of site links.
	 *
	 * @return {jQuery}
	 */
	_getFormattedCounterText: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items().filter( function() {
				var sitelinkview = lia.liInstance( $( this ) );
				return !sitelinkview.isEmpty();
			} ),
			$pendingItems = $items.filter( '.wb-new' );

		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			$items.length - $pendingItems.length,
			$pendingItems.length,
			'wikibase-sitelinks-counter',
			'wikibase-propertyedittool-counter-pending-tooltip'
		);

		// Counter result should be wrapped in parentheses, which is another message. Since the
		// message system does not return a jQuery object, a work-around is needed:
		var $parenthesesMsg = $(
			( '<div>' + mw.msg( 'parentheses', '__1__' ) + '</div>' ).replace( /__1__/g, '<span/>' )
		);
		$parenthesesMsg.find( 'span' ).replaceWith( $counterMsg );

		return $parenthesesMsg.contents();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isEmpty
	 * @return {boolean}
	 */
	isEmpty: function() {
		return !this.$listview.data( 'listview' ).items().length;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isValid
	 * @return {boolean}
	 */
	isValid: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			isValid = true;

		listview.items().each( function() {
			// Site link views are regarded valid if they have a valid site. Invalid site links
			// (without a page name) and empty values (with no site id and page name input) are
			// supposed to be stripped when querying this widget for its value.
			// Put together, we consider sitelinkviews invalid only when they have something in
			// the siteId input field which does not resolve to a valid siteId and which is not
			// empty.
			var sitelinkview = lia.liInstance( $( this ) );
			isValid = sitelinkview.isValid()
				|| sitelinkview.isEmpty()
				// Previously existing values do always feature a valid site id:
				|| Boolean( sitelinkview.option( 'value' ) );
			return isValid;
		} );

		return isValid;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isInitialValue
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$nonEmptyItems = listview.nonEmptyItems(),
			isInitialValue = true;

		if ( $nonEmptyItems.length !== this.options.value.length ) {
			return false;
		}

		// Ignore empty values.
		$nonEmptyItems.each( function() {
			var sitelinkview = lia.liInstance( $( this ) );
			isInitialValue = sitelinkview.isInitialValue();
			return isInitialValue;
		} );

		return isInitialValue;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.startEditing
	 */
	startEditing: function() {
		var self = this;

		this._eventSingletonManager.register(
			this,
			window,
			namespaceEventNames( 'scroll touchmove resize', this.widgetName ),
			function( event, self ) {
				// It's possible an event is triggered with the widget not being initialized.
				if ( self.$listview ) {
					self._startEditingInViewport();
				}
			},
			{
				throttle: 150
			}
		);

		self._startEditingInViewport();

		return PARENT.prototype.startEditing.call( this );
	},

	_startEditingInViewport: function() {
		/**
		 * @param {HTMLElement} node
		 * @return {boolean}
		 */
		function touchesViewport( node ) {
			var rect = node.getBoundingClientRect(),
				$window = $( window ),
				wHeight = $window.height(),
				wWidth = $window.width(),
				touchesViewportHorizontally = rect.right >= 0 && rect.right < wWidth
					|| rect.left >= 0 && rect.left < wWidth,
				touchesViewportVertically = rect.top >= 0 && rect.top < wHeight
					|| rect.bottom >= 0 && rect.bottom < wHeight;
			return touchesViewportHorizontally && touchesViewportVertically;
		}

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			foundOne = false;

		listview.value().forEach( function( sitelinkview ) {
			if ( touchesViewport( sitelinkview.element[0] ) ) {
				sitelinkview.startEditing();
				foundOne = true;
			}
		} );
		if ( !foundOne && listview.items().length > 0 ) {
			lia.liInstance( $( listview.items()[0] ) ).startEditing();
		}
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.stopEditing
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if ( dropValue ) {
			this.$listview.data( 'listview' ).value( this.options.value );
			this._refreshCounter();
		} else {
			this._removeIncompleteSiteLinks();
		}

		return PARENT.prototype.stopEditing.call( this, dropValue )
			.done( function() {
				self.$listview.data( 'listview' ).value( self.value() );

				self._eventSingletonManager.unregister(
					self,
					window,
					namespaceEventNames( 'scroll touchmove resize', self.widgetName )
				);
			} );
	},

	diffValue: function() {
		var listview = this.$listview.data( 'listview' );
		var siteLinks = [];
		siteLinks = siteLinks.concat( this._getRemovedSiteLinkIds().map( function( siteId ) {
			return new wb.datamodel.SiteLink( siteId, '' );
		} ) );

		listview.items().each( function( i, dom ) {
			var sitelinkview = $( dom ).data( 'sitelinkview' );
			if ( sitelinkview.isInitialValue() ) {
				return;
			}
			var value = sitelinkview.value();
			if ( value ) {
				siteLinks.push( value );
			}
		} );
		return siteLinks;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget._save
	 */
	_save: function() {
		var deferred = $.Deferred();

		return deferred.resolve().promise();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget._afterStopEditing
	 */
	_afterStopEditing: function( dropValue ) {
		var self = this;

		return PARENT.prototype._afterStopEditing.call( this, dropValue )
			.done( function() {
				self.$listview.data( 'listview' ).value( self.options.value );
				self._refreshCounter();
			} );
	},

	_removeIncompleteSiteLinks: function() {
		var listview = this.$listview.data( 'listview' );

		listview.items().not( listview.nonEmptyItems() ).each( function() {
			listview.removeItem( $( this ) );
		} );
	},

	_resetEditMode: function() {
		this.enable();

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var sitelinkview = lia.liInstance( $( this ) );
			sitelinkview.startEditing();
		} );
	},

	/**
	 * @return {string[]}
	 */
	_getRemovedSiteLinkIds: function() {
		var currentSiteIds = $.map( this.value(), function( siteLink ) {
			return siteLink.getSiteId();
		} );

		var removedSiteLinkIds = [];

		for ( var i = 0; i < this.options.value.length; i++ ) {
			var siteId = this.options.value[i].getSiteId();
			if ( $.inArray( siteId, currentSiteIds ) === -1 ) {
				removedSiteLinkIds.push( siteId );
			}
		}

		return removedSiteLinkIds;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		// Focus first invalid/incomplete item or - if there is none - the first item.
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items();

		if ( !$items.length ) {
			this.element.focus();
			return;
		}

		/**
		 * @param {jQuery} $nodes
		 * @return {jQuery}
		 */
		function findFirstInViewPort( $nodes ) {
			var $window = $( window );
			var $foundNode = null;

			$nodes.each( function() {
				var $node = $( this );
				if ( $node.is( ':visible' ) && $node.offset().top > $window.scrollTop() ) {
					$foundNode = $node;
				}
				return $foundNode === null;
			} );

			return $foundNode || $nodes.first();
		}

		if ( !this.isValid() ) {
			$items = $items.filter( function() {
				var sitelinkview = lia.liInstance( $( this ) );
				return !sitelinkview.isValid();
			} );
		}
		$items = findFirstInViewPort( $items );

		if ( $items.length ) {
			setTimeout( function() {
				lia.liInstance( $items ).focus();
			}, 10 );
		}
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.value
	 *
	 * @param {wikibase.datamodel.SiteLink[]} [value]
	 * @return {wikibase.datamodel.SiteLink[]|*}
	 */
	value: function( value ) {
		if ( value !== undefined ) {
			return this.option( 'value', value );
		}

		value = [];

		if ( !this.$listview ) {
			return this.options.value;
		}

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.nonEmptyItems().each( function() {
			var sitelinkview = lia.liInstance( $( this ) );
			value.push( sitelinkview.value() );
		} );

		return value;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if ( key === 'value' ) {
			this.$listview.data( 'listview' ).value( value );
			this._refreshCounter();
		} else if ( key === 'disabled' ) {
			this.$listview.data( 'listview' ).option( key, value );
		}

		return response;
	},

	/**
	 * Adds a pending `sitelinkview` to the `sitelinklistview`.
	 *
	 * @see jQuery.wikibase.listview.enterNewItem
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {jQuery} return.done.$sitelinkview
	 */
	enterNewItem: function() {
		var self = this,
			listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		return listview.enterNewItem().done( function( $sitelinkview ) {
			var sitelinkview = lia.liInstance( $sitelinkview );

			$sitelinkview.addClass( 'wb-new' );

			self._refreshCounter();

			if ( !self.isInEditMode() ) {
				self.startEditing();
			} else {
				sitelinkview.startEditing();
			}

			self._trigger( 'change' );
		} );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
