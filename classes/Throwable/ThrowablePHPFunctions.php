<?php

namespace GodsDev\MyCMS\Throwable;

use Exception;

function preg_match(){
$result = preg_match();
if ($result===false){
throw new Exception('error');
}
return $result;
}
