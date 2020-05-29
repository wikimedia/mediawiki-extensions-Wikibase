import {
	DataValue,
	SnakType,
} from '@wmde/wikibase-datamodel-types';

export interface PayloadSnakDataValue<COORDINATES> {
	path: COORDINATES;
	value: DataValue;
}

export interface PayloadSnakType<COORDINATES> {
	path: COORDINATES;
	value: SnakType;
}
