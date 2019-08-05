import {
	STATEMENTS_SET,
} from '@/store/entity/statements/mutationTypes';
import { mutations } from '@/store/entity/statements/mutations';
import newStatementsState from './newStatementsState';
import StatementMap from '@/datamodel/StatementMap';
import Snak from '@/datamodel/Snak';

describe( 'statements/mutations', () => {
	describe( STATEMENTS_SET, () => {
		it( 'sets a new statement', () => {
			const state = newStatementsState();

			const statements: StatementMap = {
				P42: [ {
					type: 'statement',
					id: 'Q242$6f832804-4c3f-6185-38bd-ca00b8517765',
					rank: 'normal',
					mainsnak: {} as Snak,
				} ],
			};

			mutations[ STATEMENTS_SET ]( state, { entityId: 'Q42', statements } );
			expect( state ).toStrictEqual( { Q42: statements } );
		} );

		it( 'overwrites the existing state', () => {
			const state = newStatementsState( { Q42: {
				P42: [ {
					type: 'statement',
					id: 'Q23$6f832804-4c3f-6185-38bd-ca00b8517765',
					rank: 'deprecated',
					mainsnak: {} as Snak,
				} ],
			} } );

			const statements = { Q42: {
				P42: [ {
					type: 'statement',
					id: 'Q242$6f832804-4c3f-6185-38bd-ca00b8517765',
					rank: 'normal',
					mainsnak: {} as Snak,
				} ],
			} };

			mutations[ STATEMENTS_SET ]( state, { entityId: 'Q42', statements } );
			expect( state ).toStrictEqual( { Q42: statements } );
		} );
	} );
} );
