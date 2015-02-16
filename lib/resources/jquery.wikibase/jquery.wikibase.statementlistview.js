( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing a list of `wikibase.datamodel.Statement` objects by using
 * `jQuery.wikibase.statementview` widgets.
 * @see jQuery.wikibase.statementview
 * @see wikibase.datamodel.Statement
 * @see wikibase.datamodel.StatementList
 * @class jQuery.wikibase.statementlistview
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
 * @param {wikibase.datamodel.StatementList} options.value
 *        The list of `Statement`s to be displayed by this view. If null, the view will initialize
 *        with edit mode being started.
 * @param {wikibase.utilities.ClaimGuidGenerator} options.claimGuidGenerator
 *        Required for dynamically generating GUIDs for new `Statement`s.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required for dynamically gathering `Entity`/`Property` information.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required by the `snakview` interfacing a `snakview` "value" `Variation` to
 *        `jQuery.valueview`.
 * @param {wikibase.entityChangers.EntityChangersFactory} options.entityChangersFactory
 *        Required to store the `Reference`s gathered from the `referenceview`s aggregated by the
 *        `statementview`.
 * @param {wikibase.entityChangers.ReferencesChanger} [options.referencesChanger]
 *        Required if `Statement` `Reference`s should not be saved along with each `Statement` but
 *        are supposed to be saved individually (e.g. by applying individual edit toolbars to the
 *        `referenceview`s).
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
 *        object when interacting on a "value" `Variation`.
/**
 * @event afterstartediting
 * Triggered when edit mode has been started for one of the `statementview` widgets managed by the
 * `statementlistview`.
 * @param {jQuery.Event} event
 */
/**
 * @event afterstopediting
 * Triggered when one of the `statementview` widgets managed by the `statementlistview` has
 * successfully stopped edit mode.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue If true, the value from before edit mode has been started will be
 *        reinstated (basically, a cancel/save switch).
 */
/**
 * @event afterremove
 * Triggered after one of the `statementview` widgets managed by the `statementlistview` was removed
 * from the `statementlistview`.
 * @param {jQuery.Event} event
 */
/**
 * @event toggleerror
 * Triggered when one of the `statementview` widgets managed by the `statementlistview` produces an
 * error.
 * @param {jQuery.Event} event
 */
$.widget( 'wikibase.statementlistview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		template: 'wikibase-statementlistview',
		templateParams: [
			'', // listview widget
			'' // toolbar
		],
		templateShortCuts: {
			'$listview': '.wikibase-statementlistview-listview'
		},
		value: null,
		claimGuidGenerator: null,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null,
		referencesChanger: null,
		dataTypeStore: null
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
			!this.options.claimGuidGenerator
			|| !this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.entityChangersFactory
			|| !this.options.dataTypeStore
			|| !( this.options.value instanceof wb.datamodel.StatementList )
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		this._claimsChanger = this.options.entityChangersFactory.getClaimsChanger();

		this._createListView();

		var self = this,
			lia = this.listview().listItemAdapter(),
			afterStartEditingEvent
				= lia.prefixedEvent( 'afterstartediting.' + this.widgetName ),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + this.widgetName ),
			toggleErrorEvent = lia.prefixedEvent( 'toggleerror.' + this.widgetName );

		this.element
		.on( afterStartEditingEvent, function( event ) {
			// Forward "afterstartediting" event for higher components (e.g. statementgrouplistview)
			// to recognize that edit mode has been started.
			self._trigger( 'afterstartediting' );
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
			propertyId;

		if( $.expr[':']['wikibase-statementgroupview'] ) {
			var $statementgroupview = this.element.closest( ':wikibase-statementgroupview' ),
				statementgroupview = $statementgroupview.data( 'statementgroupview' ),
				statementGroup = statementgroupview && statementgroupview.option( 'value' );
			propertyId = statementGroup && statementGroup.getKey();
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
								property: value
									? value.getClaim().getMainSnak().getPropertyId()
									: propertyId
							}
						},
						locked: {
							mainSnak: {
								property: !!( value || propertyId )
							}
						},
						dataTypeStore: self.options.dataTypeStore,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder,
						entityChangersFactory: self.options.entityChangersFactory,
						referencesChanger: self.options.referencesChanger,
						claimsChanger: self._claimsChanger,
						guidGenerator: self.options.claimGuidGenerator
					};
				}
			} ),
			value: this.options.value.toArray()
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
	 * Sets the widget's value or gets the widget's current value (including pending items). (The
	 * value the widget was initialized with may be retrieved via `.option( 'value' )`.)
	 *
	 * @param {wikibase.datamodel.StatementList} [statementList]
	 * @return {wikibase.datamodel.StatementList|undefined}
	 */
	value: function( statementList ) {
		if( statementList === undefined ) {
			var listview = this.$listview.data( 'listview' ),
				lia = listview.listItemAdapter();

			statementList = new wb.datamodel.StatementList();

			listview.items().each( function() {
				var statement = lia.liInstance( $( this ) ).value();
				if( statement ) {
					statementList.addItem( statement );
				}
			} );

			return statementList;
		}

		this.option( 'value', statementList );
	},

	/**
	 * Returns whether the widget currently features any `statementview` widgets.
	 *
	 * @return {boolean}
	 */
	isEmpty: function() {
		return !this.listview().items().length;
	},

	/**
	 * Adds a new, pending `statementview` to the `statementlistview`.
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
			.one( afterStopEditingEvent, function( event, dropValue ) {
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
		if( key === 'value' && !!value ) {
			if( !( value instanceof wb.datamodel.StatementList ) ) {
				throw new Error( 'value needs to be a wb.datamodel.StatementList instance' );
			}
			this.$listview.data( 'listview' ).value( value.toArray() );
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

}( wikibase, jQuery ) );
