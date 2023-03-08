<?php
declare(strict_types=1);

namespace Sigmalab\XmlParser;

class XMLParser
{
	/** @var int */
	private static int $instanceIndex = 0;
	/** @var self[] */
	private static array $instances = [];

	private const default_encoding = "UTF-8";
	private static self $instance;
	/** @var ffi_scope<libexpat>
	 * @noinspection KphpUndefinedClassInspection
	 */
	private $lib;

	/** @var ffi_cdata<libexpat, struct XML_ParserStruct *>
	 * @noinspection KphpUndefinedClassInspection
	 */
	private $parser = null;

	/** @var ffi_cdata<C, int[1]>
	 * @noinspection KphpUndefinedClassInspection
	 */
	private $userData;

	private int $isparsing = 0;

	/** @var (callable(XMLParser ,string):void)|null */
	private $callbackCharacterData = null;

	/** @param callable(XMLParser,string,string[]):void */
	private $callbackElementOpen = null;

	/** @param (callable(XMLParser,string):void)|null */
	private $callbackElementClose = null;

	private bool $optionCaseFolding = true;
	private bool $optionSkipWhite;
	private string $optionTargetEncoding;


	public function __construct()
	{
		self::loadFFI();
		/** @noinspection KphpUndefinedClassInspection */
		$this->lib = \FFI::scope("libexpat");
		if ($this->lib === null) {
			throw new \RuntimeException("Can't load libexpat");
		}
		$this->create("utf-8");
		self::$instance = $this;
	}

	public static function loadFFI(): bool
	{
		/** @noinspection KphpUndefinedClassInspection */
		return \FFI::load(__DIR__ . '/expat-ffi.h') !== null;
	}

	public static function getInstance(): self
	{
		return self::$instance;
	}


	public function create(?string $encoding): bool
	{
		/* The supported encoding types are hardcoded here because
		 * we are limited to the encodings supported by expat/xmltok.
		 */
		$auto_detect = false;
		if (!$encoding == 0) {
			$encoding = self::default_encoding;
			$auto_detect = true;
		} else {
			$encodingUpper = strtoupper($encoding);
			if (($encodingUpper !== "ISO-8859-1")
				|| ($encodingUpper !== "UTF-8")
				|| ($encodingUpper !== "US-ASCII")) {
				throw new \RuntimeException("is not a supported source encoding");
			}
		}
		$ns_param = null;

		$this->parser = $this->lib->XML_ParserCreateNS((string)$encoding, ":");

		$this->userData = \FFI::new('int[1]', false);
		ffi_array_set($this->userData, 0, self::$instanceIndex);

		$this->lib->XML_SetUserData($this->parser, \FFI::cast('void*', \FFI::addr($this->userData)));
		self::$instances[self::$instanceIndex] = $this;
		self::$instanceIndex++;

//		$this->parser->target_encoding = $encoding;
//		$this->parser->case_folding = 1;
//		$this->parser->isparsing = 0;
		return true;
	}

	/**
	 * @param int $opt
	 * @param mixed $value
	 * @return bool
	 */
	public function xml_parser_set_option(int $opt, $value): bool
	{

		switch ($opt) {
			case XML_OPTION_CASE_FOLDING:
				/* Boolean option */
				$this->optionCaseFolding = (bool)$value;
				break;
			case XML_OPTION_SKIP_WHITE:
				/* Boolean option */
				$this->optionSkipWhite = (bool)$value;
				break;
			case XML_OPTION_SKIP_TAGSTART:
//				/* Integer option */
//				/* The tag start offset is stored in an int */
//				/* TODO Improve handling of values? */
//				$this->parser->toffset = (int)$value;
//				if ($this->parser->toffset < 0) {
//					/* TODO Promote to ValueError in PHP 9.0 */
//					throw new \RuntimeException("Invalid offset");
//				}
				break;
			case XML_OPTION_TARGET_ENCODING:
				/* String option */
				$this->optionTargetEncoding = (string)$value;
				break;
			default:
				throw new \RuntimeException("Invalid option");
		}
		return true;
	}

	public function xml_parser_free(): bool
	{
		if ($this->isparsing === 1) {
			throw new \RuntimeException("Parser cannot be freed while it is parsing");
		}
		$this->lib->XML_ParserFree($this->parser);
		return false;
	}

	public function xml_get_error_code(): int
	{
		return $this->lib->XML_GetErrorCode($this->parser);
	}

	public function xml_get_current_line_number(): int
	{
		return $this->lib->XML_GetCurrentLineNumber($this->parser);
	}

	public function xml_parse(string $data, bool $is_final = false): int
	{

		$this->isparsing = 1;
		$ret = $this->lib->XML_Parse($this->parser, $data, strlen($data), $is_final ? 1 : 0);
		$this->isparsing = 0;
		return $ret;
	}

	public function xml_error_string(int $errorCode): string
	{
		return $this->lib->XML_ErrorString($errorCode);
	}

	/**
	 * @param callable(XMLParser ,string) $callback
	 * @return void
	 */
	public function xml_set_character_data_handler(callable $callback)
	{
		$this->callbackCharacterData = $callback;

		$this->lib->XML_SetCharacterDataHandler($this->parser, function ($userData, $data, $len) {
			$string = \FFI::string($data, $len);
			$self = self::getSelf($userData);
			$handler = $self->callbackCharacterData;
			$handler($self, (string)$string);
		});
	}

	/**
	 * @param callable(XMLParser,string,string[]) $callbackOpen
	 * @param callable(XMLParser,string) $callbackClose
	 * @return void
	 * XML_SetElementHandler
	 */
	public function xml_set_element_handler(callable $callbackOpen, callable $callbackClose): void
	{
		$this->callbackElementOpen = $callbackOpen;
		$this->callbackElementClose = $callbackClose;

		$this->lib->XML_SetElementHandler($this->parser,
			function ($userData, $name, $attrsAsciizPtrs) {
				$self = self::getSelf($userData);

				$attrsArray = [];
				//$mem = \FFI::cast('char*[10]', $attrsAsciizPtrs);
				$key = "";
				for ($idx = 0; ; ++$idx) {
					/** @var string|null $cstring */
					$cstring = ffi_array_get($attrsAsciizPtrs, $idx);
					if (!$cstring) break;
					if (($idx & 0x01) === 0) {
						$key = $self->decodeTagName((string)$cstring);
					} else {
						//value
						$attrsArray[$key] = (string)$cstring;
					}
				}

				if (!$self->callbackElementOpen) return;
				/** @var callable(XMLParser,string, string[]):void $handler */
				$handler = $self->callbackElementOpen;
				$handler($self, $self->decodeTagName((string)$name), $attrsArray);
			},
			function ($userData, $name) {
				$self = self::getSelf($userData);
				if (!$self->callbackElementClose) return;
				/** @var callable(XMLParser,string):void $handler */
				$handler = $self->callbackElementClose;
				$handler($self, $self->decodeTagName((string)$name));
			});
	}

	/**
	 * @param ffi_cdata<C, void*> $ctx
	 * @return XMLParser
	 */
	private static function getSelf($ctx): XMLParser
	{
		$userData = \FFI::cast("int32_t*", $ctx);
		/** @var int $instanceIdx */
		$instanceIdx = ffi_array_get($userData, 0);
		/** @var self $self */
		$self = self::$instances[$instanceIdx];
		return $self;
	}

	private function decodeTagName(string $tagName):string
	{
		//TODO: decode to targetEncoding
		if ($this->optionCaseFolding) {
			return strtoupper($tagName);
		}
		return $tagName;
	}

}