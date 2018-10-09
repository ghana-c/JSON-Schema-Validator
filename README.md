# JSON Formatter

> Latest Version v1.0.0
 
JSON Formatter is simple and small library used to validate JSON or JSON string or array data with preformatted JSON structure.

JSON Formatter calculates difference between JSON or JSON string or array with preformatted JSON structure and returns error on -

`Invalid value`
`Invalid data type`
`Missing JSON key`

NOTE:
1. JSON Formatter also check required JSON keys
2. You can also put default value for any JSON key

You can specify output format in which, you want to get response from JSON Formatter. Output format can be -

* `object` as JSON
* `string` or `jsonstring` as JSON string
* `array`

Input for JSON Formatter can be -

* JSON
* JSON string
* Array

### Getting started

First, you need to include "JsonFormatter.php" in your project :

```PHP
include("JsonFormatter.php");
```

NOTE: You can specify absolute path.

#### Usage

e.g. Suppose, you want to check whether your json data or array is in proper format, whether it has all mandatory keys and whether it has specific format for specific key `(like integer for key id)` then you can do that using JSON Formatter. You can see the [Example](https://github.com/ghana-c/JSON-Formatter/blob/master/example.php) :

In above example, you want to first inlude the class file JsonFormatter as below:

```PHP
include("JsonFormatter.php");
```

Create class object as below:

```PHP
$json_compiler = new JsonFormatter(true);
```

* `true` : Remove unwanted fields (fields that are not specified in preformatted json) from input data
* `false` : Keep unwanted fields (fields that are not specified in preformatted json) as it is in input data

NOTE: If you create class object without passing `true` or `false`, then by default it consider the value as `false`

There are some predefined formats in JSON Formatter to format a value of key in input data as below:

* `date` : `^\d{4}\-\d{2}\-\d{2}$` e.g. 2017-12-31
* `numeric` : `^\d+$` e.g. 789456
* `float` : `^(\-){0,}\d+(\.\d+){0,}$` e.g. 123 or 123.45 or -123 or -123.45
* `email` : `^[_a-z0-9-]+(\.[_a-z0-9-]+)*\@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$` e.g. xyz@example.com
* `non_numeric_string` : `^([A-Za-z](\,){0,1}(\.){0,1}\s*){1,}$` e.g. I am a boy. (You can use comma or dot in string)

You can write your own format, and use it in your preformatted json. You need to add your own format(s) array to JSON Formatter using method `setExtraFormats` as:

```PHP
$extra_formats = [
	'category_id_format' => '^CT\d+$'
];

$json_formatter->setExtraFormats($extra_formats);
```

Get the preformatted JSON file (please find the rules about [How to Write Preformatted JSON file]), pass this file and `error` variable to `compile` method as:

```PHP
$preformatted_json = file_get_contents('./json_preformats/products.json');
$error = '';
$result = $json_formatter->compile($products_data, $preformatted_json, $error, 'string');
```

You can specify output format in which, you want to get response from JSON Formatter. Output format can be -

* `object` as JSON
* `string` or `jsonstring` as JSON string
* `array`

NOTE: You can get the error (if any) in `error` variable, if no error, JSON Formatter will return  formatted response

### Rules to Write Preformatted JSON File

You can see the [Example](https://github.com/ghana-c/JSON-Formatter/blob/master/json_preformats/products.json) :

Possible values of JSON attributes:

* `@type` : `array` `string` `integer` `null` etc.

* `@items` : JSON object which contains it's child elements (properties) and required elements

* `@required` : array of keys which are mandatory at specific level

* `@properties` : JSON object of all keys with the format of appropriate value

* `@format` : `date` `numeric` `float` `email` `non_numeric_string` You can specify this attribute along with `@type` to match the format of the value associated with key in input data (You can write your own format, and use it in your preformatted json)

* `@pattern` : This is optional to `@format`. You can write the regular expression and write it in this attribute to match the format of the value associated with key in input data

* `@anyof` : If the value associated with key in input data can be of multiple formats, then you can use this attribute to specify muliple `@type` along with `@format` or `@pattern` if necessary

* `@default` : You can specify default value for the key in input data. If key is not present and it is mentioned in `@required` attribute, it will be created.

* `@values` : If the value associated with key in input data can be either of fixed values (like status can be success or failed or error etc.), then you can write the array of possible values in this attribute.

### Funtions You Can Use Separately (You do not want to use this functions while validating your data with preformatted JSON structure. It is handeled in JSON Formatter)

#### isJsonString

This function is used to check whether input string is valid json string or not

```PHP
$json_formatter->isJsonString($your_data);
```

Output of this function can be `true` `false`

#### convertToReturnType

This function is used to convert input data in requested format

```PHP
$json_formatter->convertToReturnType($your_data, $required_format);
```

Required format can be `jsonstring` as JSON string `object` as JSON object `array` as normal array

### Author

Ghanashyam Chaudhari (mr.ghchaudhari@gmail.com)

### NOTE

Email me at [mr.ghchaudhari@gmail.com](mailto:mr.ghchaudhari@gmail.com) for any queries.










