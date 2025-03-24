<?php

namespace App\Observers;

use App\Models\Common\Term;
use App\Models\Common\TermPath;
use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\OrmHelper;

class TermObserver
{
    public function saved(Term $term)
    {
        // 新增
        if ($term->wasRecentlyCreated) {
            $this->create($term);
        } 
        // 更新
        else if (!$term->wasRecentlyCreated) {
            $this->updated($term);
        }
    }
    

    public function updated(Term $term)
    {
        $term_id = $term->id;

        // 處理 term_paths

            // MySQL Hierarchical Data Closure Table Pattern
            // 取得自己是別人上層的所有 term (別人的祖先有我)
            $term_paths = TermPath::where('path_id', $term->id)->orderBy('level', 'ASC')->get();

            if(!$term_paths->isEmpty()){
                foreach ($term_paths as $term_path) {
                    // Delete the path below the current one (刪除比我更早的祖先)
                    // $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = '" . (int)$category_path['category_id'] . "' AND `level` < '" . (int)$category_path['level'] . "'");
                    TermPath::where('term_id', $term_path->term_id)->where('level', '<', $term_path->level)->delete();
    
                    $paths = [];
    
                    // Get the nodes new parents (取得新的上層世系)
                    // $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");
                    $rows = TermPath::where('term_id', $term->parent_id)->orderBy('level', 'ASC')->get();
    
                    foreach ($rows as $row) {
                        $paths[] = $row['path_id'];
                    }
    
                    // Get whats left of the nodes current path
                    // $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = '" . (int)$category_path['category_id'] . "' ORDER BY `level` ASC");
                    $rows = TermPath::where('term_id', $term->id)->orderBy('level', 'ASC')->get();
    
                    foreach ($rows as $row) {
                        $paths[] = $row['path_id'];
                    }
    
                    // Combine the paths with a new level
                    $level = 0;
    
                    $upsert_data = [];
    
                    foreach ($paths as $path_id) {
                        $upsert_data[] = [
                            'term_id'  => $term_path['term_id'],
                            'path_id'  => $path_id,
                            'level'    => $level,
                        ];
                    }
    
                    TermPath::upsert(
                        $upsert_data,
                        ['term_id', 'path_id'], // 唯一索引條件
                        ['level'] // 若重複，則更新的欄位
                    );

                    $level++;
                }
            } else {
                // Delete the path below the current one
                TermPath::where('term_id', $term->id)->delete();

                // Fix for records with no paths
                $level = 0;
    
                $term_paths = TermPath::where('term_id', $term->parent_id)->orderBy('level', 'ASC')->get();
    
                $insert_data = [];

                foreach ($term_paths as $term_path) {
                    $insert_data[] = [
                        'term_id'  => $term->id,
                        'path_id'  => $term_path->path_id,
                        'level'    => $level,
                    ];
    
                    $level++;
                }

                if(!empty($insert_data)){
                    TermPath::insert($insert_data);
                }
    
                DB::table('term_paths')->updateOrInsert(
                    ['term_id' => $term_id, 'path_id' => $term_id],
                    ['level' => $level] 
                );
            }
        //
    }

    public function create(Term $term)
    {
        // 處理 term_paths
            // 刪除舊的 term_paths
            TermPath::where('term_id', $term->id)->delete();

            $level = 0;

            // 找出上層的路徑
            $query = TermPath::where('term_id', $term->parent_id)->orderBy('level', 'ASC');
            $parents = $query->get();

            // 複製上層的路徑
            foreach ($parents as $parent) {
                $parentPaths[] = [
                    'term_id' => $parent->id,
                    'path_id' => $parent->path_id,
                    'level' => $level,
                ];
                $level++;  // 層級遞增
            }

            // 再加上自己
            $parentPaths[] = [
                'term_id' => $term->id,
                'path_id' => $term->id,
                'level' => $level,
            ];

            // 插入所有新路徑
            DB::table('term_paths')->insert($parentPaths);
        //
    }

	public function getPath(int $term_id): string 
    {
		return implode('_', array_column($this->getPaths($term_id), 'path_id'));
	}

	/**
	 * @param int $term_id
	 *
	 * @return array
	 */
	public function getPaths(int $term_id): array 
    {
        return TermPath::where('term_id', $term_id)->orderBy('level', 'ASC')->get()->toArray();
	}
}

?>