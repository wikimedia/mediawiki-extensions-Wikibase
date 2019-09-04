import { getters } from '@/store/entity/statements/getters';
import newApplicationState from '../../newApplicationState';
import StatementMap from '@/datamodel/StatementMap';
import newStatementsState from './newStatementsState';
import Snak from '@/datamodel/Snak';
import {
	STATEMENTS_CONTAINS_ENTITY,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
	STATEMENTS_MAP,
} from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';

describe( 'statements/Getters', () => {
	it( 'determines if statements are contained for are given entity id', () => {
		const statements = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} as StatementMap };

		const entityId = 'Q42';

		expect( getters[ STATEMENTS_CONTAINS_ENTITY ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId ) ).toBe( true );

		expect( getters[ STATEMENTS_CONTAINS_ENTITY ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( 'Q23' ) ).toBe( false );

	} );

	it( 'determines if a statement on property exists', () => {
		const statements = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} as StatementMap };
		const entityId = 'Q42';

		expect( getters[ STATEMENTS_PROPERTY_EXISTS ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId, 'P23' ) ).toBe( true );
		expect( getters[ STATEMENTS_PROPERTY_EXISTS ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId, 'P42' ) ).toBe( false );
	} );

	it( 'determines if a statement on property is ambiguous', () => {
		const statements = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			}, {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} as StatementMap };

		const entityId = 'Q42';

		expect( getters[ STATEMENTS_IS_AMBIGUOUS ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId, 'P23' ) ).toBe( true );
		expect( getters[ STATEMENTS_IS_AMBIGUOUS ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId, 'P42' ) ).toBe( false );
		expect( getters[ STATEMENTS_IS_AMBIGUOUS ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId, 'P21' ) ).toBe( false );
	} );

	it( 'returns the full statements map', () => {
		const entityId = 'Q42',
			statements = { [ entityId ]: {
				P23: [ {
					type: 'statement',
					id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
					rank: 'normal',
					mainsnak: {} as Snak,
				}, {
					type: 'statement',
					id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
					rank: 'normal',
					mainsnak: {} as Snak,
				} ],
				P42: [ {
					type: 'statement',
					id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
					rank: 'normal',
					mainsnak: {} as Snak,
				} ],
			} as StatementMap };

		expect( getters[ STATEMENTS_MAP ](
			newStatementsState( statements ), null, newApplicationState(), null,
		)( entityId ) ).toBe( statements[ entityId ] );
	} );

	it( 'integrates the snak unit', () => {
		expect( getters[ mainSnakGetterTypes.dataType ] ).toBeDefined();
		expect( typeof getters[ mainSnakGetterTypes.dataType ] ).toBe( 'function' );

		expect( getters[ mainSnakGetterTypes.dataValue ] ).toBeDefined();
		expect( typeof getters[ mainSnakGetterTypes.dataValue ] ).toBe( 'function' );

		expect( getters[ mainSnakGetterTypes.snakType ] ).toBeDefined();
		expect( typeof getters[ mainSnakGetterTypes.snakType ] ).toBe( 'function' );

		expect( getters[ mainSnakGetterTypes.dataValueType ] ).toBeDefined();
		expect( typeof getters[ mainSnakGetterTypes.dataValueType ] ).toBe( 'function' );
	} );
} );
