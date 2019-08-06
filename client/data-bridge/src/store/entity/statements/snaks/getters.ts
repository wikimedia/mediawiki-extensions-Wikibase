import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import TravelToSnak from '@/store/entity/statements/snaks/TravelToSnak';
import GetterTypesAliases from '@/store/entity/statements/snaks/GetterTypesAliases';
import DataValue from '@/datamodel/DataValue';
import DataValueType from '@/datamodel/DataValueType';
import DataType from '@/datamodel/DataType';
import { SnakType } from '@/datamodel/Snak';
import StatmentsState from '@/store/entity/statements/StatementsState';

export default function getters<COORDINATES>(
	getterAliases: GetterTypesAliases,
	travelToSnak: TravelToSnak<COORDINATES>,
): GetterTree<StatmentsState, Application> {
	return {
		[ getterAliases.snakType ]:
		( state: StatmentsState ) => ( coordinates: COORDINATES ): SnakType|null => {
			const snak = travelToSnak( state, coordinates );
			if ( !snak ) {
				return null;
			}

			return snak.snaktype;
		},

		[ getterAliases.dataType ]:
		( state: StatmentsState ) => ( coordinates: COORDINATES ): DataType|null => {
			const snak = travelToSnak( state, coordinates );
			if ( !snak ) {
				return null;
			}

			return snak.datatype;
		},

		[ getterAliases.dataValue ]:
		( state: StatmentsState ) => ( coordinates: COORDINATES ): DataValue|null => {
			const snak = travelToSnak( state, coordinates );
			if ( !snak || !snak.datavalue ) {
				return null;
			}

			return snak.datavalue;
		},

		[ getterAliases.dataValueType ]:
		( state: StatmentsState ) => ( coordinates: COORDINATES ): DataValueType|null => {
			const snak = travelToSnak( state, coordinates );
			if ( !snak || !snak.datavalue ) {
				return null;
			}

			return snak.datavalue.type;
		},
	};
}
