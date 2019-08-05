import {
	ActionContext,
	ActionTree,
} from 'vuex';
import Application from '@/store/Application';
import StatementsState from '@/store/entity/statements/StatementsState';
import MutationTypesAliases from '@/store/entity/statements/snaks/MutationTypesAliases';
import ActionTypesAliases from '@/store/entity/statements/snaks/ActionTypesAliases';
import {
	PayloadSnakDataValue,
	PayloadSnakType,
} from '@/store/entity/statements/snaks/Payloads';
import TravelToSnak from '@/store/entity/statements/snaks/TravelToSnak';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';

export default function actions<COORDINATES>(
	actionTypesAliases: ActionTypesAliases,
	mutationTypesAliases: MutationTypesAliases,
	travelToSnak: TravelToSnak<COORDINATES>,
): ActionTree<StatementsState, Application> {
	return {
		[ actionTypesAliases.setStringDataValue ](
			context: ActionContext<StatementsState, Application>,
			payloadDataValue: PayloadSnakDataValue<COORDINATES>,
		): Promise<void> {
			return new Promise( ( resolve ) => {
				const snak = travelToSnak( context.state, payloadDataValue.path );
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
				const payloadSnakType: PayloadSnakType<COORDINATES> = {
					path: payloadDataValue.path,
					value: 'value',
				};

				context.commit( mutationTypesAliases.setSnakType, payloadSnakType );
				context.commit( mutationTypesAliases.setDataValue, payloadDataValue );
				resolve();
			} );
		},
	};
}
