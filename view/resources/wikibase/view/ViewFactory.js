( function( $, wb ) {
	'use strict';

	var MODULE = wb.view;

	/**
	 * A factory for creating view widgets
	 *
	 * @class wikibase.view.ViewFactory
	 * @licence GNU GPL v2+
	 * @since 0.5
	 * @author Adrian Heine < adrian.heine@wikimedia.de >
	 * @constructor
	 *
	 * @param {util.ContentLanguages} contentLanguages
	 *        Required by the `ValueView` for limiting the list of available languages for
	 *        particular `jQuery.valueview.Expert` instances like the `Expert` responsible
	 *        for `MonoLingualTextValue`s.
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
	 *        object when interacting on a "value" `Variation`.
	 * @param {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
	 *        Required to store changed data.
	 * @param {wikibase.entityIdFormatter.EntityIdHtmlFormatter} entityIdHtmlFormatter
	 *        Required by several views for rendering links to entities.
	 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} entityIdPlainFormatter
	 *        Required by several views for rendering plain text references to entities.
	 * @param {wikibase.store.EntityStore} entityStore
	 *        Required for dynamically gathering `Entity`/`Property` information.
	 * @param {jQuery.valueview.ExpertStore} expertStore
	 *        Required by the `ValueView` for constructing `expert`s for different value types.
	 * @param {valueFormatters.ValueFormatterStore} formatterStore
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
		contentLanguages,
		dataTypeStore,
		entityChangersFactory,
		entityIdHtmlFormatter,
		entityIdPlainFormatter,
		entityStore,
		expertStore,
		formatterStore,
		messageProvider,
		parserStore,
		userLanguages,
		vocabularyLookupApiUrl
	) {
		this._contentLanguages = contentLanguages;
		this._dataTypeStore = dataTypeStore;
		this._entityChangersFactory = entityChangersFactory;
		this._entityIdHtmlFormatter = entityIdHtmlFormatter;
		this._entityIdPlainFormatter = entityIdPlainFormatter;
		this._entityStore = entityStore;
		this._expertStore = expertStore;
		this._formatterStore = formatterStore;
		this._messageProvider = messageProvider;
		this._parserStore = parserStore;
		// Maybe make userLanguages an argument to getEntityView instead of to the constructor
		this._userLanguages = userLanguages;
		this._vocabularyLookupApiUrl = vocabularyLookupApiUrl || null;
	};

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
	 * @property {wikibase.entityChangers.EntityChangersFactory}
	 * @private
	 **/
	SELF.prototype._entityChangersFactory = null;

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
	 * @property {jQuery.valueview.ExpertStore}
	 * @private
	 **/
	SELF.prototype._expertStore = null;

	/**
	 * @property {valueFormatters.ValueFormatterStore}
	 * @private
	 **/
	SELF.prototype._formatterStore = null;

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
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.entityview} The constructed entity view
	 * @throws {Error} If there is no view for the given entity type
	 **/
	SELF.prototype.getEntityView = function( entity, $dom ) {
		return this._getView(
			entity.getType() + 'view',
			$dom,
			{
				buildEntityTermsView: $.proxy( this.getEntityTermsView, this ),
				buildSitelinkGroupListView: $.proxy( this.getSitelinkGroupListView, this ),
				buildStatementGroupListView: $.proxy( this.getStatementGroupListView, this ),
				value: entity
			}
		);
	};

	/**
	 * Construct a suitable terms view for the given fingerprint on the given DOM element
	 *
	 * @param {wikibase.datamodel.Fingerprint} fingerprint
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.entitytermsview} The constructed entity terms view
	 **/
	SELF.prototype.getEntityTermsView = function( fingerprint, $dom ) {
		var value = $.map(
				this._userLanguages,
				function( language ) {
					return {
						language: language,
						label: fingerprint.getLabelFor( language )
							|| new wb.datamodel.Term( language, '' ),
						description: fingerprint.getDescriptionFor( language )
							|| new wb.datamodel.Term( language, '' ),
						aliases: fingerprint.getAliasesFor( language )
							|| new wb.datamodel.MultiTerm( language, [] )
					};
				}
			);

		return this._getView(
			'entitytermsview',
			$dom,
			{
				value: value,
				entityChangersFactory: this._entityChangersFactory,
				helpMessage: this._messageProvider.getMessage( 'wikibase-entitytermsview-input-help-message' )
			}
		);
	};

	/**
	 * Construct a suitable view for the given sitelink set on the given DOM element
	 *
	 * @param {wikibase.datamodel.SiteLinkSet} sitelinkSet
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.sitelinkgrouplistview} The constructed sitelinkgrouplistview
	 **/
	SELF.prototype.getSitelinkGroupListView = function( sitelinkSet, $dom ) {
		return this._getView(
			'sitelinkgrouplistview',
			$dom,
			{
				value: sitelinkSet,
				siteLinksChanger: this._entityChangersFactory.getSiteLinksChanger(),
				entityIdPlainFormatter: this._entityIdPlainFormatter
			}
		);
	};

	/**
	 * Construct a suitable view for the list of statement groups for the given entity on the given DOM element
	 *
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.statementgrouplistview} The constructed statementgrouplistview
	 **/
	SELF.prototype.getStatementGroupListView = function( entity, $dom ) {
		return this._getView(
			'statementgrouplistview',
			$dom,
			{
				value: entity.getStatements(),
				listItemAdapter: this.getListItemAdapterForStatementGroupView( entity.getId() )
			}
		);
	};

	/**
	 * Construct a `ListItemAdapter` for `statementgroupview`s
	 *
	 * @param {string} entityId
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 **/
	SELF.prototype.getListItemAdapterForStatementGroupView = function( entityId ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.statementgroupview,
			newItemOptionsFn: $.proxy( function( value ) {
				return {
					value: value,
					entityIdHtmlFormatter: this._entityIdHtmlFormatter,
					buildStatementListView: $.proxy( this.getStatementListView, this, entityId, value && value.getKey() )
				};
			}, this )
		} );
	};

	/**
	 * Construct a suitable view for the given list of statements on the given DOM element
	 *
	 * @param {wikibase.datamodel.EntityId} entityId
	 * @param {wikibase.datamodel.EntityId|null} propertyId Optionally specifies a property
	 *                                                      all statements should be on or are on
	 * @param {wikibase.datamodel.StatementList} value
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.statementgroupview} The constructed statementlistview
	 **/
	SELF.prototype.getStatementListView = function( entityId, propertyId, value, $dom ) {
		return this._getView(
			'statementlistview',
			$dom,
			{
				value: value,
				listItemAdapter: this.getListItemAdapterForStatementView( entityId, propertyId ),
				claimsChanger: this._entityChangersFactory.getClaimsChanger()
			}
		);
	};

	/**
	 * Construct a `ListItemAdapter` for `statementview`s
	 *
	 * @param {string} entityId
	 * @param {string|null} [propertyId] Optionally a property all statements are or should be on
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 **/
	SELF.prototype.getListItemAdapterForStatementView = function( entityId, propertyId ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.statementview,
			newItemOptionsFn: $.proxy( function( value ) {
				var currentPropertyId = value ? value.getClaim().getMainSnak().getPropertyId() : propertyId;
				return {
					value: value,
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

					buildReferenceListItemAdapter: $.proxy( this.getListItemAdapterForReferenceView, this ),
					buildSnakView: $.proxy(
						this.getSnakView,
						this,
						':' + $.wikibase.statementview.prototype.widgetFullName.toLowerCase()
					),
					claimsChanger: this._entityChangersFactory.getClaimsChanger(),
					entityIdPlainFormatter: this._entityIdPlainFormatter,
					guidGenerator: new wb.utilities.ClaimGuidGenerator( entityId ),
					qualifiersListItemAdapter: this.getListItemAdapterForSnakListView()
				};
			}, this )
		} );
	};

	/**
	 * Construct a `ListItemAdapter` for `referenceview`s
	 *
	 * @param {string} statementGuid
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForReferenceView = function( statementGuid ) {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.referenceview,
			newItemOptionsFn: $.proxy( function( value ) {
				return {
					value: value || null,
					statementGuid: statementGuid,
					dataTypeStore: this._dataTypeStore,
					listItemAdapter: this.getListItemAdapterForSnakListView(),
					referencesChanger: this._entityChangersFactory.getReferencesChanger()
				};
			}, this )
		} );
	};

	/**
	 * Construct a `ListItemAdapter` for `snaklistview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForSnakListView = function() {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.snaklistview,
			newItemOptionsFn: $.proxy( function( value ) {
				return {
					value: value || undefined,
					singleProperty: true,
					listItemAdapter: this.getListItemAdapterForSnakView()
				};
			}, this )
		} );
	};

	/**
	 * Construct a `ListItemAdapter` for `snakview`s
	 *
	 * @return {jQuery.wikibase.listview.ListItemAdapter} The constructed ListItemAdapter
	 */
	SELF.prototype.getListItemAdapterForSnakView = function() {
		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.snakview,
			newItemOptionsFn: $.proxy( function( value ) {
				return this._getSnakViewOptions(
					null,
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
	 * @param {string|null} encapsulatedBy A jQuery selector for getting the encapsulating view
	 * @param {Object} options An object with keys `locked` and `autoStartEditing`
	 * @param {wikibase.datamodel.Snak|null} snak
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.snakview} The constructed snakview
	 */
	SELF.prototype.getSnakView = function( encapsulatedBy, options, snak, $dom ) {
		return this._getView(
			'snakview',
			$dom,
			this._getSnakViewOptions( encapsulatedBy, options, snak )
		);
	};

	/**
	 * @param {string|null} encapsulatedBy A jQuery selector for getting the encapsulating view
	 * @param {Object} options An object with keys `locked` and `autoStartEditing`
	 * @param {wikibase.datamodel.Snak|null} snak
	 */
	SELF.prototype._getSnakViewOptions = function( encapsulatedBy, options, snak ) {
		return {
			value: snak || undefined,
			locked: options.locked,
			autoStartEditing: options.autoStartEditing,
			dataTypeStore: this._dataTypeStore,
			entityIdHtmlFormatter: this._entityIdHtmlFormatter,
			entityIdPlainFormatter: this._entityIdPlainFormatter,
			entityStore: this._entityStore,
			valueViewBuilder: this._getValueViewBuilder(),
			encapsulatedBy: encapsulatedBy
		};
	};

	/**
	 * @private
	 * @return {wikibase.ValueViewBuilder}
	 **/
	SELF.prototype._getValueViewBuilder = function() {
		return new wb.ValueViewBuilder(
			this._expertStore,
			this._formatterStore,
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
