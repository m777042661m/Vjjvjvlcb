<?php
class RouterosAPI {
    // Define properties
    private $host;
    private $user;
    private $pass;
    private $port;
    private $socket;
    private $connected;

    // Connect to the router
    public function connect($host, $user, $pass, $port = 8728) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!$this->socket) {
            return false;
        }
        $this->connected = true;
        // Login
        $this->write("/login");
        $response = $this->read();
        $challenge = pack('H*', substr($response[0]['ret'], -32));
        $md5 = md5(chr(0) . $this->pass . $challenge, true);
        $this->write("/login", false);
        $this->write("=name=" . $this->user, false);
        $this->write("=response=00" . bin2hex($md5));
        $response = $this->read();
        if (isset($response[0]) && $response[0] == '!done') {
            return true;
        }
        return false;
    }

    // Disconnect from the router
    public function disconnect() {
        fclose($this->socket);
        $this->connected = false;
    }

    // Write data to the router
    public function write($command, $tag = true) {
        $command = trim($command);
        if ($tag) {
            $command .= chr(0);
        }
        fwrite($this->socket, $command);
    }

    // Read data from the router
    public function read() {
        $response = [];
        while (true) {
            $line = fread($this->socket, 1);
            if (ord($line[0]) == 0) {
                break;
            }
            $length = ord($line[0]);
            if ($length > 0x80) {
                if (($length & 0xC0) == 0x80) {
                    $length = ($length & 0x3F) << 8 | ord(fread($this->socket, 1));
                } else {
                    $length = ($length & 0x3F) << 24 | ord(fread($this->socket, 1)) << 16 | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
                }
            }
            $response[] = fread($this->socket, $length);
        }
        return $response;
    }

    // Send command to the router
    public function comm($command, $arr = []) {
        $this->write($command, false);
        foreach ($arr as $key => $value) {
            $this->write("=$key=$value", false);
        }
        $this->write("", true);
        return $this->read();
    }
}
?>