/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, mw ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying an entire wikibase entity.
 * @since 0.3
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {wikibase.datamodel.Entity} [value]
 * @option {wikibase.store.EntityStore} entityStore
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
 * @option {wikibase.AbstractedRepoApi} api
 * @option {string[]} languages
 *
 * @event afterstartediting
 *        Triggered after the widget has switched to edit mode.
 *        - {jQuery.Event}
 *
 * @event afterstopediting
 *        Triggered after the widget has left edit mode.
 *        - {jQuery.Event}
 *        - {boolean} Whether the pending value has been dropped (editing has been cancelled).
 */
$.widget( 'wikibase.entityview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-entityview',
		templateParams: [
			'', // entity type
			'', // entity id
			'', // language code
			'', // language direction
			'' // content
		],
		templateShortCuts: {},
		value: null,
		entityStore: null,
		valueViewBuilder: null,
		api: null,
		languages: []
	},

	/**
	 * @type {jQuery}
	 */
	$toc: null,

	/**
	 * @type {jQuery}
	 */
	$label: null,

	/**
	 * @type {jQuery}
	 */
	$description: null,

	/**
	 * @type {jQuery}
	 */
	$aliases: null,

	/**
	 * @type {jQuery|null}
	 */
	$fingerprints: null,

	/**
	 * @type {jQuery}
	 */
	$claims: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 *
	 * @throws {Error} if a required options is missing.
	 */
	_create: function() {
		if(
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.api
		) {
			throw new Error( 'Required option(s) missing' );
		}

		this.$toc = $( '.toc', this.element );

		this._initLabel();
		this._initDescription();
		this._initAliases();
		this._initFingerprints();

		// TODO: Have an itemview and propertyview instead of ugly hack here.
		if ( this.options.value.getType() === 'item' ) {
			this._initClaims();
			this._initSiteLinks();
		}

		this._attachEventHandlers();
	},

	_initLabel: function() {
		// TODO: Allow initializing entitview on empty DOM
		this.$label = $( '.wb-firstHeading .wikibase-labelview', this.element ).first();
		if( !this.$label.length ) {
			this.$label = mw.template( 'wikibase-h1',
					this.options.value.getId(),
					$( '<div>' )
				).appendTo( this.element );
		}

		this.$label.labelview( {
			value: {
				language: mw.config.get( 'wgUserLanguage' ),
				label: this.$label.hasClass( 'wb-empty' )
					? null
					// FIXME: entity object should not contain fallback strings
					: this.options.value.getLabel( mw.config.get( 'wgUserLanguage' ) )
			},
			helpMessage: mw.msg(
				'wikibase-description-input-help-message',
				wb.getLanguageNameByCode( mw.config.get( 'wgUserLanguage' ) )
			),
			entityId: this.options.value.getId(),
			api: this.options.api,
			showEntityId: true
		} );
	},

	_initDescription: function() {
		this.$description = $( '.wikibase-descriptionview', this.element ).first();
		if( !this.$description.length ) {
			this.$description = $( '<div>' ).appendTo( this.element );
		}

		this.$description.descriptionview( {
			value: {
				language: mw.config.get( 'wgUserLanguage' ),
				description: this.$description.hasClass( 'wb-empty' )
					? null
					// FIXME: entity object should not contain fallback strings
					: this.options.value.getDescription( mw.config.get( 'wgUserLanguage' ) )
			},
			helpMessage: mw.msg(
				'wikibase-description-input-help-message',
				wb.getLanguageNameByCode( mw.config.get( 'wgUserLanguage' ) )
			),
			entityId: this.options.value.getId(),
			api: this.options.api
		} );
	},

	_initAliases: function() {
		this.$aliases = $( '.wikibase-aliasesview', this.element ).first();
		if( !this.$aliases.length ) {
			this.$aliases = $( '<div>' ).appendTo( this.element );
		}

		this.$aliases.aliasesview( {
			value: {
				language:  mw.config.get( 'wgUserLanguage' ),
				aliases: this.options.value.getAliases( mw.config.get( 'wgUserLanguage' ) )
			},
			entityId: this.options.value.getId(),
			api: this.options.api
		} );
	},

	_initFingerprints: function() {
		if( !this.options.languages.length ) {
			return;
		}

		this.$fingerprints = $( '.wikibase-fingerprintgroupview' );

		if( !this.$fingerprints.length ) {
			var $precedingNode = this.$toc;

			if( !$precedingNode.length ) {
				$precedingNode = $( '.wikibase-aliasesview' );
			} else {
				this._addTocItem(
					'#wb-terms',
					mw.msg( 'wikibase-terms' ),
					this.$toc.find( 'li' ).first()
				);
			}

			this.$fingerprints = $( '<div>' ).insertAfter( $precedingNode );
		}

		var value = [];
		for( var i = 0; i < this.options.languages.length; i++ ) {
			value.push( {
				language: this.options.languages[i],
				label: this.options.value.getLabel( this.options.languages[i] ) || null,
				description: this.options.value.getDescription( this.options.languages[i] ) || null,
				aliases: this.options.value.getAliases( this.options.languages[i] ) || []
			} );
		}

		this.$fingerprints.fingerprintgroupview( {
			value: value,
			entityId: this.options.value.getId(),
			api: this.options.api,
			helpMessage: mw.msg( 'wikibase-fingerprintgroupview-input-help-message' )
		} );
	},

	_initClaims: function() {
		this.$claims = $( '.wb-claimgrouplistview', this.element ).first();
		if( this.$claims.length === 0 ) {
			this.$claims = $( '<div>' ).appendTo( this.element );
		}

		this.$claims
		.claimgrouplistview( {
			value: this.options.value.getClaims(),
			entityType: this.options.value.getType(),
			entityStore: this.options.entityStore,
			valueViewBuilder: this.options.valueViewBuilder,
			api: this.options.api
		} )
		.claimgrouplabelscroll();

		// This is here to be sure there is never a duplicate id:
		$( '.wb-claimgrouplistview' )
		.prev( '.wb-section-heading' )
		.first()
		.attr( 'id', 'claims' );
	},

	_initSiteLinks: function() {
		var self = this;

		this.$siteLinks = $( '.wikibase-sitelinkgrouplistview', this.element );

		if( this.$siteLinks.length === 0 ) {
			// Properties for example don't have sitelinks
			return;
		}

		// Scrape group and site link order from existing DOM:
		var value = [];
		this.$siteLinks.find( '.wikibase-sitelinkgroupview' ).each( function() {
			var $sitelinkgroupview = $( this ),
				$sitelinklistview = $sitelinkgroupview.find( '.wikibase-sitelinklistview' ),
				group = $sitelinkgroupview.data( 'wb-sitelinks-group' ),
				siteIdsOfGroup = [],
				siteLinks = self.options.value.getSiteLinks(),
				siteLinksOfGroup = [];

			$sitelinklistview.find( '.wikibase-sitelinkview' ).each( function() {
				siteIdsOfGroup.push( $( this ).data( 'wb-siteid' ) );
			} );

			for( var i = 0; i < siteIdsOfGroup.length; i++ ) {
				for( var j = 0; j < siteLinks.length; j++ ) {
					if( siteLinks[j].getSiteId() === siteIdsOfGroup[i] ) {
						siteLinksOfGroup.push( siteLinks[j] );
						break;
					}
				}
			}

			value.push( {
				group: group,
				siteLinks: siteLinksOfGroup
			} );
		} );

		this.$siteLinks.sitelinkgrouplistview( {
			value: value,
			entityId: self.options.value.getId(),
			api: self.options.api,
			entityStore: self.options.entityStore
		} );
	},

	_attachEventHandlers: function() {
		var self = this;

		this.element
		.on( [
			'labelviewafterstartediting.' + this.widgetName,
			'descriptionviewafterstartediting.' + this.widgetName,
			'aliasesviewafterstartediting.' + this.widgetName,
			'fingerprintgroupviewafterstartediting.' + this.widgetName,
			'claimviewafterstartediting.' + this.widgetName,
			'statementviewafterstartediting.' + this.widgetName,
			'referenceviewafterstartediting.' + this.widgetName,
			'sitelinkgroupviewafterstartediting.' + this.widgetName
		].join( ' ' ),
		function( event ) {
			var widgetName = event.type.replace( /afterstartediting/, '' );

			self._disable( $( event.target ).data( widgetName ) );

			self._trigger( 'afterstartediting' );
		} );

		this.element
		.on( [
			'labelviewafterstopediting.' + this.widgetName,
			'descriptionviewafterstopediting.' + this.widgetName,
			'aliasesviewafterstopediting.' + this.widgetName,
			'fingerprintgroupviewafterstopediting.' + this.widgetName,
			'claimlistviewafterremove.' + this.widgetName,
			'claimviewafterstopediting.' + this.widgetName,
			'statementviewafterstopediting.' + this.widgetName,
			'statementviewafterremove.' + this.widgetName,
			'referenceviewafterstopediting.' + this.widgetName,
			'sitelinkgroupviewafterstopediting.' + this.widgetName
		].join( ' ' ),
		function( event, dropValue ) {
			self.enable();

			self._trigger( 'afterstopediting', null, [dropValue] );
		} );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.disable
	 *
	 * @param {jQuery.Widget} [exceptWidget]
	 */
	_disable: function( exceptWidget ) {
		if( exceptWidget ) {
			this._setState( 'disable' );
			exceptWidget.enable();
			return;
		}

		this.disable();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this._setState( value ? 'disable' : 'enable' );
		}

		return response;
	},

	/**
	 * @param {string} state
	 */
	_setState: function( state ) {
		this.$label.data( 'labelview' )[state]();
		this.$description.data( 'descriptionview' )[state]();
		this.$aliases.data( 'aliasesview' )[state]();
		if( this.$fingerprints ) {
			this.$fingerprints.data( 'fingerprintgroupview' )[state]();
		}

		// horrible, horrible hack until we have proper item and property views
		if( this.$claims ) {
			this.$claims.data( 'claimgrouplistview' )[state]();
			// TODO: Resolve integration of referenceviews
			this.$claims.find( '.wb-statement-references' ).each( function() {
				var $listview = $( this ).children( ':wikibase-listview' );
				if( $listview.length ) {
					$listview.data( 'listview' )[state]();
				}
			} );
		}

		if( this.$siteLinks && this.$siteLinks.length > 0 ) {
			this.$siteLinks.data( 'sitelinkgrouplistview' )[state]();
		}
	},

	/**
	 * Adds an item to the table of contents.
	 *
	 * @param {string} href
	 * @param {string} text
	 * @param {jQuery} [$insertBefore] Omit to have the item inserted at the end
	 */
	_addTocItem: function( href, text, $insertBefore ) {
		if( !this.$toc.length ) {
			return;
		}

		var $li = $( '<li>' )
			.addClass( 'toclevel-1' )
			.append( $( '<a>' ).attr( 'href', href ).text( text ) );

		if( $insertBefore ) {
			$li.insertBefore( $insertBefore );
		} else {
			this.$toc.append( $li );
		}

		this.$toc.find( 'li' ).each( function( i, li ) {
			$( li )
			.removeClass( 'tocsection-' + i )
			.addClass( 'tocsection-' + ( i + 1 ) );
		} );
	}
} );

}( wikibase, jQuery, mediaWiki ) );
