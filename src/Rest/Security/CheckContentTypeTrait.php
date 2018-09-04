<?php

namespace Gems\Rest\Security;

use Psr\Http\Message\ServerRequestInterface;

trait CheckContentTypeTrait
{
    /**
     * @var array List of allowed content types as input for write methods
     */
    protected $allowedContentTypes = ['application/json'];

    /**
     * Check if current content type is allowed for the current method
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function checkContentType(ServerRequestInterface $request)
    {
        $contentTypeHeader = $request->getHeaderLine('content-type');
        foreach ($this->allowedContentTypes as $contentType) {
            if (strpos($contentTypeHeader, $contentType) !== false) {
                return true;
            }
        }

        return false;
    }
}