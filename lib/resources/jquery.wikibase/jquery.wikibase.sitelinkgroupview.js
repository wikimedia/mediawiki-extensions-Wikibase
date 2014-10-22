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
 *
 * @option {string} [helpMessage]
 *                  Default: 'Add a site link by specifying a site and a page of that site, edit or
 *                  remove existing site links.'
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - {jQuery.Event}
 *
 * @event stopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event afterstopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event toggleerror
 *        - {jQuery.Event}
 *        - {Error|null}
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
		entityStore: null,
		helpMessage: 'Add a site link by specifying a site and a page of that site, edit or remove '
			+ 'existing site links.'
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

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

		this._createSitelinklistview();

		this._update();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		if( this.$sitelinklistview ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).destroy();
		}
		PARENT.prototype.destroy.call( this );
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
	 * @return {boolean}
	 */
	isValid: function() {
		return this.$sitelinklistview.data( 'sitelinklistview' ).isValid();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this.$sitelinklistview.data( 'sitelinklistview' ).isInitialValue();
	},

	startEditing: function() {
		if( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this.element.addClass( 'wb-edit' );

		this.$sitelinklistview.data( 'sitelinklistview' ).startEditing();

		this._trigger( 'afterstartediting' );
	},

	/**
	 * @param {boolean} [dropValue]
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		dropValue = !!dropValue;

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		this.$sitelinklistview.one(
			'sitelinklistviewafterstopediting',
			function( event, dropValue ) {
				self._afterStopEditing( dropValue );
			}
		);

		this.$sitelinklistview.data( 'sitelinklistview' ).stopEditing( dropValue );
	},

	/**
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if( !dropValue ) {
			this.options.value = this.value();
		}
		this._isInEditMode = false;
		this.enable();
		this.element.removeClass( 'wb-edit' );
		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	cancelEditing: function() {
		this.stopEditing( true );
	},

	focus: function( considerViewPort ) {
		this.$sitelinklistview.data( 'sitelinklistview' ).focus( considerViewPort );
	},

	/**
	 * Applies/Removes error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else if( this.element.hasClass( 'wb-error' ) ) {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
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

			sitelinkgroupview.focus( true );
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'sitelinkgroupview-sitelinklistview',
	selector: ':' + $.wikibase.sitelinkgroupview.prototype.namespace
		+ '-' + $.wikibase.sitelinkgroupview.prototype.widgetName,
	events: {
		sitelinkgroupviewafterstartediting: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview =sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

			$sitelinklistview
			.addtoolbar( {
				$container: $( '<span/>' ).appendTo( sitelinklistview.$tfoot.find( 'td' ).last() )
			} )
			.on( 'addtoolbaradd.addtoolbar', function() {
				sitelinklistview.$listview.one(
					'sitelinkviewafterstartediting',
					function( event ) {
						$( event.target ).data( 'sitelinkview' ).focus();
					}
				);

				$sitelinklistview.data( 'sitelinklistview' ).enterNewItem();

				// Re-focus "add" button after having added or having cancelled adding a link:
				var eventName = 'sitelinklistviewafterstopediting.addtoolbar';
				$sitelinklistview.one( eventName, function( event ) {
					$sitelinklistview.data( 'addtoolbar' ).focus();
				} );
			} );

			if( sitelinklistview.isFull() ) {
				$sitelinklistview.data( 'addtoolbar' ).disable();
			}
		},
		sitelinkgroupviewafterstopediting: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview;

			toolbarcontroller.destroyToolbar( $sitelinklistview.data( 'addtoolbar' ) );
			$sitelinklistview.off( '.addtoolbar' );
		},
		sitelinklistviewafterremove: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

			$sitelinklistview.data( 'addtoolbar' )[sitelinklistview.isFull()
				? 'disable'
				: 'enable'
			]();
		},
		sitelinkgroupviewchange: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				addtoolbar = $sitelinklistview.data( 'addtoolbar' );

			if( !addtoolbar ) {
				return;
			}

			addtoolbar[!sitelinklistview.isValid() || sitelinklistview.isFull()
				? 'disable'
				: 'enable'
			]();
		},
		sitelinkgroupviewdisable: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				addtoolbar = $sitelinklistview.data( 'addtoolbar' ),
				disabled = sitelinkgroupview.option( 'disabled' );

			if( addtoolbar && ( disabled || !sitelinklistview.isFull() ) ) {
				$sitelinklistview.data( 'addtoolbar' )[disabled ? 'disable' : 'enable']();
			}
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

			if( !$sitelinkgroupview.length || !sitelinkgroupview._isInEditMode ) {
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
