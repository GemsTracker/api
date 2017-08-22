<?php


namespace Gems\Rest\Action;

use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;

class TestModelAction implements ServerMiddlewareInterface
{
    /**
     *
     * @var ProjectOverloader
     */
    protected $loader;

    public function __construct(ContainerInterface $container, ProjectOverloader $loader)
    {
        $this->loader = $loader;
        //$this->loader->verbose = true;
        $this->loader->legacyClasses = true;

        $container->get(\Zend_Db::class);

        $clientRepository = $container->get('Rest\Auth\ClientRepository');

        //print_r($clientRespository->getClientEntity('test', null));

        $scopeRepository = $container->get('Rest\\Auth\\ScopeRepository');

        //print_r($scopeRepository->getScopeEntityByIdentifier('all'));

        $accessTokenRepository = $container->get('Rest\\Auth\\AccessTokenRepository');

        $userRepository = $container->get('Rest\\Auth\\UserRepository');

        $client = $clientRepository->getClientEntity('test', null, null, false);

        $user = $userRepository->getUserEntityByUserCredentials('jvangestel@70', 'test123', null, $client);

        if (!$user) {
            echo 'No user found!';
        }
        print_r($user);



    }



    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $model = $this->loader->create('Model_OrganizationModel');
        $model->applyBrowseSettings();

        return new JsonResponse($model->load());
    }
}