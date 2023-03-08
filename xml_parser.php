<?php
//load IsKPHP
require_once __DIR__.'/../autoload.php';

/**
 * Implement XmlParser functions for KPHP
 */
if (\Sigmalab\IsKPHP::$isKPHP) {
	require_once __DIR__ . '/compat.php';
	require_once __DIR__ . '/XMLParser.php';

	class XMLParser extends Sigmalab\XmlParser\XMLParser
	{
	}

	function xml_parser_create(?string $encoding = null): XMLParser
	{
		return new XMLParser();
	}

	function xml_parser_set_option(XMLParser $parser, int $option, $value): bool
	{
		$parser->xml_parser_set_option($option, $value);
		return false;
	}

	/**
	 * @param XMLParser $parser
	 * @param T $object
	 * @return bool
	 * @kphp-generic T
	 */
	function xml_set_object(XMLParser $parser, $object): bool
	{
		return false;
	}

	function xml_parser_free(XMLParser $parser): bool
	{
		$parser->xml_parser_free();
		return false;
	}

	function xml_get_error_code(XMLParser $parser): int
	{
		return $parser->xml_get_error_code();
	}

	function xml_get_current_line_number(XMLParser $parser): int
	{
		return $parser->xml_get_current_line_number();
	}

	function xml_parse(XMLParser $parser, string $data, bool $is_final = false): int
	{
		return $parser->xml_parse($data, $is_final);
	}

	function xml_error_string(int $error): string
	{
		return XMLParser::getInstance()->xml_error_string($error);
	}

	/**
	 * @param XMLParser  $parser
	 * @param callable(Sigmalab\XmlParser\XMLParser,string) $callback
	 * @return void
	 */
	function xml_set_character_data_handler(XMLParser $parser, callable $callback)
	{
		$parser->xml_set_character_data_handler($callback);
	}

	/**
	 * @param XMLParser $parser
	 * @param callable(Sigmalab\XmlParser\XMLParser,string,string[]) $callbackOpen
	 * @param callable(Sigmalab\XmlParser\XMLParser,string) $callbackClose
	 * @return void
	 */
	function xml_set_element_handler(XMLParser $parser, callable $callbackOpen, callable $callbackClose)
	{
		$parser->xml_set_element_handler($callbackOpen, $callbackClose);
	}
}