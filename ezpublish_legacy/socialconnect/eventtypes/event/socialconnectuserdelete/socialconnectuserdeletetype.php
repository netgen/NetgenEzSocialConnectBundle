<?php

class SocialConnectUserDeleteType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = "socialconnectuserdelete";

    public function __construct()
    {
        parent::__construct(SocialConnectUserDeleteType::WORKFLOW_TYPE_STRING, 'SocialConnect User Delete');
    }

    public function execute($process, $event)
    {
        $parameters = $process->attribute('parameter_list');
        $nodeIdList = $parameters['node_id_list'];

        $contentIds = array();
        foreach ($nodeIdList as $nodeId) {
            $contentIds[] = eZContentObject::fetchByNodeID($nodeId)->attribute('id');
        }

        $kernel = \ezpKernel::instance();

        /** @var \Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository $OAuthEzRepository */
        $OAuthEzRepository = $kernel->getServiceContainer()->get('netgen.social_connect.repository.oauthez');

        foreach ($contentIds as $contentId) {
            $users = $OAuthEzRepository->loadAllFromTableByEzId($contentId);

            foreach ($users as $user) {
                $OAuthEzRepository->removeFromTable($user);
            }
        }

        return \eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType(SocialConnectUserDeleteType::WORKFLOW_TYPE_STRING, 'SocialConnectUserDeleteType');
