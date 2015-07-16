<?php

namespace Wikibase\Repo\Api;

/**
 * Class which can be used to easily modify serializations and arrays
 *
 * @since 0.5
 * @author Adam Shorland
 */
class SerializationModifier {

	/**
	 * @param array $array
	 * @param null|string $path
	 * @param callback $callback
	 *  Callback accepts 1 parameter which is the element to touch
	 *  Callback should return the altered element
	 *
	 * @returns array the altered array
	 */
	public function modifyUsingCallback( array $array, $path, $callback ) {
		$elementsToTouch = $this->getElementsToModify(
			$array,
			$this->getPathParts( $path )
		);
		foreach ( $elementsToTouch as $key => $element ) {
			$newElement = $callback( $element );
			$this->setArrayValueAtKey( $array, $key, $newElement );
		}
		return $array;
	}

	/**
	 * @param array $array
	 * @param string $path
	 * @param mixed $value
	 */
	private function setArrayValueAtKey( &$array, $path, $value ) {
		$current = &$array;
		$pathParts = $this->getPathParts( $path );
		foreach ( $pathParts as $key ) {
			$current = &$current[$key];
		}
		$current = $value;
	}

	/**
	 * @param array $array
	 * @param array $pathElements
	 * @param string $currentPath
	 *
	 * @return array
	 */
	private function getElementsToModify( array $array, array $pathElements, $currentPath = '' ) {
		$elements = array();

		if ( empty( $pathElements ) ) {
			$elements[$currentPath] = $array;
		} else {
			$key = array_shift( $pathElements );
			if ( $key === '*' ) {
				foreach ( array_keys( $array ) as $innerKey ) {
					if ( is_array( $array[$innerKey] ) ) {
						$elements = array_merge(
							$elements,
							$this->getElementsToModify(
								$array[$innerKey],
								$pathElements,
								$this->getJoinedPath( $currentPath, $innerKey )
							)
						);
					}
				}
			} else {
				if ( isset( $array[$key] ) && is_array( $array[$key] ) ) {
					$elements = array_merge(
						$elements,
						$this->getElementsToModify(
							$array[$key],
							$pathElements,
							$this->getJoinedPath( $currentPath, $key )
						)
					);
				}
			}
		}

		return $elements;
	}

	/**
	 * @param string $prefix
	 * @param string $key
	 *
	 * @return string
	 */
	private function getJoinedPath( $prefix, $key ) {
		if ( $prefix === '' ) {
			return $key;
		}

		return $prefix . '/' . $key;
	}

	/**
	 * @param null|string $path
	 *
	 * @return array
	 */
	private function getPathParts( $path ) {
		if ( $path === null || $path === '' ) {
			return array();
		}

		return explode( '/', $path );
	}

}
