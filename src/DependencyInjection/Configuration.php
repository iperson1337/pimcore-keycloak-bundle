<?php

namespace Iperson1337\PimcoreKeycloakBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('iperson1337_pimcore_keycloak');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_target_route_name')
                    ->defaultValue('pimcore_admin_index')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('admin_user_class')
                    ->defaultValue('Pimcore\Model\User')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_language')
                    ->defaultValue('en')
                    ->info('Язык по умолчанию для новых пользователей')
                ->end()
                ->booleanNode('auto_create_users')
                    ->defaultTrue()
                    ->info('Автоматически создавать пользователей в Pimcore при первом входе через Keycloak')
                ->end()
                ->booleanNode('sync_user_data')
                    ->defaultTrue()
                    ->info('Синхронизировать данные пользователя при каждом входе')
                ->end()
                ->arrayNode('user_mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('username')->defaultValue('preferred_username')->end()
                        ->scalarNode('email')->defaultValue('email')->end()
                        ->scalarNode('firstname')->defaultValue('given_name')->end()
                        ->scalarNode('lastname')->defaultValue('family_name')->end()
                    ->end()
                ->end()
                ->arrayNode('keycloak')
                    ->isRequired()
                    ->children()
                        ->scalarNode('client_id')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('client_secret')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('server_url')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('URL сервера Keycloak (базовый)')
                        ->end()
                        ->scalarNode('server_public_url')
                            ->defaultNull()
                            ->info('URL для публичного доступа к серверу Keycloak')
                        ->end()
                        ->scalarNode('server_private_url')
                            ->defaultNull()
                            ->info('URL для приватного доступа к серверу Keycloak (например, внутри сети)')
                        ->end()
                        ->scalarNode('realm')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Realm Keycloak')
                        ->end()
                        ->booleanNode('ssl_verification')
                            ->defaultTrue()
                            ->info('Проверять SSL сертификат сервера Keycloak')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
