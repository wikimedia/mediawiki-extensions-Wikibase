import { StatementMutations } from '@/store/statements/mutations';
import newStatementState from './newStatementState';
import StatementMap from '@/datamodel/StatementMap';
import Snak from '@/datamodel/Snak';
import { inject } from 'vuex-smart-module';

describe( 'statements/Mutations', () => {

	describe( 'general mutations on a statement', () => {

		describe( 'setStatements', () => {
			it( 'sets a new statement', () => {
				const state = newStatementState();

				const statements: StatementMap = {
					P42: [ {
						type: 'statement',
						id: 'Q242$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {} as Snak,
					} ],
				};

				const mutations = inject( StatementMutations, { state } );

				mutations.setStatements( { entityId: 'Q42', statements } );
				expect( state ).toStrictEqual( { Q42: statements } );
			} );
		} );
	} );

} );
