( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing a list of `wikibase.datamodel.Statement` objects by using
 * `jQuery.wikibase.statementview` widgets.
 * @see jQuery.wikibase.statementview
 * @see wikibase.datamodel.Statement
 * @see wikibase.datamodel.StatementList
 * @class jQuery.wikibase.claimlistview
 * @extends jQuery.ui.TemplatedWidget
 * @uses jQuery.wikibase.listview
 * @uses jQuery.wikibase.listview.ListItemAdapter
 * @uses jQuery.wikibase.statementview
 * @uses mediaWiki
 * @uses wikibase.utilities.ui
 * @since 0.4
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.ClaimGroup|wikibase.datamodel.StatementGroup} [options.value=null]
 *        The list of `Statement`s to be displayed by this view. If null, the view will initialize
 *        with edit mode being started.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required for dynamically gathering `Entity`/`Property` information.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required by the `snakview` interfacing a `snakview` "value" `Variation` to
 *        `jQuery.valueview`.
 * @param {wikibase.entityChangers.EntityChangersFactory} options.entityChangersFactory
 *        Required to store the `Reference`s gathered from the `referenceview`s aggregated by the
 *        `statementview`.
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
 *        object when interacting on a "value" `Variation`.
/**
 * @event startediting
 * Triggered when edit mode is started for one of the `statementview` widgets managed by the
 * `claimlistview`.
 * @param {jQuery.Event} event
 */
/**
 * @event afterstopediting
 * Triggered when one of the `statementview` widgets managed by the `claimlistview` has successfully
 * stopped edit mode.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue If true, the value from before edit mode has been started will be
 *        reinstated (basically, a cancel/save switch).
 */
/**
 * @event afterremove
 * Triggered after one of the `statementview` widgets managed by the `claimlistview` was removed
 * from the `claimlistview`.
 * @param {jQuery.Event} event
 */
/**
 * @event toggleerror
 * Triggered when one of the `statementview` widgets managed by the `claimlistview` produces an
 * error.
 * @param {jQuery.Event} event
 */
$.widget( 'wikibase.claimlistview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
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
	 * @type {wikibase.entityChangers.ClaimsChanger}
	 * @private
	 */
	_claimsChanger: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if(
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

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

	/**
	 * @private
	 */
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
	 * @inheritdoc
	 * @protected
	 */
	destroy: function() {
		this.listview().destroy();
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates the `listview` widget managing the `statementview` widgets.
	 * @since 0.5
	 * @private
	 */
	_createListView: function() {
		var self = this,
			statements = this.option( 'value' ),
			propertyId;

		if( statements ) {
			propertyId = statements.getKey();
			statements = statements.getItemContainer().toArray();
		}

		this.$listview
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.statementview,
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
	 * Returns a reference to the `listview` widget used to manage the `statementview`s.
	 * @since 0.5
	 *
	 * @return {jQuery.wikibase.listview}
	 */
	listview: function() {
		return this.$listview.data( 'listview' );
	},

	/**
	 * Returns the current list of `Statement` objects represented by the `claimlistview`. If there
	 * are no `Statement`s, `null` is returned.
	 * @since 0.5
	 *
	 * @return {wikibase.datamodel.Statement[]|null}
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
	 * Removes a `statementview` widget.
	 *
	 * @param {jQuery.wikibase.statementview} statementview
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
	 * @inheritdoc
	 * @protected
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
	 * @inheritdoc
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
