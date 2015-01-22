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
 * @option {wikibase.datamodel.StatementGroupSet} value
 *         The `Statements` to be displayed by this view. If null, the view will display only an add
 *         button to add new `Statements`.
 *         Default: null
 *
 * @option {string} entityType Type of the entity that the statementgrouplistview refers to.
 *         Default: wb.datamodel.Item.type
 *
 * @option {wikibase.store.EntityStore} entityStore
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 * @option {dataTypes.DataTypeStore} dataTypeStore
 */
$.widget( 'wikibase.statementgrouplistview', PARENT, {
	/**
	 * (Additional) default options.
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wikibase-statementgrouplistview',
		templateParams: [
			'' // listview
		],
		templateShortCuts: {},
		value: null,
		entityType: wb.datamodel.Item.TYPE,
		dataTypeStore: null,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null
	},

	/**
	 * @property {jQuery}
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
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.entityChangersFactory
			|| !this.options.dataTypeStore
			|| !( this.options.value instanceof wb.datamodel.StatementGroupSet )
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		this._createStatementgrouplistview();

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
	 * Creates the `listview` widget used to manage the `statementgroupview` widgets.
	 * @since 0.5
	 * @private
	 */
	_createStatementgrouplistview: function() {
		var self = this;

		this.$listview = this.element.children( '.wb-listview' );

		if( !this.$listview.length ) {
			this.$listview = $( '<div/>' ).appendTo( this.element );
		}

		var propertyIds = this.options.value.getKeys(),
			value = [];

		for( var i = 0; i < propertyIds.length; i++ ) {
			value.push( this.options.value.getItemByKey( propertyIds[i] ) );
		}

		this.$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.statementgroupview,
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						entityType: self.option( 'entityType' ),
						dataTypeStore: self.option( 'dataTypeStore' ),
						entityStore: self.option( 'entityStore' ),
						valueViewBuilder: self.option( 'valueViewBuilder' ),
						entityChangersFactory: self.option( 'entityChangersFactory' )
					};
				}
			} ),
			value: value
		} );
	},

	/**
	 * Triggers adding a new `statementgroupview` to the `statementgrouplistview`. This involves
	 * triggering the corresponding process for the new pending `statementgroupview` by triggering
	 * the `statementgroupview`'s `enterNewItem()` function that instantiates a pending
	 * `statementlistview` to be added to the pending `statementgroupview` which itself is added to
	 * the `statementgrouplistview`.
	 * @see jQuery.wikibase.statementgroupview.enterNewItem
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
	 * @param {wikibase.datamodel.StatementGroup} newStatementGroup
	 * @private
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
	 * @inheritdoc
	 * @protected
	 */
	_setOption: function( key, value ) {
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
