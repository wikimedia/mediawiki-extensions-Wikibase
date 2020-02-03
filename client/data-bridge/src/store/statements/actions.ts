import { Actions } from 'vuex-smart-module';
import { STATEMENTS_INIT } from '@/store/statements/actionTypes';
import { StatementState } from '@/store/statements';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import { STATEMENTS_SET } from '@/store/statements/mutationTypes';
import { PayloadSnakDataValue, PayloadSnakType } from '@/store/statements/snaks/Payloads';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import { StatementMutations } from '@/store/statements/mutations';
import { StatementGetters } from '@/store/statements/getters';
import { SNAK_SET_STRING_DATA_VALUE } from '@/store/statements/snaks/actionTypes';
import { SNAK_SET_DATA_VALUE, SNAK_SET_SNAKTYPE } from '@/store/statements/snaks/mutationTypes';
import { PathToSnak } from '@/store/statements/PathToSnak';

export class StatementActions extends Actions<
StatementState,
StatementGetters,
StatementMutations,
StatementActions
> {
	public [ STATEMENTS_INIT ](
		payload: {
			entityId: EntityId;
			statements: StatementMap;
		},
	): Promise<void> {
		this.commit( STATEMENTS_SET, {
			entityId: payload.entityId,
			statements: payload.statements,
		} );
		return Promise.resolve();
	}

	public [ SNAK_SET_STRING_DATA_VALUE ](
		payloadDataValue: PayloadSnakDataValue<PathToSnak>,
	): Promise<void> {
		return new Promise( ( resolve ) => {
			const snak = payloadDataValue.path.resolveSnakInStatement( this.state );
			if ( snak === null ) {
				throw new Error( SnakActionErrors.NO_SNAK_FOUND );
			}

			if ( payloadDataValue.value.type !== 'string' ) {
				throw new Error( SnakActionErrors.WRONG_PAYLOAD_TYPE );
			}

			if ( typeof payloadDataValue.value.value !== 'string' ) {
				throw new Error( SnakActionErrors.WRONG_PAYLOAD_VALUE_TYPE );
			}

			// TODO put more validation here
			const payloadSnakType: PayloadSnakType<PathToSnak> = {
				path: payloadDataValue.path,
				value: 'value',
			};

			this.commit( SNAK_SET_SNAKTYPE, payloadSnakType );
			this.commit( SNAK_SET_DATA_VALUE, payloadDataValue );
			resolve();
		} );
	}
}
