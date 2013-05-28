iGeek Transient
===============

A PHP class for use in WordPress to fetch and cache data

**License:** [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html)

## Description ##

**iGeek_Transient** is a PHP class made for use in WordPress to fetch data from function callbacks and cache it. If the data exists in cache then its returned from there else the callback is used to get the data and is cached for future consumption as well. It uses WordPress Transient API for caching the data.

**Requirements**: This class has been tested with PHP 5.3 and WordPress 3.5.1. It might or might not work with earlier versions of either.

## Usage ##

Using this class is very simple and method chaining is supported.

**Example 1:**

```php
$data = array(
	'red', 'orange', 'green', 'blue', 'orange', 'yellow', 'red', 'pink'
);

$o_transient = new iGeek_Transient( 'my-random-data' );	//create new class object and pass a unique key to it for associating with cache
$filtered_data = $o_transient->expires_in( 3600 )	//set cache expiry to one hour
				->updates_using( 'array_unique', array( $data ) )	//set callback to get data from and all parameters for it should be in an array
				->get();	//get the data - either from cache or callback
```

**Example 2:**

```php
class My_Class {
	public function __construct() { }

	public function display_data() {
		$data = array(
			'red', 'orange', 'green', 'blue', 'orange', 'yellow', 'red', 'pink'
		);

		$o_transient = new iGeek_Transient( 'my-random-data' );
		$filtered_data = $o_transient->expires_in( 0 )	//set cache expiry to zero, we don't want our data cached
						->updates_using( array( $this, 'generate_data' ), array( $data ) )	//set callback to get data from and all parameters for it should be in an array
						->get();	//get the data - either from cache or callback
		
		print( "<pre>\n" );
		print( "Colours: \n" );

		$count = count( $filtered_data );
		for( $i = 0; $i < $count; $i++ ) {
			print( "-> " . $filtered_data[ $i ] . "\n" );
		}

		print( "</pre>\n" );
	}

	public function generate_data( $data ) {
		return array_unique( $data );
	}
}

$o_my_class = new My_Class;
$o_my_class->display_data();
```

