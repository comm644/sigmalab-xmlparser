<?php

require_once __DIR__ . '/../xml_parser.php';
#ifndef KPHP
require_once __DIR__ . '/../../../vendor/autoload.php';
#endif

class ValueHolder
{
	public int $offset =0;
}
$offset = new ValueHolder();
function offset(ValueHolder $n): string
{
	if ($n->offset < 1 ) return "";

	return str_repeat("   ", $n->offset);
}
echo "starting...\n";

$parser = new \Sigmalab\XmlParser\XMLParser();

echo "set...\n";
$parser->xml_set_character_data_handler(function ($parser, $data) use ($offset) {
	echo offset($offset) . "found data (" . strlen($data) . "): $data\n";
});
$parser->xml_set_element_handler(
	function ($parser, $name, $attrs) use ($offset) {
		echo offset($offset) . "open <$name>: \n";
		++$offset->offset;
		foreach ($attrs as $attr) {
			echo offset($offset) . "\tattr: $attr\n";
		}
	},
	function ($parser, $name) use ($offset) {
		$offset->offset--;
		echo offset($offset) . "close </$name>\n";
	},
);
echo "parse...\n";

$parser->xml_parse( <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<body>
	<content name="field1" count="1">
		<item>Item content</item>
</content>
</body>
XML
);

echo "parse...\n";

$parser->xml_parse( <<<XML
<body xmlns:xsl="namespace1" xmlns:xsl="body">
	<content name="field1" count="1">
		<item>Item content</item>
</content>
</body>
XML
);
