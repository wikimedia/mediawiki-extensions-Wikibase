/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Manages a sitelinklistview widget specific to a particular site link group.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object} value
 *         Object representing the widget's value.
 *         Structure: { group: <{string}>, siteLinks: <{wikibase.datamodel.SiteLink[]}> }
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
 *
 * @option {wikibase.store.EntityStore} entityStore
 */
$.widget( 'wikibase.sitelinkgroupview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-sitelinkgroupview',
		templateParams: [
			'', // id
			'', // heading
			'', // counter
			'', // sitelinklistview
			'' // group
		],
		templateShortCuts: {
			'$h': 'h2',
			'$counter': '.wikibase-sitelinkgroupview-counter'
		},
		value: null,
		entityId: null,
		api: null,
		entityStore: null
	},

	/**
	 * @type {jQuery}
	 */
	$sitelinklistview: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 *
	 * @throws {Error} if required parameters are not specified properly.
	 */
	_create: function() {
		if( !this.options.entityId || !this.options.api || !this.options.entityStore ) {
			throw new Error( 'Required parameter(s) missing' );
		}

		this.options.value = this._checkValue( this.options.value );

		PARENT.prototype._create.call( this );

		// TODO: Remove scraping
		this.__headingText = this.$h.text();

		this.$sitelinklistview = this.element.find( '.wikibase-sitelinklistview' );

		if( !this.$sitelinklistview.length ) {
			this.$sitelinklistview = $( '<table/>' ).appendTo( this.element );
		}

		this.$sitelinklistview.sitelinklistview( {
			value: this._getSiteLinksOfGroup(),
			allowedSiteIds: this.options.value
				? getSiteIdsOfGroup( this.options.value.group )
				: [],
			entityId: this.options.entityId,
			api: this.options.api,
			entityStore: this.options.entityStore,
			$counter: this.$counter
		} );

		this._update();
	},

	/**
	 * @return {wikibase.datamodel.SiteLink[]}
	 */
	_getSiteLinksOfGroup: function() {
		var self = this;

		if( !this.options.value ) {
			return [];
		}

		return $.grep( this.options.value.siteLinks, function( siteLink ) {
			return $.inArray(
				siteLink.getSiteId(),
				getSiteIdsOfGroup( self.options.value.group )
			) !== -1;
		} );
	},

	/**
	 * @param {*} value
	 * @return {Object}
	 *
	 * @throws {Error} if value is not defined properly.
	 */
	_checkValue: function( value ) {
		if( !$.isPlainObject( value ) ) {
			throw new Error( 'Value needs to be an object' );
		} else if( !value.group ) {
			throw new Error( 'Value needs group id to be specified' );
		}

		if( !value.siteLinks ) {
			value.siteLinks = [];
		}

		return value;
	},

	/**
	 * Sets/Gets the widget's value.
	 *
	 * @param {Object} [value]
	 * @return {Object|*}
	 */
	value: function( value ) {
		if( value === undefined ) {
			return this.option( 'value' );
		}
		return this.option( 'value', value );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			value = this._checkValue( value );
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if( key === 'value' ) {
			this.$sitelinklistview.data( 'sitelinklistview' )
			.option( 'allowedSiteIds', getSiteIdsOfGroup( this.options.value.group ) )
			.value( this.options.value.siteLinks );

			this._update();
		} else if( key === 'disabled' ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).option( key, value );
		}

		return response;
	},

	/**
	 * Updates the widget's group references.
	 */
	_update: function() {
		this.element.data( 'group', this.options.value.group );

		this.$h
		.attr( 'id', 'sitelinks-' + this.options.value.group )
//		.text( mw.msg( 'wikibase-sitelinks-' + this.options.value.group ) )
		.text( this.__headingText )
		.append( this.$counter );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		if( this.$sitelinklistview ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).destroy();
		}
		PARENT.prototype.destroy.call( this );
	}

} );

/**
 * @param {string} group
 * @return {string[]}
 */
function getSiteIdsOfGroup( group ) {
	var siteIds = [];
	$.each( wb.sites.getSitesOfGroup( group ), function( siteId, site ) {
		siteIds.push( siteId );
	} );
	return siteIds;
}

}( jQuery, mediaWiki, wikibase ) );
