/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( vv, dv ) {
	'use strict';

	/**
	 * @type {Object} Map from property type to expert module name
	 */
	var registeredExperts = require( 'wikibase.experts.modules' );

	/**
	 * @param {dataTypes.DataTypeStore} dataTypeStore
	 * @return {jQuery.valueview.ExpertStore}
	 */
	module.exports = function ( dataTypeStore ) {
		var expertStore = new vv.ExpertStore( vv.experts.UnsupportedValue );

		expertStore.registerDataValueExpert(
			vv.experts.StringValue,
			dv.StringValue.TYPE
		);

		expertStore.registerDataValueExpert(
			vv.experts.UnDeserializableValue,
			dv.UnDeserializableValue.TYPE
		);

		// Register experts for data types defined in Wikibase. Since those data types are defined by a
		// setting, it needs to be checked whether they are actually defined.

		var dataTypeIdToExpertConstructor = resolveExpertModules();

		for ( var dataTypeId in dataTypeIdToExpertConstructor ) {
			var dataType = dataTypeStore.getDataType( dataTypeId );
			if ( dataType ) {
				expertStore.registerDataTypeExpert(
					dataTypeIdToExpertConstructor[ dataTypeId ],
					dataType.getId()
				);
			}
		}

		return expertStore;
	};

	/**
	 * @return {Object} Map from property type to expert constructor
	 */
	function resolveExpertModules() {
		var constructors = {};

		for ( var dataType in registeredExperts ) {
			if ( Object.prototype.hasOwnProperty.call( registeredExperts, dataType ) ) {
				constructors[ dataType ] = require( registeredExperts[ dataType ] );
			}
		}

		return constructors;
	}

}( $.valueview, dataValues ) );
