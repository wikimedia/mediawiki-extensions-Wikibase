import {
	DataType,
	Snak,
	Statement,
} from '@wmde/wikibase-datamodel-types';
import newStatementState from './newStatementState';
import { MainSnakPath } from '@/store/statements/MainSnakPath';

describe( 'resolveMainSnak', () => {
	it( 'resolves the path to the statement group', () => {
		const statements: Statement[] = [ {
			type: 'statement',
			id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
			rank: 'normal',
			mainsnak: {
				property: 'P42',
				snaktype: 'somevalue',
				datatype: 'url' as DataType,
			},
		} ];

		const state = newStatementState( { Q42: {
			P42: statements,
		} } );

		const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );

		expect( mainSnakPath.resolveStatementGroup( state ) ).toStrictEqual( statements );
	} );

	it( 'resolve the path to the mainsnak of a statement', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url' as DataType,
		};

		const state = newStatementState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );

		expect( mainSnakPath.resolveSnakInStatement( state ) ).toStrictEqual( mainsnak );
	} );

	it( 'resolves the path to the statement', () => {
		const statement: Statement = {
			type: 'statement',
			id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
			rank: 'normal',
			mainsnak: {
				property: 'P42',
				snaktype: 'somevalue',
				datatype: 'url' as DataType,
			},
		};

		const state = newStatementState( { Q42: {
			P42: [ statement ],
		} } );

		const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );

		expect( mainSnakPath.resolveStatement( state ) ).toStrictEqual( statement );
	} );

	type Method = 'resolveStatement'|'resolveSnakInStatement'|'resolveStatementGroup';
	const methods: Method[] = [ 'resolveStatement', 'resolveSnakInStatement', 'resolveStatementGroup' ];

	it.each( methods )( '%s returns null if there is no statement map', ( method: Method ) => {
		const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );
		expect(
			mainSnakPath[ method ]( {} ),
		).toBeNull();
	} );

	it.each( methods )( 'returns null if the entity id is missing from the state', ( method: Method ) => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url' as DataType,
		};

		const state = newStatementState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		const mainSnakPath = new MainSnakPath( 'Q23', 'P23', 0 );

		expect(
			mainSnakPath[ method ]( state ),
		).toBeNull();
	} );

	it.each( methods )( '%s returns null if there is no property with the given id', ( method: Method ) => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url' as DataType,
		};

		const state = newStatementState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		const mainSnakPath = new MainSnakPath( 'Q42', 'P23', 0 );

		expect(
			mainSnakPath[ method ]( state ),
		).toBeNull();
	} );

	it.each(
		[ 'resolveStatement', 'resolveSnakInStatement' ] as Method[],
	)( '%s returns null if there is no property with the given index', ( method ) => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url' as DataType,
		};

		const state = newStatementState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 23 );

		expect(
			mainSnakPath[ method ]( state ),
		).toBeNull();
	} );
} );
