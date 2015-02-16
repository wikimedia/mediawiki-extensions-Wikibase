( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying `wikibase.datamodel.Statement` objects grouped by their main `Snak`'s
 * `Property` id by managing a list of `jQuery.wikibase.statementview` widgets encapsulated by a
 * `jquery.wikibase.statementlistview` widget.
 * @see wikibase.datamodel.StatementGroup
 * @uses jQuery.wikibase.statementlistview
 * @uses jQuery.wikibase.statementview
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
 * @param {wikibase.datamodel.StatementGroup} [options.value=null]
 *        The `Statements` to be displayed by this view. If `null`, the view will only display an
 *        "add" button to add new `Statements`.
 * @param {wikibase.utilities.ClaimGuidGenerator} options.claimGuidGenerator
 *        Required for dynamically generating GUIDs for new `Statement`s.
 * @param {string} [entityType=wikibase.datamodel.Item.TYPE]
 *        Type of the entity that the widget refers to.
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
	 */
	$statementlistview: null,

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
		) {
			throw new Error( 'Required option not specified properly' );
		}

		PARENT.prototype._create.call( this );

		this.$statementlistview = this.element.find( '.wikibase-statementlistview' );

		if( !this.$statementlistview.length ) {
			this.$statementlistview = $( '<div/>' ).appendTo( this.element );
		}

		if( this.options.value ) {
			this._createPropertyLabel();
		}
		this._createStatementlistview();
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		if( this.$statementlistview ) {
			this.$statementlistview.off( this.widgetName );
			this.$statementlistview.data( 'statementlistview' ).destroy();
		}
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_createPropertyLabel: function() {
		if( this.$propertyLabel.contents().length > 0 ) {
			return;
		}

		var self = this,
			propertyId = this.options.value.getKey();

		this.options.entityStore.get( propertyId ).done( function( property ) {
			var $title;

			if( property ) {
				$title = wb.utilities.ui.buildLinkToEntityPage(
					property.getContent(),
					property.getTitle()
				);
			} else {
				$title = wb.utilities.ui.buildMissingEntityInfo(
					propertyId,
					wb.datamodel.Property
				);
			}

			self.$propertyLabel.append( $title );
		} );
	},

	/**
	 * @private
	 */
	_createStatementlistview: function() {
		var self = this,
			prefix = $.wikibase.statementlistview.prototype.widgetEventPrefix;

		this.$statementlistview
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			self.$property[error ? 'addClass' : 'removeClass']( 'wb-error' );
		} )
		.on( prefix + 'afterstopediting.' + this.widgetName, function( event, dropValue ) {
			self.$property.removeClass( 'wb-error' ).removeClass( 'wb-edit' );
			self._trigger( 'afterstopediting', null, [dropValue] );
		} )
		.on( prefix + 'afterstartediting.' + this.widgetName, function( event ) {
			self.$property.addClass( 'wb-edit' );
		} )
		.on( prefix + 'afterremove.' + this.widgetName, function( event ) {
			self._trigger( 'afterremove' );
		} )
		.statementlistview( {
			value: this.options.value
				? this.options.value.getItemContainer()
				: new wb.datamodel.StatementList(),
			claimGuidGenerator: self.options.claimGuidGenerator,
			entityType: self.options.entityType,
			entityStore: self.options.entityStore,
			valueViewBuilder: self.options.valueViewBuilder,
			entityChangersFactory: self.options.entityChangersFactory,
			referencesChanger: self.options.referencesChanger,
			dataTypeStore: self.options.dataTypeStore
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
		if( statementGroupView === undefined ) {
			var statementList = this.$statementlistview.data( 'statementlistview' ).value();
			if( !statementList.length ) {
				return null;
			}
			// Use the first statement's main snak property id as the statementgroupview may have
			// been initialized without a value (as there is no initial value, the id cannot be
			// retrieved from this.options.value).
			return new wb.datamodel.StatementGroup(
				statementList.toArray()[0].getClaim().getMainSnak().getPropertyId(),
				statementList
			);
		}

		this.option( 'value', statementGroupView );
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} when trying to set the value passing something different than a
	 *         `wikibase.datamodel.StatementGroup´ object.
	 */
	_setOption: function( key, value ) {
		if( key === 'value' && !!value ) {
			if( !( value instanceof wb.datamodel.StatementGroup ) ) {
				throw new Error( 'value needs to be a wb.datamodel.StatementGroup instance' );
			}
			this.$statementlistview.data( 'statementlistview' ).value( value.getItemContainer() );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$statementlistview.data( 'statementlistview' ).option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this.$statementlistview.data( 'statementlistview' ).focus();
	}
} );

}( wikibase, jQuery ) );
