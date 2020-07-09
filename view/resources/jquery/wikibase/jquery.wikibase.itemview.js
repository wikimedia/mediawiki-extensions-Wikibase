( function ( wb ) {
	'use strict';

	var PARENT = $.wikibase.entityview;

	/**
	 * View for displaying a Wikibase `Item`.
	 *
	 * @see wikibase.datamodel.Item
	 * @class jQuery.wikibase.itemview
	 * @extends jQuery.wikibase.entityview
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {Object} options
	 * @param {Function} options.buildSitelinkGroupListView
	 * @param {Function} options.buildStatementGroupListView
	 *
	 * @constructor
	 */
	$.widget( 'wikibase.itemview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			buildSitelinkGroupListView: null,
			buildStatementGroupListView: null
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
		_create: function () {
			this._createEntityview();

			this.$statements = $( '.wikibase-statementgrouplistview', this.element );
			if ( this.$statements.length === 0 ) {
				this.$statements = $( '<div>' ).appendTo( this.$main );
			}

			this.$siteLinks = $( '.wikibase-sitelinkgrouplistview', this.element );
			if ( this.$siteLinks.length === 0 ) {
				this.$siteLinks = $( '<div>' ).appendTo( this.$side );
			}
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function () {
			if ( !this.options.buildSitelinkGroupListView ||
				!this.options.buildStatementGroupListView
			) {
				throw new Error( 'Required option(s) missing' );
			}

			this._initStatements();
			this._initSiteLinks();
			PARENT.prototype._init.call( this );
		},

		/**
		 * @protected
		 */
		_initStatements: function () {
			this.options.buildStatementGroupListView( this.options.value, this.$statements );

			// This is here to be sure there is never a duplicate id:
			$( '.wikibase-statementgrouplistview' )
			.prev( '.wb-section-heading' )
			.first()
			.attr( 'id', 'claims' );
		},

		/**
		 * @protected
		 */
		_initSiteLinks: function () {
			this.options.buildSitelinkGroupListView( this.options.value.getSiteLinks(), this.$siteLinks );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_attachEventHandlers: function () {
			PARENT.prototype._attachEventHandlers.call( this );

			var self = this;

			this.element
			.on( [
				'statementviewafterstartediting.' + this.widgetName,
				'referenceviewafterstartediting.' + this.widgetName,
				'sitelinkgroupviewafterstartediting.' + this.widgetName
			].join( ' ' ),
			function ( event ) {
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
			function ( event, dropValue ) {
				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_setState: function ( state ) {
			PARENT.prototype._setState.call( this, state );

			this.$statements.data( 'statementgrouplistview' )[ state ]();
			this.$siteLinks.data( 'sitelinkgrouplistview' )[ state ]();
		}
	} );

	$.wikibase.entityview.TYPES.push( $.wikibase.itemview.prototype.widgetName );

}( wikibase ) );
