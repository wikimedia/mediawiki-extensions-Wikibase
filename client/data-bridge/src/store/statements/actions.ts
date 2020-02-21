import { Actions } from 'vuex-smart-module';
import { StatementState } from '@/store/statements';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import { PayloadSnakDataValue } from '@/store/statements/snaks/Payloads';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import { StatementMutations } from '@/store/statements/mutations';
import { StatementGetters } from '@/store/statements/getters';
import { PathToSnak } from '@/store/statements/PathToSnak';
import clone from '@/store/clone';

export class StatementActions extends Actions<
StatementState,
StatementGetters,
StatementMutations,
StatementActions
> {
	public initStatements(
		payload: {
			entityId: EntityId;
			statements: StatementMap;
		},
	): Promise<void> {
		this.commit( 'setStatements', {
			entityId: payload.entityId,
			statements: payload.statements,
		} );
		return Promise.resolve();
	}

	public applyStringDataValue(
		payloadDataValue: PayloadSnakDataValue<PathToSnak>,
	): Promise<StatementState> {
		return new Promise( ( resolve ) => {
			const state = clone( this.state );

			const snak = payloadDataValue.path.resolveSnakInStatement( state );
			if ( snak === null ) {
				throw new Error( SnakActionErrors.NO_SNAK_FOUND );
			}

			if ( payloadDataValue.value.type !== 'string' ) {
				throw new Error( SnakActionErrors.WRONG_PAYLOAD_TYPE );
			}

			if ( typeof payloadDataValue.value.value !== 'string' ) {
				throw new Error( SnakActionErrors.WRONG_PAYLOAD_VALUE_TYPE );
			}

			snak.snaktype = 'value';

			snak.datavalue = payloadDataValue.value;

			resolve( state );
		} );
	}
}
