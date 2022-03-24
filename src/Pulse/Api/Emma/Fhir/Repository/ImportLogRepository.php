<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Gems\Rest\Log\Formatter\SimpleMulti;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream;
use Laminas\Stdlib\SplPriorityQueue;
use Psr\Http\Message\ServerRequestInterface;

class ImportLogRepository
{

    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    protected $logDir = null;

    /**
     * @var array
     */
    protected $loggers = [];

    public function __construct(EpdRepository $epdRepository, $config)
    {
        $this->epdRepository = $epdRepository;
        if (isset($config['log'], $config['log']['logDir'])) {
            $this->logDir = $config['log']['logDir'];
        }
    }

    /**
     * Create a logger with a file based on epd name
     *
     * @param $epdName string epd name
     * @return PsrLoggerAdapter Logger
     */
    protected function createImportLogger($epdName)
    {
        $logger = new Logger();
        $importWriter = new Stream($this->logDir . DIRECTORY_SEPARATOR . $epdName . '-import.log');
        $importWriter->setFormatter(new SimpleMulti());

        $logger->addWriter($importWriter);
        return new PsrLoggerAdapter($logger);
    }

    /**
     * Get an import logger
     *
     * @return PsrLoggerAdapter|null
     */
    public function getImportLogger()
    {
        $epdName = $this->epdRepository->getEpdName();
        if (!isset($this->loggers[$epdName])) {
            $this->loggers[$epdName] = $this->createImportLogger($epdName);
        }
        return $this->loggers[$epdName];
    }
}
