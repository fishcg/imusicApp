<?php
/**
 * Created by PhpStorm.
 * User: tomcao
 * Date: 2017/7/11
 * Time: 9:53
 */

namespace app\components\tree;

use \Exception;

class TreeBase
{
    private $tree = [];
    private $pid2nodes = [];
    private $id2node = [];

    protected function generateTree($nodes, $root_id = 0)
    {
        $pid2nodes = [];
        $id2node = [];
        foreach ($nodes as &$node) {
            $pid = $node['pid'];
            $id = $node['id'];
            $pid2nodes[$pid] = $pid2nodes[$pid] ?? [];
            $pid2nodes[$pid][] = &$node;
            $id2node[$id] = &$node;
        }
        unset($node);

        if (!($tree = &$id2node[$root_id])) {
            throw new Exception('根节点不存在，请检查');
        }

        if ($pid2nodes[$root_id] ?? $id2node[$root_id]) {
            $level = 0;
            $tree['level'] = $level;
            $tree['sons'] = &$pid2nodes[$root_id];
            $stack = [[$root_id, $level]];
            while ($stack) {
                list($pid, $level) = array_pop($stack);
                if (!isset($pid2nodes[$pid])) {
                    continue;
                }
                foreach ($pid2nodes[$pid] as &$node) {
                    $id = $node['id'];
                    $node['level'] = $level + 1;
                    if (isset($pid2nodes[$id])) {
                        $node['sons'] = &$pid2nodes[$id];
                    }
                    array_push($stack, [$id, $level + 1]);
                }
            }
        }

        $this->tree = $tree;
        $this->pid2nodes = $pid2nodes;
        $this->id2node = $id2node;
    }

    /**
     * 返回目录树
     *
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * 返回子树
     *
     * @param $nid 子树的根节点
     * @return array|null
     */
    public function getSubTree($nid)
    {
        return $this->id2node[$nid] ?? null;
    }

    public function getSons($nid)
    {
        $sons = $this->pid2nodes[$nid];
        if ($sons) {
            uasort($sons, function($before, $current) {
                return $current['sort'] <=> $before['sort'];
            });
            return array_values($sons);
        }
        return null;
        return $this->pid2nodes[$nid] ?? null;
    }

    /**
     * 获取叶子节点
     *
     * @param $nid
     * @return array|null
     * @throws Exception
     */
    public function getLeaves($nid)
    {
        $sub_tree = $this->getSubTree($nid);
        if (!$sub_tree) throw new Exception('子树不存在');
        if (!isset($sub_tree['sons'])) return $sub_tree;
        $stack = $sub_tree['sons'];

        $leaves = [];

        while ($stack) {
            $_tree = array_pop($stack);
            if (isset($_tree['sons'])) {
                foreach ($_tree['sons'] as $son) {
                    array_push($stack, $son);
                }
            } else {
                array_push($leaves, $_tree);
            }
        }

        uasort($leaves, function ($before, $current) {
            return $current['sort'] <=> $before['sort'];
        });

        return array_values($leaves);
    }

    /**
     * 根据祖父id和节点id寻找父类id
     *
     * @param int $nid 节点id
     * @param int $gid 祖父id
     * @return int
     */
    public function getParentByPid($nid, $gid)
    {
        $gnode = $this->id2node[$gid] ?? NULL;
        if (!$gnode || !isset($gnode['level'])) return -1;
        $level = $gnode['level'] + 1;
        return $this->getPidByLevel($nid, $level);
    }

    /**
     * 根据层级和节点id寻找父类id
     *
     * @param int $nid 节点id
     * @param int $level 层级
     * @return int 父类id
     */
    public function getPidByLevel(int $nid, int $level = 1)
    {
        $node = $this->id2node[$nid] ?? NULL;
        if (!$node || ($level < 0) ||
            !isset($node['level']) ||
            ($node['level'] <= $level)) return -1;

        $insurance = $node['level']; // 防止程序错误陷入死循环
        do {
            $pid = $node['pid'];
            $node = $this->id2node[$pid];
            if (--$insurance < 0) return -1;
        } while ($node['level'] !== $level);
        return $node['id'];
    }

    /**
     * 节点id对应的节点是否存在
     *
     * @param int $nid 节点id
     * @return bool
     */
    public function nodeExists(int $nid)
    {
        return !!$this->id2node[$nid];
    }
}