<?php

	//	require_once dirname(__FILE__) . "/element/MethodElement.php";
	//	require_once dirname(__FILE__) . "/element/AnnotationElement.php";

	//This tells PHP to auto-load classes using Slim's autoloader; this will
	//only auto-load a class file located in the same directory as Slim.php
	//whose file name (excluding the final dot and extension) is the same
	//as its class name (case-sensitive). For example, "View.php" will be
	//loaded when Slim uses the "View" class for the first time.
	spl_autoload_register(array('DocBlockParser', 'autoload'));

	/**
	 *	A simple PHP DocBlock parser
	 *
	 * @author Danny Kopping <dannykopping@gmail.com>
	 */
	class DocBlockParser
	{
		/**
		 * @var string	Regular expression to validate PHP DocBlocks
		 */
		private $validBlockRegex = "/\/\*{2}(.+)\*\//sm";

		/**
		 * @var string	Regular expression to isolate all annotations
		 */
		private $allDocBlockLinesRegex = "%^(\s+)?\*{1}.+[^/]$%m";

		/**
		 * @var string	Regular expression to isolate an annotation and its related values
		 */
		private $annotationRegex = "/^(@[\w]+)(.+)?$/m";

		/**
		 * @var string	Regular expression to split an annotation's values by whitespace
		 */
		private $splitByWhitespaceRegex = "/^(@[\w]+)(.+)?$/m";

		/**
		 * @var	MethodElement	A reference to the MethodElement currently being used
		 */
		private $currentMethod;
		/**
		 * @var	AnnotationElement	A reference to the AnnotationElement currently being used
		 */
		private $currentAnnotation;

		/**
		 * @var	array	An array of parsed MethodElement instances
		 */
		private $methods;
		/**
		 * @var	array	An array of parsed AnnotationElement instances
		 */
		private $annotations;

		/**
		 *	Create a new DocBlockParser instance
		 */
		public function __construct()
		{
			// check for the existence of the Reflection API
			$this->checkCompatibility();
		}

		/**
		 * DocBlockParser autoloader
		 *
		 * Lazy-loads class files when a given class is first referenced.
		 *
		 * @param $class
		 * @return void
		 */
		public static function autoload($class)
		{
			// check same directory
			$file = realpath(dirname(__FILE__) . "/" . $class . ".php");

			// if none found, check the element directory
			if (!$file)
				$file = realpath(dirname(__FILE__) . "/element/" . $class . ".php");

			// if found, require_once the sucker!
			if ($file)
				require_once $file;
		}

		/**
		 * Analyzes a class or instance for PHP DocBlock comments
		 *
		 * @param mixed	$className	Either a string containing the name of the class to reflect, or an object
		 */
		public function analyze($className)
		{
			if(!is_string($className) && !is_object($className))
				throw new Exception("Please pass a valid classname or instance to the DocBlockParser::analyze function");

			$reflector = new ReflectionClass($className);

			$this->methods = array();
			$this->annotations = array();

			foreach ($reflector->getMethods() as $method)
			{
				$m = new MethodElement();
				$m->name = $method->getName();

				preg_match_all($this->validBlockRegex, $method->getDocComment(), $matches, PREG_PATTERN_ORDER);
				array_shift($matches);

				preg_match_all($this->allDocBlockLinesRegex, $method->getDocComment(), $result, PREG_PATTERN_ORDER);
				for ($i = 0; $i < count($result[0]); $i++)
				{
					$this->currentMethod =& $m;
					$this->parse($result[0][$i]);
				}

				$this->methods[] = $m;
			}
		}

		/**
		 * Parses a PHP DocBlock to construct MethodElement and AnnotationElement instances
		 * based on the contents
		 *
		 * @param $string	The PHP DocBlock string
		 */
		protected function parse($string)
		{
			$an = new AnnotationElement($this->currentMethod);

			// strip first instance of asterisk
			$string = substr($string, strpos($string, "*") + 1);
			$string = trim($string);

			// find all the individual annotations
			preg_match_all($this->annotationRegex, $string, $result, PREG_PATTERN_ORDER);

			if (!empty($result[1]))
			{
				for ($i = 0; $i < count($result[2]); $i++)
				{
					if (!empty($result[2]))
					{
						$an->name = $result[1][0];
						$an->values = preg_split($this->splitByWhitespaceRegex, trim($result[2][$i]), null);
					}
				}

				$this->currentMethod->annotations[] = $an;
				$this->annotations[] = $this->currentAnnotation = $an;
			}
			else
			{
				// if there is text inside the PHP DocBlock, it may either relate to the method as a description
				// or to an annotation as a multi-line description. If there is no current annotation, then the
				// descriptive text is declared before any annotations, so it is probably a method description; otherwise
				// it probably relates to an annotation

				if (!$this->currentAnnotation)
					$this->currentMethod->description .= $string . "\n";
				else
				{
					if (!empty($this->currentAnnotation->values))
						$this->currentAnnotation->values[count($this->currentAnnotation->values) - 1] .= "\n" . $string;
				}
			}
		}

		/**
		 * Get an array of all the parsed methods with their related annotations
		 *
		 * @return array[MethodElement]
		 */
		public function getMethods()
		{
			if(!$this->methods || empty($this->methods))
				return null;

			return $this->methods;
		}

		/**
		 * Get an array of all the parsed annotations
		 *
		 * @param array	$filter	(optional) Filter by annotation name
		 * @return array[AnnotationElement]
		 */
		public function getAnnotations($filter=null)
		{
			if(!$this->annotations || empty($this->annotations))
				return null;

			if(!$this->methods || empty($this->methods))
				return null;

			$annotations = array();
			foreach($this->methods as $method)
			{
				$methodAnnotations = $method->getAnnotations($filter);
				array_merge($annotations, $methodAnnotations);
			}

			return $annotations;
		}

		/**
		 * Check to see if all dependencies are satisfied
		 *
		 * @throws Exception
		 */
		protected function checkCompatibility()
		{
			if (!class_exists("Reflection"))
				throw new Exception("Fatal error: Dependency 'Reflection API' not met. PHP5 is required.");
		}
	}

?>