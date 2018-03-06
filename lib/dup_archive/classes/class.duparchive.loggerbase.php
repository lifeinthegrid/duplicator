<?php

abstract class DupArchiveLoggerBase
{
    abstract public function log($s, $flush = false, $callingFunctionOverride = null);
}
