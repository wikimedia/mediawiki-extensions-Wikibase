import Mock = jest.Mock;

/**
 * Assert that a mock has been called with an `HTMLElement` as the *x*th argument of the *y*th call,
 * and then replace the argument with its `outerHTML`, to simplify subsequent assertions.
 *
 * Usage example:
 *
 * ```
 * const mock = jest.fn();
 * mock( 'x', 'y', 'z' );
 * mock( 'a', document.createElement( 'b' ), 'c' );
 * calledWithHTMLElement( mock, 1, 1 );
 * expect( mock ).toHaveBeenNthCalledWith( 1, 'x', 'y', 'z' );
 * expect( mock ).toHaveBeenNthCalledWith( 2, 'a', '<b></b>', 'c' );
 * ```
 *
 * @param mock any mock function
 * @param callNum 0-indexed
 * @param argumentNum 0-indexed
 */
export function calledWithHTMLElement( mock: Mock, callNum: number, argumentNum: number ): void {
	const call = mock.mock.calls[ callNum ];
	expect( call[ argumentNum ] ).toBeInstanceOf( HTMLElement );
	call[ argumentNum ] = call[ argumentNum ].outerHTML;
}
