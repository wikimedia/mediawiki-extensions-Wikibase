/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing a list of statements (wb.datamodel.Statement objects).
 * @since 0.4
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {wikibase.datamodel.ClaimGroup|wikibase.datamodel.StatementGroup} [value]
 *         The list of statements to be displayed by this view. If null, the view will initialize an
 *         empty statementview with edit mode started.
 *         Default: null
 *
 * @option {wb.store.EntityStore} entityStore
 *
 * @option {wikibase.ValueViewBuilder} valueViewBuilder

 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @option {dataTypes.DataTypeStore} dataTypeStore
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
				var statements = this.option( 'value' );
				return statements ? statements.getKey() : '';
			}
		],
		templateShortCuts: {
			'$listview': '.wb-claims'
		},
		value: null,
		entityType: null,
		dataTypeStore: null,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null
	},

	/**
	 * The widget to be managed by the claimlistview.
	 * @type {$.wikibase.statementview}
	 */
	_listItemWidget: null,

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

		this._listItemWidget = $.wikibase.statementview;

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
			var $statementview = $( event.target ),
				statementview = lia.liInstance( $statementview );

			// Cancelling edit mode or having stopped edit mode after saving an existing (not
			// pending) statement.
			if( dropValue || !statementview || statementview.value() ) {
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
			statements = this.option( 'value' ),
			propertyId;

		if( this.element.find( '.wb-claimgrouplistview-groupname' ).length > 0 ) {
			return;
		}

		if( !statements ) {
			return;
		}

		propertyId = statements.getKey();

		this.option( 'entityStore' ).get( propertyId ).done( function( property ) {
			var $title;

			if( property ) {
				// Default: Create a link to the property to be used as group title.
				$title = wb.utilities.ui.buildLinkToEntityPage(
					property.getContent(),
					property.getTitle()
				);
			} else {
				// The statements group features a property that has been deleted.
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
	 * Creates the listview widget managing the statementview widgets.
	 * @since 0.5
	 */
	_createListView: function() {
		var self = this,
			listItemWidget = this._listItemWidget,
			statements = this.option( 'value' ),
			propertyId;

		if( statements ) {
			propertyId = statements.getKey();
			statements = statements.getItemContainer().toArray();
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
						dataTypeStore: self.option( 'dataTypeStore' ),
						entityStore: self.option( 'entityStore' ),
						valueViewBuilder: self.option( 'valueViewBuilder' ),
						entityChangersFactory: self.option( 'entityChangersFactory' ),
						claimsChanger: self._claimsChanger
					};
				}
			} ),
			value: statements || null
		} );
	},

	/**
	 * Returns the listview widget used to manage the statementviews.
	 * @since 0.5
	 *
	 * @return {$.wikibase.listview}
	 */
	listview: function() {
		return this.$listview.data( 'listview' );
	},

	/**
	 * Returns the current list of statements represented by the view. If there are no statements, null is
	 * returned.
	 * @since 0.5
	 *
	 * @return {wb.datamodel.Statement[]|null}
	 */
	value: function( statements ) {
		// TODO: Implement setter logic.

		var listview = this.$listview.data( 'listview' ),
			$statementviews = listview.items();

		statements = [];

		for( var i = 0; i < $statementviews.length; i++ ) {
			var statementview = listview.listItemAdapter().liInstance( $statementviews.eq( i ) );

			statements.push( statementview.value() );
		}

		return ( statements.length > 0 ) ? statements : null;
	},

	/**
	 * Adds a new, pending `statementview` to the `claimlistview`.
	 * @see jQuery.wikibase.listview.enterNewItem
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {jQuery} return.done.$statementview
	 */
	enterNewItem: function() {
		var self = this,
			lia = this.listview().listItemAdapter(),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + self.widgetName );

		return this.listview().enterNewItem().done( function( $statementview ) {
			var statementview = lia.liInstance( $statementview );

			$statementview
			.addClass( 'wb-new' )
			.on( afterStopEditingEvent, function( event, dropValue ) {
				var statement = statementview.value();

				self.listview().removeItem( $statementview );

				if( !dropValue && statement ) {
					self.listview().addItem( statement );
				}

				self._trigger( 'afterstopediting', null, [dropValue] );
			} );

			statementview.startEditing();
		} );
	},

	/**
	 * Removes a statementview widget from the statement list.
	 * @since 0.4
	 *
	 * @param {$.wikibase.statementview} statementview
	 */
	remove: function( statementview ) {
		var self = this;

		statementview.disable();

		this._claimsChanger.removeStatement( statementview.value() )
		.done( function() {
			self.listview().removeItem( statementview.element );

			self._trigger( 'afterremove' );
		} ).fail( function( error ) {
			statementview.enable();
			statementview.setError( error );
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
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.listview().option( key, value );
		}

		return response;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		var listview = this.listview(),
			$items = listview.items();

		if( $items.length ) {
			listview.listItemAdapter().liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	}

} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.claimlistview.prototype.widgetBaseClass = 'wb-claimlistview';

}( mediaWiki, wikibase, jQuery ) );
