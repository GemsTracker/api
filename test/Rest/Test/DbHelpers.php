<?php

namespace GemsTest\Rest\Test;

trait DbHelpers
{
    protected function getDbNow($dateTime=true)
    {
        $now = new \DateTimeImmutable();
        $format = 'Y-m-d H:i:s';
        if (!$dateTime) {
            $format = 'Y-m-d';
        }
        return $now->format($format);
    }
}
