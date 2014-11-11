/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * Manages a sitelinklistview widget specific to a particular site link group.
 * @since 0.5
 * @extends jQuery.ui.EditableTemplatedWidget
 *
 * @option {Object} value
 *         Object representing the widget's value.
 *         Structure: { group: <{string}>, siteLinks: <{wikibase.datamodel.SiteLink[]}> }
 *
 * @option {wikibase.entityChangers:SiteLinksChanger} siteLinksChanger
 *
 * @option {wikibase.store.EntityStore} entityStore
 *
 * @option {string} [helpMessage]
 *                  Default: 'Add a site link by specifying a site and a page of that site, edit or
 *                  remove existing site links.'
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
		entityStore: null,
		siteLinksChanger: null,
		helpMessage: 'Add a site link by specifying a site and a page of that site, edit or remove '
			+ 'existing site links.'
	},

	/**
	 * @type {jQuery}
	 */
	$sitelinklistview: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !this.options.siteLinksChanger || !this.options.entityStore ) {
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

		this.draw();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.destroy
	 */
	destroy: function() {
		if( this.$sitelinklistview ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).destroy();
		}
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.draw()
	 */
	draw: function() {
		var deferred = $.Deferred();

		this.element.data( 'group', this.options.value.group );

		this.$h
		.attr( 'id', 'sitelinks-' + this.options.value.group )
//		.text( mw.msg( 'wikibase-sitelinks-' + this.options.value.group ) )
		.text( this.__headingText )
		.append( this.$counter );

		if( !this.$sitelinklistview.data( 'sitelinklistview' ) ) {
			this._createSitelinklistview();
			deferred.resolve();
		} else {
// TODO: Have sitelinklistview derive from EditableTemplatedWidget
//			this.$sitelinklistview.draw()
//			.done( deferred.resolve )
//			.fail( deferred.reject );
			deferred.resolve();
		}

		return deferred.promise();
	},

	/**
	 * Creates and initializes the sitelinklistview widget.
	 */
	_createSitelinklistview: function() {
		var self = this,
			prefix = $.wikibase.sitelinklistview.prototype.widgetEventPrefix;

		this.$sitelinklistview
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			event.stopPropagation();
			self.setError( error );
		} )
		.on(
			[
				prefix + 'create.' + this.widgetName,
				prefix + 'afterstartediting.' + this.widgetName,
				prefix + 'stopediting.' + this.widgetName,
				prefix + 'afterstopediting.' + this.widgetName,
				prefix + 'disable.' + this.widgetName
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		)
		.sitelinklistview( {
			value: this._getSiteLinksOfGroup(),
			allowedSiteIds: this.options.value
				? getSiteIdsOfGroup( this.options.value.group )
				: [],
			entityStore: this.options.entityStore,
			siteLinksChanger: this.options.siteLinksChanger,
			$counter: this.$counter
		} );
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
	 * @see jQuery.ui.EditableTemplatedWidget.startEditing
	 */
	startEditing: function() {
		var self = this,
			deferred = $.Deferred();

		this.$sitelinklistview.one( 'sitelinklistviewafterstartediting', function() {
			PARENT.prototype.startEditing.call( self )
				.done( deferred.resolve )
				.fail( deferred.reject );
		} );

		this.$sitelinklistview.data( 'sitelinklistview' ).startEditing();

		return deferred.promise();
	},

	stopEditing: function( dropValue ) {
		var self = this,
			deferred = $.Deferred();

		if( !this.isInEditMode() || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return deferred.resolve().promise();
		}

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		this.$sitelinklistview
		.one(
			'sitelinklistviewafterstopediting.sitelinkgroupviewstopediting',
			function( event, dropValue ) {
				self._afterStopEditing( dropValue );
				self.$sitelinklistview.off( '.sitelinkgroupviewstopediting' );
				deferred.resolve();
			}
		)
		.one( 'sitelinklistviewtoggleerror.sitelinkgroupviewstopediting', function( event, error ) {
			self.enable();
			self.$sitelinklistview.off( '.sitelinkgroupviewstopediting' );
			deferred.reject( error );
		} );

		this.$sitelinklistview.data( 'sitelinklistview' ).stopEditing( dropValue );

		return deferred.promise();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.value
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
	 * @see jQuery.ui.EditableTemplatedWidget.isEmpty
	 */
	isEmpty: function() {
		return !this.value().siteLinks.length;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isValid
	 */
	isValid: function() {
		return this.$sitelinklistview.data( 'sitelinklistview' ).isValid();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isInitialValue
	 */
	isInitialValue: function() {
		return this.$sitelinklistview.data( 'sitelinklistview' ).isInitialValue();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		this.$sitelinklistview.data( 'sitelinklistview' ).focus();
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

			this.draw();
		} else if( key === 'disabled' ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).option( key, value );
		}

		return response;
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

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'sitelinkgroupview',
	selector: ':' + $.wikibase.sitelinkgroupview.prototype.namespace
		+ '-' + $.wikibase.sitelinkgroupview.prototype.widgetName,
	events: {
		sitelinkgroupviewcreate: function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$headingContainer = $sitelinkgroupview.find(
					'.wikibase-sitelinkgroupview-heading-container'
				),
				$container = $headingContainer.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $headingContainer );
			}

			$sitelinkgroupview.edittoolbar( {
				$container: $container,
				interactionWidget: sitelinkgroupview
			} );

			$sitelinkgroupview.on( 'keydown.edittoolbar', function( event ) {
				if( sitelinkgroupview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					sitelinkgroupview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					sitelinkgroupview.stopEditing( false );
				}
			} );

			$container.sticknode( {
				$container: sitelinkgroupview.$sitelinklistview.data( 'sitelinklistview' ).$thead
			} );
		},
		'sitelinkgroupviewchange sitelinkgroupviewafterstartediting': function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				edittoolbar = $sitelinkgroupview.data( 'edittoolbar' );

			if( !edittoolbar ) {
				return;
			}

			var btnSave = edittoolbar.getButton( 'save' ),
				enable = sitelinkgroupview.isValid() && !sitelinkgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		sitelinkgroupviewdisable: function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				edittoolbar = $sitelinkgroupview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = sitelinkgroupview.isValid() && !sitelinkgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

			if( !sitelinkgroupview ) {
				return;
			}

			sitelinkgroupview.focus();
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'sitelinkgroupview-sitelinkview',
	selector: ':' + $.wikibase.sitelinkgroupview.prototype.namespace
		+ '-' + $.wikibase.sitelinkgroupview.prototype.widgetName,
	events: {
		'sitelinkgroupviewafterstartediting sitelinkgroupviewchange': function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				sitelinklistviewListview = sitelinklistview.$listview.data( 'listview' );

			if( !$sitelinkgroupview.length || !sitelinkgroupview.isInEditMode() ) {
				return;
			}

			sitelinklistviewListview.items().each( function() {
				var $sitelinkview = $( this );

				if( $sitelinkview.data( 'removetoolbar' ) ) {
					return;
				}

				$sitelinkview
				.removetoolbar( {
					$container: $( '<div/>' ).appendTo( $sitelinkview.children( 'td' ).last() )
				} )
				.on( 'removetoolbarremove.removetoolbar', function( event ) {
					if( event.target !== $sitelinkview.get( 0 ) ) {
						return;
					}
					sitelinklistview.$listview.data( 'listview' ).removeItem( $sitelinkview );
				} );
			} );
		},
		sitelinkgroupviewafterstopediting: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				sitelinklistviewListview = sitelinklistview.$listview.data( 'listview' );

			sitelinklistviewListview.items().each( function() {
				var $sitelinkview = $( this );
				toolbarcontroller.destroyToolbar( $sitelinkview.data( 'removetoolbar' ) );
			} );
		},
		sitelinkgroupviewdisable: function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				sitelinklistviewListview = sitelinklistview.$listview.data( 'listview' );

			sitelinklistviewListview.items().each( function() {
				var $sitelinkview = $( this ),
					removetoolbar = $sitelinkview.data( 'removetoolbar' );

				if( !removetoolbar ) {
					return;
				}

				removetoolbar[sitelinkgroupview.option( 'disabled' ) ? 'disable' : 'enable']();
			} );
		}
	}
} );

}( jQuery, mediaWiki, wikibase ) );
