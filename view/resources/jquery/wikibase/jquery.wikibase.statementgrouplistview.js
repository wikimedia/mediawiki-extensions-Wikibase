( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying `wikibase.datamodel.Statement` objects grouped by their main `Snak`'s
 * `Property` id by managing a list of `jQuery.wikibase.statementgroupview` widgets.
 * @see wikibase.datamodel.StatementGroup
 * @see wikibase.datamodel.StatementGroupSet
 * @uses jQuery.wikibase.statementgrouplabelscroll
 * @uses jQuery.wikibase.listview
 * @uses jQuery.wikibase.listview.ListItemAdapter
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {jquery.wikibase.listview.ListItemAdapter} options.listItemAdapter
 * @param {wikibase.datamodel.StatementGroupSet} [options.value]
 *        The `Statements` to be displayed by this view.
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
		listItemAdapter: null,
		value: null
	},

	/**
	 * @property {jQuery.wikibase.listview}
	 * @readonly
	 */
	listview: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if ( !this.options.listItemAdapter
			|| ( this.options.value && !( this.options.value instanceof wb.datamodel.StatementGroupSet ) )
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		var listview = this.listview = this._createListview(),
			lia = listview.listItemAdapter();

		this.element
		.on( lia.prefixedEvent( 'afterremove.' + this.widgetName ), function( event ) {
			var $statementgroupview = $( event.target ),
				statementgroupview = lia.liInstance( $statementgroupview );

			if ( !statementgroupview.value() ) {
				listview.removeItem( $statementgroupview );
			}
		} );

		this.element.statementgrouplabelscroll();

		this._statementGroupAdder = this.options.getAdder( this.enterNewItem.bind( this ), this.element );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	destroy: function() {
		this.listview.destroy();
		if ( this._statementGroupAdder ) {
			this._statementGroupAdder.destroy();
			this._statementGroupAdder = null;
		}
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_createListview: function() {
		var $listview = this.element.children( '.wikibase-listview' );

		if ( !$listview.length ) {
			$listview = $( '<div/>' ).appendTo( this.element );
		}

		$listview.listview( {
			listItemAdapter: this.options.listItemAdapter,
			value: this.options.value ? this._statementGroupSetToStatementGroups( this.options.value ) : null
		} );

		return $listview.data( 'listview' );
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
	 * triggering the `enterNewItem()` function of the `statementgroupview`.
	 *
	 * @see jQuery.wikibase.statementgroupview.enterNewItem
	 * @see jQuery.wikibase.listview.enterNewItem
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {jQuery} return.done.$statementgroupview
	 */
	enterNewItem: function() {
		var self = this,
			lia = this.listview.listItemAdapter();

		return this.listview.enterNewItem()
			.done( function( $statementgroupview ) {
				$statementgroupview
				.addClass( 'wb-new' )
				.one(
					lia.prefixedEvent( 'afterstopediting.' + self.widgetName ),
					function( event, dropValue ) {
						var $statementgroupview = $( event.target ),
							statementGroup = lia.liInstance( $statementgroupview ).value();

						self.listview.removeItem( $statementgroupview );

						if ( dropValue ) {
							return;
						}

						self._addStatementGroup( statementGroup );
					}
				);

				var statementgroupview = lia.liInstance( $statementgroupview );
				statementgroupview.enterNewItem();
		} );
	},

	/**
	 * @see jQuery.wikibase.listview.addItem
	 * @private
	 *
	 * @param {wikibase.datamodel.StatementGroup} newStatementGroup
	 */
	_addStatementGroup: function( newStatementGroup ) {
		var lia = this.listview.listItemAdapter(),
			propertyId = newStatementGroup.getKey(),
			$statementgroupviews = this.listview.items(),
			found = false;

		$statementgroupviews.each( function() {
			var statementgroupview = lia.liInstance( $( this ) ),
				statementGroup = statementgroupview.value();

			if ( statementGroup.getKey() === propertyId ) {
				newStatementGroup.getItemContainer().each( function() {
					statementGroup.addItem( this );
				} );
				statementgroupview.value( statementGroup );
				found = true;
			}

			return !found;
		} );

		if ( !found ) {
			this.listview.addItem( newStatementGroup );
		}
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' && value !== undefined ) {
			if ( !( value instanceof wb.datamodel.StatementGroupSet ) ) {
				throw new Error(
					'value needs to be an instance of wb.datamodel.StatementGroupSet'
				);
			}
			this.listview.value(
				this._statementGroupSetToStatementGroups( value )
			);
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if ( key === 'disabled' ) {
			this.listview.option( key, value );
			if ( this._statementGroupAdder ) {
				this._statementGroupAdder[ value ? 'disable' : 'enable' ]();
			}
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		var lia = this.listview.listItemAdapter(),
			$items = this.listview.items();

		if ( $items.length ) {
			lia.liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	}
} );

}( wikibase, jQuery ) );
