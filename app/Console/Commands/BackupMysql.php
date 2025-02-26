<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 暫時用不到。在訂單新增的時候自動處理。
 */
class BackupMysql extends Command
{
    protected $signature = 'app:backup-database';
    protected $description = '備份資料庫';

    public function handle()
    {
        try {
            // MySQL 執行檔
            $mysql_bin_path = env('DB_MYSQL_BIN_PATH');

            // 備份資料庫存放的根目錄
            $backup_dir = env('DB_BACKUP_DIR');

            // 壓縮程式執行檔
            $zipexe = env('7ZEXE');

            $db_host = 'localhost';
            $db_user = config('database.connections.mysql.username');
            $db_password = config('database.connections.mysql.password');
            $db_connect_dbname = 'chb_portal';
            $db_names = [env('DB_DATABASE')];

            $mysqli = new \mysqli($db_host, $db_user, $db_password, $db_connect_dbname);
            if ($mysqli->connect_error) {
                die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
            }

            $date_str = date("Ymd-His");

            // 產生資料庫壓縮檔
            foreach($db_names as $dbname){
                $filename = $dbname.'_'.$date_str;
                $fullpath_sql = $backup_dir . $filename .'.sql';
                $fullpath_zip = $backup_dir . $filename .'.zip';
                $zip_basename = basename($fullpath_zip);
            
                //判斷資料庫是否存在
                $results = $mysqli->query("SHOW DATABASES LIKE '".$dbname."';");

                if($results->num_rows !== 1){
                    continue;
                }	
            
                // 執行 mysqldump
                $cmd = '"'.$mysql_bin_path.'mysqldump" '.$dbname.' -u'.$db_user.' -p"'.$db_password.'" > "'.$fullpath_sql . '"';

                $output = shell_exec($cmd);
            
                //壓縮檔案
                if(is_file($fullpath_sql)){
                    $cmd = '"'.$zipexe .'"'. ' a -tzip ' . $fullpath_zip . ' ' .$fullpath_sql;
                    shell_exec($cmd);
                    unlink($fullpath_sql);

                    // 移動檔案
                    $this_db_backup_path = $backup_dir . $dbname;
                    if (!is_dir($this_db_backup_path)){
                        mkdir($this_db_backup_path);
                    }

                    $dst = $this_db_backup_path . '/' . $zip_basename;
                    echo "移動檔案：$fullpath_zip to $dst";
                    rename($fullpath_zip, $dst);

                    echo "資料庫成功產生備份壓縮檔：$fullpath_zip<BR>\r\n";
                }
                else{
                    echo "資料庫匯出失敗。\r\n";
                }
            }

            // 封存
                // 當前年月
                $this_month_yearmonth = date("Ym"); // 例如 202306

                // 資料庫名稱+年月
                $filename_prefix = $dbname . '_' . date("Ym");
                
                $this_archive_path = $backup_dir . $dbname . '/archive';

                if (!is_dir($this_archive_path)){
                    mkdir($this_archive_path);
                }

                $archive_files = scandir($this_archive_path);
                
                // 獲取當前日期的年月日（格式：YYYYMMDD）
                $current_date = date('Ymd');

                // 計算一年前的日期（格式：YYYYMMDD）
                $one_year_ago = date('Ymd', strtotime('-1 year'));


                $archive_has_this_year_month_file = false;

                foreach ($archive_files as $archive_file) {
                    if ($archive_file === '.' || $archive_file === '..') {
                        continue;
                    }

                    // 刪除一年前的封存檔
                    if (preg_match('/\d{8}-\d{6}/', $archive_file, $matches)) {
                        // 取得檔案名稱中的日期部分 (YYYYMMDD-HHMMSS)
                        $file_date = substr($matches[0], 0, 8);  // 只取出日期部分 (YYYYMMDD)
                
                        // 比較檔案日期與一年前的日期
                        if ($file_date < $one_year_ago) {
                            // 構造檔案的完整路徑
                            $file_path = $this_archive_path . '/' . $archive_file;
                
                            // 刪除該檔案
                            if (unlink($file_path)) {
                                echo "已刪除檔案: " . $archive_file . "\n";
                            } else {
                                echo "刪除檔案失敗: " . $archive_file . "\n";
                            }
                        }
                    }

                    // 已有當前年月的備份
                    if(str_starts_with($archive_file, $filename_prefix)){
                        $archive_has_this_year_month_file = true;
                        break;
                    }
                }

                // 如果都沒有
                if($archive_has_this_year_month_file == false){
                    $src = $dst;
                    $dst = $this_archive_path . '/' . basename($dst);
                    copy($src, $dst);
                }
            //

            $this->info('資料庫備份完成');
        } catch (\Exception $e) {
            Log::error('資料庫備份執行失敗: ' . $e->getMessage());
            $this->error('資料庫備份執行失敗' . $e->getMessage());
        }
    }
}
