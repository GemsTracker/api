<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Psr\Http\Message\ServerRequestInterface;

class EpdRepository
{
    /**
     * @var string Epd name
     */
    protected $epdName;

    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUser;

    public function __construct(CurrentUserRepository $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    /**
     * Get the epd name from the current user.
     *
     * @return string epd name or username
     */
    public function getEpdName()
    {
        $currentUserName = $this->currentUser->getUserName();

        if (strpos($currentUserName, 'heuvelrug') === 0) {
            return 'heuvelrug';
        }

        if (strpos($currentUserName, 'emma') === 0) {
            return 'emma';
        }

        return $currentUserName;
    }
}
