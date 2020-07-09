/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * Scrapes site links from static HTML in order to be sure the order in the static HTML matches the
	 * order set on the widget initialized on the HTML structure since that widget is not supposed to
	 * re-render the HTML for performance reasons.
	 *
	 * @ignore
	 *
	 * @param {jQuery} $siteLinks
	 * @param {datamodel.SiteLinkSet} siteLinkSet
	 * @return {Object}
	 */
	function scrapeSiteLinks( $siteLinks, siteLinkSet ) {
		var value = [];

		$siteLinks.find( '.wikibase-sitelinkgroupview' ).each( function () {
			var $sitelinkgroupview = $( this ),
				$sitelinklistview = $sitelinkgroupview.find( '.wikibase-sitelinklistview' ),
				group = $sitelinkgroupview.data( 'wb-sitelinks-group' ),
				siteIdsOfGroup = [],
				siteLinkIds = siteLinkSet.getKeys(),
				siteLinksOfGroup = [];

			$sitelinklistview.find( '.wikibase-sitelinkview' ).each( function () {
				siteIdsOfGroup.push( $( this ).data( 'wb-siteid' ) );
			} );

			for ( var i = 0; i < siteIdsOfGroup.length; i++ ) {
				for ( var j = 0; j < siteLinkIds.length; j++ ) {
					if ( siteLinkIds[ j ] === siteIdsOfGroup[ i ] ) {
						siteLinksOfGroup.push( siteLinkSet.getItemByKey( siteLinkIds[ j ] ) );
						break;
					}
				}
			}

			value.push( {
				group: group,
				siteLinks: new datamodel.SiteLinkSet( siteLinksOfGroup )
			} );
		} );

		return value;
	}

	/**
	 * Maps site links of a `datamodel.SiteLinkSet` to their Wikibase site groups.
	 *
	 * @ignore
	 *
	 * @param {datamodel.SiteLinkSet} siteLinkSet
	 * @return {Object}
	 */
	function orderSiteLinksByGroup( siteLinkSet ) {
		var value = [];

		siteLinkSet.each( function ( siteId, siteLink ) {
			var site = wb.sites.getSite( siteId ),
				found = false;

			if ( !site ) {
				mw.log.warn( 'Site with id ' + siteId + ' is not registered, but used' );
				return;
			}

			for ( var i = 0; i < value.length; i++ ) {
				if ( value[ i ].group === site.getGroup() ) {
					value[ i ].siteLinks.addItem( siteLink );
					found = true;
					break;
				}
			}

			if ( !found ) {
				value.push( {
					group: site.getGroup(),
					siteLinks: new datamodel.SiteLinkSet( [ siteLink ] )
				} );
			}
		} );

		return value;
	}

	var PARENT = $.ui.TemplatedWidget;

	/**
	 * Encapsulates multiple sitelinkgroupview widgets.
	 *
	 * @extends jQuery.ui.TemplatedWidget
	 *
	 * @option {jQuery.wikibase.listview.ListItemAdapter} listItemAdapter
	 * @option {datamodel.SiteLinkSet} value
	 */
	$.widget( 'wikibase.sitelinkgrouplistview', PARENT, {
		options: {
			template: 'wikibase-sitelinkgrouplistview',
			templateParams: [
				'' // sitelinklistview(s)
			],
			templateShortCuts: {},
			value: null
		},

		/**
		 * @type {jQuery}
		 */
		$listview: null,

		/**
		 * @see jQuery.ui.TemplatedWidget._create
		 */
		_create: function () {
			if ( !this.options.listItemAdapter || !this.options.value ) {
				throw new Error( 'Required option(s) missing' );
			}

			PARENT.prototype._create.call( this );

			this._createListview();
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			if ( this.$listview ) {
				var listview = this.$listview.data( 'listview' );
				if ( listview ) {
					listview.destroy();
				}
				this.$listview.remove();
				delete this.$listview;
			}
			PARENT.prototype.destroy.call( this );
		},

		_createListview: function () {
			var value = this.element.is( ':empty' )
				? orderSiteLinksByGroup( this.options.value )
				: scrapeSiteLinks( this.element, this.options.value );

			this.$listview = this.element.find( '.wikibase-listview' );

			if ( !this.$listview.length ) {
				this.$listview = $( '<div>' ).appendTo( this.element );
			}

			this.$listview
			.listview( {
				listItemAdapter: this.options.listItemAdapter,
				value: value,
				encapsulate: true
			} )
			.on( this.options.listItemAdapter.prefixedEvent( 'disable.' + this.widgetName ), function ( event ) {
				event.stopPropagation();
			} );
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 */
		_setOption: function ( key, value ) {
			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				this.$listview.data( 'listview' ).option( key, value );
			}

			return response;
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			var listview = this.$listview.data( 'listview' ),
				$items = listview.items();

			if ( $items.length ) {
				listview.listItemAdapter().liInstance( $items.first() ).focus();
			} else {
				this.element.trigger( 'focus' );
			}
		}
	} );

}( wikibase ) );
