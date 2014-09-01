/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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
 */
$.widget( 'wikibase.entityview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget
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
		api: null
	},

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

		this._initLabel();
		this._initDescription();
		this._initAliases();
		this._initClaims();
		this._initSiteLinks();

		this._handleEditModeAffairs();
	},

	_initLabel: function() {
		this.$label = $( '.wb-firstHeading .wikibase-labelview', this.element );
		if( !this.$label.length ) {
			this.$label = mw.template( 'wikibase-h1',
					this.options.value.getId(),
					$( '<div/>' )
				).appendTo( this.element );
		}

		this.$label.labelview( {
			value: {
				language: mw.config.get( 'wgUserLanguage' ),
				label: $( '.wikibase-labelview' ).hasClass( 'wb-empty' )
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
		this.$description = $( '.wikibase-descriptionview', this.element );
		if( !this.$description.length ) {
			this.$description = $( '<div/>' ).appendTo( this.element );
		}

		this.$description.descriptionview( {
			value: {
				language: mw.config.get( 'wgUserLanguage' ),
				description: $( '.wikibase-descriptionview', this.element ).hasClass( 'wb-empty' )
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
		this.$aliases = $( '.wikibase-aliasesview', this.element );
		if( !this.$aliases.length ) {
			this.$aliases = $( '<div/>' ).appendTo( this.element );
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

	_initClaims: function() {
		this.$claims = $( '.wb-claimgrouplistview', this.element ).first();
		if( this.$claims.length === 0 ) {
			this.$claims = $( '<div/>' ).appendTo( this.element );
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

		this.$siteLinks = $( '.wikibase-sitelinkgroupview', this.element );

		this.$siteLinks.each( function() {
			var $sitelinklistview = $( this ),
				siteIdsOfGroup = [];

			$sitelinklistview.find( '.wikibase-sitelinkview' ).each( function() {
				siteIdsOfGroup.push( $( this ).data( 'wb-siteid' ) );
			} );

			$sitelinklistview.toolbarcontroller( {
				addtoolbar: ['sitelinklistview'],
				edittoolbar: ['sitelinkview']
			} );

			// TODO: Implement sitelinkgrouplistview to manage sitelinklistview widgets
			var group = $( this ).data( 'wb-sitelinks-group' ),
				siteLinks = self.options.value.getSiteLinks(),
				siteLinksOfGroup = [];

			for( var i = 0; i < siteIdsOfGroup.length; i++ ) {
				for( var j = 0; j < siteLinks.length; j++ ) {
					if( siteLinks[j].getSiteId() === siteIdsOfGroup[i] ) {
						siteLinksOfGroup.push( siteLinks[j] );
						break;
					}
				}
			}

			$( this ).sitelinkgroupview( {
				value: {
					group: group,
					siteLinks: siteLinksOfGroup
				},
				entityId: self.options.value.getId(),
				api: self.options.api,
				entityStore: self.options.entityStore
			} );
		} );
	},

	/**
	 * Will make this edit view keeping track over global edit mode and triggers global edit mode
	 * in case any member of this entity view enters edit mode.
	 * @since 0.3
	 */
	_handleEditModeAffairs: function() {
		var self = this;
		/**
		 * Helper which returns a handler for global edit mode event to disable/enable this entity
		 * view's toolbars but not the one of the edit widget currently active.
		 *
		 * @param {string} action
		 * @return {function}
		 */
		var toolbarStatesSetter = function( action ) {
			function findToolbars( $range ) {
				var $wbToolbars = $range.find( '.wikibase-toolbar' ),
					$wbToolbarGroups = $wbToolbars.find( $wbToolbars );

				return $wbToolbars
					// Filter out toolbar groups:
					.not( $wbToolbarGroups )
					// Re-add "new UI" toolbars:
					// TODO Improve selection mechanism as soon as old UI classes have
					//  converted or get rid of this toolbarStatesSetter.
					.add( $wbToolbarGroups.filter(
						function() {
							var $toolbarNode = $( this.parentNode.parentNode );
							return $toolbarNode.hasClass( 'wb-edittoolbar' )
								|| $toolbarNode.hasClass( 'wb-removetoolbar' )
								|| $toolbarNode.hasClass( 'wb-addtoolbar' );
						}
					) );
			}

			return function( event, origin, options ) {
				// TODO: at some point, this should rather disable/enable the widgets for editing,
				//       there could be other ways for entering edit mode than using the toolbar!

				// Whether action shall influence sub-toolbars of origin:
				// TODO: "exclusive" option/variable restricts arrangement of toolbars. Interaction
				//       between toolbars should be managed via the toolbar controller.
				var originToolbars = null;
				if ( options ) {
					if ( options.exclusive === false ) {
						originToolbars = findToolbars( $( origin ) );
					} else if ( typeof options.exclusive === 'string' ) {
						originToolbars = $( origin ).find( options.exclusive );
					}
				}

				// find and disable/enable all toolbars in this edit view except,...
				findToolbars( self.element ).each( function() {
					var $toolbar = $( this ),
						toolbar = $toolbar.data( 'toolbar' );
					// ... don't disable toolbar if it has an edit group which is in edit mode
					// or if the toolbar is a sub-element of the origin.
					if (
						$toolbar.children( '.wikibase-toolbareditgroup-ineditmode' ).length === 0
						&& ( !originToolbars || $.inArray( this, originToolbars ) === -1 )
						// Checking if toolbar is defined is done for the purpose of debugging only;
						// Toolbar may only be undefined under some weird circumstances, e.g. when
						// doing $( 'body' ).empty() for debugging.
						&& toolbar
					) {
						toolbar[ action ]();
					}
				} );
			};
		};

		// disable/enable all toolbars when starting/ending an edit mode:
		// TODO: Resolve logic
		$( wb )
		.on( 'startItemPageEditMode', toolbarStatesSetter( 'disable' ) )
		.on( 'stopItemPageEditMode', toolbarStatesSetter( 'enable' ) )
		.on( 'startItemPageEditMode', function( event, target, options ) {
			$( ':wikibase-labelview, :wikibase-descriptionview, :wikibase-aliasesview' )
			.not( target )
			.find( ':wikibase-toolbar' )
			.each( function() {
				$( this ).data( 'toolbar' ).disable();
			} );
		} )
		.on( 'stopItemPageEditMode', function( event, target, options ) {
			$( ':wikibase-aliasesview' ).find( ':wikibase-toolbar' ).each( function() {
				$( this ).data( 'toolbar' ).enable();
			} );
			$( ':wikibase-labelview' ).each( function() {
				var $labelview = $( this ),
					labelview = $labelview.data( 'labelview' );

				if( labelview.value().label ) {
					$labelview.find( ':wikibase-toolbar' ).each( function() {
						$( this ).data( 'toolbar' ).enable();
					} );
				}
			} );
			$( ':wikibase-descriptionview' ).each( function() {
				var $descriptionview = $( this ),
					descriptionview = $descriptionview.data( 'descriptionview' );

				if( descriptionview.value().description ) {
					$descriptionview.find( ':wikibase-toolbar' ).each( function() {
						$( this ).data( 'toolbar' ).enable();
					} );
				}
			} );

			$( ':wikibase-sitelinklistview' ).each( function() {
				var $sitelinklistview = $( this ),
					sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

				$sitelinklistview.data( 'addtoolbar' ).toolbar[sitelinklistview.isFull()
					? 'disable'
					: 'enable'
				]();

				$sitelinklistview.find( 'tbody :wikibase-toolbar' ).each( function() {
					$( this ).data( 'toolbar' ).enable();
				} );
			} );
		} );

		// if any of the snaks enters edit mode, trigger global edit mode. This is necessary for
		// compatibility with old PropertyEditTool which is still used for label, description etc.
		// TODO: this should rather listen to 'valueviewstartediting' once implemented!
		$( this.element )
		.on( 'statementviewafterstartediting', function( event ) {
			$( wb ).trigger( 'startItemPageEditMode', [
				event.target,
				{
					exclusive: '.wb-claim-qualifiers .wikibase-toolbar',
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'referenceviewafterstartediting', function( event ) {
			$( wb ).trigger( 'startItemPageEditMode', [
				event.target,
				{
					exclusive: false,
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'snakviewstopediting', function( event, dropValue ) {
			// snak view got already removed from the DOM on "snakviewafterstopediting"
			if ( dropValue ) {
				// Return true on dropValue === false as well as dropValue === undefined
				$( wb ).trigger( 'stopItemPageEditMode', [
					event.target,
					{ save: dropValue !== true }
				] );
			}
		} )
		.on( 'statementviewafterstopediting claimlistviewafterremove '
				+ 'referenceviewafterstopediting statementviewafterremove',
			function( event, dropValue ) {
				// Return true on dropValue === false as well as dropValue === undefined
				$( wb ).trigger( 'stopItemPageEditMode', [
					event.target,
					{ save: dropValue !== true }
				] );
			}
		);
	}
} );

}( wikibase, jQuery, mediaWiki ) );
