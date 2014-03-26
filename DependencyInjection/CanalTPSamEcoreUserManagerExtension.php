<?php

namespace CanalTP\SamEcoreUserManagerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CanalTPSamEcoreUserManagerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XMLFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        $container->setParameter('sam_user.form.user', $config['form']['user']);
        $container->setParameter('sam_user.form.registration', $config['form']['registration']);
        $container->setParameter('sam_user.list.users_by_page', $config['users_by_page']);
    }

    public function getAlias()
    {
        return 'sam_user';
    }
}
