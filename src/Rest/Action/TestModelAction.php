<?php


namespace Gems\Rest\Action;

use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Zalt\Loader\ProjectOverloader;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;

class TestModelAction implements MiddlewareInterface
{
    /**
     *
     * @var ProjectOverloader
     */
    protected $loader;

    public function __construct(ProjectOverloader $loader)
    {
        $this->loader = $loader;
        //$this->loader->verbose = true;
        $this->loader->legacyClasses = true;

        $container = $loader->getServiceManager();

        $container->get(\Zend_Db::class);

        $clientRepository = $container->get('Gems\\Rest\\Auth\\ClientRepository');

        //print_r($clientRespository->getClientEntity('test', null));

        $scopeRepository = $container->get('Gems\\Rest\\Auth\\ScopeRepository');

        //print_r($scopeRepository->getScopeEntityByIdentifier('all'));

        $accessTokenRepository = $container->get('Gems\\Rest\\Auth\\AccessTokenRepository');

        $userRepository = $container->get('Gems\\Rest\\Auth\\UserRepository');

        $client = $clientRepository->getClientEntity('test', null, null, false);

        /*$user = $userRepository->getUserEntityByUserCredentials('jvangestel@70', 'test123', null, $client);

        if (!$user) {
            echo 'No user found!';
        }
        print_r($user);*/



    }



    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $model = $this->loader->create('Model_OrganizationModel');
        $model->applyBrowseSettings();

        return new JsonResponse($model->load());
    }
}
