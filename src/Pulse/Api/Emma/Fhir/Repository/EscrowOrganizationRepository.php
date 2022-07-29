<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


class EscrowOrganizationRepository
{
    protected $defaultId = 81;

    protected $idsPerEpd = [
        'emma' => 81,
        'heuvelrug' => 92,
    ];

    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(EpdRepository $epdRepository)
    {
        $this->epdRepository = $epdRepository;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        if (isset($this->idsPerEpd[$this->epdRepository->getEpdName()])) {
            return $this->idsPerEpd[$this->epdRepository->getEpdName()];
        }
        return $this->defaultId;
    }
}
