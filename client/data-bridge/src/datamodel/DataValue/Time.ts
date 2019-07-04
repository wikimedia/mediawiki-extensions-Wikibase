interface Time {
	time: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
	timezone: number;
	before: number;
	after: number;
	precision: number;
	calendarmodel: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
}

export default Time;
