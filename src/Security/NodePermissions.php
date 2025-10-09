<?php

namespace Terminal42\NodeBundle\Security;

final class NodePermissions
{
    public const USER_CAN_ACCESS_MODULE = 'contao_user.modules.nodes';

    public const USER_CAN_ACCESS_NODE = 'contao_user.nodeMounts';

    public const USER_CAN_CREATE_NODE = 'contao_user.nodePermissions.create';

    public const USER_CAN_EDIT_NODE = 'contao_user.nodePermissions.edit';

    public const USER_CAN_DELETE_NODE = 'contao_user.nodePermissions.delete';

    public const USER_CAN_EDIT_NODE_CONTENT = 'contao_user.nodePermissions.content';

    public const USER_CAN_CREATE_ROOT_NODES = 'contao_user.nodePermissions.root';
}
