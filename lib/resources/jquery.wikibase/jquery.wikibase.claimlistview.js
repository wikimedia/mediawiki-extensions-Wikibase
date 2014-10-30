/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing a list of claims (wb.datamodel.Claim objects).
 * @since 0.4
 * @extends jQuery.TemplatedWidget
 *
 * @option {wb.datamodel.Claim[]|null} value The list of claims to be displayed this view. If null, the
 *         view will initialize an empty claimview with edit mode started.
 *         Default: null
 *
 * @option {wb.store.EntityStore} entityStore
 *
 * @option {wikibase.ValueViewBuilder} valueViewBuilder

 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @option {number|null} [firstClaimIndex] The index of the claimlistview's first claim within a
 *         list of claims.
 *         Default: null
 *         TODO: Remove this option and use a proper mechanism to store the ordered claim structure
 *         at a higher level independent from claimlistview (bug #56050).
 *
 * @event startediting: Triggered when one of the claimlistview's items enters edit mode.
 *        (1) {jQuery.Event}
 *
 * @event afterstopediting: Triggered when one of the claimlistview's items has successfully left
 *        edit mode.
 *        (1) {jQuery.Event}
 *        (2) {boolean} Whether pending value has been dropped or saved.
 *
 * @event afterremove: Triggered when one of the claimlistview's items has just been removed.
 *        (1) {jQuery.Event}
 *
 * @event toggleerror: Triggered when one of the claimlistview's items produces an error.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.claimlistview', PARENT, {
	/**
	 * (Additional) default options.
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-claimlistview',
		templateParams: [
			'', // listview widget
			'', // group name and toolbar
			function() {
				var claims = this.option( 'value' );
				return claims ? claims[0].getMainSnak().getPropertyId() : '';
			}
		],
		templateShortCuts: {
			'$listview': '.wb-claims'
		},
		value: null,
		entityType: null,
		entityStore: null,
		firstClaimIndex: null,
		valueViewBuilder: null,
		entityChangersFactory: null
	},

	/**
	 * The widget to be managed by the claimlistview.
	 * @type {$.wikibase.claimview|$.wikibase.statementview}
	 */
	_listItemWidget: null,

	/**
	 * The index of the claimlistview's first claim within the flat list of claims (if it is
	 * contained within a list of claims). The initial index is stored to be able to detect whether
	 * the index has changed and the claim list does not feature its initial value.
	 * @type {number|null}
	 */
	_initialIndex: null,

	/**
	 * @type {wb.entityChangers.ClaimsChanger}
	 */
	_claimsChanger: null,

	/**
	 * @see jQuery.Widget._create
	 *
	 * @throws {Error} if any required option is not specified.
	 */
	_create: function() {
		if(
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._listItemWidget = ( this.option( 'entityType' ) === 'item' )
			? $.wikibase.statementview
			: $.wikibase.claimview;

		this._initialIndex = this.option( 'firstClaimIndex' );

		this._claimsChanger = this.options.entityChangersFactory.getClaimsChanger();

		this._createListView();

		var self = this,
			lia = this.listview().listItemAdapter(),
			startEditingEvent = lia.prefixedEvent( 'startediting.' + this.widgetName ),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + this.widgetName ),
			toggleErrorEvent = lia.prefixedEvent( 'toggleerror.' + this.widgetName );

		this.element
		.on( startEditingEvent, function( event ) {
			// Forward "startediting" event for higher components (e.g. claimgrouplistview) to
			// recognize that edit mode has been started.
			self._trigger( 'startediting' );
		} )
		.on( afterStopEditingEvent, function( event, dropValue ) {
			var $claimview = $( event.target ),
				claimview = lia.liInstance( $claimview );

			if( dropValue ) {
				// Re-order claims according to their initial indices:
				var listview = self.$listview.data( 'listview' ),
					$claimviews = listview.items();

				for( var i = 0; i < $claimviews.length; i++ ) {
					var claimviewInstance = listview.listItemAdapter().liInstance( $claimviews.eq( i ) );
					listview.move( $claimviews.eq( i ), claimviewInstance.getInitialIndex() );
				}
			}

			// Cancelling edit mode or having stopped edit mode after saving an existing (not
			// pending) claim.
			if( dropValue || !claimview || claimview.value() ) {
				self._trigger( 'afterstopediting', null, [dropValue] );
			}
		} )
		.on( toggleErrorEvent, function( event, error ) {
			self._trigger( 'toggleerror' );
		} );

		this._createGroupName();
	},

	_createGroupName: function() {
		var self = this,
			claims = this.option( 'value' ),
			propertyId;

		if( this.element.find( '.wb-claimgrouplistview-groupname' ).length > 0 ) {
			return;
		}

		if( !claims ) {
			return;
		}

		propertyId = claims[0].getMainSnak().getPropertyId();

		this.option( 'entityStore' ).get( propertyId ).done( function( property ) {
			var $title;

			if( property ) {
				// Default: Create a link to the property to be used as group title.
				$title = wb.utilities.ui.buildLinkToEntityPage(
					property.getContent(),
					property.getTitle()
				);
			} else {
				// The claims group features a property that has been deleted.
				$title = wb.utilities.ui.buildMissingEntityInfo( propertyId, wb.datamodel.Property );
			}

			self.element.append( mw.wbTemplate( 'wb-claimgrouplistview-groupname', $title ) );
		} );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this.listview().destroy();
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates the listview widget managing the claimview widgets.
	 * @since 0.5
	 */
	_createListView: function() {
		var self = this,
			listItemWidget = this._listItemWidget,
			claims = this.option( 'value' ),
			propertyId;

		if( claims ) {
			propertyId = claims[0].getMainSnak().getPropertyId();
		}

		function indexOf( element, array, firstClaimIndex ) {
			var index = $.inArray( element, array );
			return ( index !== -1 ) ? index + firstClaimIndex : null;
		}

		this.$listview
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: listItemWidget,
				newItemOptionsFn: function( value ) {
					return {
						value: value || null,
						predefined: {
							mainSnak: {
								property: propertyId
							}
						},
						locked: {
							mainSnak: {
								property: !!propertyId
							}
						},
						entityStore: self.option( 'entityStore' ),
						valueViewBuilder: self.option( 'valueViewBuilder' ),
						entityChangersFactory: self.option( 'entityChangersFactory' ),
						claimsChanger: self._claimsChanger,
						index: indexOf( value, ( claims || [] ), self.option( 'firstClaimIndex' ) )
					};
				}
			} ),
			value: claims || null
		} )
		.on( 'listviewitemadded listviewitemremoved', function( event, value, $li ) {
			self.__updateClaimIndices();
		} );

		this.__updateClaimIndices();
	},

	/**
	 * Updates the claim indices.
	 *
	 * @param {boolean} save
	 *
	 * @deprecated See bug #56050
	 */
	__updateClaimIndices: function( save ) {
		var listview = this.$listview.data( 'listview' ),
			$claimviews = listview.items();

		for( var i = 0; i < $claimviews.length; i++ ) {
			var claimview = listview.listItemAdapter().liInstance( $claimviews.eq( i ) ),
				index = this.options.firstClaimIndex + i;

			claimview.option( 'index', index );

			if( save ) {
				claimview._initialIndex = index;
			}
		}
	},

	/**
	 * Returns the initial index of the claimlist's first claim within the flat list of claims (if
	 * in any).
	 * @since 0.5
	 *
	 * @return {number|null}
	 */
	getInitialIndex: function() {
		return this._initialIndex;
	},

	/**
	 * Returns the listview widget used to manage the claimviews.
	 * @since 0.5
	 *
	 * @return {$.wikibase.listview}
	 */
	listview: function() {
		return this.$listview.data( 'listview' );
	},

	/**
	 * Returns the current list of claims represented by the view. If there are no claims, null is
	 * returned.
	 * @since 0.5
	 *
	 * @return {wb.datamodel.Claim[]|null}
	 */
	value: function( claims ) {
		// TODO: Implement setter logic.

		var listview = this.$listview.data( 'listview' ),
			$claimviews = listview.items();

		claims = [];

		for( var i = 0; i < $claimviews.length; i++ ) {
			var claimview = listview.listItemAdapter().liInstance( $claimviews.eq( i ) );

			claims.push( claimview.value() );
		}

		return ( claims.length > 0 ) ? claims : null;
	},

	/**
	 * Will insert a new list member into the list. The new list member will be a widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 * @since 0.4
	 */
	enterNewItem: function() {
		var self = this,
			lia = this.listview().listItemAdapter(),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + self.widgetName );

		this.element.one( 'listviewenternewitem', function( event, $claimview ) {
			var claimview = lia.liInstance( $claimview );

			$claimview
			.addClass( 'wb-new' )
			.on( afterStopEditingEvent, function( event, dropValue ) {
				var claim = claimview.value();

				self.listview().removeItem( $claimview );

				if( !dropValue && claim ) {
					self.listview().addItem( claim );
				}

				self._trigger( 'afterstopediting', null, [dropValue] );
			} );

			claimview.startEditing();
		} );

		this.listview().enterNewItem();
	},

	/**
	 * Removes a claimview widget from the claim list.
	 * @since 0.4
	 *
	 * @param {$.wikibase.claimview} claimview
	 */
	remove: function( claimview ) {
		var self = this;

		claimview.disable();

		this._claimsChanger.removeClaim( claimview.value() )
		.done( function() {
			self.listview().removeItem( claimview.element );

			self._trigger( 'afterremove' );
		} ).fail( function( error ) {
			claimview.enable();
			claimview.setError( error );
		} );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		// The value should not be set from outside after the initialization because
		// currently, the widget lacks a mechanism to update the value.
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		} else if( key === 'fistClaimIndex' ) {
			throw new Error( 'firstClaimIndex cannot be set after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.listview().option( key, value );
		}

		return response;
	}

} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claimlistview',
	selector: ':' + $.wikibase.claimlistview.prototype.namespace
		+ '-' + $.wikibase.claimlistview.prototype.widgetName,
	events: {
		claimlistviewcreate: function( event, toolbarcontroller ) {
			var $claimlistview = $( event.target ),
				claimlistview = $claimlistview.data( 'claimlistview' ),
				$container = $claimlistview.children( '.wikibase-toolbar-wrapper' )
					.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				// TODO: Remove layout-specific toolbar wrapper
				$container = $( '<div/>' ).appendTo(
					mw.wbTemplate( 'wikibase-toolbar-wrapper', '' ).appendTo( $claimlistview )
				);
			}

			if( !claimlistview.value() ) {
				return;
			}

			$claimlistview.addtoolbar( {
				$container: $container
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if( e.target !== $claimlistview.get( 0 ) ) {
					return;
				}
				claimlistview.enterNewItem();

				// Re-focus "add" button after having added or having cancelled adding a claim:
				var eventName = 'claimlistviewafterstopediting.addtoolbar';
				$claimlistview.one( eventName, function( event ) {
					$claimlistview.data( 'addtoolbar' ).focus();
				} );

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'claimlistviewdestroy',
					function( event, toolbarcontroller ) {
						toolbarcontroller.destroyToolbar( $( event.target ).data( 'addtoolbar' ) );
					}
				);
			} );

			toolbarcontroller.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'claimlistviewdisable',
				function() {
					var addtoolbar = $claimlistview.data( 'addtoolbar' );
					if( addtoolbar ) {
						addtoolbar[claimlistview.option( 'disabled' ) ? 'disable' : 'enable']();
					}
				}
			);
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'claimview',
	selector: ':' + $.wikibase.claimlistview.prototype.namespace
		+ '-' + $.wikibase.claimlistview.prototype.widgetName,
	events: {
		'claimviewcreate statementviewcreate': function( event, toolbarcontroller ) {
			var viewType = event.type.replace( /create$/, '' ),
				$view = $( event.target ),
				view = $view.data( viewType ),
				options = {
					interactionWidget: view
				},
				$container = $view.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $view );
			}

			options.$container = $container;

			if( !!view.value() ) {
				options.onRemove = function() {
					var $claimlistview = $view.closest( ':wikibase-claimlistview' ),
						claimlistview = $claimlistview.data( 'claimlistview' );
					if( claimlistview ) {
						claimlistview.remove( view );
					}
				};
			}

			$view.edittoolbar( options );
		},
		'claimviewdestroy statementviewdestroy': function( event, toolbarController ) {
			toolbarController.destroyToolbar( $( event.target ).data( 'editoolbar' ) );
		},
		'claimviewchange statementviewchange': function( event ) {
			var $target = $( event.target ),
				viewType = event.type.replace( /change$/, '' ),
				view = $target.data( viewType ),
				edittoolbar = $target.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' );

			/**
			 * Claimview's/Statementview's isValid() validates the qualifiers already.
			 * However, the information whether all qualifiers (grouped by property)
			 * have changed, needs to be gathered separately which, in addition, is done
			 * by this function.
			 *
			 * @param {$.wikibase.claimview} claimview
			 * @return {boolean}
			 */
			function shouldEnableSaveButton( claimview ) {
				var enable = claimview.isValid() && !claimview.isInitialValue(),
					snaklistviews = ( claimview._qualifiers )
						? claimview._qualifiers.value()
						: [],
					areInitialQualifiers = true;

				if( enable && snaklistviews.length ) {
					for( var i = 0; i < snaklistviews.length; i++ ) {
						if( !snaklistviews[i].isInitialValue() ) {
							areInitialQualifiers = false;
						}
					}
				}

				return enable && !( areInitialQualifiers && claimview.isInitialValue() );
			}

			btnSave[shouldEnableSaveButton( view ) ? 'enable' : 'disable']();
		},
		'claimviewdisable statementviewdisable': function( event ) {
			var viewType = event.type.replace( /disable$/, '' ),
				$view = $( event.target ),
				view = $view.data( viewType ),
				edittoolbar = $view.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = view.isValid() && !view.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		}
	}
} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.claimlistview.prototype.widgetBaseClass = 'wb-claimlistview';

}( mediaWiki, wikibase, jQuery ) );
