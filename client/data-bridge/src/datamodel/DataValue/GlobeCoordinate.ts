interface GlobeCoordinate {
	latitude: number;
	longitude: number;
	precision: number;
	globe: string; // https://github.com/Microsoft/TypeScript/issues/6579
	altitude?: null;
}

export default GlobeCoordinate;
