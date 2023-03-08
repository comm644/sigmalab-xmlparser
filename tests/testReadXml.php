<?php

require_once __DIR__ . '/../xml_parser.php';

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

$parser = xml_parser_create();

echo "create...\n";

xml_set_character_data_handler($parser, function ($parser, $data) use ($offset) {
	echo offset($offset) . "found CDATA (" . strlen($data) . "): '$data'\n";
});
xml_set_element_handler($parser,
	function ($parser, $name, $attrs) use ($offset) {
		echo offset($offset) . "open <$name>: \n";
		++$offset->offset;
		foreach ($attrs as $key=>$attr) {
			echo offset($offset) . "\tattr: @$key=>'$attr'\n";
		}
	},
	function ($parser, $name) use ($offset) {
		$offset->offset--;
		echo offset($offset) . "close </$name>\n";
	},
);

echo "parse...\n";

$xml = <<<XML
<body>
	<content name="field1" count="1">
		<item>Item content</item>
</content>
</body>
XML;
xml_parse($parser, $xml
);
echo "parse...\n";

xml_parse($parser, <<<XML
<body xmlns:xsl="namespace1" xmlns:xsl="body">
	<content name="field1" count="1">
		<item>Item content</item>
</content>
</body>
XML
);
