( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying `wikibase.datamodel.Statement` objects grouped by their main `Snak`'s
 * `Property` id by managing a list of `jQuery.wikibase.claimlistview` widgets.
 * @see wikibase.datamodel.StatementGroup
 * @see wikibase.datamodel.StatementGroupSet
 * @uses jQuery.wikibase.claimlistview
 * @uses jQuery.wikibase.listview
 * @uses jQuery.wikibase.listview.ListItemAdapter
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {wikibase.datamodel.ClaimGroupSet|{wikibase.datamodel.StatementGroupSet} [value]
 *         The claims to be displayed by this view. If null, the view will display only an add
 *         button to add new claims.
 *         Default: null
 *
 * @option {string} entityType Type of the entity that the claimgrouplistview referes to.
 *         Default: wb.datamodel.Item.type
 *
 * @option {wikibase.store.EntityStore} entityStore
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 * @option {dataTypes.DataTypeStore} dataTypeStore
 */
$.widget( 'wikibase.claimgrouplistview', PARENT, {
	/**
	 * (Additional) default options.
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-claimgrouplistview',
		templateParams: [
			'', // claimlistview widgets
			'' // toolbar
		],
		templateShortCuts: {
			$listview: '.wb-claimlists'
		},
		value: null,
		entityType: wb.datamodel.Item.type,
		dataTypeStore: null,
		entityStore: null,
		valueViewBuilder: null,
		entityChangersFactory: null
	},

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

		var self = this,
			statements = this.option( 'value' );

		this._createClaimGroupListview();

		var listview = this.listview(),
			lia = listview.listItemAdapter(),
			startEditingEvent = lia.prefixedEvent( 'startediting.' + this.widgetName ),
			afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + this.widgetName ),
			afterRemoveEvent = lia.prefixedEvent( 'afterremove.' + this.widgetName ),
			errorEvent = lia.prefixedEvent( 'toggleerror.' + this.widgetName );

		this.element
		.on( errorEvent, function( event, dropValue ) {
			self._toggleGroupTitleClass( $( event.target ), 'wb-error' );
		} )
		.on( afterStopEditingEvent, function( event, dropValue ) {
			self._toggleGroupTitleClass( $( event.target ), 'wb-error' );
			self._removeGroupTitleClass( $( event.target ), 'wb-edit' );
		} )
		.on( startEditingEvent, function( event ) {
			self._addGroupTitleClass( $( event.target ), 'wb-edit' );
		} )
		.on( afterRemoveEvent, function( event ) {
			// Check whether the whole claimlistview may be removed from the claimgrouplistview if
			// the last statementview has been removed from the claimlistview.
			var $claimlistview = $( event.target ),
				claimlistview = lia.liInstance( $claimlistview );

			if( !claimlistview.value() ) {
				self.listview().removeItem( $claimlistview );
			}
		} );

		if( statements ) {
			this._initStatements( statements );
		}
	},

	/**
	 * Fills the `listview` with the initially passed `Statement`s by ordering the `Statement`s
	 * according to their main `Snak`'s `Property` id.
	 * @since 0.5
	 *
	 * @param {wikibase.datamodel.StatementGroupSet} statementGroups
	 */
	_initStatements: function( statementGroups ) {
		var self = this;

		statementGroups.each( function( propertyId, statementGroup ) {
			self.listview().addItem( statementGroup );
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
	 * Creates the `listview` widget used to manage the `claimlistview` widgets.
	 * @since 0.5
	 * @private
	 */
	_createClaimGroupListview: function() {
		var self = this;

		this.$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.claimlistview,
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
			} )
		} );
	},

	/**
	 * Toggles a specific css class on the group title node.
	 * @since 0.5
	 * @private
	 *
	 * @param {jQuery} $claimlistview
	 * @param {string} cssClass
	 */
	_toggleGroupTitleClass: function( $claimlistview, cssClass ) {
		var selector =  '.' + cssClass + ':not(.wb-claimgrouplistview-groupname)',
			action = $claimlistview.find( selector ).length
				? '_addGroupTitleClass'
				: '_removeGroupTitleClass';

		this[action]( $claimlistview, cssClass );
	},

	/**
	 * Adds a specific css class to the group title node.
	 * @since 0.5
	 * @private
	 *
	 * @param {jQuery} $claimlistview
	 * @param {string} cssClass
	 */
	_addGroupTitleClass: function( $claimlistview, cssClass ) {
		var $groupTitle = $claimlistview.children( '.wb-claimgrouplistview-groupname' );
		$groupTitle.addClass( cssClass );
	},

	/**
	 * Removes a specific css class from the group title node.
	 * @since 0.5
	 * @private
	 *
	 * @param {jQuery} $claimlistview
	 * @param {string} cssClass
	 */
	_removeGroupTitleClass: function( $claimlistview, cssClass ) {
		var $groupTitle = $claimlistview.children( '.wb-claimgrouplistview-groupname' );
		$groupTitle.removeClass( cssClass );
	},

	/**
	 * Returns a reference to the `listview` widget containing the `claimlistview`s managed by the
	 * `claimgrouplistview`.
	 * @since 0.5
	 *
	 * @return {jQuery.wikibase.listview}
	 */
	listview: function() {
		return this.$listview.data( 'listview' );
	},

	/**
	 * Triggers adding a new `claimlistview` to the `claimgrouplistview`. This involves triggering
	 * the corresponding process for the new pending `claimlistview` by triggering the
	 * `claimlistview`'s `enterNewItem()` method that instantiates a pending `claimview` to be added
	 * to the pending `claimlistview` which itself is added to the `claimgrouplistview`.
	 * @see jQuery.wikibase.listview.enterNewItem
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {jQuery} return.done.$claimlistview
	 */
	enterNewItem: function() {
		var self = this,
			lia = this.listview().listItemAdapter();

		return this.listview().enterNewItem().done( function( $claimlistview ) {
			var afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + self.widgetName );

			$claimlistview
			.addClass( 'wb-new' )
			.one( afterStopEditingEvent, function( event, dropValue ) {
				var $claimlistview = $( event.target );

				if( dropValue ) {
					self.listview().removeItem( $claimlistview );
					return;
				}

				// A new claim(list) has been saved successfully. If the new claimlist features a
				// property that already is represented by a claimlistview, the new claimlist's
				// claims have to be appended to it. If there is claimlistview featuring the
				// property yet, a new claimlistview is added.
				// TODO: Do not re-add the claimlistview to the claimgrouplistview if there is not
				// claimlistview featuring the specific property yet. Instead, use the already
				// existing pending claimlistview.
				// TODO: Assume that there are more than one item to be added.
				var newStatements = lia.liInstance( $claimlistview ).value();

				self.listview().removeItem( $claimlistview );

				if ( newStatements === null ) {
					return;
				}

				var newPropertyId = newStatements[0].getClaim().getMainSnak().getPropertyId(),
					correspondingClaimlistview = self._findClaimlistview( newPropertyId );

				if( correspondingClaimlistview ) {
					correspondingClaimlistview.listview().addItem( newStatements[0] );
				} else {
					self.listview().addItem( new wb.datamodel.StatementGroup(
						newPropertyId,
						new wb.datamodel.StatementList( newStatements )
					) );
				}
			} );

			lia.liInstance( $claimlistview ).enterNewItem();
		} );
	},

	/**
	 * Finds the `claimlistview` that features `Statment`s whose main `Snak` feature a specific
	 * `Property` id. Returns `null` if no `claimlistview` featuring that `Property` id exists.
	 * @since 0.5
	 *
	 * @param {string} propertyId
	 * @return {jQuery.wikibase.claimlistview|null}
	 */
	_findClaimlistview: function( propertyId ) {
		var $claimlistviews = this.listview().items(),
			lia = this.listview().listItemAdapter();

		for( var i = 0; i < $claimlistviews.length; i++ ) {
			var claimlistview = lia.liInstance( $claimlistviews.eq( i ) ),
				claims = claimlistview.value();

			if( !claims || !claims.length ) {
				continue;
			}

			var mainSnak = claims[0].getMainSnak
				? claims[0].getMainSnak()
				: claims[0].getClaim().getMainSnak();

			if( mainSnak.getPropertyId() === propertyId ) {
				return claimlistview;
			}
		}

		return null;
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
$.wikibase.claimgrouplistview.prototype.widgetBaseClass = 'wb-claimgrouplistview';

}( wikibase, jQuery ) );
