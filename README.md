# PHP DocBlock *Lite*

### WTF is a DocBlock?

A DocBlock is a block comment in PHP (with optional annotations):

```php

/**
* Optional DocBlock comment
*
* @annotation	I'm an annotation!
*/
```

## Installation
Download or clone the repository and add a `require_once` statement to include the `DocBlockParser` class.

```php

<?php

	require_once "DocBlockParser.php";

?>
```

## Usage

phpDBL (PHP DocBlock Lite) uses the `Reflection` API (available from PHP 5) to allow you to retrieve the DocBlock comments. phpDBL will then inspect all the methods available in any given class and examine their DocBlocks.

```php

<?php
	require_once "DocBlockParser.php";

	$d = new DocBlockParser();
	$d->analyze("MyClassName");

	$methods = $d->getMethods();
	print_r($methods);
?>
```

You can also retrieve a list of given annotations:

```php

<?php

	require_once "DocBlockParser.php";
	
	$d = new DocBlockParser();
	$d->analyze("TestClass");
	
	$methods = $d->getMethods();

	foreach($methods as $method)
	{
		$annotations = $method->getAnnotations(array("param"));
		foreach ($annotations as $annotation)
		{
			echo $annotation->getMethod()->name . "\n";
			echo $annotation->name . "\n";
			echo print_r($annotation->values, true) . "\n";
		}
	}

	class TestClass
	{
		/**
		 * This is the DocBlock description
		 * @param $data  The data to be passed in
		 */
		public function test($data)
		{
		}
	}

?>
```

Which will produce:

```php

test
@param
Array
(
    [0] => $data
    [1] => The data to be passed in
)
```

## Full Example

```php
<?php

    require_once "DocBlockParser.php";

    $d = new DocBlockParser();
    $d->setAllowInherited(true);
    $d->setMethodFilter(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
    $d->analyze(array("TestClass", "DocBlockParser"));

    $classes = $d->getClasses();

    foreach ($classes as $class)
    {
        echo "Class: " . $class->name . "\n";

        $methods = $class->getMethods();
        foreach ($methods as $method)
        {
            $annotations = $method->getAnnotations(array("param", "author"));

            echo "Method: " . $method->getClass()->name . "::" . $method->name . "\n";
            echo "Description: " . $method->description . "\n";

            if (empty($annotations))
                continue;

            foreach ($annotations as $annotation)
            {
                echo "\tAnnotation: " . $annotation->name . "\n";
                echo "\tValues: " . print_r($annotation->values, true) . "\n";
            }

            echo str_repeat("-", 50) . "\n";
        }
    }

    class BaseClass
    {
        /**
         * Test function 1
         */
        public function testFunc1()
        {

        }

        /**
         * Test function 2
         */
        protected function testFunc2()
        {

        }
    }

    class TestClass extends BaseClass
    {
        /**
         * This is the DocBlock description
         * @param $data  The data to be passed in
         */
        public function test($data)
        {
        }

        /**
         * This is another DocBlock description
         * @param $data  The data to be passed in
         * @author    Danny Kopping
         */
        protected function test2($data)
        {
        }
    }

?>
```


## Contact

Feel free to suggest feature requests by creating an "Issue" or by forking the repo, making the changes yourself and sending me a pull request.