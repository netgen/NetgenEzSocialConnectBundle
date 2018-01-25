<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class GetSocialUrlCommand extends ContainerAwareCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('netgen:social:geturl')
            ->setDescription('This command takes an eZ userId and prints the urls to their social profiles to the console.'.PHP_EOL.
                             'Only the Facebook, Google and Twitter resource owners are supported at the moment.')
            ->addArgument(
                'user_id',
                InputArgument::REQUIRED,
                'eZ User id'
            )
            ->addOption(
                'resource',
                '-r',
                InputOption::VALUE_OPTIONAL,
                'If set, only the profile from the social network defined will be returned'
            )
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('user_id');
        $resourceName = $input->getOption('resource');

        $loginHelper = $this->getContainer()->get('netgen.social_connect.helper.user_content');

        $profileUrls = $loginHelper->getProfileUrlsByEzUserId($userId, $resourceName);

        if (!empty($profileUrls)) {
            foreach ($profileUrls as $resourceName => $profileUrl) {
                $output->writeln("<info>{$resourceName}</info>: {$profileUrl}".PHP_EOL);
            }
        } else {
            $output->writeln("<error>User with id '{$userId}' is not connected to any resource owner!</error>");
        }
    }
}
