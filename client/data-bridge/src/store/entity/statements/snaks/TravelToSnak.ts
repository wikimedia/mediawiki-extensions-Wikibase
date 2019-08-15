import Snak from '@/datamodel/Snak';
import StatmentsState from '@/store/entity/statements/StatementsState';

type TravelToSnak<COORDINATES> = ( state: StatmentsState, coordinates: COORDINATES ) => Snak|null;
export default TravelToSnak;
