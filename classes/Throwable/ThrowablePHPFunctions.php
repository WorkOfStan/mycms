<?php

namespace GodsDev\MyCMS\Throwable;

use Exception;

//Mixed
function throwOnFalse($result){
if ($result===false){
throw new Exception('error '.debug_backtrace()[1]['function']);
}
return $result;
}

function preg_match(){
  $result = preg_match();
  return throwOnFalse($result);
}
