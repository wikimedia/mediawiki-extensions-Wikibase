/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, QUnit, sinon ) {
	'use strict';

QUnit.module( 'wikibase.api.RepoApi' );

/**
 * Instantiates a `wikibase.api.RepoApi` object with the relevant method being overwritten and
 * having applied a SinonJS spy.
 *
 * @param {string} [getOrPost='post'] Whether to mock/spy the `get` or `post` request.
 * @return {Object}
 */
function mockApi( getOrPost ) {
	var api = new mw.Api(),
		spyMethod = getOrPost !== 'get' ? 'postWithToken' : 'get';

	api.postWithToken = function() {};
	api.get = function() {};

	return {
		spy: sinon.spy( api, spyMethod ),
		api: new wb.api.RepoApi( api )
	};
}

/**
 * Returns all request parameters submitted to the function performing the `get` or `post` request.
 *
 * @param {Object} spy The SinonJS spy to extract the parameters from.
 * @param [callIndex=0] The call index if multiple API calls have been performed on the same spy.
 * @return {Object}
 */
function getParams( spy, callIndex ) {
	callIndex = callIndex || 0;
	return spy.displayName === 'postWithToken' ? spy.args[callIndex][1] : spy.args[callIndex][0];
}

/**
 * Returns a specific parameter submitted to the function performing the `get` or `post` request.
 *
 * @param {Object} spy The SinonJS spy to extract the parameters from.
 * @param {string} paramName
 * @param [callIndex=0] The call index if multiple API calls have been performed on the same spy.
 * @return {string}
 */
function getParam( spy, paramName, callIndex ) {
	return getParams( spy, callIndex || 0 )[paramName];
}

QUnit.test( 'createEntity()', function( assert ) {
	var mock = mockApi();

	mock.api.createEntity( 'item' );
	mock.api.createEntity( 'property', {
		datatype: 'string'
	} );

	assert.ok( mock.spy.calledTwice, 'Triggered API calls.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbeditentity',
		'Verified API module being called.'
	);

	assert.equal(
		getParam( mock.spy, 'new' ),
		'item',
		'Verified submitting entity type.'
	);

	assert.equal(
		getParam( mock.spy, 'data' ),
		JSON.stringify( {} ),
		'Verified not submitting any data by default.'
	);

	assert.equal(
		getParam( mock.spy, 'data', 1 ),
		JSON.stringify( { datatype: 'string' } ),
		'Verified submitting "datatype" field.'
	);
} );

QUnit.test( 'editEntity()', function( assert ) {
	var mock = mockApi(),
		data = {
			labels: {
				de: {
					language: 'de',
					value: 'label'
				}
			}
		};

	mock.api.editEntity( 'entity id', 12345, data );

	assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbeditentity',
		'Verified API module being called.'
	);

	assert.equal( getParam( mock.spy, 'id' ), 'entity id' );
	assert.equal( getParam( mock.spy, 'baserevid' ), 12345 );
	assert.equal( getParam( mock.spy, 'data' ), JSON.stringify( data ) );
} );

QUnit.test( 'searchEntities()', function( assert ) {
	var mock = mockApi( 'get' );

	mock.api.searchEntities( 'label', 'language code', 'entity type', 10, 5 );

	assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbsearchentities',
		'Verified API module being called.'
	);

	assert.equal( getParam( mock.spy, 'search' ), 'label' );
	assert.equal( getParam( mock.spy, 'language' ), 'language code' );
	assert.equal( getParam( mock.spy, 'type' ), 'entity type' );
	assert.equal( getParam( mock.spy, 'limit' ), 10 );
	assert.equal( getParam( mock.spy, 'continue' ), 5 );
} );

QUnit.test( 'createClaim()', function( assert ) {
	var mock = mockApi();

	mock.api.createClaim( 'entity id', 12345, 'snak type', 'property id', 'snak value' );

	assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbcreateclaim',
		'Verified API module being called.'
	);

	assert.equal( getParam( mock.spy, 'entity' ), 'entity id' );
	assert.equal( getParam( mock.spy, 'baserevid' ), 12345 );
	assert.equal( getParam( mock.spy, 'snaktype' ), 'snak type' );
	assert.equal( getParam( mock.spy, 'property' ), 'property id' );
	assert.equal( getParam( mock.spy, 'value' ), JSON.stringify( 'snak value' ) );
} );

QUnit.test( 'getClaims()', function( assert ) {
	var mock = mockApi( 'get' );

	mock.api.getClaims( 'entity id', 'property id', 'claim GUID', 'rank', 'claim props' );

	assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbgetclaims',
		'Verified API module being called.'
	);

	assert.equal( getParam( mock.spy, 'entity' ), 'entity id' );
	assert.equal( getParam( mock.spy, 'property' ), 'property id' );
	assert.equal( getParam( mock.spy, 'claim' ), 'claim GUID' );
	assert.equal( getParam( mock.spy, 'rank' ), 'rank' );
	assert.equal( getParam( mock.spy, 'props' ), 'claim props' );
} );

QUnit.test( 'setLabel(), setDescription()', function( assert ) {
	var subjects = ['Label', 'Description'];

	for( var i = 0; i < subjects.length; i++ ) {
		var mock = mockApi();

		mock.api['set' + subjects[i]]( 'entity id', 12345, 'text', 'language code' );

		assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

		assert.equal(
			getParam( mock.spy, 'action' ),
			'wbset' + subjects[i].toLowerCase(),
			'Verified API module being called.'
		);

		assert.equal( getParam( mock.spy, 'id' ), 'entity id' );
		assert.equal( getParam( mock.spy, 'baserevid' ), 12345 );
		assert.equal( getParam( mock.spy, 'value' ), 'text' );
		assert.equal( getParam( mock.spy, 'language' ), 'language code' );
	}
} );

QUnit.test( 'setAliases()', function( assert ) {
	var mock = mockApi();

	mock.api.setAliases(
		'entity id', 12345, ['alias1', 'alias2'], ['alias-remove'], 'language code'
	);

	assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbsetaliases',
		'Verified API module being called.'
	);

	assert.equal( getParam( mock.spy, 'id' ), 'entity id' );
	assert.equal( getParam( mock.spy, 'baserevid' ), 12345 );
	assert.equal( getParam( mock.spy, 'add' ), 'alias1|alias2' );
	assert.equal( getParam( mock.spy, 'remove' ), 'alias-remove' );
	assert.equal( getParam( mock.spy, 'language' ), ['language code'] );
} );

QUnit.test( 'setSiteLink()', function( assert ) {
	var mock = mockApi();

	mock.api.setSitelink(
		'entity id', 12345, 'site id', 'page name', ['entity id of badge1', 'entity id of badge 2']
	);

	assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

	assert.equal(
		getParam( mock.spy, 'action' ),
		'wbsetsitelink',
		'Verified API module being called.'
	);

	assert.equal( getParam( mock.spy, 'id' ), 'entity id' );
	assert.equal( getParam( mock.spy, 'baserevid' ), 12345 );
	assert.equal( getParam( mock.spy, 'linksite' ), 'site id' );
	assert.equal( getParam( mock.spy, 'linktitle' ), 'page name' );
	assert.equal( getParam( mock.spy, 'badges' ), 'entity id of badge1|entity id of badge 2' );
} );

}( mediaWiki, wikibase, QUnit, sinon ) );
