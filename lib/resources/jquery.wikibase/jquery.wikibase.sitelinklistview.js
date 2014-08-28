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
 * @event afterstopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event afterremove
 *        - {jQuery.Event}
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
		$counter: null
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

		if( this.element.children( 'thead' ).children().length > 0 ) {
			// Initially sort on the site id column.
			this.element.tablesorter( { sortList: [{ 1: 'asc' }] } );
		}

		this._attachEventHandlers();
		this._refreshCounter();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		this.$listview.data( 'listview' ).destroy();
		this.element.removeData( 'tablesorter' );
		this.element.removeClass( 'wikibase-sitelinklistview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates the listview widget managing the sitelinkview widgets
	 */
	_createListView: function() {
		var self = this;

		this.$listview
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.sitelinkview,
				listItemWidgetValueAccessor: 'value',
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						allowedSiteIds: self._getUnusedAllowedSiteIds(),
						entityStore: self.options.entityStore
					};
				}
			} ),
			value: self.options.value || null,
			listItemNodeName: 'TR'
		} );
	},

	/**
	 * @return {string[]}
	 */
	_getUnusedAllowedSiteIds: function() {
		var representedSiteIds = $.map( this.options.value, function( siteLink ) {
			return siteLink.getSiteId();
		} );

		return $.grep( this.option( 'allowedSiteIds' ), function( siteId ) {
			return $.inArray( siteId, representedSiteIds ) === -1;
		} );
	},

	/**
	 * Returns whether all allowed sites are linked.
	 *
	 * @return {boolean}
	 */
	isFull: function() {
		return !this._getUnusedAllowedSiteIds().length;
	},

	/**
	 * Attaches basic event handlers.
	 */
	_attachEventHandlers: function() {
		var self = this;

		this.element
		.on( 'sitelinkviewstopediting.' + this.widgetName, function( event, dropValue, callback ) {
			var $sitelinkview = $( event.target ),
				sitelinkview = $sitelinkview.data( 'sitelinkview' ),
				value = sitelinkview.value();

			if( dropValue ) {
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
		// TODO: Move that code to a sensible place (see jQuery.wikibase.entityview):
		.on( 'sitelinkviewafterstartediting.' + this.widgetName, function( event ) {
			$( wb ).trigger( 'startItemPageEditMode', [
				self.element,
				{
					exclusive: false,
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'sitelinkviewafterstopediting.' + this.widgetName, function( event, dropValue ) {
			$( wb ).trigger( 'stopItemPageEditMode', [
				self.element,
				{ save: dropValue !== true }
			] );
		} );
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

			if( !self.options.value.length ) {
				// Removed last site link.
				self.$thead.empty();
				self.element.removeData( 'tablesorter' );
			}

			self._refreshCounter();
			self._refreshTableHeader();

			if( !self.isFull() ) {
				self.$tfoot.find( 'tr td' ).first().text( '' );
			}
		} )
		.fail( function( error ) {
			sitelinkview.setError( error );
		} )
		.always( function() {
			self.enable();
		} );
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

					// Init tablesorter if it has not been initialised yet (no site link existed
					// previous to adding the just added site link):
					if( !self.element.data( 'tablesorter' ) ) {
						self.element.tablesorter();
					} else {
						// Reset sorting having the sort order appear undefined when appending a new
						// site link to the bottom of the table:
						self.element.data( 'tablesorter' ).sort( [] );
					}

					if( self.isFull() ) {
						self.$tfoot.find( 'tr td' ).first()
							.text( mw.msg( 'wikibase-sitelinksedittool-full' ) );
					}
				} else {
					self._refreshTableHeader();
				}

				self._refreshCounter();

				self._trigger( 'afterstopediting', null, [dropValue] );
			} );

			sitelinkview.startEditing();

			self._refreshCounter();
		} );

		listview.enterNewItem();
		this._refreshTableHeader();
	},

	/**
	 * Creates/Removes the table header.
	 */
	_refreshTableHeader: function() {
		if( !this.$listview.data( 'listview' ).items().length ) {
			this.$thead.children().remove();
			return;
		} else if( this.$thead.children().length ) {
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
	}

} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'sitelinklistview',
	events: {
		sitelinklistviewcreate: function( event, toolbarcontroller ) {
			var $sitelinklistview = $( event.target ),
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

			$sitelinklistview.addtoolbar( {
				$container: $( '<td/>' ).appendTo(
					sitelinklistview.$tfoot.children( 'tr' ).last()
				),
				addButtonAction: function() {
					$sitelinklistview.one( 'sitelinkviewafterstartediting', function( event ) {
						$( event.target ).data( 'sitelinkview' ).focus();
					} );

					$sitelinklistview.data( 'sitelinklistview' ).enterNewItem();

					// Re-focus "add" button after having added or having cancelled adding a link:
					var eventName = 'sitelinklistviewafterstopediting.addtoolbar';
					$sitelinklistview.one( eventName, function( event ) {
						$sitelinklistview.data( 'addtoolbar' ).toolbar.$btnAdd.focus();
					} );

					toolbarcontroller.registerEventHandler(
						event.data.toolbar.type,
						event.data.toolbar.id,
						'sitelinklistviewdestroy',
						function( event, toolbarcontroller ) {
							toolbarcontroller.destroyToolbar(
								$( event.target ).data( 'addtoolbar' )
							);
						}
					);

					toolbarcontroller.registerEventHandler(
						event.data.toolbar.type,
						event.data.toolbar.id,
						'sitelinklistviewafterremove',
						function( event, toolbarcontroller ) {
							var $sitelinklistview = $( event.target ),
								sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
								toolbar = $sitelinklistview.data( 'addtoolbar' ).toolbar;

							toolbar[sitelinklistview.isFull() ? 'disable' : 'enable']();
						}
					);
				}
			} );

			if( sitelinklistview.isFull() ) {
				$sitelinklistview.data( 'addtoolbar' ).toolbar.disable();
			}
		}
	}
} );

}( mediaWiki, wikibase, jQuery ) );
