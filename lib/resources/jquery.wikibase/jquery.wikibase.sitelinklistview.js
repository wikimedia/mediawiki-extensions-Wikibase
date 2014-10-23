/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Displays and allows editing multiple site links.
 * @since 0.5
 * @extends jQuery.TemplatedWidget
 *
 * @option {wikibase.datamodel.SiteLink[]} [value]
 *         Default: []
 *
 * @option {string[]} [allowedSiteIds]
 *         Default: []
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
 *
 * @option {wikibase.store.EntityStore} entityStore
 *
 * @option {jQuery} [$counter]
 *         Node(s) that shall contain information about the number of site links.
 *
 * @option {boolean} [autoInput]
 *         Whether to automatically show and add new input fields to add a new value when in edit
 *         mode.
 *         Default: true
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - {jQuery.Event}
 *
 * @event stopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event afterstopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event afterremove
 *        - {jQuery.Event}
 *
 * @event toggleerror
 *        - {jQuery.Event}
 *        - {Error|null}
 */
$.widget( 'wikibase.sitelinklistview', PARENT, {
	options: {
		template: 'wikibase-sitelinklistview',
		templateParams: [
			'', // table header
			'', // listview
			function() {
				return mw.template( 'wikibase-sitelinklistview-tfoot',
					this.isFull() ? mw.msg( 'wikibase-sitelinksedittool-full' ) : '',
					'' // toolbar
				);
			}
		],
		templateShortCuts: {
			'$thead': 'thead',
			'$listview': 'tbody',
			'$tfoot': 'tfoot'
		},
		value: [],
		allowedSiteIds: [],
		entityId: null,
		api: null,
		entityStore: null,
		$counter: null,
		autoInput: true
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !this.options.entityId || !this.options.api || !this.options.entityStore ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._createListView();

		this.element.addClass( 'wikibase-sitelinklistview' );

		if( this.element.children( 'thead' ).children().length ) {
			// Initially sort on the site id column.
			this.element.tablesorter( { sortList: [{ 1: 'asc' }] } );
		}

		this._refreshCounter();

		if( this.options.autoInput && !this.isFull() ) {
			var self = this;

			this.element
			.on( this.widgetEventPrefix + 'afterstartediting', function() {
				self.enterNewItem();
			} );
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		this.$listview.data( 'listview' ).destroy();
		this.$listview.off( '.' + this.widgetName );
		this.element.removeData( 'tablesorter' );
		this.element.removeClass( 'wikibase-sitelinklistview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates the listview widget managing the sitelinkview widgets
	 */
	_createListView: function() {
		var self = this,
			listItemWidget = $.wikibase.sitelinkview,
			prefix = listItemWidget.prototype.widgetEventPrefix;

		// Encapsulate sitelinkviews by suppressing their events:
		this.$listview
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: listItemWidget,
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						getAllowedSites: function() {
							return $.map( self._getUnusedAllowedSiteIds(), function( siteId ) {
								return wb.sites.getSite( siteId );
							} );
						},
						entityStore: self.options.entityStore
					};
				}
			} ),
			value: self.options.value || null,
			listItemNodeName: 'TR'
		} )
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			event.stopPropagation();
			if( self.options.autoInput ) {
				self._updateAutoInput();
			}
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			event.stopPropagation();
			self.setError( error );
		} )
		.on( 'keydown.' + this.widgetName, function( event ) {
			if( event.keyCode === $.ui.keyCode.BACKSPACE ) {
				var $sitelinkview = $( event.target ).closest( ':wikibase-sitelinkview' ),
					sitelinkview = $sitelinkview.data( 'sitelinkview' );

				if( sitelinkview ) {
					self._removeSitelinkviewIfEmpty( sitelinkview );
				}
			}
		} )
		.on(
			[
				prefix + 'create.' + this.widgetName,
				prefix + 'afterstartediting.' + this.widgetName,
				prefix + 'afterstopediting.' + this.widgetName,
				prefix + 'disable.' + this.widgetName
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		)
		.on( 'sitelinkviewstopediting.' + this.widgetName, function( event, dropValue, callback ) {
			event.stopPropagation();

			var $sitelinkview = $( event.target ),
				sitelinkview = $sitelinkview.data( 'sitelinkview' ),
				value = sitelinkview.value();

			if( dropValue || sitelinkview.isInitialValue() ) {
				callback();
			} else {
				sitelinkview.disable();

				self._saveSiteLink( value )
				.done( function( response ) {
					var siteId = value.getSiteId();

					sitelinkview.value( new wb.datamodel.SiteLink(
						siteId,
						response.entity.sitelinks[siteId].title
					) );
					callback();
				} )
				.fail( function( error ) {
					sitelinkview.setError( error );
				} )
				.always( function() {
					sitelinkview.enable();
				} );
			}
		} )
		.on(
			'listviewitemremoved.' + this.widgetName
			+ ' listviewitemadded.' + this.widgetName,
			function( event ) {
				self._refreshCounter();
				self._refreshTableHeader();
				self._trigger( 'change' );
			}
		);
	},

	/**
	 * @param {jQuery.wikibase.sitelinkview} sitelinkview
	 */
	_removeSitelinkviewIfEmpty: function( sitelinkview ) {
		var $sitelinkview = sitelinkview.element,
			listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items(),
			isLast = $sitelinkview.get( 0 ) === $items.last().get( 0 ),
			isEmpty = sitelinkview.isEmpty()
				|| sitelinkview.option( 'value' ) && !sitelinkview.value();

		if( isEmpty ) {
			event.preventDefault();
			event.stopPropagation();

			// Shift focus to previous line or to following line if there is no previous:
			$items.each( function( i ) {
				if( this === $sitelinkview.get( 0 ) ) {
					if( i > 0 ) {
						lia.liInstance( $items.eq( i - 1 ) ).focus();
					} else if( $items.length > 1 ) {
						lia.liInstance( $items.eq( i + 1 ) ).focus();
					}
					return false;
				}
			} );

			if( !isLast ) {
				listview.removeItem( $sitelinkview );
			}
		}
	},

	/**
	 * @param {jQuery.wikibase.sitelinkview} sitelinkview
	 */
	_removeSitelinkviewIfEmpty: function( sitelinkview ) {
		var $sitelinkview = sitelinkview.element,
			listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items(),
			isLast = $sitelinkview.get( 0 ) === $items.last().get( 0 ),
			isEmpty = sitelinkview.isEmpty()
				|| sitelinkview.option( 'value' ) && !sitelinkview.value();

		if( isEmpty ) {
			event.preventDefault();
			event.stopPropagation();

			// Shift focus to previous line or to following line if there is no previous:
			$items.each( function( i ) {
				if( this === $sitelinkview.get( 0 ) ) {
					if( i > 0 ) {
						lia.liInstance( $items.eq( i - 1 ) ).focus();
					} else if( $items.length > 1 ) {
						lia.liInstance( $items.eq( i + 1 ) ).focus();
					}
					return false;
				}
			} );

			if( !isLast ) {
				listview.removeItem( $sitelinkview );
			}
		}
	},

	_updateAutoInput: function() {
		/**
		 * @param {jQuery} $sitelinkview
		 * @return {boolean}
		 */
		function moreThanOneEmpty( $sitelinkview ) {
			var emptyItems = 0;

			$sitelinkview.each( function() {
				if( $( this ).data( 'sitelinkview' ).isEmpty() ) {
					return ++emptyItems === 2;
				}
			} );

			return emptyItems === 2;
		}

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items(),
			$lastSitelinkview = $items.last(),
			lastSitelinkview = lia.liInstance( $lastSitelinkview ),
			multipleEmpty = moreThanOneEmpty( $items ).length > 1,
			secondToLast = $items.length > 1 && lia.liInstance( $items.eq( -2 ) ),
			secondToLastInvalidPending
				= secondToLast && !secondToLast.isValid() && !secondToLast.option( 'value' );

		if( lastSitelinkview.isEmpty() && ( multipleEmpty || secondToLastInvalidPending ) ) {
			listview.removeItem( $lastSitelinkview );
		} else if( lastSitelinkview.isValid() && !this.isFull() ) {
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
		if( !this.options.$counter ) {
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
		var $items = this.$listview.data( 'listview' ).items(),
			$pendingItems = $items.filter( '.wb-new' );

		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			$items.length - $pendingItems.length,
			$pendingItems.length,
			'wikibase-propertyedittool-counter-entrieslabel',
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
			var sitelinkview = lia.liInstance( $( this ) ),
				isValid = sitelinkview.isValid()
					|| sitelinkview.isEmpty()
					// Previously existing values do always feature a valid site id:
					|| sitelinkview.option( 'value' );
			return isValid === true;
		} );

		return isValid;
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var currentValue = this.value();

		if( currentValue.length !== this.options.value.length ) {
			return false;
		}

		// TODO: Use SiteLinkList.equals() as soon as implemented in DataModelJavaScript
		for( var i = 0; i < currentValue.length; i++ ) {
			if( currentValue[i] === null ) {
				// Ignore empty values.
				continue;
			}
			var found = false;
			for( var j = 0; j < this.options.value.length; j++ ) {
				if( currentValue[i].equals( this.options.value[j] ) ) {
					found = true;
					break;
				}
			}
			if( !found ) {
				return false;
			}
		}

		return true;
	},

	startEditing: function() {
		if( this._isInEditMode ) {
			return;
		}

		// Reset sort order before actually switching to edit mode since it should not be done while
		// in edit mode (see comment in this._refreshTablesorter()).
		this._refreshTablesorter();

		this._isInEditMode = true;
		this.element.addClass( 'wb-edit' );

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var sitelinkview = lia.liInstance( $( this ) );
			sitelinkview.startEditing();
		} );

		this._trigger( 'afterstartediting' );
	},

	/**
	 * @param {boolean} [dropValue]
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		dropValue = !!dropValue;

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();
		this._removeIncompleteSiteLinks();

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		var $queue = $( {} );

		/**
		 * @param {jQuery} $queue
		 * @param {string} siteId
		 */
		function addRemoveToQueue( $queue, siteId ) {
			$queue.queue( 'stopediting', function( next ) {
				var emptySiteLink = new wb.datamodel.SiteLink( siteId, '' );
				self._saveSiteLink( emptySiteLink )
					.done( function() {
						self._afterRemove();

						// Use setTimeout here to break out of the current call stack.
						// This is needed because the stack can get very large (if the queue
						// is very large), eventually leading to failures.
						setTimeout( next, 0 );
					} )
					.fail( function( error ) {
						self.setError( error );
					} );
			} );
		}

		if( !dropValue ) {
			var removedSiteLinkIds = this._getRemovedSiteLinkIds();

			for( var i = 0; i < removedSiteLinkIds.length; i++ ) {
				addRemoveToQueue( $queue, removedSiteLinkIds[i] );
			}
		}

		/**
		 * @param {jQuery} $queue
		 * @param {jQuery.wikibase.sitelinkview} sitelinkview
		 * @param {boolean} dropValue
		 */
		function addStopEditToQueue( $queue, sitelinkview, dropValue ) {
			$queue.queue( 'stopediting', function( next ) {
				sitelinkview.element
				.one( 'sitelinkviewafterstopediting.sitelinklistview', function( event ) {
					// Use setTimeout here to break out of the current call stack.
					// This is needed because the stack can get very large (if the queue
					// is very large), eventually leading to failures.
					setTimeout( next, 0 );
				} );
				sitelinkview.stopEditing( dropValue );
			} );
		}

		listview.items().each( function() {
			var sitelinkview = lia.liInstance( $( this ) );
			addStopEditToQueue( $queue, sitelinkview, dropValue || sitelinkview.isInitialValue() );
		} );

		$queue.queue( 'stopediting', function() {
			self._afterStopEditing( dropValue );
		} );

		$queue.dequeue( 'stopediting' );
	},

	_removeIncompleteSiteLinks: function() {
		var listview = this.$listview.data( 'listview' );

		listview.items().not( listview.nonEmptyItems() ).each( function() {
			listview.removeItem( $( this ) );
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

		for( var i = 0; i < this.options.value.length; i++ ) {
			var siteId = this.options.value[i].getSiteId();
			if( $.inArray( siteId, currentSiteIds ) === -1 ) {
				removedSiteLinkIds.push( siteId );
			}
		}

		return removedSiteLinkIds;
	},

	/**
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if( !dropValue ) {
			this.options.value = this.value();
		}
		this.$listview.data( 'listview' ).value( this.options.value );
		this._refreshCounter();
		this._refreshTableHeader();
		this._isInEditMode = false;
		this.enable();
		this.element.removeClass( 'wb-edit' );
		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	cancelEditing: function() {
		this.stopEditing( true );
	},

	focus: function() {
		// Focus first invalid/incomplete item or - if there is none - the first item.
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items();

		if( !$items.length ) {
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
				if( $node.is( ':visible' ) && $node.offset().top > $window.scrollTop() ) {
					$foundNode = $node;
				}
				return $foundNode === null;
			} );

			return $foundNode || $nodes.first();
		}

		if( !this.isValid() ) {
			$items = $items.filter( function() {
				var sitelinkview = lia.liInstance( $( this ) );
				return !sitelinkview.isValid();
			} );
		}
		$items = findFirstInViewPort( $items );

		if( $items.length ) {
			setTimeout( function() {
				lia.liInstance( $items ).focus();
			}, 10 );
		}
	},

	/**
	 * Applies/Removes error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else if( this.element.hasClass( 'wb-error' ) ) {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * Sets/Gets the widget's value.
	 *
	 * @param {wikibase.datamodel.SiteLink[]} [value]
	 * @return {wikibase.datamodel.SiteLink[]|*}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		value = [];

		if( !this.$listview ) {
			return this.options.value;
		}

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.nonEmptyItems().each( function() {
			value.push( lia.liInstance( $( this ) ).value() );
		} );

		return value;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'value' ) {
			this.$listview.data( 'listview' ).value( value );
			this._refreshCounter();
			this._refreshTableHeader();
		} else if( key === 'disabled' ) {
			this.$listview.data( 'listview' ).option( key, value );
		}

		return response;
	},

	/**
	 * Issues the API action to save a site link.
	 *
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {Object}
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	_saveSiteLink: function( siteLink ) {
		var self = this,
			deferred = $.Deferred();

		this.options.api.setSitelink(
			this.options.entityId,
			wb.getRevisionStore().getSitelinksRevision( siteLink.getSiteId() ),
			siteLink.getSiteId(),
			siteLink.getPageName(),
			siteLink.getBadges()
		)
		.done( function( response, jqXHR ) {
			wb.getRevisionStore().setSitelinksRevision(
				response.entity.lastrevid,
				siteLink.getSiteId()
			);

			// Remove site link:
			self.options.value = $.grep( self.options.value, function( sl ) {
				return sl.getSiteId() !== siteLink.getSiteId();
			} );

			// (Re-)add (altered) site link when editing/adding a site link:
			if( siteLink.getPageName() !== '' ) {
				self.options.value.push( siteLink );
			}

			deferred.resolve( response );
		} )
		.fail( function( errorCode, details ) {
			// TODO: Have API return an Error object instead of constructing it here.
			var error = wb.RepoApiError.newFromApiResponse(
				errorCode,
				details,
				siteLink.getPageName() === '' ? 'remove' : 'save'
			);
			deferred.reject( error );
		} );

		return deferred.promise();
	},

	/**
	 * Removes a sitelinkview instance.
	 *
	 * @param {jQuery.wikibase.sitelinkview} sitelinkview
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {Object}
	 *         Rejected parameters:
	 *         - {wikibase.RepoApiError}
	 */
	remove: function( sitelinkview ) {
		var self = this,
			siteLink = sitelinkview.value(),
			emptySiteLink = new wb.datamodel.SiteLink( siteLink.getSiteId(), '' );

		this.disable();

		return this._saveSiteLink( emptySiteLink )
		.done( function() {
			self.$listview.data( 'listview' ).removeItem( sitelinkview.element );
			self._afterRemove();
		} )
		.fail( function( error ) {
			sitelinkview.setError( error );
		} )
		.always( function() {
			self.enable();
		} );
	},

	_afterRemove: function() {
		if( !this.options.value.length ) {
			// Removed last site link.
			this.$thead.empty();
		}

		this._refreshCounter();
		this._refreshTableHeader();

		if( !this.isFull() ) {
			this.$tfoot.find( 'tr td' ).first().text( '' );
		}
	},

	/**
	 * Triggers entering a new item offering respective input elements.
	 */
	enterNewItem: function() {
		var self = this,
			listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + this.widgetName );

		this.element.one( 'listviewenternewitem', function( event, $sitelinkview ) {
			var sitelinkview = lia.liInstance( $sitelinkview );

			$sitelinkview
			.addClass( 'wb-new' )
			.on( afterStopEditingEvent, function( event, dropValue ) {
				var siteLink = sitelinkview.value();

				listview.removeItem( $sitelinkview );

				if( !dropValue && siteLink ) {
					listview.addItem( siteLink );

					if( self.isFull() ) {
						self.$tfoot.find( 'tr td' ).first()
							.text( mw.msg( 'wikibase-sitelinksedittool-full' ) );
					}
				}

				if( self.__pendingItems && --self.__pendingItems !== 0 ) {
					return;
				}

				self._refreshTableHeader();
				self._refreshCounter();

				self._trigger( 'afterstopediting', null, [dropValue] );
			} );

			self._refreshTableHeader();
			self._refreshCounter();

			if( !self._isInEditMode ) {
				self.startEditing();
			} else {
				sitelinkview.startEditing();
			}
		} );

		listview.enterNewItem();

		this.__pendingItems = this.__pendingItems ? this.__pendingItems + 1 : 1;
	},

	_refreshTablesorter: function() {
		if( this._isInEditMode ) {
			// Re-sorting the table involves reconstructing the table's DOM. This may lead to
			// interference with focusing (IE, though it does not lose focus, is unable to reflect
			// character input into the input box).
			return;
		}

		this.element.removeData( 'tablesorter' );

		if( this.$thead.children().length ) {
			this.element.tablesorter();
			this.element.data( 'tablesorter' ).sort( [] );
		}
	},

	_refreshTableHeader: function() {
		var $items = this.$listview.data( 'listview' ).items();

		if( !$items.length ) {
			this.$thead.empty();
			return;
		} else if( this.$thead.children().length ) {
			this._refreshTablesorter();
			return;
		}

		var siteNameMessageKey = 'wikibase-sitelinks-sitename-columnheading';

		// FIXME: quickfix to allow a custom site-name / handling for the site groups which
		// are special according to the specialSiteLinkGroups setting
		if( this.element.data( 'wikibase-sitelinks-group' ) === 'special' ) {
			siteNameMessageKey += '-special';
		}

		this.$thead.append( mw.template( 'wikibase-sitelinklistview-thead',
			mw.message( siteNameMessageKey ).text(),
			mw.message( 'wikibase-sitelinks-siteid-columnheading' ).text(),
			mw.message( 'wikibase-sitelinks-link-columnheading' ).text()
		) );

		this._refreshTablesorter();
	}

} );

}( mediaWiki, wikibase, jQuery ) );
