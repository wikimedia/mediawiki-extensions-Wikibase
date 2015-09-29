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
 * @uses wikibase.utilities
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
 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} options.entityIdHtmlFormatter
 *        Required for dynamically rendering links to `Entity`s.
 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} options.entityIdPlainFormatter
 *        Required for dynamically rendering plain text references to `Entity`s.
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
			$listview: '.wikibase-statementlistview-listview'
		},
		value: null,
		claimGuidGenerator: null,
		entityIdHtmlFormatter: null,
		entityIdPlainFormatter: null,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null,
		dataTypeStore: null
	},

	/**
	 * @type {jQuery.wikibase.listview}
	 * @private
	 */
	_listview: null,

	/**
	 * @type {wikibase.entityChangers.ClaimsChanger}
	 * @private
	 */
	_claimsChanger: null,

	/**
	 * @type {wikibase.entityChangers.ReferencesChanger}
	 * @private
	 */
	_referencesChanger: null,

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
		this._referencesChanger = this.options.entityChangersFactory.getReferencesChanger();

		this._createListView();

		var self = this,
			lia = this._listview.listItemAdapter(),
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
		this._listview.destroy();
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
						entityIdHtmlFormatter: self.options.entityIdHtmlFormatter,
						entityIdPlainFormatter: self.options.entityIdPlainFormatter,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder,
						claimsChanger: self._claimsChanger,
						referencesChanger: self._referencesChanger,
						guidGenerator: self.options.claimGuidGenerator
					};
				}
			} ),
			value: this.options.value.toArray()
		} );

		this._listview = this.$listview.data( 'listview' );
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
			var lia = this._listview.listItemAdapter();

			statementList = new wb.datamodel.StatementList();

			this._listview.items().each( function() {
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
		return !this._listview.items().length;
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
			lia = this._listview.listItemAdapter(),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + self.widgetName );

		return this._listview.enterNewItem().done( function( $statementview ) {
			var statementview = lia.liInstance( $statementview );

			$statementview
			.addClass( 'wb-new' )
			.one( afterStopEditingEvent, function( event, dropValue ) {
				var statement = statementview.value();

				self._listview.removeItem( $statementview );

				if( !dropValue && statement ) {
					self._listview.addItem( statement );
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
			self._listview.removeItem( statementview.element );

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
			this._listview.value( value.toArray() );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this._listview.option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		var $items = this._listview.items();

		if( $items.length ) {
			this._listview.listItemAdapter().liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	}

} );

}( wikibase, jQuery ) );
