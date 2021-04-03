<?php

namespace GodsDev\MyCMS\Throwable;

use Exception;


//can't be used to return the result directly as mixed return would not be type strict
//@return void
function throwOnFalse($result){
    if ($result===false){
        throw new Exception('error '.debug_backtrace()[1]['function']);
    }
}

function preg_match(){
    $result = preg_match();
    throwOnFalse($result);
    return $result;
}
