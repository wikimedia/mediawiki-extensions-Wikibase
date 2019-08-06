import { SnakType } from '@/datamodel/Snak';
import DataValue from '@/datamodel/DataValue';

export interface PayloadSnakDataValue<COORDINATES> {
	path: COORDINATES;
	value: DataValue;
}

export interface PayloadSnakType<COORDINATES> {
	path: COORDINATES;
	value: SnakType;
}
