( function( $, wb ) {
	'use strict';

	var MODULE = wb.view;

	/**
	 * A factory for creating view widgets
	 *
	 * @class wikibase.view.ViewFactory
	 * @license GNU GPL v2+
	 * @author Adrian Heine < adrian.heine@wikimedia.de >
	 *
	 * @param {util.ContentLanguages} contentLanguages
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 * @param {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
	 * @param {wikibase.store.EntityStore} entityStore
	 * @param {jQuery.valueview.ExpertStore} expertStore
	 * @param {valueFormatters.ValueFormatterStore} formatterStore
	 * @param {util.MessageProvider} messageProvider
	 * @param {valueParsers.ValueParserStore} parserStore
	 * @param {string[]} userLanguages An array of language codes, the first being the UI language
	 */
	var SELF = MODULE.ViewFactory = function ViewFactory(
		contentLanguages,
		dataTypeStore,
		entityChangersFactory,
		entityStore,
		expertStore,
		formatterStore,
		messageProvider,
		parserStore,
		userLanguages
	) {
		this._contentLanguages = contentLanguages;
		this._dataTypeStore = dataTypeStore;
		this._entityChangersFactory = entityChangersFactory;
		this._entityStore = entityStore;
		this._expertStore = expertStore;
		this._formatterStore = formatterStore;
		this._messageProvider = messageProvider;
		this._parserStore = parserStore;
		this._userLanguages = userLanguages;
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
	 * Construct a suitable view for the given entity on the given DOM element
	 *
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {jQuery} $dom
	 * @return {Object} The constructed entity view
	 **/
	SELF.prototype.getEntityView = function( entity, $dom ) {
		return this._getView(
			entity.getType() + 'view',
			$dom,
			{
				dataTypeStore: this._dataTypeStore,
				entityChangersFactory: this._entityChangersFactory,
				entityStore: this._entityStore,
				languages: this._userLanguages,
				value: entity,
				valueViewBuilder: this._getValueViewBuilder()
			}
		);
	};

	SELF.prototype._getValueViewBuilder = function() {
		return new wb.ValueViewBuilder(
			this._expertStore,
			this._formatterStore,
			this._parserStore,
			this._userLanguages && this._userLanguages[0],
			this._messageProvider,
			this._contentLanguages
		);
	};

	SELF.prototype._getView = function( viewName, $dom, options ) {
		if( !$.wikibase[ viewName ] ) {
			throw new Error( 'View ' + viewName + ' does not exist' );
		}

		$dom[ viewName ]( options );

		return $dom.data( viewName );
	};

}( jQuery, wikibase ) );
