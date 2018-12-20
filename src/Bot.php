<?php

namespace Minhsieh\TwitchBot;

class Bot
{
    // Socket Server property
    private $server = '';
    private $port = 6667;

    // Bot property
    private $name, $nick, $mask, $nickToUse, $pass;
    private $nickCounter = 1;
    private $channels = [];
    private $loop;
    private $debug = false;

    private $socket;
    
    public function __construct(){}
    
    public function __destruct()
    {
        $this->disconnect();
    }
    
    public function isConnected()
    {
        return (is_resource($this->socket) ? true : false);
    }

    public function connect()
    {
        if(isset($this->server) && isset($this->port)){
            $this->socket = fsockopen($this->server, $this->port);
            
            if(!$this->isConnected()){
                throw new Exception("Unable to connect to server:" . $this->server . " and port: " . $this->port . ".");
            }
        }else{
            throw new Exception("IRC must setServer or setPort");
        }
    }
    
    public function disconnect()
    {
        return fclose($this->socket);
    }
    
    public function reconnect()
    {
        if(!$this->isConnected()){
            $this->connect();
        }else{
            $this->disconnect();
            $this->connect();
        }
    }
    
    public function sendData($data)
    {
        $data = $this->colour($data);
        return fwrite($this->socket, $data . "\r\n");
    }

    public function getData()
    {
        $data = fgets($this->socket, 256);
        if($this->debug && !empty($data)) echo "[".date("Y-m-d H:i:s")."] ".$data;
        return $data;
    }


    public function botConnect()
    {
        if(empty($this->nickToUse)){
            $this->nickToUse = $this->nick;
        }

        if($this->isConnected()){
            $this->disconnect();
        }

        $this->connect();

        if(!empty($this->pass)){
            $this->sendData('PASS '.$this->pass);
        }

        $this->sendData('USER '.$this->nickToUse.' '.$this->mask.' '.$this->nickToUse . ' :' . $this->name);
        $this->sendData('NICK '.$this->nickToUse);

        $checking = true;

        while($checking){
            $data = $this->getData();

            if(stripos($data, 'Nickname is already in use.') !== false){
                $this->nickToUse = $this->nick . (++$this->nickCounter);
                $this->sendData('NICK ' . $this->nickToUse);
            }

            if (stripos($data, 'Welcome') !== false) {
                $this->sendData('PRIVMSG NICKSERV :IDENTIFY ' . $this->pass);
                $this->joinChannel($this->channels);
                $checking = false;
            }

            if (stripos($data, 'Registration Timeout') !== false || stripos($data, 'Erroneous Nickname') !== false || stripos($data, 'Closing Link') !== false){
                die();
            }
        }

        // Main Loop
        if ($this->loop) {
			call_user_func($this->loop, $pno , $this);
		}
    }

    private function joinChannel($channel)
    {
        $channel = (array) $channel;

        foreach($channel as $chan){
            if(!empty($chan)){
                $chan = explode(' ', $chan);
                if(count($chan) <= 1){
                    $this->sendData('JOIN ' . $chan[0]);
                }else{
                    $this->sendData('JOIN ' . $chan[0] . ' ' . $chan[1]);
                }
            }
        }
    }

    public function setServer($server)
    {
        $this->server = (string) $server;
    }
    
    public function setPort($port)
    {
        $this->port = (int) $port;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function setNick($nick)
    {
        $this->nick = (string) $nick;
    }

    public function setMask($mask)
    {
        $this->mask = (string) $mask;
    }

    public function setPass($pass)
    {
        $this->pass = (string) $pass;
    }

    public function getName()
    {
        return $this->nickToUse;
    }
    
    public function getServer()
    {
        return $this->server;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setChannel($channel)
    {
        $this->channels = (array) $channel;
    }

    public function setLoop($loop)
    {
        $this->loop = $loop;
    }

    public function setDebug($boolean = true)
    {
        $this->debug = $boolean;
    }

    private function colour($line)
    {
        return preg_replace(array(0=>'#\[w\](.*)\[/w\]#U', 1=>'#\[bla\](.*)\[/bla\]#U',
                            2=>'#\[bl2\](.*)\[/bl2\]#U', 3=>'#\[gn2\](.*)\[/gn2\]#U',
                            4=>'#\[r\](.*)\[/r\]#U', 5=>'#\[br\](.*)\[/br\]#U', 6=>'#\[l\](.*)\[/l\]#U',
                            7=>'#\[o\](.*)\[/o\]#U', 8=>'#\[y\](.*)\[/y\]#U', 9=>'#\[g\](.*)\[/g\]#U',
                            10=>'#\[t2\](.*)\[/t2\]#U', 11=>'#\[t\](.*)\[/t\]#U',
                            12=>'#\[bl\](.*)\[/bl\]#U', 13=>'#\[p\](.*)\[/p\]#U',
                            14=>'#\[gy2\](.*)\[/gy2\]#U', 15=>'#\[gy\](.*)\[/gy\]#U',
                            16=>'#\[B\](.*)?\[/B\]#U', 17=>'#\[U\](.*)?\[/U\]#U'),
                            array(chr(3)."00$1".chr(3)."\t", chr(3)."01$1".chr(3)."\t", chr(3)."02$1".chr(3)."\t", 
                                    chr(3)."03$1".chr(3)."\t", chr(3)."04$1".chr(3)."\t", chr(3)."05$1".chr(3)."\t",
                                    chr(3)."06$1".chr(3)."\t", chr(3)."07$1".chr(3)."\t", chr(3)."08$1".chr(3)."\t", 
                                    chr(3)."09$1".chr(3)."\t", chr(3)."10$1".chr(3)."\t", chr(3)."11$1".chr(3)."\t",
                                    chr(3)."12$1".chr(3)."\t", chr(3)."12$1".chr(3)."\t", chr(3)."14$1".chr(3)."\t", 
                                    chr(3)."15$1".chr(3)."\t", "\2$1\2", "\37$1\37"),
                                    $line);
    }
}
?>