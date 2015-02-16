( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying `wikibase.datamodel.Statement` objects grouped by their main `Snak`'s
 * `Property` id by managing a list of `jQuery.wikibase.statementgroupview` widgets.
 * @see wikibase.datamodel.StatementGroup
 * @see wikibase.datamodel.StatementGroupSet
 * @uses jQuery.wikibase.statementgroupview
 * @uses jQuery.wikibase.listview
 * @uses jQuery.wikibase.listview.ListItemAdapter
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.StatementGroupSet} options.value
 *        The `Statements` to be displayed by this view.
 * @param {wikibase.utilities.ClaimGuidGenerator} options.claimGuidGenerator
 *        Required for dynamically generating GUIDs for new `Statement`s.
 * @param {string} [options.entityType=wikibase.datamodel.Item.TYPE]
 *        Type of the `Entity` the `statementgrouplistview` refers to.
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
 */
$.widget( 'wikibase.statementgrouplistview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		template: 'wikibase-statementgrouplistview',
		templateParams: [
			'' // listview
		],
		templateShortCuts: {},
		value: null,
		claimGuidGenerator: null,
		entityType: wb.datamodel.Item.TYPE,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null,
		referencesChanger: null,
		dataTypeStore: null
	},

	/**
	 * @property {jQuery}
	 * @readonly
	 */
	$listview: null,

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
			|| !( this.options.value instanceof wb.datamodel.StatementGroupSet )
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		this._createListview();

		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter();

		this.element
		.on( lia.prefixedEvent( 'afterremove.' + this.widgetName ), function( event ) {
			var $statementgroupview = $( event.target ),
				statementgroupview = lia.liInstance( $statementgroupview );

			if( !statementgroupview.value() ) {
				listview.removeItem( $statementgroupview );
			}
		} );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	destroy: function() {
		this.$listview.data( 'listview' ).destroy();
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_createListview: function() {
		var self = this;

		this.$listview = this.element.children( '.wikibase-listview' );

		if( !this.$listview.length ) {
			this.$listview = $( '<div/>' ).appendTo( this.element );
		}

		this.$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.statementgroupview,
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						claimGuidGenerator: self.options.claimGuidGenerator,
						entityType: self.options.entityType,
						dataTypeStore: self.options.dataTypeStore,
						entityStore: self.options.entityStore,
						valueViewBuilder: self.options.valueViewBuilder,
						entityChangersFactory: self.options.entityChangersFactory,
						referencesChanger: self.options.referencesChanger
					};
				}
			} ),
			value: self._statementGroupSetToStatementGroups( this.options.value )
		} );
	},

	/**
	 * @private
	 *
	 * @param {wikibase.datamodel.StatementGroupSet} statementGroupSet
	 * @return {wikibase.datamodel.StatementGroup[]}
	 */
	_statementGroupSetToStatementGroups: function( statementGroupSet ) {
		return $.map( statementGroupSet.getKeys(), function( propertyId ) {
			return statementGroupSet.getItemByKey( propertyId );
		} );
	},

	/**
	 * Triggers adding a new `statementgroupview` to the `statementgrouplistview`. This involves
	 * triggering the corresponding process for the new pending `statementgroupview` by instantly
	 * triggering the `enterNewItem()` function of the `statementgroupview`'s `statemenlistview`.
	 * @see jQuery.wikibase.statementlistview.enterNewItem
	 * @see jQuery.wikibase.listview.enterNewItem
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {jQuery} return.done.$statementlistview
	 */
	enterNewItem: function() {
		var self = this,
			listview = this.$listview.data( 'listview' ),
			lia = this.$listview.data( 'listview' ).listItemAdapter();

		return this.$listview.data( 'listview' ).enterNewItem()
			.done( function( $statementgroupview ) {
				$statementgroupview
				.addClass( 'wb-new' )
				.one(
					lia.prefixedEvent( 'afterstopediting.' + self.widgetName ),
					function( event, dropValue ) {
						var $statementgroupview = $( event.target ),
							statementGroup = lia.liInstance( $statementgroupview ).value();

						listview.removeItem( $statementgroupview );

						if( dropValue ) {
							return;
						}

						self._addStatementGroup( statementGroup );
					}
				);

				var statementgroupview = lia.liInstance( $statementgroupview ),
					$statementlistview = statementgroupview.$statementlistview,
					statementlistview = $statementlistview.data( 'statementlistview' );
				statementlistview.enterNewItem();
		} );
	},

	/**
	 * @see jQuery.wikibase.listview.addItem
	 * @private
	 *
	 * @param {wikibase.datamodel.StatementGroup} newStatementGroup
	 */
	_addStatementGroup: function( newStatementGroup ) {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			propertyId = newStatementGroup.getKey(),
			$statementgroupviews = listview.items(),
			found = false;

		$statementgroupviews.each( function() {
			var statementgroupview = lia.liInstance( $( this ) ),
				statementGroup = statementgroupview.value();

			if( statementGroup.getKey() === propertyId ) {
				newStatementGroup.getItemContainer().each( function() {
					statementGroup.addItem( this );
				} );
				statementgroupview.value( statementGroup );
				found = true;
			}

			return !found;
		} );

		if( !found ) {
			listview.addItem( newStatementGroup );
		}
	},

	/**
	 * Sets the widget's value or retrieves the widget's current value (including any pending
	 * changes). The value the widget was initialized with may be retrieve using
	 * `this.option( 'value' )`.
	 *
	 * @param {wikibase.datamodel.StatementGroupSet} [statementGroupSet]
	 * @return {wikibase.datamodel.StatementGroupSet|undefined}
	 */
	value: function( statementGroupSet ) {
		if( statementGroupSet === undefined ) {
			return new wb.datamodel.StatementGroupSet(
				$.map( this.$listview.data( 'listview' ).value(), function( statementGroup ) {
					return statementGroup.value();
				} )
			);
		}
		this.option( 'value', statementGroupSet );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_setOption: function( key, value ) {
		if( key === 'value' && value !== undefined ) {
			if( !( value instanceof wb.datamodel.StatementGroupSet ) ) {
				throw new Error(
					'value needs to be an instance of wb.datamodel.StatementGroupSet'
				);
			}
			this.$listview.data( 'listview' ).value(
				this._statementGroupSetToStatementGroups( value )
			);
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$listview.data( 'listview' ).option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		var listview = this.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items();

		if( $items.length ) {
			lia.liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	}
} );

}( wikibase, jQuery ) );
