<?php

declare(strict_types=1);

namespace App\Infrastructure\TrackMania;

/**
 * GbxRemote - XML-RPC client for TrackMania dedicated servers
 * Based on IXR - The Incutio XML-RPC Library
 */
class GbxRemote
{
    private mixed $socket = false;
    private int $reqHandle = 0x80000000;
    private int $protocol = 0;
    private ?string $error = null;

    public function connect(string $ip, int $port, int $timeout = 5): bool
    {
        $this->error = null;
        $this->socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);

        if (!$this->socket) {
            $this->error = "Could not connect: {$errno} - {$errstr}";
            return false;
        }

        // Handshake
        $data = fread($this->socket, 4);
        if ($data === false || strlen($data) < 4) {
            $this->error = 'Handshake failed: could not read header size';
            $this->disconnect();
            return false;
        }

        $result = unpack('Vsize', $data);
        $size = $result['size'];

        if ($size > 64) {
            $this->error = 'Handshake failed: wrong protocol header';
            $this->disconnect();
            return false;
        }

        $handshake = fread($this->socket, $size);
        if ($handshake === 'GBXRemote 2') {
            $this->protocol = 2;
        } elseif ($handshake === 'GBXRemote 1') {
            $this->protocol = 1;
        } else {
            $this->error = 'Handshake failed: wrong protocol version';
            $this->disconnect();
            return false;
        }

        return true;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = false;
        }
        $this->protocol = 0;
    }

    public function query(string $method, mixed ...$args): mixed
    {
        if (!$this->socket || $this->protocol === 0) {
            $this->error = 'Not connected';
            return false;
        }

        $xml = $this->buildRequest($method, $args);

        if (strlen($xml) > 512 * 1024 - 8) {
            $this->error = 'Request too large';
            return false;
        }

        // Send request
        if (!$this->sendRequest($xml)) {
            return false;
        }

        // Get response
        return $this->getResponse();
    }

    public function authenticate(string $username, string $password): bool
    {
        $result = $this->query('Authenticate', $username, $password);
        return $result === true;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    private function buildRequest(string $method, array $args): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8" ?><methodCall><methodName>' . $method . '</methodName><params>';

        foreach ($args as $arg) {
            $xml .= '<param><value>' . $this->encodeValue($arg) . '</value></param>';
        }

        $xml .= '</params></methodCall>';
        return $xml;
    }

    private function encodeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return '<boolean>' . ($value ? '1' : '0') . '</boolean>';
        }
        if (is_int($value)) {
            return '<int>' . $value . '</int>';
        }
        if (is_float($value)) {
            return '<double>' . $value . '</double>';
        }
        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                $xml = '<struct>';
                foreach ($value as $k => $v) {
                    $xml .= '<member><name>' . $k . '</name><value>' . $this->encodeValue($v) . '</value></member>';
                }
                $xml .= '</struct>';
                return $xml;
            } else {
                $xml = '<array><data>';
                foreach ($value as $v) {
                    $xml .= '<value>' . $this->encodeValue($v) . '</value>';
                }
                $xml .= '</data></array>';
                return $xml;
            }
        }
        return '<string>' . htmlspecialchars((string) $value) . '</string>';
    }

    private function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function sendRequest(string $xml): bool
    {
        $this->reqHandle++;

        if ($this->protocol === 1) {
            $bytes = pack('Va*', strlen($xml), $xml);
        } else {
            $bytes = pack('VVa*', strlen($xml), $this->reqHandle, $xml);
        }

        stream_set_timeout($this->socket, 5);
        $written = @fwrite($this->socket, $bytes);

        if ($written === false || $written === 0) {
            $this->error = 'Failed to send request';
            return false;
        }

        return true;
    }

    private function getResponse(): mixed
    {
        stream_set_timeout($this->socket, 5);

        if ($this->protocol === 1) {
            $header = fread($this->socket, 4);
            if (strlen($header) < 4) {
                $this->error = 'Failed to read response header';
                return false;
            }
            $result = unpack('Vsize', $header);
        } else {
            $header = fread($this->socket, 8);
            if (strlen($header) < 8) {
                $this->error = 'Failed to read response header';
                return false;
            }
            $result = unpack('Vsize/Vhandle', $header);
        }

        $size = $result['size'];

        if ($size > 4096 * 1024) {
            $this->error = 'Response too large';
            return false;
        }

        $contents = '';
        while (strlen($contents) < $size) {
            $chunk = fread($this->socket, $size - strlen($contents));
            if ($chunk === false) {
                break;
            }
            $contents .= $chunk;
        }

        return $this->parseResponse($contents);
    }

    private function parseResponse(string $xml): mixed
    {
        $doc = @simplexml_load_string($xml);
        if ($doc === false) {
            $this->error = 'Failed to parse XML response';
            return false;
        }

        // Check for fault
        if (isset($doc->fault)) {
            $fault = $this->parseValue($doc->fault->value);
            $this->error = $fault['faultString'] ?? 'Unknown fault';
            return false;
        }

        // Parse params
        if (isset($doc->params->param->value)) {
            return $this->parseValue($doc->params->param->value);
        }

        return null;
    }

    private function parseValue(\SimpleXMLElement $value): mixed
    {
        $children = $value->children();

        if (count($children) === 0) {
            return (string) $value;
        }

        $child = $children[0];
        $name = $child->getName();

        return match ($name) {
            'boolean' => (bool) (int) (string) $child,
            'int', 'i4' => (int) (string) $child,
            'double' => (float) (string) $child,
            'string' => (string) $child,
            'base64' => base64_decode((string) $child),
            'array' => $this->parseArray($child),
            'struct' => $this->parseStruct($child),
            default => (string) $child,
        };
    }

    private function parseArray(\SimpleXMLElement $array): array
    {
        $result = [];
        if (isset($array->data->value)) {
            foreach ($array->data->value as $value) {
                $result[] = $this->parseValue($value);
            }
        }
        return $result;
    }

    private function parseStruct(\SimpleXMLElement $struct): array
    {
        $result = [];
        if (isset($struct->member)) {
            foreach ($struct->member as $member) {
                $name = (string) $member->name;
                $result[$name] = $this->parseValue($member->value);
            }
        }
        return $result;
    }
}
