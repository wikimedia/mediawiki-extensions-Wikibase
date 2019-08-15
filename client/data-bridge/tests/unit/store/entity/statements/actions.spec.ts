import { actions } from '@/store/entity/statements/actions';
import {
	STATEMENTS_INIT,
} from '@/store/entity/statements/actionTypes';
import {
	STATEMENTS_SET,
} from '@/store/entity/statements/mutationTypes';
import StatementMap from '@/datamodel/StatementMap';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import newMockStore from '../../newMockStore';

describe( 'statements/actions', () => {
	describe( STATEMENTS_INIT, () => {
		it( `commits to ${STATEMENTS_SET}`, () => {
			const payload = {
				entityId: 'Q42',
				statements: {
					P23: [ {
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						mainsnak: {
							snaktype: 'value',
							property: 'P23',
							datatype: 'wikibase-item',
							datavalue: {
								value: {
									'entity-type': 'item',
									id: 'Q6342720',
								},
								type: 'wikibase-entityid',
							},
						},
						type: 'statement',
						rank: 'normal',
					} ],
				} as StatementMap,
			};

			const context = newMockStore( {} );
			actions[ STATEMENTS_INIT ](
				context,
				payload,
			);

			expect( context.commit ).toHaveBeenCalledWith(
				STATEMENTS_SET,
				payload,
			);
		} );
	} );

	it( 'binds the snak action unit', () => {
		expect( ( actions as any )[ mainSnakActionTypes.setStringDataValue ] ).toBeDefined();
		expect( typeof ( actions as any )[ mainSnakActionTypes.setStringDataValue ] ).toBe( 'function' );
	} );
} );
