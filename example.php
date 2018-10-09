<?php

	/* Include JSON Formatter class file */
	include("JsonFormatter.php");
	
	/* Products data (dummy data) */
	$products_data = '[{
											"id": "CT9871",
											"products": [
												{
													"product_id": "47586"
												},
												{
													"product_id": null
												},
												{
													"product_id": 47587
												}
											],
											"links": {
												"sandbox": {
												  "method": "GET",
												  "url": "http://localhost/"
												}
											},
											"categories": [
												{
													"id": 1,
													"sub_category_ids": [896,897,898]
												}
											]
										},
										{
											"id": "CT9872",
											"products": [
												{
													"product_id": 47596
												},
												{
													"product_id": 47597
												},
												{
													"product_id": 47598
												}
											],
											"codes": [589,590,591]
										},
										{
											"id": "CT9873",
											"products": [
												{
													"product_id": "47606"
												}
											],
											"codes": [258,259,260]
										}
									]';

	try {

		$json_formatter = new JsonFormatter(true);
		
		/* category_id_format: Any string starting with alphabets CT followed by numeric digits */
		$extra_formats = [
			'category_id_format' => '^CT\d+$'
		];
		/* Set extra formats in JSON formatter's class variable using method "setExtraFormats" */
		$json_formatter->setExtraFormats($extra_formats);

		$preformatted_json = file_get_contents('./json_preformats/products.json');

		$error = '';
		$result = $json_formatter->compile($products_data, $preformatted_json, $error, 'string');
		
		if(!empty($result)) {
			echo "Response: <br/>";
			echo "<pre>";
			if(gettype($result) != 'string') {
				$result = json_encode($result);
			}
			print_r(json_decode($result, true));
			echo "<br/></pre>";
		} else {
			echo "<br/>ERROR in your data: <br/>".$error;
		}

	} catch(Exception $e) {
		echo "Exception occurred: " . $e->getMessage();
	}

?>