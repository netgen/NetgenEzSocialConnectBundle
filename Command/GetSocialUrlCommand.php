<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class GetSocialUrlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('netgen:get:social-url')
            ->setDescription('Command takes ezuser id and returns urls to their social profiles.')
            ->addArgument(
                'user_id',
                InputArgument::REQUIRED,
                'eZ User id'
            )
            ->addOption(
                'resource',
                '-r',
                InputOption::VALUE_OPTIONAL,
                'If set, only profile from the defined social network will be returned'
            )
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('user_id');
        $loginHelper = $this->getContainer()->get('netgen.social_connect.helper');
        
        $baseUrl = array(
            'facebook' => 'https://www.facebook.com/app_scoped_user_id/',
            'twitter' => 'https://twitter.com/intent/user?user_id=',
            'google' => 'https://plus.google.com/',
        );

        if ($resourceName = $input->getOption('resource')) {
            if (!array_key_exists($resourceName, $baseUrl)) {
                $output->writeln("<error>Resource owner '{$resourceName}' is not supported!</error>");

                return;
            }

            $OAuthEz = $loginHelper->loadFromTableByEzId($userId, $resourceName);

            if (empty($OAuthEz)) {
                $output->writeln("<error>User with id '{$userId}' is not connected to '{$resourceName}'!</error>");

                return;
            }

            $externalId = $OAuthEz->getResourceUserId();
            switch ($resourceName) {
                case 'facebook':
                    $profileUrl = $baseUrl['facebook'].$externalId;
                    break;
                case 'twitter':
                    $profileUrl = $baseUrl['twitter'].$externalId;
                    break;
                case 'google':
                    $profileUrl = $baseUrl['google'].$externalId;
                    break;
                default:
                    $output->writeln("<error>Resource owner '{$resourceName}' is not supported!</error>");

                    return;
            }

            $output->writeln("{$resourceName}: {$profileUrl}");
        } else {
            $output->writeln('');
            foreach ($baseUrl as $resourceName => $resourceBaseUrl) {
                $OAuthEz = $loginHelper->loadFromTableByEzId($userId, $resourceName);

                if (empty($OAuthEz)) {
                    continue;
                }

                $externalId = $OAuthEz->getResourceUserId();
                $profileUrl = $resourceBaseUrl.$externalId;
                $output->writeln("{$resourceName}: {$profileUrl}");
            }
            $output->writeln('');
        }
    }
}
