<?php
/**
 * This class is used to compile JSON and check if JSON is in proper format
 */
class JsonFormatter {

	/**
	 * Set to true, if you want to remove non declared fields
	 *
	 * @var bool
	 */
	private $_remove_undeclared = false;

	/**
	 * Array consists of default @format and their regular expression
	 *
	 * @var array
	 */
	private $_var_formats = [
		/* Date format Y-m-d */
		'date' => '^\d{4}\-\d{2}\-\d{2}$',
		/* String with numeric characters only */
		'numeric' => '^\d+$',
		/* Float number (for both negative and positive) */
		'float' => '^(\-){0,}\d+(\.\d+){0,}$',
		/* Email format */
		'email' => '^[_a-z0-9-]+(\.[_a-z0-9-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$',
		/* String with non numeric characters only (comma or dot allowed) */
		'non_numeric_string' => '^([A-Za-z](\,){0,1}(\.){0,1}\s*){1,}$'
	];

	/**
	 * Class constructor
	 *
	 * @param bool 		$remove_undeclared 	True if you want to remove non declared fields 
	 * @param array 	$var_formats 				Array you wish to add to predefined @format as regex 
	 */
	public function __construct($remove_undeclared = false) {
		if(!empty($remove_undeclared))
			$this->_remove_undeclared = true;
	}

	/**
	 * This method is used to add various formats to predefined @format as regex
	 *
	 * @param array 	$var_formats 	Array you wish to add to predefined @format as regex
	 *
	 * @return void
	 */
	public function setExtraFormats($var_formats = []) {
		/* Merge array of variable formats (having regex) to existing variable formats */
		if(!empty($var_formats) && is_array($var_formats))
			$this->_var_formats = array_merge($var_formats, $this->_var_formats);
	}

	/**
	 * This method is used to check if $element is valid
	 * - If valid, initiate JSON compile process
	 *
	 * @param string/object/array 	$element 						Element which you want to check
	 * @param string 								$formatted_string 	Formatted string which you want to compare with
	 * @param string 								$error 							Error that will return as reference
	 * @param string 								$return_type 				Return type in which you want to get result
	 *
	 * @return string/object/array 	$result 						Clean result
	 */
	public function compile($element, $formatted_string, &$error = '', $return_type = '') {
		$result = '';
		/* Check if input $element is of valid data type (array or JSON object or JSON string) */
		$is_valid = self::isValidType($element);
		if($is_valid) {
			/* If return type is not specified, use data type of input $element as return type */
			if(!$return_type) {
				$return_type = gettype($element);
			}
			$return_type = ($return_type == 'string') ? 'jsonstring' : $return_type;
			if(!in_array($return_type, ['jsonstring', 'object','array'])) {
				$return_type = gettype($element);
			}
			/* Convert input $element to array */
			$r_flag = $this->convertToArray($element, $error);
			//$formatted_string = str_replace("\\", "\/", $formatted_string);
			/* Convert pre formatted $formatted_string to array */
			$f_flag = $this->convertToArray($formatted_string, $error);
			/* If input $element is not valid, return error */
			if(!$r_flag) {
				$error = "Not a valid request";
				return false;
			}
			/* If pre formatted $formatted_string is not valid, throw exception */
			if(!$f_flag) {
				throw new Exception("Pre formatted JSON is not valid");
				return false;
			}
			/* Both $element and $formatted_elem are now become array. Start comparing them */
			$valid = $this->compare($element, $formatted_string, $error);
			/* If input $element is proper, convert it to return type */
			if($valid) {
				$result = self::convertToReturnType($element, $return_type);
			}
		} else {
			$error = 'Not a valid request';
			return false;
		}
		return $result;
	}

	/**
	 * This function is used to convert request in array and call function to compare
	 *
	 * @param string/array/object 	$element 						Element which you want to check (with reference)
	 * @param string 								$formatted_elem 		Formatted string which you want to compare with
	 * @param string 								$error 							Error that will return as reference
	 *
	 * @return string/object/array 	$result 						Clean result
	 */
	private function compare(&$element, $formatted_elem, &$error) {
		/* Check if variable type in pre formatted structure is array, if so, call self function recursively */
		if(isset($formatted_elem['@type']) && $formatted_elem['@type'] == 'array' && isset($formatted_elem['@items']) && !empty($formatted_elem['@items']) && !empty($element)) {
			foreach($element as &$item) {
				if(isset($formatted_elem['@items']) && !empty($formatted_elem['@items'])) {
					$valid = $this->compare($item, $formatted_elem['@items'], $error);
					/* If input $item is not valid, return false */
					if(!$valid) 
						return false;
				}
			}
		} elseif(isset($formatted_elem['@properties']) && !empty($formatted_elem['@properties'])) {
			/* If $this->_remove_undeclared is set, then remove undeclared items from input element */
			if($this->_remove_undeclared) {
				$element = array_intersect_key($element, $formatted_elem['@properties']);
			}
			foreach ($formatted_elem['@properties'] as $key => $property) {
				/** 
				 * Check if current key is required, and is not found in input $element
				 * If '@default' is present in item, assign default value
				 * And if default value not found, return error
				 */
				if(isset($formatted_elem['@required']) &&
					 !empty($formatted_elem['@required']) &&
					 in_array($key, $formatted_elem['@required']) &&
					 !array_key_exists($key, $element)) {
					/* If key is required and it's default value is found in pre formatted array, then set the default value */
					if(array_key_exists('@default', $property)) {
						/**
						 * If item type not array and its default value is array OR
						 * If item type is integer and its default value is not integer OR
						 *
						 * If item type is array and its default value is not array or not null or not blank OR
						 *
						 * Then throw exception
						 *
						 * Otherwise assign default value to variable
						 * NOTE: If item has property '@anyof', then directly assign default value to variable
						 */
						if(($property['@type'] != 'array' && gettype($property['@default']) == 'array') ||
							 ($property['@type'] == 'integer' && gettype($property['@default']) != 'integer')) {
							throw new Exception("Default value for `".$key."` with @type = `".$property['@type']."` cannnot be `".gettype($property['@default'])."`");
							return false;
						} elseif($property['@type'] == 'array' && (gettype($property['@default']) != 'array' && !is_null($property['@default']) && !empty($property['@default']))) {
							throw new Exception("Default value for `".$key."` with @type = array must be array or null or blank value");
							return false;
						} else {
							if($property['@type'] == 'array' && !is_null($property['@default']) && empty($property['@default'])) {
								$property['@default'] = [];
							}
							$element[$key] = $property['@default'];
							if(isset($property['@type']) && $property['@type'] == 'array' && isset($property['@items']['@properties'])) {
								$valid = $this->compare($element[$key], $property['@items'], $error);
								/* If input $item is not valid, return false */
								if(!$valid) 
									return false;
							}
							continue;
						}
					} else {
						$error = "`".$key."` is mandatory in the request data.";
						return false;	
					}
				}
				/* Check if variable type in pre formatted structure is array, if so, call self function recursively */
				if(isset($property['@type']) && $property['@type'] == 'array') {
					if(isset($formatted_elem['@required']) && !empty($formatted_elem['@required']) &&
					 	 in_array($key, $formatted_elem['@required']) &&
						 array_key_exists($key, $element) && empty($element[$key])) {
						$error = "`".$key."` cannot be empty in the request data.";
						return false;
					}
					if(isset($element[$key])) {
						if(isset($property['@items']) && !empty($property['@items'])) {
							foreach($element[$key] as &$item) {
								$valid = $this->compare($item, $property['@items'], $error);
								if(!$valid) 
									return false;
							}
						}
					}
				} elseif(isset($property['@properties']) && !empty($property['@properties']) && isset($element[$key])) {
					$valid = $this->compare($element[$key], $property, $error);
					/* If input $item is not valid, return false */
					if(!$valid) 
						return false;
				} else {
					/* If key is required and is not found in input $element, then return error */
					if(isset($formatted_elem['@required']) &&
						 !empty($formatted_elem['@required']) &&
						 in_array($key, $formatted_elem['@required']) &&
						 !array_key_exists($key, $element)) {
						$error = "`".$key."` is mandatory in the request data.";
						return false;
					} elseif(isset($formatted_elem['@required']) &&
						 !empty($formatted_elem['@required']) &&
						 !in_array($key, $formatted_elem['@required']) &&
						 !array_key_exists($key, $element)) {
						continue;
					}
					/* If item has @anyof, then check value matches to any of them */
					if(isset($property['@anyof']) && !empty($property['@anyof'])) {
						$valid = false;
						foreach ($property['@anyof'] as $key1 => $type) {
							$valid = $this->validateJsonProperty($type, $element[$key], $error, $key);
							if($valid) {
								$error = '';
								break;
							}
						}
						if(!$valid)
							return false;
					} else {
						$valid = $this->validateJsonProperty($property, $element[$key], $error, $key);
						if(!$valid) 
							return false;
					}
				}
			}
		} elseif(isset($formatted_elem['@anyof']) && !empty($formatted_elem['@anyof'])) {
			$valid = false;
			/* If item has @anyof, then check value matches to any of them */
			foreach ($formatted_elem['@anyof'] as $key1 => $type) {
				$valid = $this->validateJsonProperty($type, $element, $error);
				if($valid) {
					$error = '';
					break;
				}
			}
			if(!$valid) 
				return false;
		} else {
			$valid = $this->validateJsonProperty($formatted_elem, $element, $error);
			if(!$valid) 
				return false;
		}
		return true;
	}

	/**
	 * This method is used to validate JSON individual property
	 *
	 * @param array 				$type 		Element which you want to check
	 * @param string/array	$value 		Formatted string which you want to compare with
	 * @param string 				$error 		Error that will return as reference
	 * @param string 				$key 			JSON key in input element
	 *
	 * @return void
	 */
	private function validateJsonProperty($type, $value, &$error, $key = '') {
		$value_type = strtolower(gettype($value));
		if(gettype($type['@type']) == 'array') {
			$type['@type'] = array_map('strtolower', $type['@type']);
		} else {
			$type['@type'] = strtolower($type['@type']);
		}
		/* Check if input $value type matches with pre formatted type */
		if(isset($type['@type']) && ((gettype($type['@type']) == 'array' && in_array($value_type, $type['@type'])) || (gettype($type['@type']) == 'string' && $type['@type'] == $value_type))) {
			$preg_equation = '';
			/* If @format is specified, get regex for specified @format from ($this->_var_formats) array */
			if(isset($type['@format']) && isset($this->_var_formats[$type['@format']]) && !empty($this->_var_formats[$type['@format']])) {
				$preg_equation = $this->_var_formats[$type['@format']];
			}
			/* If @pattern or @values are specified, set them as regex */
			if(isset($type['@pattern'])) {
				$preg_equation = $type['@pattern'];
			} elseif(isset($type['@values'])) {
				$preg_equation = implode('|', $type['@values']);
			}
			//$preg_equation = str_ireplace('//', '\\', $preg_equation);
			//$preg_equation = str_ireplace('/', '\/', $preg_equation);
			/**
			 * Check if input $value matches to regex
			 * - If no match found, return error
			 * - If @format or @pattern are not specified or empty, throw exception
			 */
			if(!empty($preg_equation) && !preg_match('#'.$preg_equation.'#im', trim($value))) {
				$error = "Value `".$value."` is invalid. Please send valid value.";
				if(!empty($key)) {
					if(empty($value)) {
						$error = "Empty value found for `".$key."`. Please send valid value.";
					} else {
						$error = "Value `".$value."` for `".$key."` is invalid. Please send valid value.";
					}
				}
				if(isset($type['@values']) && !empty($type['@values'])) {
					$error .= " Value can be `".implode('`,`', $type['@values'])."`";
				}
				return false;
			} elseif((isset($type['@format']) || isset($type['@pattern'])) && empty($preg_equation)) {
				throw new Exception("Preg equation for @format or @pattern cannot be empty");
				return false;
			}
		} else {
			$error = "Value `".$value."` is not in valid format. Please send valid format.";
			if(!empty($key))
				$error = "Data type for `".$key."` is invalid. Please send valid format.";
			return false;
		}
		return true;
	}

	/**
	 * This function is used to check if element is array or valid JSON
	 *
	 * @param string/array/object 	$element 	Element which you want to check
	 *
	 * @return boolean
	 */
	public static function isValidType($element) {
		if(in_array(gettype($element), ['array','object']))
			return true;
		return self::isJsonString($element);
	}

	/**
	 * This function is used to convert element to array
	 *
	 * @param string/array/object 	$element 	Element which you want to check (with reference)
	 * @param string 								$error 		Error if any (with reference)
	 *
	 * @return boolean
	 */
	private function convertToArray(&$element, &$error) {
		if(self::isJsonString($element)) {
			$element = json_decode($element, true);
			return true;
		} else if(is_array($element) || is_object($element)) {
			$element = json_decode(json_encode($element), true);
			return true;
		} else {
			$error = "Not a valid request";
			return false;
		}
	}

	/**
	 * This function is used to convert response in requested format
	 * 
	 * @param  array|jsonstring|json 	$input 				Input for converting
	 * @param  string 								$return_type 	Return type in which you want to convert
	 *
	 * @return array|jsonstring|json 	Converted structure of input
	 */
	public static function convertToReturnType($input, $return_type) {
		switch (strtolower($return_type)) {
			case 'jsonstring':
				if(!(self::isJsonString($input))) {
					return json_encode($input);
				}
				return $input;
				break;
			case 'object':
				if(self::isJsonString($input)) {
					return json_decode($input, false);
				}
				return json_decode(json_encode($input), false);
				break;
			case 'array':
				if(self::isJsonString($input)) {
					return json_decode($input, true);
				}
				return json_decode(json_encode($input), true);
				break;
			default: 
				return false;
				break;
		}
	}

	/**
	 * This function is used to check if string is valid JSON or not
	 *
	 * @param string 	$string 	String which you want to check
	 *
	 * @return boolean
	 */
	public static function isJsonString($string) {
		if(!is_string($string))
			return false;
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}
}