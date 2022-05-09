( function ( wb ) {
	'use strict';

	var MODULE = wb.view,
		EventSingletonManager = require( '../../jquery/jquery.util.EventSingletonManager.js' ),
		ValueViewBuilder = require( '../wikibase.ValueViewBuilder.js' ),
		datamodel = require( 'wikibase.datamodel' );

	require( '../../jquery/wikibase/snakview/snakview.variations.NoValue.js' );
	require( '../../jquery/wikibase/snakview/snakview.variations.SomeValue.js' );
	require( '../../jquery/wikibase/snakview/snakview.variations.Value.js' );

	// Widgets
	require( '../../jquery/ui/jquery.ui.EditableTemplatedWidget.js' );
	require( '../../jquery/wikibase/snakview/snakview.js' );
	require( '../../jquery/wikibase/snakview/snakview.SnakTypeSelector.js' );
	require( '../../jquery/wikibase/jquery.wikibase.snaklistview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.listview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.listview.ListItemAdapter.js' );

	require( '../../jquery/wikibase/jquery.wikibase.aliasesview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.badgeselector.js' );
	require( '../../jquery/wikibase/jquery.wikibase.descriptionview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.entitytermsforlanguagelistview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.entitytermsforlanguageview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.entitytermsview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.entityview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.itemview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.labelview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.pagesuggester.js' );
	require( '../../jquery/wikibase/jquery.wikibase.propertyview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.referenceview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.sitelinkgrouplistview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.sitelinkgroupview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.sitelinklistview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.sitelinkview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.statementgrouplistview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.statementgroupview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.statementlistview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.statementview.js' );
	require( '../../jquery/wikibase/jquery.wikibase.statementview.RankSelector.js' );
	require( '../../jquery/ui/jquery.ui.tagadata.js' );
	require( '../../../lib/wikibase-data-values-value-view/lib/jquery.ui/jquery.ui.toggler.js' );

	// Plugins
	require( '../../jquery/jquery.removeClassByRegex.js' );

	/**
	 * A factory for creating view widgets
	 *
	 * @class wikibase.view.ViewFactory
	 * @license GPL-2.0-or-later
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 * @constructor
	 *
	 * @param {wikibase.view.StructureEditorFactory} structureEditorFactory
	 * @param {util.ContentLanguages} contentLanguages
	 *        Required by the `ValueView` for limiting the list of available languages for
	 *        particular `jQuery.valueview.Expert` instances like the `Expert` responsible
	 *        for `MonoLingualTextValue`s.
	 * @param {wikibase.dataTypes.DataTypeStore} dataTypeStore
	 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
	 *        object when interacting on a "value" `Variation`.
	 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} entityIdHtmlFormatter
	 *        Required by several views for rendering links to entities.
	 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} entityIdPlainFormatter
	 *        Required by several views for rendering plain text references to entities.
	 * @param {PropertyDataTypeStore} propertyDataTypeStore
	 *        Required for dynamically gathering `Entity`/`Property` information.
	 * @param {jQuery.valueview.ExpertStore} expertStore
	 *        Required by the `ValueView` for constructing `expert`s for different value types.
	 * @param {wikibase.ValueFormatterFactory} formatterFactory
	 *        Required by the `ValueView` for formatting entered values.
	 * @param {util.MessageProvider} messageProvider
	 *        Required by the `ValueView` for showing the user interface in the correct language.
	 * @param {valueParsers.ValueParserStore} parserStore
	 *        Required by the `ValueView` for parsing entered values.
	 * @param {string[]} userLanguages An array of language codes, the first being the UI language
	 *        Required for showing the user interface in the correct language and for showing terms
	 *        in all languages requested by the user.
	 * @param {string|null} [vocabularyLookupApiUrl=null]
	 * @param {string} commonsApiUrl
	 */
	var SELF = MODULE.ViewFactory = function ViewFactory(
		structureEditorFactory,
		contentLanguages,
		dataTypeStore,
		entityIdHtmlFormatter,
		entityIdPlainFormatter,
		propertyDataTypeStore,
		expertStore,
		formatterFactory,
		messageProvider,
		parserStore,
		userLanguages,
		vocabularyLookupApiUrl,
		commonsApiUrl
	) {
		if ( ( !structureEditorFactory || !structureEditorFactory.getAdder )
			|| ( !messageProvider || !messageProvider.getMessage )
			|| !Array.isArray( userLanguages )
			|| ( vocabularyLookupApiUrl && typeof vocabularyLookupApiUrl !== 'string'
			|| !commonsApiUrl )
		) {
			throw new Error( 'Required parameter(s) not specified properly' );
		}

		this._structureEditorFactory = structureEditorFactory;
		this._contentLanguages = contentLanguages;
		this._dataTypeStore = dataTypeStore;
		this._entityIdHtmlFormatter = entityIdHtmlFormatter;
		this._entityIdPlainFormatter = entityIdPlainFormatter;
		this._expertStore = expertStore;
		this._formatterFactory = formatterFactory;
		this._messageProvider = messageProvider;
		this._parserStore = parserStore;
		// Maybe make userLanguages an argument to getEntityView instead of to the constructor
		this._userLanguages = userLanguages;
		this._vocabularyLookupApiUrl = vocabularyLookupApiUrl || null;
		this._eventSingletonManager = new EventSingletonManager();
		this._commonsApiUrl = commonsApiUrl;
		this._propertyDataTypeStore = propertyDataTypeStore;
	};

	/**
	 * @property {wikibase.view.StructureEditorFactory}
	 * @private
	 */
	SELF.prototype._structureEditorFactory = null;

	/**
	 * @property {util.ContentLanguages}
	 * @private
	 */
	SELF.prototype._contentLanguages = null;

	/**
	 * @property {wikibase.dataTypes.DataTypeStore}
	 * @private
	 */
	SELF.prototype._dataTypeStore = null;

	/**
	 * @property {wikibase.entityIdFormatter.EntityIdHtmlFormatter}
	 * @private
	 */
	SELF.prototype._entityIdHtmlFormatter = null;

	/**
	 * @property {wikibase.entityIdFormatter.EntityIdPlainFormatter}
	 * @private
	 */
	SELF.prototype._entityIdPlainFormatter = null;

	/**
	 * @property {PropertyDataTypeStore}
	 * @private
	 */
	SELF.prototype._propertyDataTypeStore = null;

	/**
	 * @property {jQuery.util.EventSingletonManager}
	 * @private
	 */
	SELF.prototype._eventSingletonManager = null;

	/**
	 * @property {jQuery.valueview.ExpertStore}
	 * @private
	 */
	SELF.prototype._expertStore = null;

	/**
	 * @property {wikibase.ValueFormatterFactory}
	 * @private
	 */
	SELF.prototype._formatterFactory = null;

	/**
	 * @property {util.MessageProvider}
	 * @private
	 */
	SELF.prototype._messageProvider = null;

	/**
	 * @property {valueParsers.ValueParserStore}
	 * @private
	 */
	SELF.prototype._parserStore = null;

	/**
	 * @property {string[]}
	 * @private
	 */
	SELF.prototype._userLanguages = null;

	/**
	 * @property {string|null}
	 * @private
	 */
	SELF.prototype._vocabularyLookupApiUrl = null;

	/**
	 * @property {string}
	 * @private
	 */
	SELF.prototype._commonsApiUrl = null;

	/**
	 * Construct a suitable view for the given entity on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {datamodel.Entity} entity
	 * @param {jQuery} $entityview
	 * @return {jQuery.wikibase.entityview} The constructed entity view
	 * @throws {Error} If there is no view for the given entity type
	 */
	SELF.prototype.getEntityView = function ( startEditingCallback, entity, $entityview ) {
		return this._getView(
			// Typically "itemview" or "propertyview".
			entity.getType() + 'view',
			$entityview,
			{
				buildEntityTermsView: this.getEntityTermsView.bind( this, startEditingCallback ),
				buildSitelinkGroupListView: this.getSitelinkGroupListView.bind( this, startEditingCallback ),
				buildStatementGroupListView: this.getStatementGroupListView.bind( this, startEditingCallback ),
				value: entity
			}
		);
	};

	/**
	 * Construct a suitable terms view for the given fingerprint on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {datamodel.Fingerprint} fingerprint
	 * @param {jQuery} $entitytermsview
	 * @return {jQuery.wikibase.entitytermsview} The constructed entity terms view
	 */
	SELF.prototype.getEntityTermsView = function ( startEditingCallback, fingerprint, $entitytermsview ) {
		return this._getView(
			'entitytermsview',
			$entitytermsview,
			{
				value: fingerprint,
				userLanguages: this._userLanguages,
				helpMessage: this._messageProvider.getMessage( 'wikibase-entitytermsview-input-help-message' )
			}
		);
	};

	/**
	 * Construct a suitable view for the given sitelink set on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {datamodel.SiteLinkSet} sitelinkSet
	 * @param {jQuery} $sitelinkgrouplistview
	 * @return {jQuery.wikibase.sitelinkgrouplistview} The constructed sitelinkgrouplistview
	 */
	SELF.prototype.getSitelinkGroupListView = function ( startEditingCallback, sitelinkSet, $sitelinkgrouplistview ) {
		var self = this;

		return this._getView(
			'sitelinkgrouplistview',
			$sitelinkgrouplistview,
			{
				value: sitelinkSet,
				listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
					listItemWidget: $.wikibase.sitelinkgroupview,
					getNewItem: function ( value, dom ) {
						return self.getSitelinkGroupView( startEditingCallback, value.group, value.siteLinks, $( dom ) );
					}
				} )
			}
		);
	};

	/**
	 * Construct a suitable view for the given sitelink group on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {string} groupName
	 * @param {datamodel.SiteLinkSet} siteLinks
	 * @param {jQuery} $sitelinkgroupview
	 * @return {jQuery.wikibase.sitelinkgroupview} The constructed sitelinkgroupview
	 */
	SELF.prototype.getSitelinkGroupView = function ( startEditingCallback, groupName, siteLinks, $sitelinkgroupview ) {
		return this._getView(
			'sitelinkgroupview',
			$sitelinkgroupview,
			{
				groupName: groupName,
				value: siteLinks,
				getSiteLinkListView: this.getSiteLinkListView.bind( this, startEditingCallback )
			}
		);
	};

	/**
	 * Construct a suitable view for the given sitelink list on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {datamodel.SiteLink[]} siteLinks
	 * @param {jQuery} $sitelinklistview
	 * @param {string[]} allowedSiteIds
	 * @param {jQuery} $counter
	 * @return {jQuery.wikibase.sitelinklistview} The constructed sitelinklistview
	 */
	SELF.prototype.getSiteLinkListView = function ( startEditingCallback, siteLinks, $sitelinklistview, allowedSiteIds, $counter ) {
		return this._getView(
			'sitelinklistview',
			$sitelinklistview,
			{
				$counter: $counter,
				allowedSiteIds: allowedSiteIds,
				encapsulate: true,
				eventSingletonManager: this._eventSingletonManager,
				getListItemAdapter: this.getListItemAdapterForSiteLinkView.bind( this, startEditingCallback ),
				value: siteLinks
			}
		);
	};

	/**
	 * @param {Function} startEditingCallback
	 * @param {Function} getAllowedSites
	 * @param {Function} removeCallback
	 * @return {jQuery.wikibase.listview.ListItemAdapter}
	 */
	SELF.prototype.getListItemAdapterForSiteLinkView = function ( startEditingCallback, getAllowedSites, removeCallback ) {
		var self = this;
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.sitelinkview,
			getNewItem: function ( value, dom ) {
				var view = self._getView(
					'sitelinkview',
					$( dom ),
					{
						value: value,
						getAllowedSites: getAllowedSites,
						entityIdPlainFormatter: self._entityIdPlainFormatter,
						getSiteLinkRemover: function ( $dom, title ) {
							return self._structureEditorFactory.getRemover(
								function () {
									return startEditingCallback()
										.then( function () {
											return removeCallback( view );
										} );
								},
								$dom,
								title
							);
						}
					}
				);
				return view;
			}
		} );
	};

	/**
	 * @param {Function} startEditingCallback
	 * @return {function(*=, *=, *=, *=): *}
	 */
	SELF.prototype.getAdderWithStartEditing = function ( startEditingCallback ) {
		var structureEditorFactory = this._structureEditorFactory;
		return function ( doAdd, $dom, label, title ) {
			var newDoAdd = function () {
				return startEditingCallback().then( doAdd );
			};
			return structureEditorFactory.getAdder( newDoAdd, $dom, label, title );
		};
	};

	/**
	 * Construct a suitable view for the list of statement groups for the given entity on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {datamodel.Item|datamodel.Property} entity
	 * @param {jQuery} $statementgrouplistview
	 * @param {string} htmlIdPrefix
	 *
	 * @return {jQuery.wikibase.statementgrouplistview} The constructed statementgrouplistview
	 */
	SELF.prototype.getStatementGroupListView = function ( startEditingCallback, entity, $statementgrouplistview, htmlIdPrefix ) {
		var statementGroupSet = entity.getStatements();

		function getStatementForGuid( guid ) {
			var res = null;
			statementGroupSet.each( function () {
				// FIXME: This accesses a private property to avoid cloning.
				this._groupableCollection.each( function () {
					if ( this.getClaim().getGuid() === guid ) {
						res = this;
					}
					return res === null;
				} );
				return res === null;
			} );
			return res;
		}

		return this._getView(
			'statementgrouplistview',
			$statementgrouplistview,
			{
				// If we have no HTML to initialize on, pass the raw data
				value: $statementgrouplistview.is( ':empty' ) ? statementGroupSet : null,
				listItemAdapter: this.getListItemAdapterForStatementGroupView(
					startEditingCallback,
					entity.getId(),
					getStatementForGuid,
					htmlIdPrefix
				),
				getAdder: this.getAdderWithStartEditing( startEditingCallback )
			}
		);
	};

	/**
	 * Construct a `ListItemAdapter` for `statementgroupview`s
	 *
	 * @param {Function} startEditingCallback
	 * @param {string} entityId
	 * @param {Function} getStatementForGuid A function returning a `datamodel.Statement` for a given GUID
	 * @param {string} htmlIdPrefix
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForStatementGroupView = function ( startEditingCallback, entityId, getStatementForGuid, htmlIdPrefix ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.statementgroupview,
			newItemOptionsFn: function ( value ) {
				return {
					value: value,
					entityIdHtmlFormatter: this._entityIdHtmlFormatter,
					buildStatementListView: this.getStatementListView.bind( this, startEditingCallback, entityId, value && value.getKey(), getStatementForGuid ),
					htmlIdPrefix: htmlIdPrefix
				};
			}.bind( this )
		} );
	};

	/**
	 * Construct a suitable view for the given list of statements on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {string} entityId
	 * @param {string|null} propertyId Optionally specifies a property
	 *                                                      all statements should be on or are on
	 * @param {Function} getStatementForGuid A function returning a `datamodel.Statement` for a given GUID
	 * @param {datamodel.StatementList} value
	 * @param {jQuery} $statementlistview
	 * @return {jQuery.wikibase.statementgroupview} The constructed statementlistview
	 */
	SELF.prototype.getStatementListView = function ( startEditingCallback, entityId, propertyId, getStatementForGuid, value, $statementlistview ) {
		propertyId = propertyId || $statementlistview.closest( '.wikibase-statementgroupview' ).data( 'property-id' );

		return this._getView(
			'statementlistview',
			$statementlistview,
			{
				value: value.length === 0 ? null : value,
				getListItemAdapter: this.getListItemAdapterForStatementView.bind(
					this,
					startEditingCallback,
					entityId,
					function ( dom ) {
						var guidMatch = dom.className.match( /wikibase-statement-(\S+)/ );
						return guidMatch ? getStatementForGuid( guidMatch[ 1 ] ) : null;
					},
					propertyId
				),
				getAdder: this.getAdderWithStartEditing( startEditingCallback )
			}
		);
	};

	/**
	 * Construct a `ListItemAdapter` for `statementview`s
	 *
	 * @param {Function} startEditingCallback
	 * @param {string} entityId
	 * @param {Function} getValueForDom A function returning a `datamodel.Statement` or `null`
	 *                                  for a given DOM element
	 * @param {string|null} [propertyId] Optionally a property all statements are or should be on
	 * @param {Function} removeCallback A function that accepts a statementview and removes it from
	 *  the list.
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForStatementView = function ( startEditingCallback, entityId, getValueForDom, propertyId, removeCallback ) {
		var listItemAdapter = new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.statementview,
			getNewItem: function ( value, dom ) {
				value = value || getValueForDom( dom );
				var view = this.getStatementView( startEditingCallback, entityId, propertyId, removeCallback, value, $( dom ) );
				return view;
			}.bind( this )
		} );
		return listItemAdapter;
	};

	SELF.prototype.getStatementView = function ( startEditingCallback, entityId, propertyId, removeCallback, value, $dom ) {
		var currentPropertyId = value ? value.getClaim().getMainSnak().getPropertyId() : propertyId;
		var view = this._getView(
			'statementview',
			$dom,
			{
				value: value, // FIXME: remove
				locked: {
					mainSnak: {
						property: Boolean( currentPropertyId )
					}
				},
				predefined: {
					mainSnak: {
						property: currentPropertyId || undefined
					}
				},

				buildSnakView:
					this.getSnakView.bind(
						this,
						startEditingCallback,
						false
					),
				entityIdPlainFormatter: this._entityIdPlainFormatter,
				getAdder: this.getAdderWithStartEditing( startEditingCallback ),
				getQualifiersListItemAdapter: this.getListItemAdapterForSnakListView.bind( this, startEditingCallback ),
				getReferenceListItemAdapter: this.getListItemAdapterForReferenceView.bind( this, startEditingCallback ),
				guidGenerator: new wb.utilities.ClaimGuidGenerator( entityId )
			}
		);
		return view;
	};

	/**
	 * Construct a `ListItemAdapter` for `referenceview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForReferenceView = function ( startEditingCallback, removeCallback ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.referenceview,
			getNewItem: function ( value, dom ) {
				return this.getReferenceView( startEditingCallback, removeCallback, value, $( dom ) );
			}.bind( this )
		} );
	};

	SELF.prototype.getReferenceView = function ( startEditingCallback, removeCallback, value, $dom ) {
		var structureEditorFactory = this._structureEditorFactory;
		var view;
		var doRemove = function () {
			return removeCallback( view );
		};
		view = this._getView(
			'referenceview',
			$dom,
			{
				value: value || null,
				getAdder: this.getAdderWithStartEditing( startEditingCallback ),
				getListItemAdapter: this.getListItemAdapterForSnakListView.bind( this, startEditingCallback ),
				getReferenceRemover: function ( $dom2 ) {
					return structureEditorFactory.getRemover( function () {
						return startEditingCallback().then( doRemove );
					}, $dom2 );
				},
				removeCallback: doRemove
			}
		);
		return view;
	};

	/**
	 * Construct a `ListItemAdapter` for `snaklistview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForSnakListView = function ( startEditingCallback, removeCallback ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.snaklistview,
			getNewItem: function ( value, dom ) {
				return this.getSnakListView( startEditingCallback, removeCallback, $( dom ), value );
			}.bind( this )
		} );
	};

	/**
	 * Construct a `snaklistview`
	 *
	 * @return {jQuery.wikibase.snaklistview} The constructed snaklistview
	 */
	SELF.prototype.getSnakListView = function ( startEditingCallback, removeCallback, $dom, value ) {
		var view = this._getView(
			'snaklistview',
			$dom,
			{
				value: value || undefined,
				singleProperty: true,
				removeCallback: function () {
					removeCallback( view );
				},
				getListItemAdapter: this.getListItemAdapterForSnakView.bind( this, startEditingCallback )
			}
		);
		return view;
	};

	/**
	 * Construct a `ListItemAdapter` for `snakview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForSnakView = function ( startEditingCallback, removeCallback ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.snakview,
			getNewItem: function ( value, dom ) {
				return this.getSnakView(
					startEditingCallback,
					true,
					{
						locked: {
							// Do not allow changing the property when editing existing an snak.
							property: Boolean( value )
						}
					},
					value || {
						property: null,
						snaktype: datamodel.PropertyValueSnak.TYPE
					},
					$( dom ),
					removeCallback
				);
			}.bind( this )
		} );
	};

	/**
	 * Construct a suitable view for the given snak on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {boolean} drawProperty Whether the snakview should draw its property
	 * @param {Object} options An object with keys `locked` and `autoStartEditing`
	 * @param {datamodel.Snak|null} snak
	 * @param {jQuery} $snakview
	 * @param {Function} removeCallback
	 * @return {jQuery.wikibase.snakview} The constructed snakview
	 */
	SELF.prototype.getSnakView = function ( startEditingCallback, drawProperty, options, snak, $snakview, removeCallback ) {
		var structureEditorFactory = this._structureEditorFactory;
		var view = this._getView(
			'snakview',
			$snakview,
			{
				value: snak || undefined,
				locked: options.locked,
				autoStartEditing: options.autoStartEditing,
				dataTypeStore: this._dataTypeStore,
				entityIdHtmlFormatter: this._entityIdHtmlFormatter,
				entityIdPlainFormatter: this._entityIdPlainFormatter,
				propertyDataTypeStore: this._propertyDataTypeStore,
				valueViewBuilder: this._getValueViewBuilder(),
				drawProperty: drawProperty,
				getSnakRemover: removeCallback ? function ( $dom ) {
					return structureEditorFactory.getRemover( function () {
						return startEditingCallback()
							.then( function () {
								return removeCallback( view );
							} );
					}, $dom );
				} : null
			}
		);
		return view;
	};

	/**
	 * @private
	 * @return {ValueViewBuilder}
	 */
	SELF.prototype._getValueViewBuilder = function () {
		return new ValueViewBuilder(
			this._expertStore,
			this._formatterFactory,
			this._parserStore,
			this._userLanguages && this._userLanguages[ 0 ],
			this._messageProvider,
			this._contentLanguages,
			this._vocabularyLookupApiUrl,
			this._commonsApiUrl
		);
	};

	/**
	 * @private
	 * @return {Object} The constructed view
	 * @throws {Error} If there is no view with the given name
	 */
	SELF.prototype._getView = function ( viewName, $dom, options ) {
		if ( !$.wikibase[ viewName ] ) {
			throw new Error( 'View ' + viewName + ' does not exist' );
		}

		$dom[ viewName ]( options );

		return $dom.data( viewName );
	};

	module.exports = SELF;

}( wikibase ) );
