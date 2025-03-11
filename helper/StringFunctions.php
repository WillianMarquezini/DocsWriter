<?php

class StringFunctions {
    public static function utf8Conv($s, $convOf = 'ISO-8859-1', $convTo = 'UTF-8'){
        return mb_convert_encoding($s, $convOf, $convTo);
    }
}