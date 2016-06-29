( function( $, wb ) {
	'use strict';

	var MODULE = wb.view;

	/**
	 * A factory for creating view widgets
	 *
	 * @class wikibase.view.ViewFactory
	 * @license GPL-2.0+
	 * @since 0.5
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 * @constructor
	 *
	 * @param {util.ContentLanguages} contentLanguages
	 *        Required by the `ValueView` for limiting the list of available languages for
	 *        particular `jQuery.valueview.Expert` instances like the `Expert` responsible
	 *        for `MonoLingualTextValue`s.
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
	 *        object when interacting on a "value" `Variation`.
	 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} entityIdHtmlFormatter
	 *        Required by several views for rendering links to entities.
	 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} entityIdPlainFormatter
	 *        Required by several views for rendering plain text references to entities.
	 * @param {wikibase.store.EntityStore} entityStore
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
	 */
	var SELF = MODULE.ViewFactory = function ViewFactory(
		structureEditorFactory,
		contentLanguages,
		dataTypeStore,
		entityIdHtmlFormatter,
		entityIdPlainFormatter,
		entityStore,
		expertStore,
		formatterFactory,
		messageProvider,
		parserStore,
		userLanguages,
		vocabularyLookupApiUrl
	) {
		this._structureEditorFactory = structureEditorFactory;
		this._contentLanguages = contentLanguages;
		this._dataTypeStore = dataTypeStore;
		this._entityIdHtmlFormatter = entityIdHtmlFormatter;
		this._entityIdPlainFormatter = entityIdPlainFormatter;
		this._entityStore = entityStore;
		this._expertStore = expertStore;
		this._formatterFactory = formatterFactory;
		this._messageProvider = messageProvider;
		this._parserStore = parserStore;
		// Maybe make userLanguages an argument to getEntityView instead of to the constructor
		this._userLanguages = userLanguages;
		this._vocabularyLookupApiUrl = vocabularyLookupApiUrl || null;
		this._eventSingletonManager = new $.util.EventSingletonManager();
	};

	/**
	 * @property {wikibase.view.StructureEditorFactory}
	 * @private
	 **/
	SELF.prototype._structureEditorFactory = null;

	/**
	 * @property {util.ContentLanguages}
	 * @private
	 **/
	SELF.prototype._contentLanguages = null;

	/**
	 * @property {dataTypes.DataTypeStore}
	 * @private
	 **/
	SELF.prototype._dataTypeStore = null;

	/**
	 * @property {wikibase.entityIdFormatter.EntityIdHtmlFormatter}
	 * @private
	 **/
	SELF.prototype._entityIdHtmlFormatter = null;

	/**
	 * @property {wikibase.entityIdFormatter.EntityIdPlainFormatter}
	 * @private
	 **/
	SELF.prototype._entityIdPlainFormatter = null;

	/**
	 * @property {wikibase.store.EntityStore}
	 * @private
	 **/
	SELF.prototype._entityStore = null;

	/**
	 * @property {jQuery.util.EventSingletonManager}
	 * @private
	 **/
	SELF.prototype._eventSingletonManager = null;

	/**
	 * @property {jQuery.valueview.ExpertStore}
	 * @private
	 **/
	SELF.prototype._expertStore = null;

	/**
	 * @property {wikibase.ValueFormatterFactory}
	 * @private
	 **/
	SELF.prototype._formatterFactory = null;

	/**
	 * @property {util.MessageProvider}
	 * @private
	 **/
	SELF.prototype._messageProvider = null;

	/**
	 * @property {valueParsers.ValueParserStore}
	 * @private
	 **/
	SELF.prototype._parserStore = null;

	/**
	 * @property {string[]}
	 * @private
	 **/
	SELF.prototype._userLanguages = null;

	/**
	 * @property {string|null}
	 * @private
	 **/
	SELF.prototype._vocabularyLookupApiUrl = null;

	/**
	 * Construct a suitable view for the given entity on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {jQuery} $entityview
	 * @return {jQuery.wikibase.entityview} The constructed entity view
	 * @throws {Error} If there is no view for the given entity type
	 **/
	SELF.prototype.getEntityView = function( startEditingCallback, entity, $entityview ) {
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
	 * @param {wikibase.datamodel.Fingerprint} fingerprint
	 * @param {jQuery} $entitytermsview
	 * @return {jQuery.wikibase.entitytermsview} The constructed entity terms view
	 **/
	SELF.prototype.getEntityTermsView = function( startEditingCallback, fingerprint, $entitytermsview ) {
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
	 * @param {wikibase.datamodel.SiteLinkSet} sitelinkSet
	 * @param {jQuery} $sitelinkgrouplistview
	 * @return {jQuery.wikibase.sitelinkgrouplistview} The constructed sitelinkgrouplistview
	 **/
	SELF.prototype.getSitelinkGroupListView = function( startEditingCallback, sitelinkSet, $sitelinkgrouplistview ) {
		var self = this;

		return this._getView(
			'sitelinkgrouplistview',
			$sitelinkgrouplistview,
			{
				value: sitelinkSet,
				listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
					listItemWidget: $.wikibase.sitelinkgroupview,
					getNewItem: function( value, dom ) {
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
	 * @param {wikibase.datamodel.SiteLinkSet} siteLinks
	 * @param {jQuery} $sitelinkgroupview
	 * @return {jQuery.wikibase.sitelinkgroupview} The constructed sitelinkgroupview
	 **/
	SELF.prototype.getSitelinkGroupView = function( startEditingCallback, groupName, siteLinks, $sitelinkgroupview ) {
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
	 * @param {wikibase.datamodel.SiteLink[]} siteLinks
	 * @param {jQuery} $sitelinklistview
	 * @param {string[]} allowedSiteIds
	 * @param {jQuery} $counter
	 * @return {jQuery.wikibase.sitelinklistview} The constructed sitelinklistview
	 **/
	SELF.prototype.getSiteLinkListView = function( startEditingCallback, siteLinks, $sitelinklistview, allowedSiteIds, $counter ) {
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
	 * @return {jQuery.wikibase.listview.ListItemAdapter}
	 */
	SELF.prototype.getListItemAdapterForSiteLinkView = function( startEditingCallback, getAllowedSites ) {
		var self = this;
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.sitelinkview,
			getNewItem: function( value, dom ) {
				var view = self._getView(
					'sitelinkview',
					$( dom ),
					{
						value: value,
						getAllowedSites: getAllowedSites,
						entityIdPlainFormatter: self._entityIdPlainFormatter
					}
				);
				return view;
			}
		} );
	};

	SELF.prototype._getAdderWithStartEditing = function( startEditingCallback ) {
		var structureEditorFactory = this._structureEditorFactory;
		return function( doAdd, $dom, label ) {
			var newDoAdd = function() {
				return startEditingCallback().then( doAdd );
			};
			return structureEditorFactory.getAdder( newDoAdd, $dom, label );
		};
	};

	/**
	 * Construct a suitable view for the list of statement groups for the given entity on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {wikibase.datamodel.Item|wikibase.datamodel.Property} entity
	 * @param {jQuery} $statementgrouplistview
	 * @return {jQuery.wikibase.statementgrouplistview} The constructed statementgrouplistview
	 **/
	SELF.prototype.getStatementGroupListView = function( startEditingCallback, entity, $statementgrouplistview ) {
		var statementGroupSet = entity.getStatements();
		return this._getView(
			'statementgrouplistview',
			$statementgrouplistview,
			{
				// If we have no HTML to initialize on, pass the raw data
				value: $statementgrouplistview.is( ':empty' ) ? statementGroupSet : null,
				listItemAdapter: this.getListItemAdapterForStatementGroupView(
					startEditingCallback,
					entity.getId(),
					function( guid ) {
						var res = null;
						statementGroupSet.each( function() {
							// FIXME: This accesses a private property to avoid cloning.
							this._groupableCollection.each( function() {
								if ( this.getClaim().getGuid() === guid ) {
									res = this;
								}
								return res === null;
							} );
							return res === null;
						} );
						return res;
					}
				),
				getAdder: this._getAdderWithStartEditing( startEditingCallback )
			}
		);
	};

	/**
	 * Construct a `ListItemAdapter` for `statementgroupview`s
	 *
	 * @param {Function} startEditingCallback
	 * @param {string} entityId
	 * @param {Function} getStatementForGuid A function returning a `wikibase.datamodel.Statement` for a given GUID
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 **/
	SELF.prototype.getListItemAdapterForStatementGroupView = function( startEditingCallback, entityId, getStatementForGuid ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.statementgroupview,
			newItemOptionsFn: $.proxy( function( value ) {
				return {
					value: value,
					entityIdHtmlFormatter: this._entityIdHtmlFormatter,
					buildStatementListView: $.proxy( this.getStatementListView, this, startEditingCallback, entityId, value && value.getKey(), getStatementForGuid )
				};
			}, this )
		} );
	};

	/**
	 * Construct a suitable view for the given list of statements on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {wikibase.datamodel.EntityId} entityId
	 * @param {wikibase.datamodel.EntityId|null} propertyId Optionally specifies a property
	 *                                                      all statements should be on or are on
	 * @param {Function} getStatementForGuid A function returning a `wikibase.datamodel.Statement` for a given GUID
	 * @param {wikibase.datamodel.StatementList} value
	 * @param {jQuery} $statementlistview
	 * @return {jQuery.wikibase.statementgroupview} The constructed statementlistview
	 **/
	SELF.prototype.getStatementListView = function( startEditingCallback, entityId, propertyId, getStatementForGuid, value, $statementlistview ) {
		propertyId = propertyId || $statementlistview.closest( '.wikibase-statementgroupview' ).attr( 'id' );

		return this._getView(
			'statementlistview',
			$statementlistview,
			{
				value: value.length === 0 ? null : value,
				getListItemAdapter: this.getListItemAdapterForStatementView.bind(
					this,
					startEditingCallback,
					entityId,
					function( dom ) {
						var guidMatch = dom.className.match( /wikibase-statement-(\S+)/ );
						return guidMatch ? getStatementForGuid( guidMatch[ 1 ] ) : null;
					},
					propertyId
				),
				getAdder: this._getAdderWithStartEditing( startEditingCallback )
			}
		);
	};

	/**
	 * Construct a `ListItemAdapter` for `statementview`s
	 *
	 * @param {Function} startEditingCallback
	 * @param {string} entityId
	 * @param {Function} getValueForDom A function returning a `wikibase.datamodel.Statement` or `null`
	 *                                  for a given DOM element
	 * @param {string|null} [propertyId] Optionally a property all statements are or should be on
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 **/
	SELF.prototype.getListItemAdapterForStatementView = function( startEditingCallback, entityId, getValueForDom, propertyId, removeCallback ) {
		var listItemAdapter = new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.statementview,
			getNewItem: $.proxy( function( value, dom ) {
				value = value || getValueForDom( dom );
				var view = this.getStatementView( startEditingCallback, entityId, propertyId, removeCallback, value, $( dom ) );
				return view;
			}, this )
		} );
		return listItemAdapter;
	};

	SELF.prototype.getStatementView = function( startEditingCallback, entityId, propertyId, removeCallback, value, $dom ) {
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

				buildReferenceListItemAdapter: $.proxy( this.getListItemAdapterForReferenceView, this, startEditingCallback ),
				buildSnakView: $.proxy(
					this.getSnakView,
					this,
					startEditingCallback,
					false
				),
				entityIdPlainFormatter: this._entityIdPlainFormatter,
				getAdder: this._getAdderWithStartEditing( startEditingCallback ),
				guidGenerator: new wb.utilities.ClaimGuidGenerator( entityId ),
				qualifiersListItemAdapter: this.getListItemAdapterForSnakListView( startEditingCallback )
			}
		);
		return view;
	};

	/**
	 * Construct a `ListItemAdapter` for `referenceview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForReferenceView = function( startEditingCallback ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.referenceview,
			getNewItem: $.proxy( function( value, dom ) {
				return this.getReferenceView( startEditingCallback, value, $( dom ) );
			}, this )
		} );
	};

	SELF.prototype.getReferenceView = function( startEditingCallback, value, $dom ) {
		var view = this._getView(
			'referenceview',
			$dom,
			{
				value: value || null,
				listItemAdapter: this.getListItemAdapterForSnakListView( startEditingCallback ),
				getAdder: this._getAdderWithStartEditing( startEditingCallback )
			}
		);
		return view;
	};

	/**
	 * Construct a `ListItemAdapter` for `snaklistview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForSnakListView = function( startEditingCallback ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.snaklistview,
			newItemOptionsFn: $.proxy( function( value ) {
				return {
					value: value || undefined,
					singleProperty: true,
					listItemAdapter: this.getListItemAdapterForSnakView( startEditingCallback )
				};
			}, this )
		} );
	};

	/**
	 * Construct a `ListItemAdapter` for `snakview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForSnakView = function( startEditingCallback ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.snakview,
			newItemOptionsFn: $.proxy( function( value ) {
				return this._getSnakViewOptions(
					true,
					{
						locked: {
							// Do not allow changing the property when editing existing an snak.
							property: Boolean( value )
						}
					},
					value || {
						property: null,
						snaktype: wb.datamodel.PropertyValueSnak.TYPE
					}
				);
			}, this )
		} );
	};

	/**
	 * Construct a suitable view for the given snak on the given DOM element
	 *
	 * @param {Function} startEditingCallback
	 * @param {boolean} drawProperty Whether the snakview should draw its property
	 * @param {Object} options An object with keys `locked` and `autoStartEditing`
	 * @param {wikibase.datamodel.Snak|null} snak
	 * @param {jQuery} $snakview
	 * @return {jQuery.wikibase.snakview} The constructed snakview
	 */
	SELF.prototype.getSnakView = function( startEditingCallback, drawProperty, options, snak, $snakview ) {
		return this._getView(
			'snakview',
			$snakview,
			this._getSnakViewOptions( drawProperty, options, snak )
		);
	};

	/**
	 * @param {boolean} drawProperty Whether the snakview should draw its property
	 * @param {Object} options An object with keys `locked` and `autoStartEditing`
	 * @param {wikibase.datamodel.Snak|null} snak
	 */
	SELF.prototype._getSnakViewOptions = function( drawProperty, options, snak ) {
		return {
			value: snak || undefined,
			locked: options.locked,
			autoStartEditing: options.autoStartEditing,
			dataTypeStore: this._dataTypeStore,
			entityIdHtmlFormatter: this._entityIdHtmlFormatter,
			entityIdPlainFormatter: this._entityIdPlainFormatter,
			entityStore: this._entityStore,
			valueViewBuilder: this._getValueViewBuilder(),
			drawProperty: drawProperty
		};
	};

	/**
	 * @private
	 * @return {wikibase.ValueViewBuilder}
	 **/
	SELF.prototype._getValueViewBuilder = function() {
		return new wb.ValueViewBuilder(
			this._expertStore,
			this._formatterFactory,
			this._parserStore,
			this._userLanguages && this._userLanguages[0],
			this._messageProvider,
			this._contentLanguages,
			this._vocabularyLookupApiUrl
		);
	};

	/**
	 * @private
	 * @return {Object} The constructed view
	 * @throws {Error} If there is no view with the given name
	 **/
	SELF.prototype._getView = function( viewName, $dom, options ) {
		if ( !$.wikibase[ viewName ] ) {
			throw new Error( 'View ' + viewName + ' does not exist' );
		}

		$dom[ viewName ]( options );

		return $dom.data( viewName );
	};

}( jQuery, wikibase ) );
