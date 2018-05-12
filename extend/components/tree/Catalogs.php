<?php
/**
 * Created by PhpStorm.
 * User: tomcao
 * Date: 2017/7/10
 * Time: 18:59
 */

namespace app\components\tree;

use app\models\Catalog;

class Catalogs extends TreeBase
{
    const SOUND_CATALOG_ID = 1;

    public function __construct($root_id = self::SOUND_CATALOG_ID)
    {
        // 初始数据
        $nodes = Catalog::find()
            ->select('id, catalog_name name, parent_id pid, sort_order sort')
            ->where('status_is = 1')->asArray()->all();

        $this->generateTree($nodes, $root_id);
    }
}