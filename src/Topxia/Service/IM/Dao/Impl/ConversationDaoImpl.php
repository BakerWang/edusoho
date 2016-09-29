<?php
namespace Topxia\Service\IM\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\IM\Dao\ConversationDao;

class ConversationDaoImpl extends BaseDao implements ConversationDao
{
    protected $table = 'im_conversation';

    public $serializeFields = array(
        'memberIds' => 'saw'
    );

    public function getConversationByMemberIds(array $memberIds)
    {
        $conversation = array(
            'memberIds' => $memberIds
        );
        $conversation = $this->createSerializer()->serialize($conversation, $this->serializeFields);
        $sql          = "SELECT * FROM {$this->getTable()} where memberIds=? LIMIT 1";
        $conversation = $this->getConnection()->fetchAssoc($sql, array($conversation['memberIds']));
        return $conversation ? $this->createSerializer()->unserialize($conversation, $this->serializeFields) : null;
    }

    public function getConversationByMemberHash($memberHash)
    {
        $sql          = "SELECT * FROM {$this->getTable()} where memberHash=? LIMIT 1";
        $conversation = $this->getConnection()->fetchAssoc($sql, array($memberHash));
        return $conversation ? $this->createSerializer()->unserialize($conversation, $this->serializeFields) : null;
    }

    public function getConversation($id)
    {
        $sql          = "SELECT * FROM {$this->getTable()} where id=? LIMIT 1";
        $conversation = $this->getConnection()->fetchAssoc($sql, array($id));
        return $conversation ? $this->createSerializer()->unserialize($conversation, $this->serializeFields) : null;
    }

    public function addConversation($conversation)
    {
        $conversation = $this->createSerializer()->serialize($conversation, $this->serializeFields);
        $lockName     = $conversation['targetType'].$conversation['targetId'];
        if ($conversation['targetType'] == 'global') {
            $lockName = $conversation['targetType'].$conversation['memberIds'];
        }

        $res = $this->getConnection()->exec("SELECT GET_LOCK('im_'".$lockName.", 10)");
        if (!$res) {
            throw $this->createDaoException('Insert Conversation error.');
        }

        $affected = $this->getConnection()->insert($this->table, $conversation);
        $this->clearCached();

        if ($affected <= 0) {
            throw $this->createDaoException('Insert Conversation error.');
        }

        $this->getConnection()->exec("SELECT RELEASE_LOCK('im_'".$lockName.", 10)");

        return $this->getConversation($this->getConnection()->lastInsertId());
    }
}
