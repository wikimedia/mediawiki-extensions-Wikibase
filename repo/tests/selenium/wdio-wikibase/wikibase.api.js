'use strict';

const MWBot = require( 'mwbot' );

let getBot = () => {
	return new MWBot( {
		apiUrl: browser.options.baseUrl + '/api.php',
		username: browser.options.username,
		password: browser.options.password
	} );
};

class WikibaseApi {

	/**
	 * Create an item
	 *
	 * @param {string} [label] Optional English label of the item
	 * @param {Object} [data] Optional data to populate the item with
	 * @return {Promise}
	 */
	createItem( label, data ) {
		let bot = getBot(),
			labels = {},
			itemData = {};

		if ( label ) {
			labels = {
				en: {
					language: 'en',
					value: label
				}
			};
		}

		Object.assign( itemData, { labels }, data );
		return bot.loginGetEditToken()
			.then( ( response ) => {
				return new Promise( ( resolve, reject ) => {
					bot.request( {
						action: 'wbeditentity',
						'new': 'item',
						data: JSON.stringify( itemData ),
						token: response.csrftoken
					} ).then( ( response ) => {
						resolve( response.entity.id );
					}, reject );
				} );
			} );
	}

	createProperty( datatype, data ) {
		let bot = getBot(),
			propertyData = {};

		propertyData = Object.assign( {}, { datatype }, data );

		return bot.loginGetEditToken()
			.then( ( response ) => {
				return new Promise( ( resolve, reject ) => {
					bot.request( {
						action: 'wbeditentity',
						'new': 'property',
						data: JSON.stringify( propertyData ),
						token: response.csrftoken
					} ).then( ( response ) => {
						resolve( response.entity.id );
					}, reject );
				} );
			} );
	}

	getEntity( id ) {
		let bot = getBot();

		return new Promise( ( resolve, reject ) => {
			bot.request( {
				ids: id,
				action: 'wbgetentities',
				token: bot.editToken
			} ).then( ( response ) => {
				resolve( response.entities[ id ] );
			}, reject );
		} );
	}

	protectEntity( entityId ) {
		let bot = getBot();

		return Promise.all( [
			bot.login( {
				username: browser.options.username,
				password: browser.options.password
			} ).then( () => {
				return bot.request( {
					action: 'query',
					meta: 'tokens',
					format: 'json'
				} ).then( csrfTokenResponse => {
					return csrfTokenResponse.query.tokens.csrftoken;
				} );
			} ),
			bot.request( {
				action: 'wbgetentities',
				format: 'json',
				ids: entityId,
				props: 'info'
			} ).then( getEntitiesResponse => {
				return getEntitiesResponse.entities[ entityId ].title;
			} )
		] )
			.then( ( results ) => {
				const csrfToken = results[ 0 ],
					entityTitle = results[ 1 ];
				return bot.request( {
					action: 'protect',
					title: entityTitle,
					protections: 'edit=sysop',
					token: csrfToken
				} );
			} );
	}

	getProperty( datatype ) {
		let envName = `WIKIBASE_PROPERTY_${ datatype.toUpperCase() }`;
		if ( envName in process.env ) {
			return Promise.resolve( process.env[ envName ] );
		} else {
			return this.createProperty( datatype ).then( ( propertyId ) => {
				process.env[ envName ] = propertyId;
				return propertyId;
			} );
		}
	}

}

module.exports = new WikibaseApi();
