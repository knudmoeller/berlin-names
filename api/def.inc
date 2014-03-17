<?php

define('APP_PATH', "/berlin-names/");
define('LIST_LIMIT', 72);
define('PAGINATION_RANGE', 3);
define('MAX_COUNT', 122);

require_once(dirname(__FILE__)."/../lib/php/sparqllib.php" );


sparql_connect( "http://dydra.com/knudmoeller/berliner-vornamen/sparql" );

?>