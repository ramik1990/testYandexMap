<?php

namespace App\Services\YandexMaps;

final class RequestSigner
{
    public function signedQuery(array $params): string
    {
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return $query.'&s='.$this->hash($query);
    }

    public function hash(string $value): int
    {
        $hash = 5381;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $hash = (($hash * 33) ^ ord($value[$i])) & 0xFFFFFFFF;
        }

        return $hash;
    }
}
