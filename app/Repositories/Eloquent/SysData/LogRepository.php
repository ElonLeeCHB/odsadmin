<?php

namespace App\Repositories\Eloquent\SysData;

use App\Repositories\Eloquent\Repository;
use App\Models\SysData\Log;
use Carbon\Carbon;

class LogRepository extends Repository
{
    public $modelName = "\App\Models\SysData\Log";


    public function log($params)
    {
        $log = new Log;

        $log->uniqueid = app('unique_id');
        $log->area = config('app.env');
        $log->url = $params['url'] ?? '';
        $log->method = $params['method'] ?? '';
        $log->data = json_encode($params['data']);
        $log->status = $params['status'] ?? '';
        $log->note = $params['note'] ?? '';

        //client_ip
        if (request()->hasHeader('X-CLIENT-IPV4')) {
            $log->client_ip = request()->header('X-CLIENT-IPV4');
        }

        //api_ip
        $log->api_ip = request()->ip();

        $log->created_at = Carbon::now();
        
        $log->save();
    }

    public function logRequest($uniqueid, $note = '')
    {
        $log = new Log;

        $log->uniqueid = $uniqueid;
        $log->area = config('app.env');
        $log->url = request()->fullUrl() ?? '';
        $log->method = request()->method() ?? '';
        $log->created_at = Carbon::now();

        //data
        if (request()->isJson()) {
            $json = json_decode(request()->getContent()); //為確保拿到的是一行 json 字串，先 json_decode 再 json_encode。
            $log->data = json_encode($json);
        } else{
            $log->data = json_encode(request()->all());
        }

        $log->status = '';
        
        $log->note = $note ?? '';

        //client_ip
        if (request()->hasHeader('X-CLIENT-IPV4')) {
            $log->client_ip = request()->header('X-CLIENT-IPV4');
        }

        //api_ip
        $log->api_ip = request()->ip();

        return $log->save();
    }


    public function logErrorNotRequest($params)
    {
        $log = new Log;

        $log->uniqueid = time() . '-' . uniqid();
        $log->area = config('app.env');
        $log->url = '';
        $log->method = '';
        $log->created_at = Carbon::now();
        $log->data = json_encode($params['data']);
        $log->status = $params['status'] ?? 'error';
        $log->note = $params['note'] ?? '';
        $log->client_ip = '';
        $log->api_ip = '';

        $log->save();
    }
}

