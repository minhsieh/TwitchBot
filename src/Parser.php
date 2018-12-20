<?php

namespace Minhsieh\TwitchBot;

class Parser
{
    static $dummy = [
        'raw' => null,
        'prefix' => null,
        'type' => null,
        'sender' => [
            'type' => null,
            'nick' => null,
            'id'   => null,
            'mask' => null
        ],
        'params' => [
            'full' => null,
            'middle' => null,
            'trailing' => null
        ]
    ];

    public function __construct() {}

    static public function parse($data)
    {
        $data = self::removeLineBreaks($data);

        $parsed = self::$dummy;
        $parsed['raw'] = $data;

        $parts = explode(' ', $data, 3);

        // Setup the prefix, type and parameters variables in the return array
        if(isset($parts[0]) && substr($parts[0], 0, 1) == ':' && isset($parts[1]) && isset($parts[2])){
            $parsed['prefix'] = $parts[0];
            $parsed['type'] = $parts[1];
            $parsed['params']['full'] = $parts[2];
        }elseif(isset($parts[0]) && substr($parts[0], 0, 1) !== ':' && isset($parts[1]) && !isset($parts[2])){
            $parsed['prefix'] = null;
            $parsed['type'] = $parts[0];
            $parsed['params']['full'] = $parts[1];
        }else{
            return false;
        }

        // Retrieve sender information: <nick> <type> <id> <host/mask>
        preg_match( "/^:(.*)!(.*)@(.*)/", $parsed['prefix'], $match );
        
        // Check for server response - normally numerical message.
        if(isset($match[1]) && isset($match[2]) && isset($match[3])){
            $parsed['sender']['type'] = 'client';
            $parsed['sender']['nick'] = $match[1];
            $parsed['sender']['id']   = $match[2];
            $parsed['sender']['mask'] = $match[3];
        }else{
            $parsed['sender']['type'] = 'server';
            $parsed['sender']['nick'] = substr($parsed['prefix'], 1);
        }
        
        // Split parameters into sections: full, middle, trailing
        $parts = explode(' ', $parsed['params']['full'], 2);
        
        // Parameters after the command
        $parsed['params']['middle'] = trim($parts[0]);
        
        // Trailing parameters if they exist - anything after last ':'
        $parsed['params']['trailing'] = isset($parts[1]) ? $parts[1] : '';
        
        // Return the parsed data
        return $parsed;
    }

    static private function removeLineBreaks($data)
    {
        return str_replace(array(chr(10), chr(13)), '', $data);
    }
}