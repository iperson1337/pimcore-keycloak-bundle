<?php

namespace Iperson1337\PimcoreKeycloakBundle;

use Pimcore\Bundle\AdminBundle\Installer;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Iperson1337\PimcoreKeycloakBundle\DependencyInjection\PimcoreKeycloakExtension;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreKeycloakBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'iperson1337/pimcore-keycloak-bundle';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorekeycloak/js/pimcore/startup.js'
        ];
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorekeycloak/css/admin.css'
        ];
    }

    public function getInstaller(): ?Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new PimcoreKeycloakExtension();
    }

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
