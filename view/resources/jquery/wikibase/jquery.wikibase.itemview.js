( function( $, wb ) {
	'use strict';

var PARENT = $.wikibase.entityview;

/**
 * View for displaying a Wikibase `Item`.
 * @see wikibase.datamodel.Item
 * @class jQuery.wikibase.itemview
 * @extends jQuery.wikibase.entityview
 * @uses jQuery.wikibase.statementgrouplistview
 * @uses jQuery.wikibase.statementgrouplabelscroll
 * @uses jQuery.wikibase.sitelinkgrouplistview
 * @uses wikibase.utilities.ClaimGuidGenerator
 * @since 0.5
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} options.entityIdHtmlFormatter
 *        Required for dynamically rendering links to `Entity`s.
 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} options.entityIdPlainFormatter
 *        Required for dynamically rendering plain text references to `Entity`s.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required by sub-components of the `entityview` to enable those to dynamically query for
 *        `Entity` objects.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required by the `snakview` interfacing a `snakview` "value" `Variation` to
 *        `jQuery.valueview`.
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
 *        object when interacting on a "value" `Variation`.
 */
$.widget( 'wikibase.itemview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		entityIdHtmlFormatter: null,
		entityIdPlainFormatter: null,
		entityStore: null,
		valueViewBuilder: null,
		dataTypeStore: null
	},

	/**
	 * @property {jQuery}
	 * @readonly
	 */
	$statements: null,

	/**
	 * @inheritdoc
	 * @protected
	 */
	_create: function() {
		this._createEntityview();

		this.$statements = $( '.wikibase-statementgrouplistview', this.element );
		if( this.$statements.length === 0 ) {
			this.$statements = $( '<div/>' ).appendTo( this.element );
		}

		this.$siteLinks = $( '.wikibase-sitelinkgrouplistview', this.element );
		if( this.$siteLinks.length === 0 ) {
			this.$siteLinks = $( '<div/>' ).appendTo( this.element );
		}
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_init: function() {
		this._initStatements();
		this._initSiteLinks();
		PARENT.prototype._init.call( this );
	},

	/**
	 * @protected
	 */
	_initStatements: function() {
		var claimGuidGenerator = new wb.utilities.ClaimGuidGenerator( this.options.value.getId() );

		this.$statements
		.statementgrouplistview( {
			value: this.options.value.getStatements(),
			claimGuidGenerator: claimGuidGenerator,
			dataTypeStore: this.option( 'dataTypeStore' ),
			entityStore: this.options.entityStore,
			entityIdHtmlFormatter: this.options.entityIdHtmlFormatter,
			entityIdPlainFormatter: this.options.entityIdPlainFormatter,
			valueViewBuilder: this.options.valueViewBuilder,
			entityChangersFactory: this.options.entityChangersFactory
		} )
		.statementgrouplabelscroll();

		// This is here to be sure there is never a duplicate id:
		$( '.wikibase-statementgrouplistview' )
		.prev( '.wb-section-heading' )
		.first()
		.attr( 'id', 'claims' );
	},

	/**
	 * @protected
	 */
	_initSiteLinks: function() {
		this.$siteLinks.sitelinkgrouplistview( {
			value: this.options.value.getSiteLinks(),
			siteLinksChanger: this.options.entityChangersFactory.getSiteLinksChanger(),
			entityIdPlainFormatter: this.options.entityIdPlainFormatter
		} );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_attachEventHandlers: function() {
		PARENT.prototype._attachEventHandlers.call( this );

		var self = this;

		this.element
		.on( [
			'statementviewafterstartediting.' + this.widgetName,
			'referenceviewafterstartediting.' + this.widgetName,
			'sitelinkgroupviewafterstartediting.' + this.widgetName
		].join( ' ' ),
		function( event ) {
			self._trigger( 'afterstartediting' );
		} );

		this.element
		.on( [
			'statementlistviewafterremove.' + this.widgetName,
			'statementviewafterstopediting.' + this.widgetName,
			'statementviewafterremove.' + this.widgetName,
			'referenceviewafterstopediting.' + this.widgetName,
			'sitelinkgroupviewafterstopediting.' + this.widgetName
		].join( ' ' ),
		function( event, dropValue ) {
			self._trigger( 'afterstopediting', null, [dropValue] );
		} );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_setState: function( state ) {
		PARENT.prototype._setState.call( this, state );

		this.$statements.data( 'statementgrouplistview' )[state]();
		// TODO: Resolve integration of referenceviews
		this.$statements.find( '.wb-statement-references' ).each( function() {
			var $listview = $( this ).children( ':wikibase-listview' );
			if( $listview.length ) {
				$listview.data( 'listview' )[state]();
			}
		} );

		this.$siteLinks.data( 'sitelinkgrouplistview' )[state]();
	}
} );

$.wikibase.entityview.TYPES.push( $.wikibase.itemview.prototype.widgetName );

}( jQuery, wikibase ) );
