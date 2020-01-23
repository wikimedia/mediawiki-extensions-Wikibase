import newStatementState from './newStatementState';
import Snak from '@/datamodel/Snak';
import { MainSnakPath } from '../../../../src/store/statements/MainSnakPath';

describe( 'resolveMainSnak', () => {
	it( 'resolve the path to the mainsnak of a statement', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
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

	it( 'returns null if there is no statement map', () => {
		const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );
		expect(
			mainSnakPath.resolveSnakInStatement( {} ),
		).toBeNull();
	} );

	it( 'returns null if the entity id is missing from the state', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
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
			mainSnakPath.resolveSnakInStatement( state ),
		).toBeNull();
	} );

	it( 'returns null if there is no property with the given id', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
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
			mainSnakPath.resolveSnakInStatement( state ),
		).toBeNull();
	} );

	it( 'returns null if there is no property with the given index', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
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
			mainSnakPath.resolveSnakInStatement( state ),
		).toBeNull();
	} );
} );
