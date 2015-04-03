<?php

namespace Plr\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PlrApiExtension extends Extension {
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
        }

        $this->addServers($config, $container);
    }

    /**
     * Adds API servers to the service contaienr
     *
     * @param array $clients Array of server configurations
     * @param ContainerBuilder $container Service container
     *
     * @throws \LogicException
     */
    private function addServers(array $clients, ContainerBuilder $container) {
        foreach ($clients as $client => $clientConfig) {
            $this->newApiServer($client, $clientConfig, $container);
        }
    }

    /**
     * Creates a new API server definition
     *
     * @param string $name Server name
     * @param array $config Server configuration
     * @param ContainerBuilder $container Service container
     *
     * @throws \LogicException
     */
    private function newApiServer($name, array $config, ContainerBuilder $container) {

        $apiCaller = new Definition();
        $apiCaller->setClass($container->getParameter("plr_api.api_plugin.class"));

        $client = new Definition();
        $client->addArgument($config["baseUrl"]);
        $client->setClass("Guzzle\Http\Client");

        if ($container->getParameter('kernel.debug')) {
            $client->addMethodCall('addSubscriber', array(new Reference("playbloom_guzzle.client.plugin.profiler")));
        }

        $apiCaller->addArgument($client);
        $apiCaller->addArgument($config);
        $apiCaller->addArgument(new Reference("service_container"));

        // Add the service to the container
        $serviceName = sprintf('api.%s', $name);
        $container->setDefinition($serviceName, $apiCaller);
    }
}
