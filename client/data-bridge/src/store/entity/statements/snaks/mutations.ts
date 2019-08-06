import Vue from 'vue';
import { MutationTree } from 'vuex';
import TravelToSnak from '@/store/entity/statements/snaks/TravelToSnak';
import MutationTypesAliases from '@/store/entity/statements/snaks/MutationTypesAliases';
import {
	PayloadSnakDataValue,
	PayloadSnakType,
} from '@/store/entity/statements/snaks/Payloads';
import StatmentsState from '@/store/entity/statements/StatementsState';
import Snak from '@/datamodel/Snak';

export default function <COORDINATES> (
	mutationAliases: MutationTypesAliases,
	travelToSnak: TravelToSnak<COORDINATES>,
): MutationTree<StatmentsState> {
	return {
		[ mutationAliases.setDataValue ]:
		( state: StatmentsState, payload: PayloadSnakDataValue<COORDINATES> ): void => {
			const snak = travelToSnak( state, payload.path );
			Vue.set( snak as Snak, 'datavalue', payload.value );
		},

		[ mutationAliases.setSnakType ]:
		( state: StatmentsState, payload: PayloadSnakType<COORDINATES> ): void => {
			const snak = travelToSnak( state, payload.path );
			( snak as Snak ).snaktype = payload.value;
		},
	};
}
