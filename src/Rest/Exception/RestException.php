<?php


namespace Gems\Rest\Exception;


use Psr\Http\Message\ResponseInterface;

class RestException extends \Exception
{
    /**
     * @var int
     */
    protected $httpStatusCode;

    /**
     * @var string
     */
    protected $errorType;

    /**
     * @var null|string
     */
    protected $hint;

    /**
     * @var null|string
     */
    protected $redirectUri;

    /**
     * Throw a new exception.
     *
     * @param string      $message        Error message
     * @param int         $code           Error code
     * @param string      $errorType      Error type
     * @param int         $httpStatusCode HTTP status code to send (default = 400)
     * @param null|string $hint           A helper hint
     * @param null|string $redirectUri    A HTTP URI to redirect the user back to
     */
    public function __construct($message, $code, $errorType, $httpStatusCode = 400, $hint = null, $redirectUri = null)
    {
        parent::__construct($message, $code);
        $this->httpStatusCode = $httpStatusCode;
        $this->errorType = $errorType;
        $this->hint = $hint;
        $this->redirectUri = $redirectUri;
    }

    /**
     * Generate a HTTP response.
     *
     * @param ResponseInterface $response
     * @param bool              $useFragment True if errors should be in the URI fragment instead of query string
     *
     * @return ResponseInterface
     */
    public function generateHttpResponse(ResponseInterface $response, $useFragment = false)
    {
        $payload = [
            'error'   => $this->getErrorType(),
            'message' => $this->getMessage(),
        ];

        if ($this->hint !== null) {
            $payload['hint'] = $this->hint;
        }

        if ($this->redirectUri !== null) {
            if ($useFragment === true) {
                $this->redirectUri .= (strstr($this->redirectUri, '#') === false) ? '#' : '&';
            } else {
                $this->redirectUri .= (strstr($this->redirectUri, '?') === false) ? '?' : '&';
            }

            return $response->withStatus(302)->withHeader('Location', $this->redirectUri . http_build_query($payload));
        }

        $response->getBody()->write(json_encode($payload));

        return $response->withStatus($this->getHttpStatusCode());
    }

    /**
     * Get the type of the error
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /**
     * get the HTML status code
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}