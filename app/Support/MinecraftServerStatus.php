<?php

namespace App\Support;

use RuntimeException;

class MinecraftServerStatus
{
    private const COLORS = [
        'black' => '#000000',
        'dark_blue' => '#0000aa',
        'dark_green' => '#00aa00',
        'dark_aqua' => '#00aaaa',
        'dark_red' => '#aa0000',
        'dark_purple' => '#aa00aa',
        'gold' => '#ffaa00',
        'gray' => '#aaaaaa',
        'dark_gray' => '#555555',
        'blue' => '#5555ff',
        'green' => '#55ff55',
        'aqua' => '#55ffff',
        'red' => '#ff5555',
        'light_purple' => '#ff55ff',
        'yellow' => '#ffff55',
        'white' => '#ffffff',
    ];

    private const LEGACY_COLORS = [
        '0' => '#000000',
        '1' => '#0000aa',
        '2' => '#00aa00',
        '3' => '#00aaaa',
        '4' => '#aa0000',
        '5' => '#aa00aa',
        '6' => '#ffaa00',
        '7' => '#aaaaaa',
        '8' => '#555555',
        '9' => '#5555ff',
        'a' => '#55ff55',
        'b' => '#55ffff',
        'c' => '#ff5555',
        'd' => '#ff55ff',
        'e' => '#ffff55',
        'f' => '#ffffff',
    ];

    public function ping(string $host, int $port = 25565, float $timeout = 1.5): array
    {
        $startedAt = microtime(true);
        [$host, $port] = $this->resolveSrv($host, $port);
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $error,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (! $socket) {
            throw new RuntimeException($error ?: "Could not connect to {$host}:{$port}", $errno);
        }

        stream_set_timeout($socket, (int) $timeout, (int) (($timeout - floor($timeout)) * 1_000_000));

        $hostLength = strlen($host);
        $handshake = $this->writeVarInt(-1)
            .$this->writeString($host)
            .pack('n', $port)
            .$this->writeVarInt(1);

        fwrite($socket, $this->writePacket(0, $handshake));
        fwrite($socket, $this->writePacket(0, ''));

        $length = $this->readVarInt($socket);
        if ($length <= 0) {
            throw new RuntimeException('Empty status packet.');
        }

        $packetId = $this->readVarInt($socket);
        if ($packetId !== 0) {
            throw new RuntimeException('Unexpected status packet.');
        }

        $jsonLength = $this->readVarInt($socket);
        $payload = $this->readBytes($socket, $jsonLength);
        fclose($socket);

        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $description = $data['description'] ?? '';

        return [
            'online' => true,
            'latency' => (int) round((microtime(true) - $startedAt) * 1000),
            'version' => $data['version']['name'] ?? null,
            'protocol' => $data['version']['protocol'] ?? null,
            'players' => [
                'online' => $data['players']['online'] ?? null,
                'max' => $data['players']['max'] ?? null,
            ],
            'motd' => [
                'plain' => $this->plainText($description),
                'segments' => $this->formatSegments($description),
            ],
            'favicon' => $data['favicon'] ?? null,
        ];
    }

    private function resolveSrv(string $host, int $port): array
    {
        if ($port !== 25565 || filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host, $port];
        }

        $records = dns_get_record("_minecraft._tcp.{$host}", DNS_SRV);

        if ($records === false || $records === []) {
            return [$host, $port];
        }

        usort($records, fn (array $a, array $b) => [$a['pri'], -$a['weight']] <=> [$b['pri'], -$b['weight']]);
        $record = $records[0];

        return [rtrim($record['target'], '.'), (int) $record['port']];
    }

    private function formatSegments(mixed $description): array
    {
        if (is_string($description)) {
            return $this->legacySegments($description);
        }

        if (! is_array($description)) {
            return [];
        }

        $segments = [];
        $this->appendJsonSegments($description, [], $segments);

        return $segments;
    }

    private function appendJsonSegments(array $component, array $style, array &$segments): void
    {
        $style = [
            ...$style,
            ...array_filter([
                'color' => isset($component['color']) ? (self::COLORS[$component['color']] ?? $component['color']) : null,
                'bold' => $component['bold'] ?? null,
                'italic' => $component['italic'] ?? null,
                'underlined' => $component['underlined'] ?? null,
                'strikethrough' => $component['strikethrough'] ?? null,
            ], fn ($value) => $value !== null),
        ];

        if (isset($component['text']) && $component['text'] !== '') {
            foreach ($this->legacySegments($component['text']) as $segment) {
                $segments[] = [
                    'text' => $segment['text'],
                    'style' => [...$style, ...$segment['style']],
                ];
            }
        }

        foreach ($component['extra'] ?? [] as $child) {
            if (is_array($child)) {
                $this->appendJsonSegments($child, $style, $segments);
            }
        }
    }

    private function legacySegments(string $text): array
    {
        $segments = [];
        $style = [];
        $buffer = '';

        foreach (preg_split('/(§.|&.)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^[§&](.)$/u', $part, $match) === 1) {
                if ($buffer !== '') {
                    $segments[] = ['text' => $buffer, 'style' => $style];
                    $buffer = '';
                }

                $code = strtolower($match[1]);
                if (isset(self::LEGACY_COLORS[$code])) {
                    $style = ['color' => self::LEGACY_COLORS[$code]];
                } elseif ($code === 'l') {
                    $style['bold'] = true;
                } elseif ($code === 'o') {
                    $style['italic'] = true;
                } elseif ($code === 'n') {
                    $style['underlined'] = true;
                } elseif ($code === 'm') {
                    $style['strikethrough'] = true;
                } elseif ($code === 'r') {
                    $style = [];
                }

                continue;
            }

            $buffer .= $part;
        }

        if ($buffer !== '') {
            $segments[] = ['text' => $buffer, 'style' => $style];
        }

        return $segments;
    }

    private function plainText(mixed $description): string
    {
        return collect($this->formatSegments($description))->pluck('text')->implode('');
    }

    private function writePacket(int $packetId, string $payload): string
    {
        $packet = $this->writeVarInt($packetId).$payload;

        return $this->writeVarInt(strlen($packet)).$packet;
    }

    private function writeString(string $value): string
    {
        return $this->writeVarInt(strlen($value)).$value;
    }

    private function writeVarInt(int $value): string
    {
        $buffer = '';
        if ($value < 0) {
            $value += 1 << 32;
        }

        while (true) {
            if (($value & ~0x7F) === 0) {
                return $buffer.chr($value);
            }

            $buffer .= chr(($value & 0x7F) | 0x80);
            $value >>= 7;
        }
    }

    private function readVarInt(mixed $socket): int
    {
        $value = 0;
        $position = 0;

        while (true) {
            $byte = ord($this->readBytes($socket, 1));
            $value |= ($byte & 0x7F) << $position;

            if (($byte & 0x80) === 0) {
                return $value;
            }

            $position += 7;

            if ($position >= 35) {
                throw new RuntimeException('VarInt is too big.');
            }
        }
    }

    private function readBytes(mixed $socket, int $length): string
    {
        $data = '';

        while (strlen($data) < $length && ! feof($socket)) {
            $chunk = fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }

        if (strlen($data) !== $length) {
            throw new RuntimeException('Could not read server response.');
        }

        return $data;
    }
}
