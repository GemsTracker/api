<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Repository\AccesslogRepository;
use Zalt\Loader\ProjectOverloader;
use Mezzio\Helper\UrlHelper;

class TokenController extends ModelRestController
{
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb, \Gems_Tracker $tracker)
    {
        $this->tracker = $tracker;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    protected function afterSaveRow($newRow)
    {
        $token = $this->tracker->getToken($newRow);

        $updateData['gto_valid_from']         = $newRow['gto_valid_from'];
        $updateData['gto_valid_from_manual']  = $newRow['gto_valid_from_manual'];
        $updateData['gto_valid_until']        = $newRow['gto_valid_until'];
        $updateData['gto_valid_until_manual'] = $newRow['gto_valid_until_manual'];
        $updateData['gto_comment']            = $newRow['gto_comment'];

        $token->refresh($updateData);

        $respTrack = $token->getRespondentTrack();
        $changed   = $respTrack->checkTrackTokens($this->userId, $token);

        return $newRow;
    }
}
