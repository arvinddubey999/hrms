<?php

namespace App\Services;

class SimpleZipWriter
{
    /**
     * Create a binary ZIP file from an associative array of ['filename' => 'file content'].
     */
    public static function createZip(array $files): string
    {
        $zipData = '';
        $centralDirectory = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $name = str_replace('\\', '/', $name);
            $uncLen = strlen($content);
            $crc = crc32($content);
            $zContent = gzcompress($content);
            $zContent = substr($zContent, 2, -4);
            $cLen = strlen($zContent);

            $header = "\x50\x4b\x03\x04"
                . "\x14\x00"
                . "\x00\x00"
                . "\x08\x00"
                . "\x00\x00\x00\x00"
                . pack('V', $crc)
                . pack('V', $cLen)
                . pack('V', $uncLen)
                . pack('v', strlen($name))
                . pack('v', 0)
                . $name;

            $zipData .= $header . $zContent;

            $cd = "\x50\x4b\x01\x02"
                . "\x14\x00"
                . "\x14\x00"
                . "\x00\x00"
                . "\x08\x00"
                . "\x00\x00\x00\x00"
                . pack('V', $crc)
                . pack('V', $cLen)
                . pack('V', $uncLen)
                . pack('v', strlen($name))
                . pack('v', 0)
                . pack('v', 0)
                . pack('v', 0)
                . pack('v', 0)
                . pack('V', 32)
                . pack('V', $offset)
                . $name;

            $centralDirectory .= $cd;
            $offset += strlen($header) + $cLen;
        }

        $cdOffset = strlen($zipData);
        $cdSize = strlen($centralDirectory);
        $eocd = "\x50\x4b\x05\x06"
            . "\x00\x00"
            . "\x00\x00"
            . pack('v', count($files))
            . pack('v', count($files))
            . pack('V', $cdSize)
            . pack('V', $cdOffset)
            . pack('v', 0);

        return $zipData . $centralDirectory . $eocd;
    }
}
