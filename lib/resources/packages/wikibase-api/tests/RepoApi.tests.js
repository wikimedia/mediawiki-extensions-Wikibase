/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, QUnit, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.api.RepoApi', {
		beforeEach: function () {
			mw._mockUser = null;
		}
	} );

	/**
 * Instantiates a `wikibase.api.RepoApi` object with the relevant method being overwritten and
 * having applied a SinonJS spy.
 *
 * @param {string} [getOrPost='post'] Whether to mock/spy the `get` or `post` request.
 * @param {string[]} [tags] Which tags to configure in the RepoApi.
 * @return {Object}
 */
	function mockApi( getOrPost, tags ) {
		var api = {
				postWithToken: function () {},
				get: function () {}
			},
			spyMethod = getOrPost !== 'get' ? 'postWithToken' : 'get';

		return {
			spy: sinon.spy( api, spyMethod ),
			api: new wb.api.RepoApi( api, 'testlanguage', tags || [ 'a', 'b' ] )
		};
	}

	/**
 * Returns all request parameters submitted to the function performing the `get` or `post` request.
 *
 * @param {Object} spy The SinonJS spy to extract the parameters from.
 * @param {number} [callIndex=0] The call index if multiple API calls have been performed on the same spy.
 * @return {Object}
 */
	function getParams( spy, callIndex ) {
		callIndex = callIndex || 0;
		return spy.displayName === 'postWithToken' ? spy.args[ callIndex ][ 1 ] : spy.args[ callIndex ][ 0 ];
	}

	/**
 * Returns a specific parameter submitted to the function performing the `get` or `post` request.
 *
 * @param {Object} spy The SinonJS spy to extract the parameters from.
 * @param {string} paramName
 * @param {number} [callIndex=0] The call index if multiple API calls have been performed on the same spy.
 * @return {string}
 */
	function getParam( spy, paramName, callIndex ) {
		return getParams( spy, callIndex || 0 )[ paramName ];
	}

	QUnit.test( 'createEntity()', function ( assert ) {
		var mock = mockApi();

		mock.api.createEntity( 'item' );
		mock.api.createEntity( 'property', { 'I am': 'data' } );

		assert.ok( mock.spy.calledTwice, 'Triggered API calls.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbeditentity',
			'Verified API module being called.'
		);

		assert.strictEqual(
			getParam( mock.spy, 'new' ),
			'item',
			'Verified submitting entity type.'
		);

		assert.strictEqual(
			getParam( mock.spy, 'data' ),
			JSON.stringify( {} ),
			'Verified not submitting any data by default.'
		);

		assert.strictEqual(
			getParam( mock.spy, 'data', 1 ),
			JSON.stringify( { 'I am': 'data' } ),
			'Verified submitting "data" field.'
		);

		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );
	} );

	QUnit.test( 'editEntity()', function ( assert ) {
		var mock = mockApi();

		mock.api.editEntity( 'entity id', 12345, { 'I am': 'entity data' }, true );

		assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbeditentity',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'id' ), 'entity id' );
		assert.strictEqual( getParam( mock.spy, 'baserevid' ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'data' ), JSON.stringify( { 'I am': 'entity data' } ) );
		assert.strictEqual( getParam( mock.spy, 'clear' ), true );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );
	} );

	QUnit.test( 'formatValue()', function ( assert ) {
		var mock = mockApi( 'get' );

		mock.api.formatValue(
			{ 'I am': 'DataValue serialization' },
			{ lang: 'language code', option: 'option value' },
			'data type id',
			'output format'
		);

		mock.api.formatValue( { 'I am': 'DataValue serialization' } );

		// make sure that property id overrides data type id
		mock.api.formatValue(
			{ 'I am': 'DataValue serialization' },
			{ option: 'option value' },
			'data type id',
			'output format',
			'property id'
		);

		assert.ok( mock.spy.calledThrice, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbformatvalue',
			'Verified API module being called.'
		);

		assert.strictEqual(
			getParam( mock.spy, 'datavalue' ),
			JSON.stringify( { 'I am': 'DataValue serialization' } )
		);
		assert.strictEqual(
			getParam( mock.spy, 'options' ),
			JSON.stringify( { lang: 'language code', option: 'option value' } )
		);
		assert.strictEqual( getParam( mock.spy, 'datatype' ), 'data type id' );
		assert.strictEqual( getParam( mock.spy, 'generate' ), 'output format' );
		assert.strictEqual( getParam( mock.spy, 'property' ), undefined );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'language code' );

		assert.strictEqual(
			getParam( mock.spy, 'datavalue', 1 ),
			JSON.stringify( { 'I am': 'DataValue serialization' } )
		);
		assert.strictEqual( getParam( mock.spy, 'options', 1 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'datatype', 1 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'generate', 1 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'property', 1 ), undefined );

		assert.strictEqual(
			getParam( mock.spy, 'datavalue', 2 ),
			JSON.stringify( { 'I am': 'DataValue serialization' } )
		);
		assert.strictEqual(
			getParam( mock.spy, 'options', 2 ),
			JSON.stringify( { option: 'option value' } )
		);
		assert.strictEqual( getParam( mock.spy, 'datatype', 2 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'generate', 2 ), 'output format' );
		assert.strictEqual( getParam( mock.spy, 'property', 2 ), 'property id' );
	} );

	QUnit.test( 'getEntities()', function ( assert ) {
		var mock = mockApi( 'get' );

		mock.api.getEntities(
			[ 'entity id 1', 'entity id 2' ],
			[ 'property1', 'property2' ],
			[ 'language code 1', 'language code 2' ]
		);

		mock.api.getEntities(
			'entity id',
			'property',
			'language code'
		);

		mock.api.getEntities( 'entity id' );

		assert.ok( mock.spy.calledThrice, 'Triggered API calls.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbgetentities',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'ids' ), '\x1fentity id 1\x1fentity id 2' );
		assert.strictEqual( getParam( mock.spy, 'props' ), '\x1fproperty1\x1fproperty2' );
		assert.strictEqual( getParam( mock.spy, 'languages' ), '\x1flanguage code 1\x1flanguage code 2' );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );

		assert.strictEqual( getParam( mock.spy, 'ids', 1 ), '\x1fentity id' );
		assert.strictEqual( getParam( mock.spy, 'props', 1 ), '\x1fproperty' );
		assert.strictEqual( getParam( mock.spy, 'languages', 1 ), '\x1flanguage code' );

		assert.strictEqual( getParam( mock.spy, 'ids', 2 ), '\x1fentity id' );
		assert.strictEqual( getParam( mock.spy, 'props', 2 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'languages', 2 ), undefined );
	} );

	QUnit.test( 'getEntitiesByPage()', function ( assert ) {
		var mock = mockApi( 'get' );

		mock.api.getEntitiesByPage(
			[ 'site id 1', 'site id 2' ],
			'title',
			[ 'property1', 'property2' ],
			[ 'language code 1', 'language code 2' ],
			true
		);

		mock.api.getEntitiesByPage(
			'site id',
			[ 'title1', 'title2' ],
			[ 'property1', 'property2' ],
			[ 'language code 1', 'language code 2' ],
			true
		);

		mock.api.getEntitiesByPage(
			'site id',
			'title',
			'property',
			'language code',
			false
		);

		mock.api.getEntitiesByPage( 'site id', 'title' );
		mock.api.getEntitiesByPage( [ 'site id' ], 'title' );
		mock.api.getEntitiesByPage( 'site id', [ 'title' ] );

		assert.strictEqual( mock.spy.callCount, 6, 'Triggered API calls.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbgetentities',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'sites' ), '\x1fsite id 1\x1fsite id 2' );
		assert.strictEqual( getParam( mock.spy, 'titles' ), '\x1ftitle' );
		assert.strictEqual( getParam( mock.spy, 'props' ), '\x1fproperty1\x1fproperty2' );
		assert.strictEqual( getParam( mock.spy, 'languages' ), '\x1flanguage code 1\x1flanguage code 2' );
		assert.strictEqual( getParam( mock.spy, 'normalize' ), true );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );

		assert.strictEqual( getParam( mock.spy, 'sites', 1 ), '\x1fsite id' );
		assert.strictEqual( getParam( mock.spy, 'titles', 1 ), '\x1ftitle1\x1ftitle2' );
		assert.strictEqual( getParam( mock.spy, 'props', 1 ), '\x1fproperty1\x1fproperty2' );
		assert.strictEqual( getParam( mock.spy, 'languages' ), '\x1flanguage code 1\x1flanguage code 2' );
		assert.strictEqual( getParam( mock.spy, 'normalize', 1 ), true );

		assert.strictEqual( getParam( mock.spy, 'sites', 2 ), '\x1fsite id' );
		assert.strictEqual( getParam( mock.spy, 'titles', 2 ), '\x1ftitle' );
		assert.strictEqual( getParam( mock.spy, 'props', 2 ), '\x1fproperty' );
		assert.strictEqual( getParam( mock.spy, 'languages', 2 ), '\x1flanguage code' );
		assert.strictEqual( getParam( mock.spy, 'normalize', 2 ), false );

		assert.strictEqual( getParam( mock.spy, 'sites', 3 ), '\x1fsite id' );
		assert.strictEqual( getParam( mock.spy, 'titles', 3 ), '\x1ftitle' );
		assert.strictEqual( getParam( mock.spy, 'props', 3 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'languages', 3 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'normalize', 3 ), undefined );

		assert.strictEqual( getParam( mock.spy, 'sites', 4 ), '\x1fsite id' );
		assert.strictEqual( getParam( mock.spy, 'titles', 4 ), '\x1ftitle' );
		assert.strictEqual( getParam( mock.spy, 'props', 4 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'languages', 4 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'normalize', 4 ), undefined );

		assert.strictEqual( getParam( mock.spy, 'sites', 5 ), '\x1fsite id' );
		assert.strictEqual( getParam( mock.spy, 'titles', 5 ), '\x1ftitle' );
		assert.strictEqual( getParam( mock.spy, 'props', 5 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'languages', 5 ), undefined );
		assert.strictEqual( getParam( mock.spy, 'normalize', 5 ), undefined );
	} );

	QUnit.test( 'parseValue()', function ( assert ) {
		var mock = mockApi( 'get' );

		mock.api.parseValue(
			'parser id',
			[ 'serialization1', 'serialization2' ],
			{ lang: 'language code', option: 'option value' }
		);
		mock.api.parseValue( 'parser id', [ 'serialization with p|pe' ] );

		assert.ok( mock.spy.calledTwice, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbparsevalue',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'parser' ), 'parser id' );
		assert.strictEqual( getParam( mock.spy, 'values' ), '\x1fserialization1\x1fserialization2' );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'language code' );
		assert.strictEqual(
			getParam( mock.spy, 'options' ),
			JSON.stringify( { lang: 'language code', option: 'option value' } )
		);

		assert.strictEqual( getParam( mock.spy, 'parser', 1 ), 'parser id' );
		assert.strictEqual( getParam( mock.spy, 'values', 1 ), '\x1fserialization with p|pe' );
		assert.strictEqual( getParam( mock.spy, 'errorformat', 1 ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang', 1 ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'options', 1 ), undefined );
	} );

	QUnit.test( 'setLabel(), setDescription()', function ( assert ) {
		var subjects = [ 'Label', 'Description' ];

		for ( var i = 0; i < subjects.length; i++ ) {
			var mock = mockApi();

			mock.api[ 'set' + subjects[ i ] ]( 'entity id', 12345, 'text', 'language code' );

			assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

			assert.strictEqual(
				getParam( mock.spy, 'action' ),
				'wbset' + subjects[ i ].toLowerCase(),
				'Verified API module being called.'
			);

			assert.strictEqual( getParam( mock.spy, 'id' ), 'entity id' );
			assert.strictEqual( getParam( mock.spy, 'baserevid' ), 12345 );
			assert.strictEqual( getParam( mock.spy, 'value' ), 'text' );
			assert.strictEqual( getParam( mock.spy, 'language' ), 'language code' );
			assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );
		}
	} );

	QUnit.test( 'setAliases()', function ( assert ) {
		var mock = mockApi();

		mock.api.setAliases(
			'entity id', 12345, [ 'alias1', 'alias2' ], [ 'alias-remove with p|pe' ], 'language code'
		);

		assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbsetaliases',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'id' ), 'entity id' );
		assert.strictEqual( getParam( mock.spy, 'baserevid' ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'add' ), '\x1falias1\x1falias2' );
		assert.strictEqual( getParam( mock.spy, 'remove' ), '\x1falias-remove with p|pe' );
		assert.strictEqual( getParam( mock.spy, 'language' ), 'language code' );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );
	} );

	QUnit.test( 'setClaim()', function ( assert ) {
		var mock = mockApi();

		mock.api.setClaim( { 'I am': 'a Claim serialization' }, 12345, 67890 );
		mock.api.setClaim( { 'I am': 'a Claim serialization' }, 12345 );

		assert.ok( mock.spy.calledTwice, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbsetclaim',
			'Verified API module being called.'
		);

		assert.strictEqual(
			getParam( mock.spy, 'claim' ),
			JSON.stringify( { 'I am': 'a Claim serialization' } )
		);
		assert.strictEqual( getParam( mock.spy, 'baserevid' ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'index' ), 67890 );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );

		assert.strictEqual(
			getParam( mock.spy, 'claim', 1 ),
			JSON.stringify( { 'I am': 'a Claim serialization' } )
		);
		assert.strictEqual( getParam( mock.spy, 'baserevid', 1 ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'index', 1 ), undefined );
	} );

	QUnit.test( 'removeClaim()', function ( assert ) {
		var mock = mockApi();

		mock.api.removeClaim( 'claim GUID', 12345 );
		mock.api.removeClaim( 'claim GUID' );

		assert.ok( mock.spy.calledTwice, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbremoveclaims',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'claim' ), 'claim GUID' );
		assert.strictEqual( getParam( mock.spy, 'baserevid' ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );

		assert.strictEqual( getParam( mock.spy, 'claim', 1 ), 'claim GUID' );
		assert.strictEqual( getParam( mock.spy, 'baserevid', 1 ), undefined );
	} );

	QUnit.test( 'removeClaim() without tags', function ( assert ) {
		var mock = mockApi( undefined, [] );

		mock.api.removeClaim( 'claim GUID', 12345 );

		assert.ok( mock.spy.calledOnce, 'Triggered API call.' );

		assert.strictEqual( getParam( mock.spy, 'action' ), 'wbremoveclaims' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), undefined );
	} );

	QUnit.test( 'setSitelink()', function ( assert ) {
		var mock = mockApi();

		mock.api.setSitelink(
			'entity id', 12345, 'site id', 'page name', [ 'entity id of badge1', 'entity id of badge 2' ]
		);
		mock.api.setSitelink( 'entity id', 12345, 'site id', 'page name' );

		assert.ok( mock.spy.calledTwice, 'Triggered API call.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbsetsitelink',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'id' ), 'entity id' );
		assert.strictEqual( getParam( mock.spy, 'baserevid' ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'linksite' ), 'site id' );
		assert.strictEqual( getParam( mock.spy, 'linktitle' ), 'page name' );
		assert.strictEqual(
			getParam( mock.spy, 'badges' ),
			'\x1fentity id of badge1\x1fentity id of badge 2'
		);
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );

		assert.strictEqual( getParam( mock.spy, 'id', 1 ), 'entity id' );
		assert.strictEqual( getParam( mock.spy, 'baserevid', 1 ), 12345 );
		assert.strictEqual( getParam( mock.spy, 'linksite', 1 ), 'site id' );
		assert.strictEqual( getParam( mock.spy, 'linktitle', 1 ), 'page name' );
		assert.strictEqual( getParam( mock.spy, 'badges', 1 ), undefined );
	} );

	QUnit.test( 'mergeItems() - no ignoreConflicts', function ( assert ) {
		var mock = mockApi();

		mock.api.mergeItems( 'entity id from', 'entity id to' );

		assert.ok( mock.spy.calledOnce, 'Triggered API calls.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbmergeitems',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'fromid' ), 'entity id from' );
		assert.strictEqual( getParam( mock.spy, 'toid' ), 'entity id to' );
		assert.strictEqual( getParam( mock.spy, 'ignoreconflicts' ), undefined );
		assert.strictEqual( getParam( mock.spy, 'summary' ), undefined );
		assert.strictEqual( getParam( mock.spy, 'errorformat' ), 'plaintext' );
		assert.strictEqual( getParam( mock.spy, 'uselang' ), 'testlanguage' );
		assert.strictEqual( getParam( mock.spy, 'tags' ), '\x1fa\x1fb' );
	} );

	QUnit.test( 'mergeItems() - single ignoreConflicts', function ( assert ) {
		var mock = mockApi();

		mock.api.mergeItems(
			'entity id from',
			'entity id to',
			'property to ignore conflict for',
			'edit summary'
		);

		assert.ok( mock.spy.calledOnce, 'Triggered API calls.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbmergeitems',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'fromid' ), 'entity id from' );
		assert.strictEqual( getParam( mock.spy, 'toid' ), 'entity id to' );
		assert.strictEqual(
			getParam( mock.spy, 'ignoreconflicts' ),
			'\x1fproperty to ignore conflict for'
		);
		assert.strictEqual( getParam( mock.spy, 'summary' ), 'edit summary' );
	} );

	QUnit.test( 'mergeItems() - multiple ignoreConflicts', function ( assert ) {
		var mock = mockApi();

		mock.api.mergeItems(
			'entity id from',
			'entity id to',
			[ 'property to ignore conflict for 1', 'property to ignore conflict for 2' ],
			'edit summary'
		);

		assert.ok( mock.spy.calledOnce, 'Triggered API calls.' );

		assert.strictEqual(
			getParam( mock.spy, 'action' ),
			'wbmergeitems',
			'Verified API module being called.'
		);

		assert.strictEqual( getParam( mock.spy, 'fromid' ), 'entity id from' );
		assert.strictEqual( getParam( mock.spy, 'toid' ), 'entity id to' );
		assert.strictEqual(
			getParam( mock.spy, 'ignoreconflicts' ),
			'\x1fproperty to ignore conflict for 1\x1fproperty to ignore conflict for 2'
		);
		assert.strictEqual( getParam( mock.spy, 'summary' ), 'edit summary' );
	} );

	QUnit.test( 'normalizeMultiValue()', function ( assert ) {
		var mock = mockApi();

		assert.strictEqual( mock.api.normalizeMultiValue( [] ), '', 'empty array -> empty string' );
		assert.strictEqual(
			mock.api.normalizeMultiValue( [ 'val1', 'val2' ] ),
			'\x1fval1\x1fval2',
			'array values are prefixed with `\\x1f`'
		);
	} );

	QUnit.test( 'check post asserts user when logged in ', function ( assert ) {
		var mock = mockApi();
		mw._mockUser = 'fooBarUser';
		mock.api.post( { action: 'foobar' } );
		assert.strictEqual( getParam( mock.spy, 'assertuser' ), 'fooBarUser' );
	} );

	QUnit.test( 'check post does not assert user when not logged in ', function ( assert ) {
		var mock = mockApi();

		mock.api.post( { action: 'foobar' } );
		assert.strictEqual( getParam( mock.spy, 'assertuser' ), undefined );
	} );

}( wikibase, QUnit, sinon ) );
