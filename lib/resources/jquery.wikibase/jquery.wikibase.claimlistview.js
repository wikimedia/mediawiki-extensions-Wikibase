/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing a list of claims (wikibase.Claim objects).
 * @since 0.4
 * @extends jQuery.TemplatedWidget
 *
 * @option value {wikibase.Claim[]|null} The list of claims to be displayed this view. If null, the
 *         view will initialize an empty claimview with edit mode started.
 *         Default: null
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
 *
 * @event disable: Triggered whenever the claimlistview gets disabled.
 *        (1) {jQuery.Event}
 *
 * @event enable: Triggered whenever the claimlistview gets enabled.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.claimlistview', PARENT, {
	widgetBaseClass: 'wb-claimlistview',

	/**
	 * (Additional) default options.
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-claimlistview',
		templateParams: [
			'', // listview widget
			'' // toolbar
		],
		templateShortCuts: {
			'$listview': '.wb-claims'
		},
		value: null,
		entityType: null
	},

	/**
	 * The widget to be managed by the claimlistview.
	 * @type {jQuery.wikibase.claimview|jQuery.wikibase.statementview}
	 */
	_listItemWidget: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		this._listItemWidget = ( this.option( 'entityType' ) === 'item' )
			? $.wikibase.statementview
			: $.wikibase.claimview;

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

			// Cancelling edit mode or having stopped edit mode after saving an existing (not
			// pending) claim.
			if( dropValue || !claimview || claimview.value() ) {
				self._trigger( 'afterstopediting' );
			}
		} )
		.on( toggleErrorEvent, function( event, error ) {
			self._trigger( 'toggleerror' );
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
		var listItemWidget = this._listItemWidget,
			value = this.option( 'value' ),
			propertyId;

		if( value ) {
			propertyId = value[0].getMainSnak().getPropertyId();
		}

		this.$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: listItemWidget,
				listItemWidgetValueAccessor: 'value',
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
						}
					};
				}
			} ),
			value: ( this.option( 'value' ) ) ? this.option( 'value' ) : null
		} );
	},

	/**
	 * Returns the listview widget used to manage the claimviews.
	 * @since 0.5
	 *
	 * @return {jQuery.wikibase.listview}
	 */
	listview: function() {
		return this.$listview.data( 'listview' );
	},

	/**
	 * Returns the current list of claims represented by the view. If there are no claims, null is
	 * returned.
	 * @since 0.5
	 *
	 * @return {wikibase.Claim[]|null}
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

		this._removeClaimApiCall( claimview.value() )
		.done( function( savedClaim, pageInfo ) {
			// NOTE: we don't update rev store here! If we want uniqueness for Claims, this
			//  might be an issue at a later point and we would need a solution then

			self.listview().removeItem( claimview.element );

			self._trigger( 'afterremove' );
		} ).fail( function( errorCode, details ) {
			var error = wb.RepoApiError.newFromApiResponse( errorCode, details, 'remove' );

			claimview.enable();
			claimview.setError( error );
		} );
	},

	/**
	 * Triggers the API call to remove a claim.
	 * @since 0.4
	 *
	 * @return {jQuery.Promise}
	 */
	_removeClaimApiCall: function( claim ) {
		var guid = claim.getGuid(),
			abstractedApi = new wb.AbstractedRepoApi(),
			revStore = wb.getRevisionStore();

		return abstractedApi.removeClaim( guid, revStore.getClaimRevision( guid ) );
	},

	/**
	 * Disables the snaklistview.
	 * @since 0.4
	 */
	disable: function() {
		var self = this;
		$.each( this.listview().items(), function( i, item ) {
			var $item = $( item );
			self.listview().listItemAdapter().liInstance( $item ).disable();
		} );
		this._trigger( 'disable' );
	},

	/**
	 * Enables the snaklistview.
	 * @since 0.4
	 */
	enable: function() {
		var self = this;
		$.each( this.listview().items(), function( i, item ) {
			var $item = $( item );
			// Item might be about to be removed not being a list item instance.
			if ( self.listview().listItemAdapter().liInstance( $item ) ) {
				self.listview().listItemAdapter().liInstance( $item ).enable();
			}
		} );
		this._trigger( 'enable' );
	},

	/**
	 * @see jQuery.widget._setOption
	 */
	_setOption: function( key, value ) {
		// The value should not be set from outside after the initialization because
		// currently, the widget lacks a mechanism to update the value.
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}
		$.Widget.prototype._setOption.call( this, key, value );
	}

} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claimlistview',
	selector: ':' + $.wikibase.claimlistview.prototype.namespace
		+ '-' + $.wikibase.claimlistview.prototype.widgetName,
	events: {
		claimlistviewcreate: function( event ) {
			var $claimlistview = $( event.target );

			$claimlistview.addtoolbar( {
				interactionWidgetName: $.wikibase.claimlistview.prototype.widgetName
			} );
		},
		claimlistviewdestroy: function( event ) {
			var $target = $( event.target ),
				addtoolbar = $target.data( 'addtoolbar' );

			if( addtoolbar ) {
				addtoolbar.destroy();
				$target.removeData( 'addtoolbar' );
				$target.children( '.' + $.wikibase.addtoolbar.prototype.widgetBaseClass ).remove();
			}
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'statementview',
	selector: ':' + $.wikibase.claimlistview.prototype.namespace
		+ '-' + $.wikibase.claimlistview.prototype.widgetName,
	events: {
		statementviewcreate: function( event ) {
			var $statementview = $( event.target ),
				statementview = $statementview.data( 'statementview' );

			$statementview.edittoolbar( {
				interactionWidgetName: $.wikibase.statementview.prototype.widgetName,
				parentWidgetFullName: 'wikibase.claimlistview',
				enableRemove: !!statementview.value()
			} );
		},
		statementviewdestroy: function( event ) {
			var $statementview = $( event.target ),
				edittoolbar = $statementview.data( 'edittoolbar' );

			if( edittoolbar ) {
				edittoolbar.destroy();
				$statementview.removeData( 'addtoolbar' );
				$statementview.children( '.' + $.wikibase.edittoolbar.prototype.widgetBaseClass ).remove();
			}
		},
		statementviewchange: function( event ) {
			var $target = $( event.target ),
				statementview = $target.data( 'statementview' ),
				$btnSave = $target.data( 'edittoolbar' ).toolbar.editGroup.$btnSave,
				btnSave = $btnSave.data( 'toolbarbutton' );

			/**
			 * Statementview's isValid() validates the qualifiers already. However, the information
			 * whether all qualifiers (grouped by property) have changed, needs to be gathered
			 * separately which, in addition, is done by this function.
			 *
			 * @param {jquery.wikibase.statementview} statementview
			 * @return {boolean}
			 */
			function shouldEnableSaveButton( statementview ) {
				var enable = statementview.isValid() && !statementview.isInitialValue(),
					snaklistviews = ( statementview._qualifiers )
						? statementview._qualifiers.value()
						: [],
					areInitialQualifiers = true;

				if( enable && snaklistviews.length ) {
					for( var i = 0; i < snaklistviews.length; i++ ) {
						if( !snaklistviews[i].isInitialValue() ) {
							areInitialQualifiers = false;
						}
					}
				}

				return enable && !( areInitialQualifiers && statementview.isInitialValue() );
			}

			btnSave[shouldEnableSaveButton( statementview ) ? 'enable' : 'disable']();
		}
	}
} );

}( mediaWiki, wikibase, jQuery ) );
