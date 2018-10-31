<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Security\CheckContentTypeTrait;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class CorrectTokenController extends RestControllerAbstract
{
    use CheckContentTypeTrait;

    protected $correctReceptionCode = 'correct';

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker, \Zend_Translate_Adapter $translateAdapter)
    {
        $this->tracker = $tracker;
        $this->translateAdapter = $translateAdapter;
    }

    public function patch(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            return new EmptyResponse(404);
        }

        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $token = $this->tracker->getToken($id);
        $oldComment = $token->getComment();

        $parsedBody = json_decode($request->getBody()->getContents(), true);

        $comment = null;
        if (isset($parsedBody['comment'])) {
            $comment = $parsedBody['comment'];
        }


        $receptionCodeResult = $token->setReceptionCode($this->correctReceptionCode, $comment, $this->userId);
        if ($receptionCodeResult) {
            $newComment = $this->getNewComment($id, $oldComment);

            $replacementTokenId = $token->createReplacement($newComment, $this->userId);

            return new JsonResponse(['replacement_token' => $replacementTokenId], 201);
        }

        return new EmptyResponse(200);
    }

    protected function getNewComment($tokenId, $oldComment=null)
    {
        $newComment = sprintf($this->translateAdapter->_('Redo of token %s.'), $tokenId);
        if ($oldComment) {
            $newComment .= "\n\n";
            $newComment .= $this->translateAdapter->_('Old comment:');
            $newComment .= "\n";
            $newComment .= $oldComment;
        }
        return $newComment;
    }
}