( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying `wikibase.datamodel.Statement` objects grouped by their main `Snak`'s
 * `Property` id by managing a list of `jQuery.wikibase.statementview` widgets encapsulated by a
 * `jquery.wikibase.statementlistview` widget.
 * @see wikibase.datamodel.StatementGroup
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.StatementGroup} [options.value=null]
 *        The `Statements` to be displayed by this view. If `null`, the view will only display an
 *        "add" button to add new `Statements`.
 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} options.entityIdHtmlFormatter
 *        Required for dynamically rendering links to `Entity`s.
 * @param {Function} options.buildStatementListView
 */
/**
 * @event afterremove
 * Triggered after a `statementview` was removed from the `statementlistview` encapsulated by this
 * `statementgroupview`.
 * @param {jQuery.Event} event
 */
$.widget( 'wikibase.statementgroupview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		template: 'wikibase-statementgroupview',
		templateParams: [
			'', // label
			'', // statementlistview widget
			'' // id
		],
		templateShortCuts: {
			$property: '.wikibase-statementgroupview-property',
			$propertyLabel: '.wikibase-statementgroupview-property-label'
		},
		value: null,
		buildStatementListView: null,
		entityIdHtmlFormatter: null
	},

	/**
	 * @property {jQuery.wikibase.statementlistview}
	 */
	statementlistview: null,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if ( !this.options.entityIdHtmlFormatter || !this.options.buildStatementListView ) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		if ( this.options.value ) {
			this._createPropertyLabel();
		}
		this._createStatementlistview();
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		if ( this.statementlistview ) {
			this.statementlistview.element.off( this.widgetName );
			this.statementlistview.destroy();
		}
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_createPropertyLabel: function() {
		if ( this.$propertyLabel.contents().length > 0 ) {
			return;
		}

		var self = this,
			propertyId = this.options.value.getKey();

		this.options.entityIdHtmlFormatter.format( propertyId ).done( function( title ) {
			self.$propertyLabel.append( title );
		} );
	},

	/**
	 * @private
	 */
	_createStatementlistview: function() {
		var self = this,
			prefix;

		var $statementlistview = this.element.find( '.wikibase-statementlistview' );

		if ( !$statementlistview.length ) {
			$statementlistview = $( '<div/>' ).appendTo( this.element );
		}

		this.statementlistview = this.options.buildStatementListView(
			this.options.value ? this.options.value.getItemContainer() : new wb.datamodel.StatementList(),
			$statementlistview
		);
		prefix = this.statementlistview.widgetEventPrefix;

		$statementlistview
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			self.$property.toggleClass( 'wb-error', Boolean( error ) );
		} )
		.on( prefix + 'afterstopediting.' + this.widgetName, function( event, dropValue ) {
			self.$property.removeClass( 'wb-error wb-edit' );
			self._trigger( 'afterstopediting', null, [dropValue] );
		} )
		.on( prefix + 'afterstartediting.' + this.widgetName, function( event ) {
			self.$property.addClass( 'wb-edit' );
		} )
		.on( prefix + 'afterremove.' + this.widgetName, function( event ) {
			self.$property.removeClass( 'wb-error wb-edit' );
			self._trigger( 'afterremove' );
		} );
	},

	/**
	 * Sets the widget's value or gets the widget's current value (including pending items). (The
	 * value the widget was initialized with may be retrieved via `.option( 'value' )`.)
	 *
	 * @param {wikibase.datamodel.StatementGroup} [statementGroupView]
	 * @return {wikibase.datamodel.StatementGroup|null|undefined}
	 */
	value: function( statementGroupView ) {
		if ( statementGroupView !== undefined ) {
			return this.option( 'value', statementGroupView );
		}

		var statementList = this.statementlistview.value();
		if ( !statementList.length ) {
			return null;
		}
		// Use the first statement's main snak property id as the statementgroupview may have
		// been initialized without a value (as there is no initial value, the id cannot be
		// retrieved from this.options.value).
		return new wb.datamodel.StatementGroup(
			statementList.toArray()[0].getClaim().getMainSnak().getPropertyId(),
			statementList
		);
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} when trying to set the value passing something different than a
	 *         `wikibase.datamodel.StatementGroup´ object.
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' && !!value ) {
			if ( !( value instanceof wb.datamodel.StatementGroup ) ) {
				throw new Error( 'value needs to be a wb.datamodel.StatementGroup instance' );
			}
			this.statementlistview.value( value.getItemContainer() );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if ( key === 'disabled' ) {
			this.statementlistview.option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this.statementlistview.focus();
	},

	/**
	 * Adds a new, pending `statementview` to the encapsulated `statementlistview`.
	 *
	 * @see jQuery.wikibase.statementlistview.enterNewItem
	 *
	 * @return {Object} jQuery.Promise
	 * @return {Function} return.done
	 * @return {jQuery} return.done.$statementview
	 */
	enterNewItem: function() {
		return this.statementlistview.enterNewItem();
	}

} );

}( wikibase, jQuery ) );
