import resolveMainSnak from '@/store/entity/statements/resolveMainSnak';
import newStatementsState from './newStatementsState';
import Snak from '@/datamodel/Snak';

describe( 'resolveMainSnak', () => {
	it( 'resolve the path to the mainsnak of a statement', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
		};

		const map = newStatementsState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		expect(
			resolveMainSnak( map, { entityId: 'Q42', propertyId: 'P42', index: 0 } ),
		).toStrictEqual( mainsnak );
	} );

	it( 'returns null if there is no statement map', () => {
		expect(
			resolveMainSnak( {}, {
				entityId: 'Q42',
				propertyId: 'P23',
				index: 0,
			} ),
		).toBeNull();
	} );

	it( 'returns null if there is entity id', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
		};

		const map = newStatementsState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		expect(
			resolveMainSnak( map, {
				entityId: 'Q23',
				propertyId: 'P23',
				index: 0,
			} ),
		).toBeNull();
	} );

	it( 'returns null if there is no property with the given id', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
		};

		const map = newStatementsState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		expect(
			resolveMainSnak( map, { entityId: 'Q42', propertyId: 'P23', index: 0 } ),
		).toBeNull();
	} );

	it( 'returns null if there is no property with the given index', () => {
		const mainsnak: Snak = {
			property: 'P42',
			snaktype: 'somevalue',
			datatype: 'url',
		};

		const map = newStatementsState( { Q42: {
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak,
			} ],
		} } );

		expect(
			resolveMainSnak( map, { entityId: 'Q42', propertyId: 'P42', index: 23 } ),
		).toBeNull();
	} );
} );
