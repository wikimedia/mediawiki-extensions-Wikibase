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
 * @since 0.4
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.StatementList} [options.value]
 *        The list of `Statement`s to be displayed by this view.
 * @param {wikibase.entityChangers.ClaimsChanger} options.claimsChanger
 * @param {jQuery.wikibase.listview.ListItemAdapter} options.listItemAdapter
 */
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
		claimsChanger: null,
		listItemAdapter: null
	},

	/**
	 * @type {jQuery.wikibase.listview}
	 * @private
	 */
	_listview: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if ( !this.options.claimsChanger
			|| !this.options.listItemAdapter
			|| ( this.options.value && !( this.options.value instanceof wb.datamodel.StatementList ) )
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

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
			if ( dropValue || !statementview || statementview.value() ) {
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
	 *
	 * @since 0.5
	 * @private
	 */
	_createListView: function() {
		this.$listview.listview( {
			listItemAdapter: this.options.listItemAdapter,
			value: this.options.value ? this.options.value.toArray() : null
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
		if ( statementList !== undefined ) {
			return this.option( 'value', statementList );
		}

		var statements = [],
			lia = this._listview.listItemAdapter();

		this._listview.items().each( function() {
			var statementview = lia.liInstance( $( this ) ),
				statement = statementview.value();
			if ( statement ) {
				statements.push( statement );
			}
		} );

		return new wb.datamodel.StatementList( statements );
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
	 *
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
				$statementview.removeClass( 'wb-new' );
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
		var self = this,
			statement = statementview.option( 'value' );

		if ( statement && statement.getClaim().getGuid() ) {
			statementview.disable();
			this.options.claimsChanger.removeStatement( statement )
			.done( function() {
				self._removeStatementview( statementview );
			} ).fail( function( error ) {
				statementview.enable();
				statementview.setError( error );
			} );
		} else {
			this._removeStatementview( statementview );
		}
	},

	/**
	 * @param {jQuery.wikibase.statementview} statementview
	 * @private
	 */
	_removeStatementview: function( statementview ) {
		this._listview.removeItem( statementview.element );
		this._trigger( 'afterremove' );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' && !!value ) {
			if ( !( value instanceof wb.datamodel.StatementList ) ) {
				throw new Error( 'value needs to be a wb.datamodel.StatementList instance' );
			}
			this._listview.value( value.toArray() );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if ( key === 'disabled' ) {
			this._listview.option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		var $items = this._listview.items();

		if ( $items.length ) {
			this._listview.listItemAdapter().liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	}

} );

}( wikibase, jQuery ) );
