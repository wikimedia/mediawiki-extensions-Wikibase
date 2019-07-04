interface Quantity {
	amount: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
	upperBound?: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
	lowerBound?: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
	unit: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
}

export default Quantity;
