<?php

namespace Iperson1337\PimcoreKeycloakBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PimcoreKeycloakExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $container->setParameter('iperson1337_pimcore_keycloak.config', $config);
        $container->setParameter('iperson1337_pimcore_keycloak.default_target_route_name', $config['default_target_route_name']);
        $container->setParameter('iperson1337_pimcore_keycloak.admin_user_class', $config['admin_user_class']);
        $container->setParameter('iperson1337_pimcore_keycloak.auto_create_users', $config['auto_create_users']);
        $container->setParameter('iperson1337_pimcore_keycloak.sync_user_data', $config['sync_user_data']);
        $container->setParameter('iperson1337_pimcore_keycloak.user_mapping', $config['user_mapping']);
        $container->setParameter('iperson1337_pimcore_keycloak.default_language', $config['default_language']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['KnpUOAuth2ClientBundle'])) {
            throw new \LogicException('You must install knpuniversity/oauth2-client-bundle to use PimcoreKeycloakBundle');
        }

        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);


        if (isset($config['keycloak'])) {
            $container->prependExtensionConfig(
                'knpu_oauth2_client',
                $this->generateKeycloakAuthConfiguration($config)
            );
        }
    }

    protected function generateKeycloakAuthConfiguration(array $config): array
    {
        return [
            'clients' => [
                'keycloak' => [
                    'type' => 'generic',
                    'provider_class' => 'Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakProvider',
                    'client_id' => $config['keycloak']['client_id'],
                    'client_secret' => $config['keycloak']['client_secret'],
                    'redirect_route' => 'iperson1337_pimcore_keycloak_auth_check',
                    'redirect_params' => [],
                    'provider_options' => [
                        'auth_server_private_url' => $config['keycloak']['server_private_url'] ?? null,
                        'auth_server_public_url' => $config['keycloak']['server_public_url'] ?? null,
                        'auth_server_url' => $config['keycloak']['server_url'] ?? null,
                        'realm' => $config['keycloak']['realm'],
                        'verify' => $config['keycloak']['ssl_verification'],
                        'default_scopes' => $config['keycloak']['default_scopes'],
                    ],
                ],
            ],
        ];
    }

    public function getAlias(): string
    {
        return 'iperson1337_pimcore_keycloak';
    }
}
