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
 * @since 0.5
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
$.widget( 'wikibase.itemview', PARENT, {
	/**
	 * @property {jQuery}
	 * @protected
	 */
	$statements: null,

	/**
	 * @inheritdoc
	 * @protected
	 */
	_create: function() {
		this._initStatements();
		this._initSiteLinks();
		this._initEntityview();
	},

	/**
	 * @protected
	 */
	_initStatements: function() {
		this.$statements = $( '.wikibase-statementgrouplistview', this.element ).first();
		if( this.$statements.length === 0 ) {
			this.$statements = $( '<div/>' ).appendTo( this.element );
		}

		var claimGuidGenerator = new wb.utilities.ClaimGuidGenerator( this.options.value.getId() );

		this.$statements
		.statementgrouplistview( {
			value: this.options.value.getStatements(),
			claimGuidGenerator: claimGuidGenerator,
			dataTypeStore: this.option( 'dataTypeStore' ),
			entityType: this.options.value.getType(),
			entityStore: this.options.entityStore,
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
		var self = this,
			value = [];

		this.$siteLinks = $( '.wikibase-sitelinkgrouplistview', this.element );

		if( this.$siteLinks.length ) {
			value = scrapeSiteLinks( this.$siteLinks, this.options.value.getSiteLinks() );
		} else {
			this.$siteLinks = $( '<div/>' ).appendTo( this.element );
			value = orderSiteLinksByGroup( this.options.value.getSiteLinks() );
		}

		this.$siteLinks.sitelinkgrouplistview( {
			value: value,
			entityId: self.options.value.getId(),
			siteLinksChanger: self.options.entityChangersFactory.getSiteLinksChanger(),
			entityStore: self.options.entityStore
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

/**
 * Scrapes site links from static HTML in order to be sure the order in the static HTML matches the
 * order set on the widget initialized on the HTML structure since that widget is not supposed to
 * re-render the HTML for performance reasons.
 * @ignore
 *
 * @param {jQuery} $siteLinks
 * @param {wikibase.datamodel.SiteLinkSet} siteLinkSet
 * @return {Object}
 */
function scrapeSiteLinks( $siteLinks, siteLinkSet ) {
	var value = [];

	$siteLinks.find( '.wikibase-sitelinkgroupview' ).each( function() {
		var $sitelinkgroupview = $( this ),
			$sitelinklistview = $sitelinkgroupview.find( '.wikibase-sitelinklistview' ),
			group = $sitelinkgroupview.data( 'wb-sitelinks-group' ),
			siteIdsOfGroup = [],
			siteLinkIds = siteLinkSet.getKeys(),
			siteLinksOfGroup = [];

		$sitelinklistview.find( '.wikibase-sitelinkview' ).each( function() {
			siteIdsOfGroup.push( $( this ).data( 'wb-siteid' ) );
		} );

		for( var i = 0; i < siteIdsOfGroup.length; i++ ) {
			for( var j = 0; j < siteLinkIds.length; j++ ) {
				if( siteLinkIds[j] === siteIdsOfGroup[i] ) {
					siteLinksOfGroup.push( siteLinkSet.getItemByKey( siteLinkIds[j] ) );
					break;
				}
			}
		}

		value.push( {
			group: group,
			siteLinks: siteLinksOfGroup
		} );
	} );

	return value;
}

/**
 * Maps site links of a `wikibase.datamodel.SiteLinkSet` to their Wikibase site groups.
 * @ignore
 *
 * @param {wikibase.datamodel.SiteLinkSet} siteLinkSet
 * @return {Object}
 */
function orderSiteLinksByGroup( siteLinkSet ) {
	var value = [];

	siteLinkSet.each( function( siteId, siteLink ) {
		var site = wb.sites.getSite( siteId ),
			found = false;

		if( !site ) {
			throw new Error( 'Site with id ' + siteId + ' is not registered' );
		}

		for( var i = 0; i < value.length; i++ ) {
			if( value[i].group === site.getGroup() ) {
				value[i].siteLinks.push( siteLink );
				found = true;
				break;
			}
		}

		if( !found ) {
			value.push( {
				group: site.getGroup(),
				siteLinks: [siteLink]
			} );
		}
	} );

	return value;
}

$.wikibase.entityview.TYPES.push( $.wikibase.itemview.prototype.widgetName );

}( jQuery, wikibase ) );
